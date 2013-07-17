=== Plugin Organizer ===
Contributors: foomagoo
Donate link: 
Tags: plugin organizer, load order, organize plugins, plugin order, sort plugin, group plugin, disable plugins by post, disable plugins by page, disable plugins by custom post type, turn off plugins for post, turn off plugins for page, turn off plugins for custom post type
Requires at least: 3.1
Tested up to: 3.5.2
Stable tag: 3.1.1


This plugin allows you to do the following:
1. Change the order that your plugins are loaded.
2. Selectively disable plugins by any post type or wordpress managed URL.
3. Adds grouping to the plugin admin age.

== Description ==

This plugin allows you to do the following:
1. Change the order that your plugins are loaded.
2. Selectively disable plugins by any post type or wordpress managed URL.
3. Adds grouping to the plugin admin age.

== Installation ==

1. Extract the downloaded Zip file.
2. Upload the 'plugin-organizer' directory to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Use the menu item under settings in the WordPress admin called Plugin Organizer to get the plugin set up.

IMPORTANT: To enable selective plugin loading you must move the /wp-content/plugins/plugin-organizer/lib/PluginOrganizerMU.class.php file to /wp-content/mu-plugins or wherever your mu-plugins folder is located.  If the mu-plugins directory does not exist you can create it.  The plugin will attempt to create this directory and move the file itself when activated.  Depending on your file permissions it may not be successful.


== Frequently Asked Questions ==
Q. How do I enable the selective plugin loading functionality?

A. Go to the Plugin Organizer settings page and check the enable radio button under selective plugin loading.  Then visit your homepage.  Finally return to the Plugin Organizer settings page and see if the enable radio button is still checked.  If it is not then you are running an old version of the MU component.  Copy the PluginOrganizerMU.class.php file to the mu-plugins folder then deactivate and reactivate the plugin.  Repeat these steps to ensure that the plugin is working.  Remember that you will need to update the PluginOrganizerMU.class.php file whenever the plugin is updated and check your settings afterward.

Q. Does this plugin work with wordpress multi-site?

A. Yes it has been tested on several multi-site installs.  Both subdomain and sub folder types.

Q. Does this plugin work with custom post types?

A. Yes it has been tested with custom post types.  You can add support for your custom post types on the settings page.

Q. Does this only apply to WP MU or all types of WP installs?
"IMPORTANT: To enable selective plugin loading you must move the /wp-content/plugins/plugin-organizer/lib/PluginOrganizerMU.class.php file to /wp-content/mu-plugins or wherever your mu-plugins folder is located. If the mu-plugins directory does not exist you can create it.  The plugin will attempt to create this directory and move the file itself when activated.  Depending on your file permissions it may not be successful."

A. The mu-plugins folder contains "Must Use" plugins that are loaded before regular plugins. The mu is not related to WordPress MU. This was added to regular WordPress in 3.0 I believe. I only placed this one class in the MU folder because I wanted to have my plugin run as a normal plugin so it could be disabled if needed. 


Q. In what instance would this plugin be useful?

A. 
  Example 1: If you have a large number of plugins and don't want them all to load for every page you can disable the unneeded plugins for each individual page.  Or you can globally disable them and enable them for each post or page you will need them on.
  Example 2: If you have plugins that conflict with eachother then you can disable the plugins that are conflicting for each indivdual post or page.
  Example 3: If you have plugins that conflict with eachother then you can disable the plugins globally and activate them only on posts or pages where they will be used.

== Screenshots ==

1. Plugin admin page example.
2. Settings page example.
3. Global plugins page.
4. Page edit screen.

== Changelog ==

= 3.1.1 =
Adding cache variable to store the plugin list so it is only created once per page load instead of every time the active_plugins option is retrieved.

= 3.1 =
Adding the ability to target specific browsers.  Useful for loading specific plugins for mobile browsers.

= 3.0.10 =
Fixing warning from searching empty array in group members on plugins page.

= 3.0.9 =
Fixing typo in version number check on initialization.
Got rid of code to fix old custom permalink field

= 3.0.8 =
Removed a call to wp_count_posts in the activation function.  It may have been causing issues on activation.

