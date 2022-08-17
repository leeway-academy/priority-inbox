image_name=gmail-priority-inbox

build:
	docker build -t $(image_name) .

build-dev: build
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer install

build-prod: build
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist

test: 
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer run-script test

cs-fix: 
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer run-script cs-fix



