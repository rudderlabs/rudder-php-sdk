# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [2.0.0](https://github.com/rudderlabs/rudder-sdk-js/compare/v2.0.0...v1.0.1) (2023-01-05)


### Features

* Correct Payload size check of 512kb
* Add new consumer configurable options: curl_timeout, curl_connecttimeout, max_item_size_bytes, max_queue_size_bytes
* Deprecate HTTP Option

* PSR-12 coding standard with Slevomat and phcs extensions
* Namespace and file rearrangement to follow PSR-4 naming scheme and more logical separation
* Provide strict types for all properties, parameters, and return values
* Add an exception class so we can have segment-specific exceptions
* Add dependencies on JSON extension
* Add dependency on the Roave security checker
* Since the library already required a minimum of PHP 7.4, make use of PHP 7.4+ features, and avoid compat issues with 8.0
* More sensible error handling, don't try to catch exceptions that are never thrown
* Extensive linting and static analysis using phpcs, psalm, phpstan, and PHPStorm to spot issues

* Modify Endpoint to match API docs
* usleep in flush() causes unexpected delays on page loads
* Support PHP 8
* Remove Support for PHP 7.2
* Namespacing

* Fix socket return response
* API Endpoint update
* Update Batch Size Check
* Remove messageID override capabilities
* Update flush sleep waiting period

* Retry Network errors
* Update Tests [Improvement]
* Update Readme Status Badging
* Bump e2e tests to latest version [Improvement]
* Add Limits to message, batch and memeory usage [Feature]
* Add Configurable flush parameters [Feature]
* Add ability to use custom consumer [Feature]
* Add ability to set file permmissions [Feature]
* Fix curl error handler [Improvement]
* Fix timestamp implementation for microseconds
* Modify max queue size setting to match requirements
* Add ability to set userid as zero

### Bug Fixes

* bug fix
