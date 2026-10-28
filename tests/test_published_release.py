"""Offline release workflow and GitHub metadata contract checks."""

import importlib.util
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch

import yaml

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("verify_release", ROOT / "scripts/verify_release_metadata.py")
verifier = importlib.util.module_from_spec(spec)
spec.loader.exec_module(verifier)
SHA = "a" * 40
TAG = "v2.3.4"


def workflow(name):
    with (ROOT / ".github/workflows" / name).open() as stream:
        return yaml.safe_load(stream)


class MetadataTests(unittest.TestCase):
    def fetch(self, path):
        data = {
            f"releases/tags/{TAG}": {
                "id": 1, "tag_name": TAG, "draft": False, "prerelease": False,
                "published_at": "2026-10-01T00:00:00Z", "target_commitish": "master",
            },
            f"git/ref/tags/{TAG}": {"object": {"type": "tag", "sha": "b" * 40}},
            f"git/tags/{'b' * 40}": {"object": {"type": "commit", "sha": SHA}},
        }
        return data[path]

    def verify(self, event="workflow_dispatch", ref="refs/heads/master", tag=TAG, origin=SHA, fetch=None):
        return verifier.verify(event, verifier.REPOSITORY, ref, tag, origin, fetch or self.fetch)

    def test_exact_annotated_tag_and_version(self):
        self.assertEqual(self.verify(), ("2.3.4", SHA))

    def test_release_uses_peeled_commit_not_event_sha(self):
        with tempfile.NamedTemporaryFile(mode="w", encoding="utf-8") as file:
            json.dump({"release": {"id": 1, "tag_name": TAG, "draft": False, "prerelease": False}}, file)
            file.flush()
            with patch.dict(os.environ, {"GITHUB_EVENT_PATH": file.name}):
                self.assertEqual(self.verify("release", f"refs/tags/{TAG}", origin=""), ("2.3.4", SHA))

    def test_rejects_invalid_retry_metadata(self):
        for tag, origin, ref in [
            ("", SHA, "refs/heads/master"), ("v2.3.4-rc.1", SHA, "refs/heads/master"),
            ("v02.3.4", SHA, "refs/heads/master"), (TAG, "", "refs/heads/master"),
            (TAG, "c" * 40, "refs/heads/master"), (TAG, SHA, "refs/tags/v2.3.4"),
            (TAG, SHA, "refs/heads/feature"),
        ]:
            with self.subTest(tag=tag, origin=origin, ref=ref), self.assertRaises(ValueError):
                self.verify(ref=ref, tag=tag, origin=origin)

    def test_historical_branch_target_is_checked_by_peeled_tag(self):
        def fetch(path):
            data = self.fetch(path)
            return {**data, "target_commitish": "develop"} if path.startswith("releases/") else data
        self.assertEqual(self.verify(fetch=fetch), ("2.3.4", SHA))
        with self.assertRaises(ValueError):
            self.verify(origin="c" * 40, fetch=fetch)

    def test_rejects_unpublished_or_mismatched_commit_target(self):
        for change in ({"draft": True}, {"prerelease": True}, {"published_at": None},
                       {"target_commitish": ""}, {"target_commitish": "c" * 40}):
            def fetch(path):
                data = self.fetch(path)
                if path.startswith("releases/"):
                    return {**data, **change}
                return data
            with self.subTest(change=change), self.assertRaises(ValueError):
                self.verify(fetch=fetch)

    def test_rejects_moved_or_non_commit_tag(self):
        for obj in ({"type": "commit", "sha": "c" * 40}, {"type": "blob", "sha": SHA}):
            def fetch(path):
                return {"object": obj} if path.startswith("git/ref/") else self.fetch(path)
            with self.subTest(obj=obj), self.assertRaises(ValueError):
                self.verify(fetch=fetch)

    def test_rejects_other_repositories(self):
        with self.assertRaises(ValueError):
            verifier.verify("release", "fork/rudder-php-sdk", f"refs/tags/{TAG}", TAG, SHA, self.fetch)


