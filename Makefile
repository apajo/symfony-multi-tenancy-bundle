# Entry point for the dockerized test environment. See compose.yaml.

# Exported so compose can map the runner container onto the invoking user and
# keep bind-mounted files (var/cache, migrations/) out of root's hands.
export UID := $(shell id -u)
export GID := $(shell id -g)

COMPOSE  := docker compose
RUN      := $(COMPOSE) run --rm tests
# --no-deps skips the database dependency for the tiers that must not need one.
RUN_SOLO := $(COMPOSE) run --rm --no-deps tests
DOWN     := $(COMPOSE) down -v --remove-orphans

# The test targets leave nothing running: they tear the stack down afterwards
# and then exit with phpunit's own status, so a failing suite still fails the
# command. Each recipe line is a separate shell, hence the one-liners.
teardown-and-exit = ; status=$$?; $(DOWN); exit $$status

.PHONY: help build up down test test-unit test-integration test-db shell logs

help: ## List the available targets
	@grep -hE '^[a-z-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "} {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

build: ## Build the runner image
	$(COMPOSE) build

up: ## Start the test database and wait for it to be healthy
	$(COMPOSE) up -d --wait database

down: ## Stop everything and discard the database
	$(DOWN)

test: ## Run the full suite, then stop the containers
	@$(RUN) vendor/bin/phpunit $(teardown-and-exit)

test-unit: ## Run the unit suite (needs no database)
	@$(RUN_SOLO) vendor/bin/phpunit --testsuite unit $(teardown-and-exit)

test-integration: ## Run the integration suite (needs no database)
	@$(RUN_SOLO) vendor/bin/phpunit --testsuite integration $(teardown-and-exit)

test-db: ## Run the database suite, then stop the containers
	@$(RUN) vendor/bin/phpunit --testsuite database $(teardown-and-exit)

shell: ## Open a shell in the runner container
	$(RUN) bash

logs: ## Tail the database logs
	$(COMPOSE) logs -f database
