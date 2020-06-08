#!/bin/sh

if [ "$(id -u)" != "0" ]; then
    exec "$@"
    exit $?
fi

stat_dir=$(pwd)
uid=$(stat -c '%u' $stat_dir)

exec su-exec $uid bash -c "HOME=/tmp $@"
