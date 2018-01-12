#!/bin/bash

ls debug/* | while read line
do
    ln -sf $line 
done

export XDEBUG_CONFIG="idekey=netbeans-xdebug remote_host=localhost"

dev_appserver.py . --php_remote_debugging=yes "$*"
