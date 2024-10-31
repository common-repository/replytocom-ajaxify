=== ReplyToCom Ajaxify ===
Contributors: sultanicq
Tags: replytocom, seo
Requires at least: 2.8
Tested up to: 3.7.1
Stable tag: 1.0.3

Removes the ReplyToCom parameter from the comments querystring. This action favor the SEO optimizations.

== Description ==

This plugin removes the replytocom parameter that wordpress uses in threaded comments. Replaces them with a data-replytocom and later rebuild the href via an ajax call. This avoids duplicated content and favor SEO optimizations.

Visit the <a href="http://www.seocom.es/">Seocom website</a> for more information about SEO or WPO optimization

== Installation ==

1. Install ReplyToCom Ajaxify either via the WordPress.org plugin directory, or by uploading the files to your server inside the wp-content/plugins folder of your WordPress installation.
2. Activate ReplyToCom Ajaxify plugin via WordPress Settings.
3. It's done. Easy, isn't it?

== Changelog ==

= 1.0.3 =
* BugFix. Fixed .htaccess rules management when installing/uninstalling the plugin.

= 1.0.2 =
* Verify the .htaccess write access to make the plugin able to modify it with Rewrite Rules.

= 1.0.1 =
* Added the option to do a 301 redirect when receiving simple replytocom queries. The only allowed replytocom query must be used with a _safereplytocom querystring parameter.

= 1.0.0 =
* Initial Release.