class WorkflowTests(unittest.TestCase):
    def test_release_please_and_native_pr_tests_unchanged(self):
        release = workflow("release-please.yml")
        self.assertEqual(release[True]["push"]["branches"], ["master"])
        self.assertIn("googleapis/release-please-action@", str(release))
        self.assertNotIn("package-version", str(release))
        native = workflow("test.yml")
        self.assertIn("pull_request", native[True])
        self.assertNotIn("RELEASE_PRIVATE_KEY", str(native))
        contract = native["jobs"]["workflow-contract"]
        self.assertIn("github.event_name == 'pull_request'", contract["if"])
        self.assertNotIn("RELEASE_PRIVATE_KEY", str(contract))
        self.assertIn("PyYAML==6.0.3", str(contract))
        self.assertIn("unittest discover -s tests -p test_published_release.py", str(contract))

    def test_published_retry_validation_and_notification_contract(self):
        flow = workflow("published-package-e2e.yml")
        self.assertEqual(flow[True]["release"]["types"], ["published"])
        self.assertEqual(set(flow[True]["workflow_dispatch"]["inputs"]), {"tag", "origin_sha"})
        self.assertEqual(flow["concurrency"]["cancel-in-progress"], False)
        self.assertIn("github.run_id", flow["concurrency"]["group"])
        self.assertNotIn("inputs.tag", flow["concurrency"]["group"])
        jobs = flow["jobs"]
        self.assertIn("github.repository == 'rudderlabs/rudder-php-sdk'", jobs["metadata"]["if"])
        self.assertIn("github.ref == 'refs/heads/master'", jobs["metadata"]["if"])
        self.assertEqual(jobs["validate"]["needs"], "metadata")
        self.assertEqual(jobs["notify"]["needs"], ["metadata", "validate"])
        self.assertIn("always()", jobs["notify"]["if"])
        self.assertNotIn("RELEASE_PRIVATE_KEY", str(jobs["metadata"]))
        self.assertNotIn("release-please", str(flow))
        self.assertNotIn("create tag", str(flow).lower())

        steps = jobs["validate"]["steps"]
        token = next(step for step in steps if "create-github-app-token@" in step.get("uses", ""))
        self.assertEqual(token["with"]["repositories"], "rudder-sdk-e2e-tests")
        self.assertEqual(token["with"]["permission-actions"], "write")
        self.assertEqual(set(token["with"]) - {"client-id", "private-key", "owner", "repositories", "permission-actions"}, set())
        action = next(step for step in steps if "github-action-sdk-e2e@" in step.get("uses", ""))
        self.assertEqual(action["with"]["suite"], "full")
        self.assertEqual(action["with"]["package-version"], "${{ needs.metadata.outputs.version }}")
        self.assertEqual(action["env"]["GITHUB_SHA"], "${{ needs.metadata.outputs.origin }}")
        self.assertNotIn("latest", str(action).lower())
        notify = str(jobs["notify"])
        self.assertIn("needs.validate.result == 'success'", notify)
        self.assertIn("Published but acceptance failed", notify)
        self.assertEqual(action["continue-on-error"], True)
        self.assertEqual(jobs["validate"]["outputs"]["accepted"], "${{ steps.e2e.outcome == 'success' }}")
        self.assertIn("run_url", notify)
        self.assertIn("needs.validate.outputs.run_url", notify)
        self.assertIn("needs.validate.outputs.accepted == 'true'", notify)
        self.assertNotIn("github.sha", notify)
        self.assertIn("json.dumps", notify)
        self.assertIn("Fail caller when acceptance failed", notify)
        self.assertNotIn("release: types: [created]", str(flow))

    def test_notification_serializes_untrusted_retry_metadata(self):
        steps = workflow("published-package-e2e.yml")["jobs"]["notify"]["steps"]
        message = next(step for step in steps if step.get("id") == "message")
        slack = next(step for step in steps if "slackapi/slack-github-action@" in step.get("uses", ""))
        self.assertEqual(slack["with"]["payload"], "${{ steps.message.outputs.payload }}")
        injected = 'v1.2.3"},"text":"Release complete'
        with tempfile.NamedTemporaryFile(mode="r+", encoding="utf-8") as output:
            environment = {**os.environ, "TAG": injected, "VERSION": "unverified", "ORIGIN": "unverified",
                           "CENTRAL_RUN": "https://example.test/actions/runs/123", "ACCEPTED": "false",
                           "CALLER": "https://example.test/actions/runs/456", "GITHUB_OUTPUT": output.name}
            subprocess.run(["bash", "-e", "-c", message["run"]], env=environment, check=True)
            payload = json.loads(output.read().removeprefix("payload="))
        self.assertEqual(set(payload), {"text"})
        self.assertIn(injected, payload["text"])
        self.assertIn("Published but acceptance failed", payload["text"])
        self.assertIn("Central run: https://example.test/actions/runs/123", payload["text"])
        self.assertIn("commit unverified", payload["text"])


if __name__ == "__main__":
    unittest.main()
