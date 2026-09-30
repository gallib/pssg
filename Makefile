.PHONY: up build down reset install update require dump composer

up:
	docker compose up -d

build:
	docker compose up --build -d

down:
	docker compose down

reset:
	docker compose down -v

install:
	docker compose run --rm php composer install

update:
	docker compose run --rm php composer update

require:
	docker compose run --rm php composer require $(pkg)

dump:
	docker compose run --rm php composer dump-autoload

composer:
	docker compose run --rm php composer $(cmd)