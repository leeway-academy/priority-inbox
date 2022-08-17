image_name=gmail-priority-inbox

build-prod:
	DOCKER_BUILDKIT=1 docker build --target=prod_img -t $(image_name) .

build-dev:
	DOCKER_BUILDKIT=1 docker build --target=dev_img -t $(image_name) .