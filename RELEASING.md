# Releasing

Release Please opens the release PR from `master`. Follow the normal review and merge process; its release tag starts Packagist indexing. There is no separate package upload. The published version appears at https://packagist.org/packages/rudderstack/rudder-php-sdk.

## Published-package E2E acceptance

Release completion also requires the exact public package to pass registry verification, full E2E delivery, and cleanup checks after the tag is published. A failed or incomplete acceptance leaves the version published and is reported separately. See [release acceptance and validation-only retries](.github/RELEASE_E2E.md). Hosted acceptance remains pending the next normal release.
