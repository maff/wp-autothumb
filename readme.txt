=== AutoThumb ===
Contributors:
Donate link: http://ailoo.net
Tags: images, resize, thumbnails, library, phpthumb
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: 0.2

A plugin which integrates <a href="http://phpthumb.sourceforge.net/">phpThumb</a> into Wordpress.

== Description ==

See the [plugin page](http://maff.ailoo.net/projects/autothumb/) for more details on the plugin.

== Installation ==

1. Download the .zip file and extract it
1. Open phpthumb/phpThumb.config.php
1. Edit phpThumb settings according to your server settings (ImageMagick, debug output, ...) — the config file is well documented, so you should not run into any problems here
1. **Important:** edit `$PHPTHUMB_CONFIG['high_security_password']` on line 196. You can enter any long random string (except quotes, please), but make sure it is not guessable and that it differs from the distributed one
1. Upload the plugin to your `/wp-content/plugins/` and make sure your web server has write access to the cache folder
1. Activate the plugin and have fun