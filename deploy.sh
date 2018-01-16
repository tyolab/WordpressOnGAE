#!/bin/bash

# cp -fr batcache/advanced-cache.php wordpress/wp-content/
# cp -fr batcache/batcache.php wordpress/wp-content/plugins/
# cp -fr wp-memcache/object-cache.php wordpress/wp-content/

ls release/* | while read line
do
    ln -sf $line 
done

appcfg.py update . "$*"
