image_name=gmail-priority-inbox

build:
	docker build -t $(image_name) .

install-vim:
	docker run $(image_name) apk add vim

composer-install:
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer install

build-dev: build composer-install install-vim

build-prod: build
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist

test: 
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer run-script test

cs-fix: 
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer run-script cs-fix

composer-update:
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer update

phpstan:
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer run-script phpstan

behat:
	docker run -v $(shell pwd)/app/:/app/:rw $(image_name) composer run-script behat

sh:
	docker run -it -v $(shell pwd)/app/:/app/:rw $(image_name) sh
