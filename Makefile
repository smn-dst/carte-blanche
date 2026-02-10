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

test:
	docker compose exec -it php php bin/phpunit

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