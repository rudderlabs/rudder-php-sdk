# Release PR live E2E

The `PHP SDK candidate gate` check runs on same-repository Release Please PRs targeting `master`. It sends the exact current PR merge commit to the shared central harness. The central workflow builds the unpublished candidate in isolation, verifies real dev Webhook delivery, and deletes its own Terraform resources. Fork and Dependabot PRs do not receive the dispatch credential.

The caller never checks out PR code. Its release App token has Actions write only in `rudder-sdk-e2e-tests`. Candidate execution receives no GitHub or control-plane credentials. The central receiver verifies payloads and the result is bound to the current PR merge commit.

Native build/unit/integration checks remain separate. Release Please and publication remain unchanged. The existing published-package check validates the registry package independently after publication. The source candidate check does not prove that a later publication build is byte-identical.

## Rollout

1. Merge [central PR #49](https://github.com/rudderlabs/rudder-sdk-e2e-tests/pull/49), tracked under [SDK-5973](https://linear.app/rudderstack/issue/SDK-5973).
2. Merge this caller workflow under [SDK-5984](https://linear.app/rudderstack/issue/SDK-5984).
3. Let Release Please refresh the open release PR and verify full hosted delivery and cleanup.
4. Require `PHP SDK candidate gate` on `master`, retain existing required checks, and enable strict up-to-date checks.

This PR does not change repository rules. A workflow file alone does not enforce the release gate. Keep the release PR open until rollout and acceptance are complete. Do not publish a version for this validation.

If E2E fails, fix the SDK in a separate PR and let Release Please refresh its candidate. Rerun an unchanged candidate for an infrastructure-only failure. A central harness fix can require a caller rerun. Central live runs own cleanup and are not cancelled by newer caller runs.
