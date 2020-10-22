all: csfix static-analysis test
	@echo "Done."

vendor: composer.json
	composer update
	touch vendor

php-cs-fixer:
	wget https://cs.symfony.com/download/php-cs-fixer-v2.phar -O php-cs-fixer

.PHONY: csfix
csfix: php-cs-fixer
	php php-cs-fixer fix --verbose --rules @PhpCsFixer src/
	php php-cs-fixer fix --verbose --rules @PhpCsFixer tests/

.PHONY: static-analysis
static-analysis: vendor
	vendor/bin/phpstan analyse

.PHONY: test
test: vendor
	php -d zend.assertions=1 vendor/bin/phpunit
