image_name=gmail-priority-inbox

build:
	docker build -t $(image_name) .

build-dev: build
	docker run -v $(shell pwd)/app/:/app/:rw -it $(image_name) composer install

build-prod: build
	docker run -v $(shell pwd)/app/:/app/:rw -it $(image_name) composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist
