#!/bin/bash

ls debug/* | while read line
do
    ln -sf $line 
done

export XDEBUG_CONFIG="idekey=netbeans-xdebug remote_host=localhost"

# Option: --php_executable_path
# Possible paths:
#
# /usr/local/Cellar/php55/5.5.38_12/
# --php_executable_path=/usr/local/Cellar/php72/7.2.1_12/
# /usr/local/opt/google-cloud-sdk/platform/php55/

dev_appserver.py . --php_remote_debugging=yes
