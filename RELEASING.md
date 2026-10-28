# Releasing

Merging to `master` runs Release Please, which opens/updates the release PR. After
normal native CI/security checks and release PR review, merging that PR lets
Release Please create the GitHub release and `vX.Y.Z` tag. Packagist indexes
Git tags for `rudderstack/rudder-php-sdk`; there is **no separate upload**.
The historical `VERSION=X.Y.Z make release` command only edits local version
files; it does not publish a release by itself.

A stable published GitHub release starts **post-publication** acceptance. The
caller checks the existing release/tag, peels its commit, and supplies the exact
version and commit to the shared server-SDK action with `suite: full`. Central
validation waits a bounded time for Packagist and checks Composer's resolved
version **and source reference**, then full live delivery and cleanup. Only a
successful central result sends a release-complete Slack message. If metadata,
registry visibility, dispatch, delivery or cleanup fails, the package remains
published and Slack reports **published but acceptance failed**, with the
caller and (when available) central run links. Investigate before retrying;
there is no automatic unpublish.

To retry **validation only**, run the `Published PHP SDK acceptance` workflow
on `master` via `workflow_dispatch` with the exact existing published `vX.Y.Z`
tag and its full peeled commit SHA (`git rev-parse 'vX.Y.Z^{commit}'`). The
workflow verifies both against the GitHub release/tag; it does not run Release
Please, make tags, publish packages, or fall back to latest. Do not retry while
an earlier central live run may still be cleaning up. Never run on a fork/PR.

SDK-5967 is a draft until SDK-5961 central published-mode support and SDK-5962
shared `package-version` input are merged and its reviewed action SHA is pinned.
The hosted live acceptance test is deferred until the next **normal** release;
this change is implementation-tested only, not live-release accepted.