= 3.0.7 =
Removed a call to get_permalink in the activation function.  It may have been causing issues on multisite activation.

= 3.0.6 =
Fixed an issue with activation where too many posts on the site caused the php to run out of memory and the activation to fail.

= 3.0.5 =
Fixed issue on multisite where the $GLOBALS['wp_taxonomies'] array hadn't been created yet so a php warning was thrown.

= 3.0.4 =
Fixed a typo that caused imported filters to not have a permalink.
Added code to repair anyones database that has already been upgraded with bad permalinks.

= 3.0.3 =
Fixed an issue with the advanced meta query from get_posts adding % characters and escaping my % character.

= 3.0.2 =
Fixed an issue when using ignore protocol the first query wouldn't match.
Fixed an issue where a post is found on the first query but no plugins have been disabled so the enabled plugins are overlooked.

= 3.0.1 =
Fixed a problem with fuzzy url matching.  " characters were being added to the url so it would never match.
Commented out the code that deleted the tables and added an option to the databse to prevent multiple imports.  Will add the delete code in a later version to clean up the tables after everyone is stable and has imported their settings.
Added code to ensure that the old MU plugin is deleted before attempting to copy it from the lib directory.

= 3.0 =
Complete redesign of the plugin.
Removed all custom db tables and moved the data to the post_meta table.
Added custom post type plugin_filter to replace the URl admin.
Added custom post type plugin_group to replace the plugin groups table.
The plugins displayed on post/pages/custom post types/global plugins page are now sorted and colored similar to the main plugin page.
There is no longer an enabled and disabled plugin box.  Enabled and disabled plugins are now all managed together to avoid confusion.
Fixed a bug where the MU plugin chopped the url before checking it so it looped 15 times on the homepage before stopping the search for a fuzzy url.
Fixed a bug where globally disabled plugins were listed as inactive when the list of active plugins was accessed.

= 2.6.3 =
Fixing bug that allows plugins to be disabled on the update pages.

= 2.6.2 =
Fixing PHP notices

= 2.6.1 =
Fixing bad characters added during commit

= 2.6 =
Fixed error on windows when inserting into po_post_plugins without specifying all fields.
Added ability to effect children of posts, pages, custom post types.
Redesign of the post edit screen meta box.

= 2.5.9 =
Missed a file when committing 2.5.8.

= 2.5.8 =
Fixing grouping issues.  
Plugin names were not being escaped when building the group list for display so they werent showing up.
On the recently active screen the plugin organizer actions were duplicated and so when adding to group the group name was duplicated.

= 2.5.7 =
Fixing more bad characters being added by svn or wordpress.org.

= 2.5.6 =
Replacing Icons because they were released under creative commons and not gpl.

= 2.5.5 =
Fixing missing db table error message when the table exists on windows server.

= 2.5.4 =
Fixing bad characters being added by svn or wordpress.org.

= 2.5.3 =
Fixed a jquery issue with wp 3.5

= 2.5.2 =
Added warnings on settings page if the database tables are missing.
Removed default value for longtext database fields.  Caused issues on windows.

= 2.5.1 =
Fixed a problem with URL admin not saving edited URLs
Changed the first menu item to settings under Plugin Organizer

= 2.5 =
Removed PHP notice errors.
The plugin organizer plugin can no longer be disabled on the admin.
Added better support for multi-site.
The plugin will now correct plugins that are network activated and activated on the local site so they are only network activated.  This fixes an error where more plugins were seen as active than were displayed on the plugins page.
The plugin organizer features will not load on the network admin.
Network activated plugins can now be disabled.

= 2.4 =
Adding ability to ignore arguments to a URL.  You can now enter URLs into the URL admin with arguments so that http://yoururl.com/page/?foo=2&bar=3 will have different plugins loaded than http://yoururl.com/page/?foo=1&bar=4 and http://yoururl.com/page/.
Fixed URL admin so that it checks to make sure the URL was entered into the database before saying it was successful.

