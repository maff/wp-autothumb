=== AutoThumb ===

Donate link: http://ailoo.net/about
Tags: images, phpthumb, resize, thumbnails, library
Requires at least: 2.7
Tested up to: 3.3.1
Stable tag: 0.6.1

A plugin which integrates <a href="http://phpthumb.sourceforge.net/">phpThumb</a> into WordPress.

== Description ==

The plugin is actually just a port of a plugin/snippet I wrote for <a href="http://www.modxcms.com">MODx</a> a while ago (<a href="http://modxcms.com/phpThumb-Package-1522.html">see here</a>). It scans your content's source code for <kbd>&lt;img&gt;</kbd> tags with <kbd>width</kbd> and <kbd>height</kbd> attributes and passes them to <a href="http://phpthumb.sourceforge.net/">phpThumb</a>, a great PHP image editing library, which handles the resizing and editing of the image. Additionally, by passing parameters to phpThumb using a query string, it's possible to edit the image in several ways (take a look at <a href="http://phpthumb.sourceforge.net/demo/demo/phpThumb.demo.demo.php">phpThumb's demos</a>).

The simplest way to get resized images is to specify width/height attibutes in your HTML. AutoThumb will use the given values and remove the attributes from your page's output. When you take a look at the URLs of the example images found on the plugin home page, you will see that the <kbd>&lt;img&gt;</kbd>-tag's <kbd>src</kbd>-attribute points to a file called <kbd>image.php</kbd> in the plugin's directory. This file takes the image source and several other parameters and passes them to phpThumb, which will generate and output the final image (of course images will be cached).

To prevent evil people playing with the query string and letting your server generate tons of different images, the plugin generates a hash which is a combination of the query string and a secret password you set in phpThumb's config file. So every call with different parameters (even the same image with different sizes) has another unique hash and it's not possible to generate new images without knowing the secret password.

See the [plugin page](http://ailoo.net/projects/autothumb/) for more details on the plugin.

All development is done on [GitHub](https://github.com/maff/wp-autothumb). If you have ideas, enhancements, etc. feel free to fork the project and send a pull request.

For any issues please use the [Issue Tracker](https://github.com/maff/wp-autothumb/issues).

== Installation ==

1. Download the .zip file and extract it.
2. Upload the plugin to your `/wp-content/plugins/` directory and make sure your web server has write access to the cache folder and the config file.
3. Activate the plugin.
4. Go to Settings - AutoThumb and setup the plugin
5. Have fun!