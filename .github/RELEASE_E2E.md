# Published PHP SDK acceptance

The published-package workflow requests the full central E2E suite for the exact stable release version. It uses the existing release App and waits for delivery validation and cleanup. The central workflow checks the release tag against the event commit and master ancestry, installs the exact public registry package, and verifies its Composer-resolved version and source reference.

Only stable releases use this acceptance and notification path. Prereleases have no supported published-validation path and do not produce completion announcements. The PHP repository had no required Slack mentions; the notification preserves that behavior.

The release tag makes the package available to the registry. Acceptance starts on the published GitHub release event. This is post-tag acceptance, not a pre-publication gate.

Full source-candidate E2E before publication is tracked separately in [SDK-5973](https://linear.app/rudderstack/issue/SDK-5973). Do not treat native tests or this post-publication check as that gate.

For stable release events, Slack receives a short release root after validation finishes. Its non-broadcast thread reply links the release, Packagist, and validation run, and reports registry, delivery, and cleanup acceptance. A passing message means all three checks passed. If any check fails, times out, or is skipped, the release remains published and the message says "published; validation failed or incomplete". Slack links do not unfurl.

## Validation-only retry

1. Open the failed central run from the shared action summary.
2. Resolve the validation failure.
3. Run this workflow at the existing release tag:

```sh
gh workflow run published-sdk-e2e.yml --repo rudderlabs/rudder-php-sdk --ref vX.Y.Z
```

The retry has no upload path or publishing credential. It derives the exact version and commit from the selected tag. It does not use `latest`, publish a package, or recreate a tag. Tags predating this workflow need an exact-version dispatch through the central workflow. Stable `X.Y.Z` versions are supported; prerelease acceptance is not implemented.

A published package remains published if acceptance fails. A retry does not create a tag or reannounce the release in Slack. A successful validation-only retry does not change an earlier failed run or its notification. Record the new acceptance run in the release ticket and existing Slack release thread.

## Retry after a workflow fix

A tag retry runs workflow code and pins stored at that tag. It cannot load a fix
merged later to master. The retry workflow must exist on the default branch and
selected tag. For an older tag or a workflow fix, use the current central workflow.
Copy the full commit SHA from the original release run. Replace the example
version, commit placeholder, and request ID before running:

```sh
gh workflow run server-sdk-validation.yml --repo rudderlabs/rudder-sdk-e2e-tests --ref main \
  -f selection=auto -f source_repository=rudderlabs/rudder-php-sdk \
  -f source_sha=RELEASE_EVENT_COMMIT_SHA -f package_version=X.Y.Z \
  -f suite=full -f request_id=rudder-php-sdk-published-X_Y_Z-retry-UNIQUE_ID
```

The central fallback validates the exact public version and release commit. It
cannot upload, create tags, or send a release announcement. Release-run reruns
also stay silent after the first attempt. Record new evidence in the release
ticket and existing release thread. If notification itself fails, repair that
thread separately.

## Rollout

Tracked in [SDK-5967](https://linear.app/rudderstack/issue/SDK-5967). Central
#32/#33/#35 and the shared v1.0.0 action/workflow are merged. The caller pins the
released commit `b6de40cc65abe8ee6c6593aac1787aea746b671f`. Hosted full registry,
delivery, and cleanup acceptance remains pending the next normal release.
Do not create a release solely to test this wiring.
