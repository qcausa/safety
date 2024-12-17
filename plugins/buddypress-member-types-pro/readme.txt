=== BuddyPress Member Types Pro ===
Contributors: buddydev,sbrajesh,raviousprime
Tags: buddypress, member-type, bp-member-type
Requires at least: 4.5
Tested up to: 6.4.3
Stable tag: 1.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

BuddyPress Member Types Pro is a easy to use complete member type solution for BuddyPress.

== Description ==

BuddyPress Member Types Pro is complete member type management plugin for

Features:

*   Create/Edit/Delete Member Types  from WordPress dashboard 
*   Bulk assign member type to users from the users list screen
*   A member type can be marked active/deactive from the edit member type page(Only till BuddyPress 2.6.2, BuddyPress 2.7.0 does it out of the box )
*   Assign roles to member types
*   Make users join group based on Member types
*   Assign WooCommerce Memberships based on member type
*   Assign Paid memberships Pro levels based on member type
*   Conditional login redirect
*   Conditional account activation redirect
*   Conditional default profile tab

== Installation ==

1. Download `buddypress-member-types-pro-x.y.z.zip` , x.y.z are version numbers eg. 1.0.0
1. Extract the zip file
1. Upload `buddypress-member-types-pro` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit Dashboard-> Users-> Member Types to add/edit/delete member types

== Frequently Asked Questions ==

= Does it detects the member types added via code =
No, It will only list the member types that you add from dashboard. You don't need to use any code to register a new member type.

= Will it Work With WordPress Mulitiste & BuddyPress? =

Yes.

== Screenshots ==

1. Add/Edit Member type screen screenshot-1.png 
2. Member type list screen screenshot-2.png
2. Bulk Change Member type screen screenshot-3.png
2. Extended Profile Member Type list screenshot-4.png


== Changelog ==
1.5.2
* Fixed header already sent notice when a plugin is using redirection at lower priority than our nav update code.

1.5.1
* Ensured that group ids are always passed as numeric value.

1.5.0
* Fixes fatal error when xprofile was disabled.

1.4.9
* Added filter to disable bp role override.

1.4.8
* Update Indeed Ultimate Membership compatibility.

1.4.7
* make roles to member type mapping work when using ajax registration.

1.4.5
* Added a filter to turn on group join request for private groups instead of auto joining.

1.4.4
* Fix Learndash group joining when using Multi member type on registration.

1.4.3
* Added support of expiration date for PMPro levels.

1.4.2
* Added integration with IF menu plugin for menu visibility conditions.

1.4.1
* Added support for setting up redirect for first login of the user.

1.4.0
* Add login redirection compatibility with Boombox theme.

1.3.9
* Fix support to assign users to Learndash groups.

1.3.8
* Added filter 'bpmtp_profile_search_form_allowed_member_types' to limit searchable member types when using BuddyPress Profile search plugin.
* Add french translation by Johan.

1.3.7
* Fix alphabetical sort.

1.3.6
* Sort member types alphabetically.
* Add rehub theme compatibility for login/registration redirect.
* Better memberpress support.

1.3.5
* Fix multivalued field being not set if the field values were restricted to some member types.
* Fix multi valued field data being reset on member type removal. Now, it is updated to reflect the state.

1.3.4
* Added support for memberpress. Now assign member type/remove member type based on user subscription.
* Added support for cover image based on member type.

1.3.3
* Better support for conditional registration. Works with required fields too.

1.3.1
* Add support for member type based default profile cover images.

1.3.1
* Do not sync member type updates and field updates when they are not governed by the field settings.

1.3.0
* Add support for conditional registration form. https://buddydev.com/docs/buddypress-member-types-pro/conditional-buddypress-registration-fields-based-on-buddypress-member-types/

1.2.8
* Add support for changing member type with 'add_user_role' action too. Earlier, we only supported set_user_role action.

1.2.7
* Add support for hidden groups in admin.

1.2.6
* Fix Registration role assignment.

1.2.5
* Better integration with Ultimate Memberships Pro.

1.2.4
* Add Ultimate Membership Pro integration.
* Add support for Ghostpool's theme's login redirect.

1.2.2
* Fix directory listing when some member types are excluded
* Update Single Member type field to make it work with Conditional profile field.

1.2.2
* Add support for BP Profile Search 4.8+

1.2.1
* Add compatibility with Subway plugin login redirect

1.2.0
* Add the redirection settings.

1.1.2
* Add support for the default value.
* Better member type field.

1.0.1
* Allow joining groups based on member type
* Allow assigning Paid Membership Pro Levels with member type.

= 1.0.0 =
* Initial release
