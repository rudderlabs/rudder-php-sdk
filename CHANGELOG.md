# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [2.0.0](https://github.com/rudderlabs/rudder-sdk-js/compare/v2.0.0...v1.0.1) (2023-01-05)


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
