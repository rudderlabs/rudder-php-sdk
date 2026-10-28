# Releasing

1.  Run `VERSION=X.Y.Z make release` (where X.Y.Z is the new version).

That's it! Composer will pick up the new tag and you can see the latest version at https://packagist.org/packages/rudderstack/rudder-php-sdk.

## Published-package E2E acceptance

Release completion also requires the exact public package to pass full E2E delivery and cleanup checks. See [release acceptance and validation-only retries](.github/RELEASE_E2E.md). Hosted acceptance remains pending the next normal release.
