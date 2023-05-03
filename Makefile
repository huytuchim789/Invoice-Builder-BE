setup:
	@make build
	@make up 
	@make composer-update
	@make key-gen
	@make run
build:
	docker-compose build --no-cache --force-rm
docker-down:
	docker-compose stop
docker-up:
	docker-compose up -d
composer-update:
	docker exec laravel-docker bash -c "composer update"
data:
	docker exec laravel-docker bash -c "php artisan migrate"
	docker exec laravel-docker bash -c "php artisan db:seed"
run:
	docker exec laravel-docker bash -c "php artisan serve --port=9000"
key-gen:
	docker exec laravel-docker bash -c "php artisan key:generate"