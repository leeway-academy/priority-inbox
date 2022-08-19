#!/usr/bin/env sh

docker run -v $(pwd)/app:/app/:rw gmail-priority-inbox php run.php -vvv $@
