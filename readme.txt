=== Plugin Name ===
Contributors: foomagoo
Donate link: 
Tags: plugin organizer, load order, organize plugins, plugin order, sort plugin, group plugin, disable plugins by post, disable plugins by page, turn off plugins for post, turn off plugins for page
Requires at least: 3.0
Tested up to: 3.0.4
Stable tag: 0.5

This plugin allows you to do the following:
1. Change the order that your plugins are loaded.
2. Selectively disable plugins by page or post.

== Description ==

This plugin allows you to do the following:
1. Change the order that your plugins are loaded.
2. Selectively disable plugins by page or post.
3. Adds grouping to the plugin admin age.

== Installation ==

1. Extract the downloaded Zip file.
2. Upload the 'plugin_organizer' directory to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. You can either use the menu item under settings in the WordPress admin called Organize Plugins or there will be a drop down list for each plugin in the plugin admin page to select the load order.

NOTE: To enable selective plugin loading you must move the /wp-content/plugins/plugin-organizer/lib/PluginOrganizerMU.class.php file to /wp-content/mu-plugins.  If the mu-plugins directory does not exist you can create it.


== Frequently Asked Questions ==
Q. Does this only apply to WP MU or all types of WP installs?
"NOTE: To enable selective plugin loading you must move the /wp-content/plugins/plugin-organizer/lib/PluginOrganizerMU.class.php file to /wp-content/mu-plugins. If the mu-plugins directory does not exist you can create it."

A. The mu-plugins folder contains "Must Use" plugins that are loaded before regular plugins. The mu is not related to WordPress MU. This was added to regular WordPress in 3.0 I believe. I only placed this one class in the MU folder because I wanted to have my plugin run as a normal plugin so it could be disabled if needed. 

== Screenshots ==

1. Plugin admin page example.
2. Settins page example.
3. Post edit page example with disable plugins meta box.

== Changelog ==

= 0.5 =
Added functionality to selectively disable plugins by post or page.  
There is now a Must Use plugin component that comes with the main plugin.
To enable selective plugin loading you must move the /wp-content/plugins/plugin-organizer/lib/PluginOrganizerMU.class.php file to /wp-content/mu-plugins.
If the mu-plugins directory does not exist you must create it.


= 0.4.1 =
Fixed empty items in plugin list.

= 0.4 =
Added grouping to the plugin admin page.
Improved ajax requests
Added ajax loading image.
Added page to create and organize plugin groups.

= 0.3 =
Added ajax requests to the settings page so both forms now use ajax.
Added nonce checking to the ajax requests.  
Requires user to have activate_plugins capability.

= 0.2 =
Made function to reorder the plugins on plugin admin page in the order they will be loaded.
Redid the sort functions to use PHP's array_multisort.

= 0.1.1 =
improved the ajax requests on the plugin admin page.  

= 0.1 =
Initial version.

== Upgrade Notice ==

= 0.5 =
Added functionality to selectively disable plugins by post or page.
