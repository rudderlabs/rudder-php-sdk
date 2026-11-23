bootstrap:
	scripts/bootstrap.sh

dependencies: vendor

vendor: composer.phar
	@php ./composer.phar install
	@php ./composer.phar require overtrue/phplint --dev;
	@php ./composer.phar require squizlabs/php_codesniffer --dev;
	@php ./composer.phar require dealerdirect/phpcodesniffer-composer-installer --dev;

composer.phar:
	@curl -sS https://getcomposer.org/installer | php

# Minimum line coverage for `make tests`. Raise it when coverage improves.
COVERAGE_MIN ?= 85

# CI always measures coverage. Locally, without pcov or Xdebug, the tests run without the coverage check.
tests: dependencies
	@mkdir -p build/logs
	@if [ "$$CI" = "true" ] || php -r 'exit(extension_loaded("pcov") || extension_loaded("xdebug") ? 0 : 1);'; then \
		vendor/bin/phpunit --colors --coverage-clover=build/logs/coverage-result.xml --log-junit=build/logs/execution-result.xml && \
		php scripts/check-coverage.php build/logs/coverage-result.xml $(COVERAGE_MIN); \
	else \
		echo "No coverage driver (pcov or Xdebug): running the tests without the coverage check."; \
		vendor/bin/phpunit --colors --no-coverage --log-junit=build/logs/execution-result.xml; \
	fi
	@php ./composer.phar validate

lint: dependencies
	@./vendor/bin/phplint;
	@./vendor/bin/phpcs;

lint-ci: dependencies
	@mkdir -p build/logs
	@./vendor/bin/phplint --format=junit -o build/logs/phplint.xml;
	@./vendor/bin/phpcs --report=checkstyle --report-file=build/logs/phpcs.xml;

release:
	@printf "releasing ${VERSION}..."
	@printf '<?php\n\ndeclare(strict_types=1);\n\nglobal $$RUDDER_VERSION;\n\n$$RUDDER_VERSION = "%b";\n' ${VERSION} > ./lib/Version.php
	@node -e "var fs = require('fs'), pkg = require('./composer'); pkg.version = '${VERSION}'; fs.writeFileSync('./composer.json', JSON.stringify(pkg, null, '\t'));"

example:
	@php -f examples/App.php

smoke-test:
	@php -f examples/sanity-test/Sanity.php

clean:
	rm -rf \
		composer.phar \
		vendor \
		composer.lock \
		build

.PHONY: bootstrap release clean
