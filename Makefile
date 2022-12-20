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
	@vendor/bin/phpunit --colors --coverage-xml build/logs/coverage test/
	@php ./composer.phar validate

lint: dependencies
	@mkdir -p build/logs
	@./vendor/bin/phplint --xml=build/logs/phplint.xml;
	@./vendor/bin/phpcs --report=checkstyle --report-file=build/logs/phpcs.xml;

release:
	@printf "releasing ${VERSION}..."
	@printf '<?php\nglobal $$RUDDER_VERSION;\n$$RUDDER_VERSION = "%b";\n' ${VERSION} > ./lib/Version.php
	@node -e "var fs = require('fs'), pkg = require('./composer'); pkg.version = '${VERSION}'; fs.writeFileSync('./composer.json', JSON.stringify(pkg, null, '\t'));"
	@git changelog -t ${VERSION}
	@git release ${VERSION}

example:
	@php -f examples/App.php

clean:
	rm -rf \
		composer.phar \
		vendor \
		composer.lock \
		build
