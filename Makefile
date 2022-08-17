build:
	docker build . -t gmail-priority-inbox

build-dev: build
	docker run -t gmail-priority-inbox apk add vim