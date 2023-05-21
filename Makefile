IMAGE_NAME=invoice-builder-be_laravel-docker
CONTAINER_NAME=laravel-docker
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
	docker exec ${CONTAINER_NAME} bash -c "composer update"
data:
	docker exec ${CONTAINER_NAME} bash -c "php artisan migrate"
	docker exec ${CONTAINER_NAME} bash -c "php artisan db:seed"
run:
	docker exec ${CONTAINER_NAME} bash -c "php artisan serve --host=0.0.0.0 --port=8000"
key-gen:
	docker exec ${CONTAINER_NAME} bash -c "cp .env.example .env"
	docker exec ${CONTAINER_NAME} bash -c "php artisan key:generate"
log_permissions:
	docker exec ${CONTAINER_NAME} bash -c "chmod o+w ./storage/ -R"
migrate_all:
	docker exec ${CONTAINER_NAME} bash -c "php artisan migrate"
cache:
	docker exec ${CONTAINER_NAME} bash -c "php artisan config:clear"
	docker exec ${CONTAINER_NAME} bash -c "php artisan cache:clear"
jwt-gen:
	docker exec ${CONTAINER_NAME} bash -c "php artisan jwt:secret"

gen-swagger:
	docker exec ${CONTAINER_NAME} bash -c "	php artisan laravel-swagger:generate > public/documentation/swagger.json"
