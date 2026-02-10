# Makefile
.DEFAULT_GOAL := sh

sh:
	docker compose exec -it php sh

cache:
	docker compose exec -it php php bin/console cache:clear

logs:
	docker compose logs -f --tail=100

migrate:
	docker compose exec -it php php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	docker compose exec -it php php bin/console doctrine:fixtures:load --no-interaction

phpstan:
	docker compose exec php php vendor/bin/phpstan analyse --memory-limit=512M

cs-fix:
	./php-cs-fixer.phar fix

cs-check:
	./php-cs-fixer.phar fix --dry-run --diff

quality: cs-check phpstan

lint:
	docker compose exec php php bin/console lint:twig templates/
	docker compose exec php php bin/console lint:yaml config/
	docker compose exec php php bin/console lint:container

# === Tests ===

test:
	docker compose exec php php bin/phpunit

test-unit:
	docker compose exec php php bin/phpunit --testsuite Unit

test-functional:
	docker compose exec php php bin/phpunit --testsuite Functional

test-e2e:
	npx cypress run

test-e2e-open:
	npx cypress open

test-all: test-unit test-functional test-e2e

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose up -d --build

reset-db:
	docker compose exec -it php php bin/console doctrine:database:drop --force --if-exists
	docker compose exec -it php php bin/console doctrine:database:create
	docker compose exec -it php php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec -it php php bin/console doctrine:fixtures:load --no-interaction

# git config core.hooksPath .githooks pour configurer les hooks
setup-hooks:
	git config core.hooksPath .githooks
	npm install
	@echo "Hooks et dependances configures !"