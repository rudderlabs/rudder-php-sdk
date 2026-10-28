# Published PHP SDK acceptance

The published-package workflow requests the full central E2E suite for the exact stable release version. It uses the existing release App and waits for delivery validation and cleanup. Before this draft can merge, the central workflow must check the release tag against the event commit and release provenance, install the exact public registry package, and verify its Composer-resolved version and source reference.

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

## Rollout

Tracked in [SDK-5967](https://linear.app/rudderstack/issue/SDK-5967). Keep this PR draft until [central PR #32](https://github.com/rudderlabs/rudder-sdk-e2e-tests/pull/32) resolves [SDK-5978](https://linear.app/rudderstack/issue/SDK-5978) and its cleanup-budget finding. Adopt [SDK-5977](https://linear.app/rudderstack/issue/SDK-5977)'s shared workflow only after it is reviewed and available at a full immutable SHA. The current shared-action pin is from [PR #5](https://github.com/rudderlabs/github-action-sdk-e2e/pull/5) and must be verified or refreshed before merge. Hosted acceptance remains pending the next normal release. Do not create a release solely to test this wiring.
