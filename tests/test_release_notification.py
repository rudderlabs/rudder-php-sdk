import os
import subprocess
import tempfile
import unittest
from pathlib import Path

import yaml


WORKFLOW = Path(__file__).resolve().parents[1] / ".github/workflows/published-sdk-e2e.yml"


class ReleaseNotificationTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.workflow = yaml.load(WORKFLOW.read_text(), Loader=yaml.BaseLoader)
        cls.validate = cls.workflow["jobs"]["validate"]
        cls.notify = cls.workflow["jobs"]["notify-release"]

    def test_release_and_retry_are_separate(self):
        self.assertEqual(self.workflow["on"]["release"]["types"], ["published"])
        self.assertIn("workflow_dispatch", self.workflow["on"])
        self.assertEqual(self.workflow["jobs"]["release-ref"]["runs-on"], "ubuntu-latest")
        self.assertIn("GITHUB_REF_TYPE", self.workflow["jobs"]["release-ref"]["steps"][1]["run"])
        self.assertRegex(self.validate["uses"], r"^rudderlabs/github-action-sdk-e2e/\.github/workflows/published-sdk-e2e\.yml@[0-9a-f]{40}$")
        self.assertEqual(self.validate["needs"], "release-ref")
        self.assertEqual(self.validate["with"]["package-version"], "${{ github.event.release.tag_name || github.ref_name }}")
        self.assertEqual(self.notify["needs"], "validate")
        self.assertIn("always()", self.notify["if"])
        self.assertIn("github.event_name == 'release'", self.notify["if"])
        self.assertIn("!github.event.release.prerelease", self.notify["if"])

    def test_scoped_token_and_no_publication_path(self):
        self.assertEqual(self.validate["secrets"], {"RELEASE_PRIVATE_KEY": "${{ secrets.RELEASE_PRIVATE_KEY }}"})
        self.assertNotIn("steps", self.validate)
        self.assertNotIn("cancel-in-progress: true", WORKFLOW.read_text())
        self.assertNotIn("id-token: write", WORKFLOW.read_text())

    def test_slack_root_and_nonbroadcast_thread_use_existing_pin(self):
        steps = self.notify["steps"]
        root = next(step for step in steps if step["name"] == "Send release status to Slack channel")
        thread = next(step for step in steps if step["name"] == "Reply with links and check results")
        pin = "slackapi/slack-github-action@37ebaef184d7626c5f204ab8d3baff4262dd30f0"
        for step in (root, thread):
            self.assertEqual(step["uses"], pin)
            self.assertEqual(step["with"]["channel-id"], "${{ secrets.SLACK_RELEASE_CHANNEL_ID }}")
            self.assertEqual(step["env"]["SLACK_BOT_TOKEN"], "${{ secrets.SLACK_BOT_TOKEN }}")
            self.assertIn('"unfurl_links": false', step["with"]["payload"])
            self.assertIn('"unfurl_media": false', step["with"]["payload"])
        self.assertNotIn("packagist.org", root["with"]["payload"])
        self.assertIn('"thread_ts": "${{ steps.slack.outputs.ts }}"', thread["with"]["payload"])
        self.assertIn('"reply_broadcast": false', thread["with"]["payload"])
        self.assertIn("packagist.org", thread["with"]["payload"])
        self.assertIn("actions/runs", thread["with"]["payload"])

    def test_status_script_reports_acceptance_and_incomplete_release(self):
        script = next(step for step in self.notify["steps"] if step.get("id") == "status")["run"]
        with tempfile.TemporaryDirectory() as temp:
            output = Path(temp) / "output"
            for result in ("success", "failure", "cancelled", "skipped"):
                with self.subTest(result=result):
                    output.write_text("")
                    env = dict(
                        os.environ,
                        GITHUB_OUTPUT=str(output),
                        VALIDATION_RESULT=result,
                        RELEASE_VERSION="v2.2.2",
                        RELEASE_PUBLISHED_AT="2026-10-28T12:34:56Z",
                    )
                    subprocess.run(["bash", "-e", "-o", "pipefail", "-c", script], env=env, check=True)
                    message = output.read_text()
                    self.assertIn("PHP SDK v2.2.2", message)
                    self.assertIn("2026-10-28", message)
                    self.assertIn("<!subteam^", message)
                    if result == "success":
                        self.assertIn("accepted", message)
                        self.assertIn("cleanup: passed", message)
                        self.assertNotIn("failed or incomplete", message)
                    else:
                        self.assertIn("published; validation failed or incomplete", message)
                        self.assertIn("cleanup: failed or incomplete", message)

            output.write_text("")
            env["VALIDATION_RESULT"] = "skipped"
            env["RELEASE_VERSION"] = 'v2.2.2<@U123>'
            subprocess.run(["bash", "-e", "-o", "pipefail", "-c", script], env=env, check=True)
            self.assertIn("unverified release tag", output.read_text())
            self.assertNotIn("<@U123>", output.read_text())


if __name__ == "__main__":
    unittest.main()
