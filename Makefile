setup:
	@make build
	@make docker-up 
	@make composer-update
	@make key-gen
	# @make run
	@make log_permissions
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
	docker exec laravel-docker bash -c "cp .env.example .env"
	docker exec laravel-docker bash -c "php artisan key:generate"
log_permissions:
	docker exec laravel-docker bash -c "chmod o+w ./storage/ -R"
migrate_all:
	docker exec laravel-docker bash -c "php artisan migrate"
cache:
	docker exec laravel-docker bash -c "php artisan config:clear"
	docker exec laravel-docker bash -c "php artisan cache:clear"
jwt-gen:
	docker exec laravel-docker bash -c "php artisan jwt:secret"