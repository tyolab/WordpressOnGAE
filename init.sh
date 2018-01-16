#!/bin/sh
#
#cp -fr wp-config.php wordpress/

home=`dirname $0`
home=`readlink -f $home`

## DEBUG
# echo "home: " $home

themesDir=$home/themes
pluginsdDir=$home/plugins

wpContentDir=$home/wordpress/wp-content
wpThemesDir=$wpContentDir/themes
wpPluginsdDir=$wpContentDir/plugins

cd $wpContentDir
## DEBUG
echo `readlink -f .`

ln -sf $plugins/batcache/advanced-cache.php 
ln -sf $plugins/wp-memcache/object-cache.php

cd $wpPluginsdDir

## DEBUG
echo `readlink -f .`

ln -sf $plugins/appengine-wordpress-plugin/
ln -sf $plugins/batcache/batcache.php 

