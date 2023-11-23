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

tests: dependencies
	@mkdir -p build/logs
	@vendor/bin/phpunit --colors --coverage-clover=build/logs/coverage-result.xml --log-junit=build/logs/execution-result.xml
	@php ./composer.phar validate

lint: dependencies
	@./vendor/bin/phplint;
	@./vendor/bin/phpcs;

lint-ci: dependencies
	@mkdir -p build/logs
	@./vendor/bin/phplint --log-junit=build/logs/phplint.xml;
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