= 2.3.3 =
Undoing a change that was done in 2.3.1 to the request uri that removed arguments from the uri.  It is causing some issues for some users.  Will redesign and create a later release to optionally remove the arguments. 

= 2.3.2 =
When the user hadnt set the number of plugins displayed per page it was being defaulted to 20.  Changed it to default to 999.
Set $endChar to an empty string in PluginOrganizerMU.class.php to prevent debug notices.

= 2.3.1 =
Fixed a javascript error on the URL admin page.
Fixed logic for Global plugins where all plugins were disabled none where getting disabled.
Fixed use of REQUEST_URI.  Now it Splits the REQUEST_URI to trim off URL arguments.
Added ability to reset plugin order back to wordpress default.
Renamed some javascript functions and consolidated some of them.

= 2.3 =
Removed the old admin pages.  The plugins can now be managed directly on the plugins page.
Redesigned the settings page to use ajax queries instead of reloading the page to save settings.
Redesigned the URL admin to use ajax to save and edit URL's instead of reloading the page.
Moved most of the javascript out of the main class and into template files.
Added a setting to preserve the plugin data when it is deactivated.  The plugin data including database tables and MU plugin file can now be removed on deactivation.

= 2.2.1 =
Added ability to ignore the protocol when matching the requested URL by checking a checkbox on the settings page.

= 2.2 =
Added Fuzzy URL matching to the arbitrary URL admin.  URLs can now effect their children.
Added nonce checking to URL admin.
Restructured forms on the main settings page.

= 2.1.3 =
Added checks to ensure plugin load order cant be changed when all plugins are not viewable on the page.

= 2.1.2 =
Fixed group view on plugin organizer page when the plugins per page has been set too low or extremely high.
Fixed setting of the show old admin page when either save settings button is clicked.

= 2.1.1 =
Adding option to show the old admin pages.

= 2.1 =
Added better group management to the plugin admin page.
Removed group management pages from the menu.

= 2.0 =
Added drag and drop functionality to the plugin admin page.
Added group links to the top of the plugin admin page that replace the group dropdown.
Added better checking to make sure the plugin load order can only be changed when all plugins are being displayed.

= 1.2.3 =
Fixed URL admin page.  Enabled plugins list wasnt saving on creation.

= 1.2.2 =
Fixed typo in recreate permalinks function.
Centralized the nonce generation so the PluginOrganizer class now holds it.

= 1.2.1 =
Adding license tag to header and replacing global path variables with path variables inside the PluginOrganizer class.

= 1.2 =
Removed a conditional and some whitespace from the main plugin file becasue it may have been causing issues with activation.  
Adding menu and header icons to pretty up the plugin.

= 1.1 =
Added option to settings page so the selective plugin loading can be enabled or disabled for the admin pages.

= 1.0 =
Added ability to disable plugins in the admin using the Arbitrary URL admin page.
Fixed some flow issues and html problems on the PO admin pages.
Properly escaped all queries

= 0.9 =
Added admin area for entering arbitrary URL's to allow plugin management for url's that don't have a post tied to them.
Added some form validation for the admin screens.

= 0.8.3 =
Fixing a bug with globaly disabled plugins not being enabled on individual posts
Fixing bug with version number not updating when plugin is updated.

= 0.8.2 =
Fixing wrong version number on plugins page.
Adding FAQ's

= 0.8.1 =
Added missing tpl/globalPlugins.php file.

= 0.8 =
Adding custom post type support.

= 0.7.3 =
Fixed activation errors when mu-plugins folder is not writable.

= 0.7.2 =
Fixed bug that reordered plugins back to default when plugins were activated or deactivated.
Fixed jQuery loading indicator on plugin admin.
Fixed Bulk Actions on plugin admin

= 0.7.1 =
Removed display of plugin load order functions on plugin admin if the view is paged.  To view load order functions on plugin admin you must display all active plugins on one page.

= 0.7 =
Wordpress 3.1 fixes for jQuery 1.4.4

= 0.6 =
Added functionality to disable plugins globally and selectively enable them for posts and pages.
Added functionality to create the mu-plugins folder and move the MU plugin class when activated.
New databse layout.  Will be created when plugin is activated.

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

