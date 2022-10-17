# This file is paste in each MS, added from filesGeneralPathCopy.php

# The stack name will be the current directory name.
STACK_NAME = $(notdir $(shell pwd))
IP_GATEWAY=$(shell docker inspect -f "{{json .IPAM.Config }}" web-proxy | grep -Po '"Gateway":.*"}]' | grep -Po '(?:[0-9]{1,3}\.){3}[0-9]{1,3}')
DOCKER_COMPOSE = docker-compose -p $(STACK_NAME)
EXEC_PHP = $(DOCKER_COMPOSE) exec php
EXEC_PHP_WITHOUT_TTY = $(DOCKER_COMPOSE) exec -T php
SYMFONY = $(EXEC_PHP) bin/console
SYMFONY_WITHOUT_TTY = $(EXEC_PHP_WITHOUT_TTY) bin/console
COMPOSER = $(DOCKER_COMPOSE) exec -e COMPOSER_MEMORY_LIMIT=-1 php composer
REVERSE_PROXY_IP = $(shell docker network inspect -f '{{ index (index .IPAM.Config) 0 "Gateway" }}' web-proxy)
PHPTOOLS = docker run -v $$PWD:/app --rm -w /app ubitransport/php-tools:ci-php8.1
PHPSTAN = $(EXEC_PHP_WITHOUT_TTY) vendor/bin/phpstan
PHP_CS_FIX = $(PHPTOOLS) php-cs-fixer
BASELINE_FILE = "current-baseline.neon"
COVERAGE_FOLDER = 'coverage'
COVERAGE_SPECIFIC_FOLDER = 'coverage-specific'
COVERAGE_INFECTION_FOLDER = 'coverage-infection'

# Find number of possible threads
NPROCS = 1
OS=$(shell uname -s)
ifeq ($(OS),Linux) # linux
  NPROCS := $(shell grep -c ^processor /proc/cpuinfo)
else ifeq ($(OS),Darwin) # Mac Os
  NPROCS := $(shell sysctl -n hw.ncpu)
endif # $(OS)

ifdef level
	PHPSTAN_LEVEL = --level $(level)
endif

ifdef baseline_file
	BASELINE_FILE = $(baseline_file)
endif

ifdef filter
	TEST_FILTER = --filter=$(filter)
endif

ifdef coverage_folder
	COVERAGE_FOLDER = $(coverage_folder)
	COVERAGE_SPECIFIC_FOLDER = $(coverage_folder)
	COVERAGE_INFECTION_FOLDER = $(coverage_folder)
endif

debug:
	echo "running using $(NPROCS) threads on $(OS)";

.env:
	echo '\033[1;41m/!\ The .env is missing.\033[0m';\
	exit 1;\

include .env

.DEFAULT_GOAL := help
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help

##
## Project
## -------
##

build:
	IP_GATEWAY=$(IP_GATEWAY) $(DOCKER_COMPOSE) pull --ignore-pull-failures
	IP_GATEWAY=$(IP_GATEWAY) $(DOCKER_COMPOSE) build --pull

kill:
	$(DOCKER_COMPOSE) kill
	IP_GATEWAY=$(IP_GATEWAY) $(DOCKER_COMPOSE) down --volumes --remove-orphans

install: ## Install and start the project
install: .docker.env .env build start composer-install npm-install

reset: ## Stop and start a fresh install of the project
reset: clean install

start: .docker.env ## Start the project
	REVERSE_PROXY_IP=$(REVERSE_PROXY_IP) IP_GATEWAY=$(IP_GATEWAY) $(DOCKER_COMPOSE) up -d --remove-orphans --force-recreate

stop: ## Stop the project
	$(DOCKER_COMPOSE) stop

rm-generated-folder-files: ## Remove generated folders / files
rm-generated-folder-files:
	$(EXEC_PHP) rm -rf var vendor node_modules public/bundles tmp-php-quality-analysis node_modules .php_cs.cache .phpunit.result.cache php-cs-fixer.phar bin/.phpunit infection.log infection-log.json

clean: ## Stop the project and remove generated folders / files
clean: rm-generated-folder-files kill

.docker.env:
	cp docker/php/.docker.env.example .docker.env

.PHONY: build kill install reset start stop clean

##
## Utils
## -----
##

db: ## Reset the database and load fixtures
db: .env
	$(EXEC_PHP) php ./docker/php/set_database.php

migration: ## Generate a new doctrine migration
migration:
	$(SYMFONY) doctrine:migrations:diff

migrate: ## Apply migrations.
migrate:
	$(SYMFONY) doctrine:migrations:migrate

pbc: ## Run php bin/console inside the php container using a cmd parameter. Example: make pbc cmd=debug:router
pbc:
	$(SYMFONY) $(cmd)

cc: ## Clear Symfony cache by running php bin/console cache:clear
cc:
	$(SYMFONY) cache:clear

delete-cache: ## Remove the cache folder and make a cache clear
delete-cache:
	$(EXEC_PHP) rm -rf ./var/cache && $(SYMFONY) cache:clear

