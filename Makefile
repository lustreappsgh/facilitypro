SHELL := /bin/sh

.PHONY: up down build logs ps shell artisan composer npm test migrate fresh seed

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

logs:
	docker compose logs -f --tail=200

ps:
	docker compose ps

shell:
	docker compose exec php sh

artisan:
	docker compose exec php php artisan $(cmd)

composer:
	docker compose exec php composer $(cmd)

npm:
	docker compose exec node npm $(cmd)

test:
	docker compose exec php php artisan test

migrate:
	docker compose exec php php artisan migrate

fresh:
	docker compose exec php php artisan migrate:fresh --seed
