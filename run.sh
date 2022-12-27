#!/usr/bin/env bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
docker run -v $SCRIPT_DIR/app:/app/:rw gmail-priority-inbox php run.php -vvv $@