phpinfo: ## Run phpinfo() inside the php container.
phpinfo:
	$(EXEC_PHP) php-fpm7.4 -i

phprepl: ## Write and execute php code directly inside the php container (php -a).
phprepl:
	$(EXEC_PHP) php -a

bash: ## Open a bash shell inside the php container.
bash:
	$(EXEC_PHP) bash

.PHONY: cc phpinfo phprepl bash

##
## Tests
## -----
##

prepare-test-database: ## Prepare the test database.
prepare-test-database:
	$(EXEC_PHP) php ./docker/php/set_database.php test

test: ## Run all tests.
test: prepare-test-database
	$(EXEC_PHP) bin/phpunit

test-coverage: ## Run all tests and generate an HTML coverage report inside coverage/ folder. Add coverage_folder=coverageFolderNameHere to use a different folder.
test-coverage: prepare-test-database
	$(DOCKER_COMPOSE) exec -e PHP_XDEBUG_MODE="coverage" php php bin/phpunit --coverage-html $(COVERAGE_FOLDER)

test-unit: ## Run unit tests only.
test-unit:
	$(EXEC_PHP) bin/phpunit tests/Unit

test-functional: ## Run functional tests only.
test-functional: prepare-test-database
	$(EXEC_PHP) bin/phpunit tests/Functional

test-specific: ## Run specific tests. Add folder=testFolderHere to run tests inside a specific folder. Add filter=CriteriaHere to filter tests by a common criteria.
test-specific:
	$(EXEC_PHP) bin/phpunit $(folder) $(TEST_FILTER)

test-specific-coverage: ## Run test-specific and generate an HTML coverage report inside coverage-specific/ folder. Add coverage_folder=coverageFolderNameHere to use a different folder.
test-specific-coverage:
	$(EXEC_PHP) bin/phpunit $(folder) $(TEST_FILTER) --coverage-html $(COVERAGE_SPECIFIC_FOLDER)

test-infection: ## Run infection on the entire codebase.
test-infection: prepare-test-database
	$(DOCKER_COMPOSE) exec -e PHP_XDEBUG_MODE="coverage" php infection --threads=$(shell nproc) $(args) --no-progress

test-infection-specific: ## Run infection on a specific file. Add filter=sourceFile.php to target a source file. Optional : Add phpUnitFilter=criteriaHere if your test file name is not like sourceFileTest.php.
test-infection-specific:
	./scripts/run_infection_specific.sh $(filter) $(COVERAGE_INFECTION_FOLDER) $(phpUnitFilter)

.PHONY: prepare-test-database test test-coverage test-unit test-functional test-specific test-specific-coverage test-infection test-infection-specific

##
## Composer
## --------
##

composer-install: ## Run composer install
composer-install:
	$(COMPOSER) install

composer-update: ## Run composer update
composer-update:
	$(COMPOSER) update

.PHONY: composer-install composer-update

##
## Linter
## ------
##


php-cs-fix: ## Fix files using php cs fixer
php-cs-fix:
	$(PHP_CS_FIX) fix --config=/root/.composer/vendor/ubitransport/coding-standards/config/.php_cs_microservice.dist

php-cs-check: ## Check files using php cs fixer
php-cs-check:
	$(PHP_CS_FIX) fix --config=/root/.composer/vendor/ubitransport/coding-standards/config/.php_cs_microservice.dist --verbose --dry-run --using-cache=no

.PHONY: php-cs-fix php-cs-check

##
## Quality analysis
## ----------------
##

phpstan-analyse: ## Run phpstan analyse using phpstan.neon configuration file. Add level=PhpStanLevelHere to use a specific level between 0 and 8.
phpstan-analyse:
	$(PHPSTAN) analyse $(PHPSTAN_LEVEL)

phpstan-generate-baseline: ## Run phpstan analyse and generate a baseline. Add baseline_file=PhpStanBaseLineFileNameHere to set the baseline file name (default: current-baseline.neon).
phpstan-generate-baseline:
	$(PHPSTAN) analyse $(PHPSTAN_LEVEL) --generate-baseline $(BASELINE_FILE)

phpstan-version: ## Show phpstan version used
phpstan-version:
	$(PHPSTAN) --version

.PHONY: phpstan-analyse phpstan-generate-baseline phpstan-version

##
## Logs
## ----------------
##

logs: ## Get the log output for php.
logs:
	$(DOCKER_COMPOSE) logs -f --tail=1000 php

.PHONY: logs

##
## npm
## ----------------
##

npm-install: ## Install JavaScript dependencies using npm.
npm-install:
	npm install

.PHONY: npm-install

##
## Release
## ----------------
##

release: ## Create a git tag.
release: npm-install
	node release

.PHONY: release

##
## Version
## ----------------
##

version: ## Set the git tag in swagger documentation.
version: npm-install
	node version

.PHONY: version

##
## CI
## ----------------
##

ci: ## Run commands used by GitLab CI.
ci: php-cs-fix test phpstan-analyse

.PHONY: ci
