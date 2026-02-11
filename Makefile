# Variables
IMAGE_NAME := gmail-priority-inbox
PWD := $(shell pwd)
VOLUMES := -v $(PWD)/app:/app:rw
DOCKER_RUN := docker run --rm --network=host $(VOLUMES) $(IMAGE_NAME)

.PHONY: build install-vim composer-install build-dev build-prod test cs-fix composer-update phpstan behat sh run-app

build:
	docker build --network=host -t $(IMAGE_NAME) .

install-vim:
	$(DOCKER_RUN) apk add vim

composer-install:
	$(DOCKER_RUN) composer install

build-dev: build composer-install install-vim

build-prod: build
	$(DOCKER_RUN) composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist

test: 
	$(DOCKER_RUN) composer run-script test

cs-fix: 
	$(DOCKER_RUN) composer run-script cs-fix

composer-update:
	$(DOCKER_RUN) composer update

phpstan:
	$(DOCKER_RUN) composer run-script phpstan

behat:
	$(DOCKER_RUN) composer run-script behat

sh:
	docker run --rm -it --network=host $(VOLUMES) $(IMAGE_NAME) sh

run-app:
	$(DOCKER_RUN) php run.php -vvv $(ARGS)