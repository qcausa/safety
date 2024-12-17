=== Wbcom Designs – Shortcodes & Elementor Widgets For BuddyPress ===
Contributors: vapvarun,wbcomdesigns
Donate link: http://wbcomdesigns.com/donate
Tags: Activity, BuddyPress, Members, Groups, BuddyPress Shortcodes, BuddyPress Widgets
Requires at least: 5.0.0
Tested up to: 6.8.0
Stable tag: 2.8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will generate a Shortcode for Listing Activity Streams, Members, and Groups on any post/page on the website.

== Description ==

This plugin will add an extended feature to BuddyPress. It will use Shortcode for Listing Activity Streams, Members directory, and Groups directory on any post/page within the website.

With our current update, we have added three widgets to display the activity stream, member directory, and group directory using Elementor.

[vimeo https://vimeo.com/554193567 ]

=== THEME - WORDPRESS THEME WITH OUTSTANDING BUDDYPRESS SUPPORT ===
* [FREE BuddyPress Theme: BuddyX](https://wordpress.org/themes/buddyx/) - Offers unique layouts with clean code and easy-to-customize options, giving you a whole new way to visualize BuddyPress.

== Installation ==
This section describes how to install the plugin and get it working.

1. Download the zip file and extract it.
2. Upload this plugin to the `/wp-content/plugins/` directory
3. Activate the plugin through the \'Plugins\' menu.
4. Alternatively, you can use the WordPress Plugin installer from Dashboard->Plugins->Add New to add this plugin
5. Enjoy
If you need more help, please contact us for [Custom Development](https://wbcomdesigns.com/hire-us/).

== Frequently Asked Questions ==

= Does This plugin require BuddyPress =

Yes, It needs you to have BuddyPress installed and activated.

= Where Do I Ask for Support? =

Please visit [wbcomdesigns] (http://wbcomdesigns.com/) for any query related to the plugin and Buddypress.

== Screenshots ==

1. It is displaying admin documentation page screenshot-1.png
2. It displays members' listing based on the given user ID screenshot-2.png
3. It displays activity listing based on the given user ID and allow_posting=true screenshot-3.png
4. It is displaying group listing based on the given user ID screenshot-4.png

== Changelog ==
= 2.8.0 =
* Fix: Resolved double popup issue with Youzify after scheduling an activity.
* Fix: Addressed issues with scheduling activities in Youzify.
* Fix: Corrected image-related problems in the Youzify plugin.
* Fix: Resolved UI issues with Youzify, including activity and shortcode errors.
* Fix: Ensured scheduled time displays properly with Youzify.
* Fix: Fixed fatal errors related to activity and group listings with Youzify.
* Fix: Resolved PHP deprecation warnings across components.
* Fix: Fixed notification icon and button issues for BuddyPress and BuddyBoss themes.
* Fix: Removed pagination issues in group and member listings with Youzify.
* Fix: Resolved errors when editing activity shortcodes with Elementor.
* Fix: Fixed compatibility issues with rtMedia and Youzify activity listing shortcodes.
* Fix: Corrected spelling and translation errors in multiple components.
* Enhancement: Added Member Elementor widget compatibility with Youzify.
* Enhancement: Integrated display count functionality for rtMedia + Youzify.
* Enhancement: Added filters to modify activity and member listing outputs.
* Enhancement: Improved compatibility for group actions (Manage Groups, Request Membership) in Youzify.
* Enhancement: Enhanced CSS and UI for group and member shortcodes with Elementor.
* Enhancement: Added filters for modifying notifications and group query arguments.
* Enhancement: Added action hooks before and after rendering activity and member shortcodes.
* Enhancement: Added admin notices for missing templates or disabled BuddyPress components.
* General: Improved UI consistency for BuddyBoss and Reign themes.
* General: Enhanced error handling and compatibility for BuddyPress shortcodes.
* General: Added detailed comments for better code readability.
* General: Ensured PHPCS compliance for all plugin files.

= 2.7.1 =
* Fix: (#179)Fixed fatal error call to undefined function bp_nouveau_get_container_classes()
* Fix: Fixed Plugin redirect issue when multi plugin activate the same time
* Fix: Compatibility check with WordPress 6.5.0

= 2.7.0 =
* Fix: Added activity-meta in activity shortcode
* Fix: Update shortcode code flow
* Fix: Updated top banner and Links 
* Fix: Fixed text domain
* Fix: Remove display_comments parameter from activity
* Fix: Managed groups, member shortcode listing
* Fix: Add loop-layout parameters for member and group
* Fix: Member list shortcode UI managed
* Fix: Managed UI activity shortcode

= 2.6.5 =
* Fix: (#175) Issue with WPBakery
* Fix: Deprecated issue
* Fix: PHPCS fixes

= 2.6.4 =
* Fix: Group Shortcode group content length issue
* Fix: (#172) Pagination issue in notification
* Fix: (#168) Activity UI issue
* Fix: (#172) Issue with php 8.2
* Fix: (#170) Issue with BuddyBoss
* Fix: (#169) Page title issue
* Fix: (#167) Pagination is missing in the per_page parameter
* Fix: (#161) Group Listing issue
* Fix: (#162) Page title issue with Buddyx
* Fix: (#106) Shortcode activity page has two stories options
* Update: Buddypress class for the default theme

= 2.6.3 =
* Fix: (#161) Pagination missing

= 2.6.2 =
* Fix: (#153) Managed shortcode notifications order actions
* Fix: (#151) Fixed error on adding shortcode
* Fix: (#148) Fixed php error while activate free and pro plugins togather
* Fix: (#153) fixed UI of [notifications-listing] shortcode border
* Fix: (#152) Fixed buddyboss notification does not work

= 2.6.1 =
* Fix: (#147) Fixed members and group shortcode notice issue
* Fix: (#150) Fixed pagination is not showing with perpage shortcode
* Fix: (#64)  Fixed knowledge base url issue

= 2.6.0 =
* Fix: Updated Admin wrapper

= 2.5.0 =
* Fix: (#145) Fixed php notices
* Fix: Fixed phpcs issue
* Fix: Update Url of Group Read More

= 2.4.0 =
* Fix: updated widget logic to be used together
* Fix: Group views as per BP l\oop grid column count setting
* Fix: #119 - Comment is navigating to page not count
* Fix:  enqueue admin js and css on plugin page

= 2.3.0 =
* New Feature: Added Widgets for Activity, Member Directory, and Group Directory with Elementor
* Update: Shortcode compatibility upto BuddyPress v7.2.0

= 2.1.0 =
* Fixed #72 - Not working with reign learndash addon

= 2.0.1 =
* BP 5.0.0 compatibility testing

= 2.0.0 =
* Code Rewrite

= 1.0.0 =
* first version.