= 3.1.1 =
Adding cache variable to store the plugin list so it is only created once per page load instead of every time the active_plugins option is retrieved.

= 3.1 =
Adding the ability to target specific browsers.  Useful for loading specific plugins for mobile browsers.

= 3.0.10 =
Fixing warning from searching empty array in group members on plugins page.

= 3.0.9 =
Fixing typo in version number check on initialization.
Got rid of code to fix old custom permalink field

= 3.0.8 =
Removed a call to wp_count_posts in the activation function.  It may have been causing issues on activation.

= 3.0.7 =
Removed a call to get_permalink in the activation function.  It may have been causing issues on multisite activation.

= 3.0.6 =
Fixed an issue with activation where too many posts on the site caused the php to run out of memory and the activation to fail.

= 3.0.5 =
Fixed issue on multisite where the $GLOBALS['wp_taxonomies'] array hadn't been created yet so a php warning was thrown.

= 3.0.4 =
Fixed a typo that caused imported filters to not have a permalink.
Added code to repair anyones database that has already been upgraded with bad permalinks.

= 3.0.3 =
Fixed an issue with the advanced meta query from get_posts adding % characters and escaping my % character.

= 3.0.2 =
Fixed an issue when using ignore protocol the first query wouldn't match.
Fixed an issue where a post is found on the first query but no plugins have been disabled so the enabled plugins are overlooked.

= 3.0.1 =
Fixed a problem with fuzzy url matching.  " characters were being added to the url so it would never match.
Commented out the code that deleted the tables and added an option to the databse to prevent multiple imports.  Will add the delete code in a later version to clean up the tables after everyone is stable and has imported their settings.
Added code to ensure that the old MU plugin is deleted before attempting to copy it from the lib directory.

= 3.0 =
Complete redesign of the plugin.
Removed all custom db tables and moved the data to the post_meta table.
Added custom post type plugin_filter to replace the URl admin.
Added custom post type plugin_group to replace the plugin groups table.
The plugins displayed on post/pages/custom post types/global plugins page are now sorted and colored similar to the main plugin page.
There is no longer an enabled and disabled plugin box.  Enabled and disabled plugins are now all managed together to avoid confusion.
Fixed a bug where the MU plugin chopped the url before checking it so it looped 15 times on the homepage before stopping the search for a fuzzy url.
Fixed a bug where globally disabled plugins were listed as inactive when the list of active plugins was accessed.

= 2.6.3 =
Fixing bug that allows plugins to be disabled on the update pages.

= 2.6.2 =
Fixing PHP notices

= 2.6.1 =
Fixing bad characters added during commit

= 2.6 =
Fixed error on windows when inserting into po_post_plugins without specifying all fields.
Added ability to effect children of posts, pages, custom post types.
Redesign of the post edit screen meta box.

= 2.5.9 =
Missed a file when committing 2.5.8.

= 2.5.8 =
Fixing grouping issues.  
Plugin names were not being escaped when building the group list for display so they werent showing up.
On the recently active screen the plugin organizer actions were duplicated and so when adding to group the group name was duplicated.

= 2.5.7 =
Fixing more bad characters being added by svn or wordpress.org.

= 2.5.6 =
Replacing Icons because they were released under creative commons and not gpl.

= 2.5.5 =
Fixing missing db table error message when the table exists on windows server.

= 2.5.4 =
Fixing bad characters being added by svn or wordpress.org.

= 2.5.3 =
Fixed a jquery issue with wp 3.5

= 2.5.2 =
Added warnings on settings page if the database tables are missing.
Removed default value for longtext database fields.  Caused issues on windows.

= 2.5.1 =
Fixed a problem with URL admin not saving edited URLs
Changed the first menu item to settings under Plugin Organizer

= 2.5 =
Removed PHP notice errors.
The plugin organizer plugin can no longer be disabled on the admin.
Added better support for multi-site.
The plugin will now correct plugins that are network activated and activated on the local site so they are only network activated.  This fixes an error where more plugins were seen as active than were displayed on the plugins page.
The plugin organizer features will not load on the network admin.
Network activated plugins can now be disabled.

