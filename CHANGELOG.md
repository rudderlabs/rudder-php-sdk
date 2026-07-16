# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [2.2.0](https://github.com/rudderlabs/rudder-php-sdk/compare/v2.1.2...v2.2.0) (2026-07-16)


### Features

* implement retry handling in PHP SDK ([5043a75](https://github.com/rudderlabs/rudder-php-sdk/commit/5043a759c83acaad74cca7c0a55c9c5bbbaa0023))


### Bug Fixes

* sdk-4947 align maximum retry delay ([497d615](https://github.com/rudderlabs/rudder-php-sdk/commit/497d61512b2a0ec9cda9644fb27ca2a3fc56f611))
* sdk-4947 report curl errors before retry delay ([5d06481](https://github.com/rudderlabs/rudder-php-sdk/commit/5d06481409f32d1c3b92eed7e723f6826f421d38))
* sdk-4947 report terminal socket errors consistently ([66adc06](https://github.com/rudderlabs/rudder-php-sdk/commit/66adc063e62d7386acacd3fbab939c81fac76b41))


### Miscellaneous

* apply security best practices from step security ([#132](https://github.com/rudderlabs/rudder-php-sdk/issues/132)) ([b9b3813](https://github.com/rudderlabs/rudder-php-sdk/commit/b9b38139b1209280d96cab3c1bb67749e8e3834e))
* sdk-4947 align socket tests with retry behavior ([c5f3e30](https://github.com/rudderlabs/rudder-php-sdk/commit/c5f3e30d63d94eff5387fed8e2a93cc9ac4ac18c))
* sdk-4947 cover socket retry handling ([6e92a5d](https://github.com/rudderlabs/rudder-php-sdk/commit/6e92a5d70ee8e57d3cf9de9cbd2ef424dfe173f7))
* sdk-4947 harden retry integration coverage ([9064658](https://github.com/rudderlabs/rudder-php-sdk/commit/90646585e7e41c6bf034111ad2c474ff0cfd92b7))


### Documentation

* sdk-4947 remove retry-after option from readme ([e6cf72a](https://github.com/rudderlabs/rudder-php-sdk/commit/e6cf72a0e77d2fc147d6066c39cdd7f05dacbc35))

## [2.1.2](https://github.com/rudderlabs/rudder-php-sdk/compare/v2.1.1...v2.1.2) (2026-06-24)


### Miscellaneous

* **ci:** add release-please workflow ([ba5e436](https://github.com/rudderlabs/rudder-php-sdk/commit/ba5e436c4436d9f9e024031eb7b7d02a708841cd))
* **ci:** add release-please workflow ([d827510](https://github.com/rudderlabs/rudder-php-sdk/commit/d827510fdb508b586676481b5f42dac07f55dce1))
* **vuln:** scope workflow permissions to least privilege (SEC-167) ([bdeb2d3](https://github.com/rudderlabs/rudder-php-sdk/commit/bdeb2d3cfc26a90ccc804d187f05438da217f65b))

## [2.1.1](https://github.com/rudderlabs/rudder-php-sdk/compare/v2.1.0...v2.1.1) (2026-04-24)

### Bug Fixes

* skip `curl_close()` on PHP 8.0+ where it has no effect ([#125](https://github.com/rudderlabs/rudder-php-sdk/pull/125))

## [2.1.0](https://github.com/rudderlabs/rudder-php-sdk/compare/v2.1.0...v2.0.1) (2023-11-23)

### Features

* enable event ordering for batches with single event on single instance setups or setups with sticky sessions

## [2.0.1](https://github.com/rudderlabs/rudder-php-sdk/compare/v2.0.1...v2.0.0) (2023-01-13)

### Bug Fixes

* allow minor version range on ramsey/uuid dependency

## [2.0.0](https://github.com/rudderlabs/rudder-php-sdk/compare/v2.0.0...v1.0.1) (2023-01-05)


### Features

* Add new consumer configurable options: curl_timeout, curl_connecttimeout, max_item_size_bytes, max_queue_size_bytes
* Add an exception class so we can have rudder-specific exceptions
* More sensible error handling, don't try to catch exceptions that are never thrown
* API Endpoint updates
* Update Batch Size Check
* Remove messageID override capabilities
* Set messageID to be a UUID v4
* Update flush sleep waiting period
* Retry Network errors
* Add Limits to message, batch and memory usage
* Add Configurable flush parameters
* Add ability to use custom consumer
* Add ability to set file permissions
* Modify max queue size setting
* Add ability to set userid as zero

### Bug Fixes

* Fix socket return response
* usleep in flush() causes unexpected delays on page loads
* Correct Payload size check of 512kb
* Fix curl error handler
* Fix timestamp implementation for microseconds
* Fix deprecations for PHP 8.2

### Chores

* PSR-12 coding standard with Slevomat and phcs extensions
* Namespace and file rearrangement to follow PSR-4 naming scheme and more logical separation
* Extensive linting and static analysis using phpcs, psalm, phpstan, and PHPStorm to spot issues
* Provide strict types for all properties, parameters, and return values
* Add dependencies on JSON extension
* Add dependency on the Roave security checker

### BREAKING CHANGES

* Since the library already required a minimum of PHP 7.4, make use of PHP 7.4+ features and avoid compat issues with 8.0
* Support PHP 8
* Remove Support for PHP 7.2 and below
