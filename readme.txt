=== Plugin Name ===
Contributors: foomagoo
Donate link: 
Tags: plugin organizer, load order, organize plugins, plugin order, sort plugin, group plugin
Requires at least: 3.0
Tested up to: 3.0.4
Stable tag: 0.4

This plugin allows you to change the order that your plugins are loaded.  It also adds grouping to the plugin admin age.

== Description ==

This plugin allows you to change the order that your plugins are loaded.  It also adds grouping to the plugin admin age.

== Installation ==

1. Extract the downloaded Zip file.
2. Upload the 'plugin_organizer' directory to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. You can either use the menu item under settings in the WordPress admin called Organize Plugins or there will be a drop down list for each plugin in the plugin admin page to select the load order.

== Frequently Asked Questions ==

== Screenshots ==

1. Plugin admin page example.
http://www.nebraskadigital.com/wp-content/uploads/2010/12/PO_screen_1.jpg

2. Settins page example.
http://www.nebraskadigital.com/wp-content/uploads/2010/12/PO_screen_2.jpg

== Changelog ==

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