= 2.4 =
Adding ability to ignore arguments to a URL.  You can now enter URLs into the URL admin with arguments so that http://yoururl.com/page/?foo=2&bar=3 will have different plugins loaded than http://yoururl.com/page/?foo=1&bar=4 and http://yoururl.com/page/.
Fixed URL admin so that it checks to make sure the URL was entered into the database before saying it was successful.

= 2.3.3 =
Undoing a change that was done in 2.3.1 to the request uri that removed arguments from the uri.  It is causing some issues for some users.  Will redesign and create a later release to optionally remove the arguments. 

= 2.3.2 =
When the user hadnt set the number of plugins displayed per page it was being defaulted to 20.  Changed it to default to 999.
Set $endChar to an empty string in PluginOrganizerMU.class.php to prevent debug notices.

= 2.3.1 =
Fixed a javascript error on the URL admin page.
Fixed logic for Global plugins where all plugins were disabled none where getting disabled.
Fixed use of REQUEST_URI.  Now it Splits the REQUEST_URI to trim off URL arguments.
Added ability to reset plugin order back to wordpress default.
Renamed some javascript functions and consolidated some of them.

= 2.3 =
Removed the old admin pages.  The plugins can now be managed directly on the plugins page.
Redesigned the settings page to use ajax queries instead of reloading the page to save settings.
Redesigned the URL admin to use ajax to save and edit URL's instead of reloading the page.
Moved most of the javascript out of the main class and into template files.
Added a setting to preserve the plugin data when it is deactivated.  The plugin data including database tables and MU plugin file can now be removed on deactivation.

= 2.2.1 =
Added ability to ignore the protocol when matching the requested URL by checking a checkbox on the settings page.

= 2.2 =
Added Fuzzy URL matching to the arbitrary URL admin.  URLs can now effect their children.
Added nonce checking to URL admin.
Restructured forms on the main settings page.

= 2.1.3 =
Added checks to ensure plugin load order cant be changed when all plugins are not viewable on the page.

= 2.1.2 =
Fixed group view on plugin organizer page when the plugins per page has been set too low or extremely high.
Fixed setting of the show old admin page when either save settings button is clicked.

= 2.1.1 =
Adding option to show the old admin pages.

= 2.1 =
Added better group management to the plugin admin page.
Removed group management pages from the menu.

= 2.0 =
Added drag and drop functionality to the plugin admin page.
Added group links to the top of the plugin admin page that replace the group dropdown.
Added better checking to make sure the plugin load order can only be changed when all plugins are being displayed.

= 1.2.3 =
Fixed URL admin page.  Enabled plugins list wasnt saving on creation.

= 1.2.2 =
Fixed typo in recreate permalinks function.
Centralized the nonce generation so the PluginOrganizer class now holds it.

= 1.2.1 =
Adding license tag to header and replacing global path variables with path variables inside the PluginOrganizer class.

= 1.2 =
Removed a conditional and some whitespace from the main plugin file becasue it may have been causing issues with activation.  

= 1.1 =
Added option to settings page so the selective plugin loading can be enabled or disabled for the admin pages seperately to prevent problems.

= 1.0 =
Added ability to disable plugins in the admin using the Arbitrary URL admin page.
Fixed some flow issues and html problems on the PO admin pages.
Properly escaped all queries

= 0.9 =
Added plugin management for url's that don't have a post tied to them.

= 0.8.1 =
Added missing tpl/globalPlugins.php file.

= 0.8 =
Adding custom post type support.

= 0.7.3 =
Fixed activation errors when mu-plugins folder is not writable.

= 0.7.2 =
Bug fixes for plugin admin page.  Plugin order could be lost on activation or deactivation.  Top bulk actions fixed.

= 0.7.1 =
Fixing plugin admin page.  Could cause plugins to be disabled in old version.

= 0.7 =
Fixes for jQuery 1.4.4

= 0.6 =
Added functionality to disable plugins globally and selectively enable them for posts and pages.
Added functionality to create the mu-plugins folder and move the MU plugin class when activated.
New databse layout.  Will be created when plugin is activated.
