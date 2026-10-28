#!/usr/bin/env python3
"""Fail closed on release metadata before a privileged published-package E2E run."""

import json
import os
import re
import sys
from urllib.request import Request, urlopen

REPOSITORY = "rudderlabs/rudder-php-sdk"
VERSION_TAG = re.compile(r"v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\Z")
COMMIT = re.compile(r"[0-9a-f]{40}\Z")


def api(path):
    request = Request(
        f"https://api.github.com/repos/{REPOSITORY}/{path}",
        headers={
            "Accept": "application/vnd.github+json",
            "Authorization": f"Bearer {os.environ['GH_TOKEN']}",
            "X-GitHub-Api-Version": "2022-11-28",
        },
    )
    with urlopen(request, timeout=15) as response:
        return json.load(response)


def verify(event, repository, ref, tag, origin, fetch=api):
    if repository != REPOSITORY or event not in ("release", "workflow_dispatch"):
        raise ValueError("Only canonical release or trusted manual validation is supported")
    if event == "workflow_dispatch" and ref != "refs/heads/master":
        raise ValueError("Retry must run from master, not a tag or PR ref")
    if event == "release" and ref != f"refs/tags/{tag}":
        raise ValueError("Published event must name the exact release tag ref")
    match = VERSION_TAG.fullmatch(tag)
    if not match or (event == "workflow_dispatch" and not COMMIT.fullmatch(origin)):
        raise ValueError("An exact stable vX.Y.Z tag and full lowercase retry origin SHA are required")

    release = fetch(f"releases/tags/{tag}")
    if (release.get("tag_name") != tag or release.get("draft") or release.get("prerelease")
            or not release.get("published_at")):
        raise ValueError("The exact stable GitHub release must already be published")
    target = release.get("target_commitish")
    if not isinstance(target, str) or not target:
        raise ValueError("Release target commitish is missing")
    # Historical releases may target a branch (including develop). Do not resolve
    # a mutable branch as the origin; a commit-valued target can be compared.

    obj = fetch(f"git/ref/tags/{tag}")["object"]
    if obj["type"] == "tag":
        obj = fetch(f"git/tags/{obj['sha']}")["object"]
    if obj["type"] != "commit" or not COMMIT.fullmatch(obj["sha"]):
        raise ValueError("Release tag does not peel to a commit")
    if (event == "workflow_dispatch" and obj["sha"] != origin) or (
            COMMIT.fullmatch(target) and obj["sha"] != target):
        raise ValueError("Release tag does not peel to the exact origin SHA")

    if event == "release":
        with open(os.environ["GITHUB_EVENT_PATH"], encoding="utf-8") as stream:
            payload = json.load(stream)["release"]
        if (payload.get("id") != release.get("id") or payload.get("tag_name") != tag
                or payload.get("draft") or payload.get("prerelease")):
            raise ValueError("Release event does not match the published stable release")
        # github.sha can name the default branch; the origin is the peeled tag.
    return tag[1:], obj["sha"]


def main():
    event = os.environ["GITHUB_EVENT_NAME"]
    tag = os.environ["RELEASE_TAG"]
    origin = os.environ.get("ORIGIN_SHA", "")
    version, peeled_sha = verify(event, os.environ["GITHUB_REPOSITORY"], os.environ["GITHUB_REF"], tag, origin)
    with open(os.environ["GITHUB_OUTPUT"], "a", encoding="utf-8") as output:
        output.write(f"tag={tag}\nversion={version}\norigin={peeled_sha}\n")


if __name__ == "__main__":
    try:
        main()
    except (KeyError, OSError, ValueError) as exc:
        print(f"Invalid published-release metadata: {exc}", file=sys.stderr)
        sys.exit(1)
