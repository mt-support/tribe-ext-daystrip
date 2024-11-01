=== The Events Calendar Extension: Day Strip ===
Contributors: theeventscalendar
Donate link: https://evnt.is/29
Tags: events, calendar
Requires at least: 6.3.0
Tested up to: 6.6.2
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPL version 3 or any later version
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adds a day-by-day navigation strip at the top of the Day View.

== Installation ==

Install and activate like any other plugin!

* You can upload the plugin zip file via the *Plugins ‣ Add New* screen
* You can unzip the plugin and then upload to your plugin directory (typically _wp-content/plugins_) via FTP
* Once it has been installed or uploaded, simply visit the main plugin list and activate it

== Frequently Asked Questions ==

= Where can I find more extensions? =

Please visit our [extension library](https://theeventscalendar.com/extensions/) to learn about our complete range of extensions for The Events Calendar and its associated plugins.

= What if I experience problems? =

We're always interested in your feedback and our [Help Desk](https://support.theeventscalendar.com/) are the best place to flag any issues. Do note, however, that the degree of support we provide for extensions like this one tends to be very limited.

== Upgrade Notice ==
= [2.1.0] =

This extension is no longer compatible with versions of The Events Calendar prior to 6.7.0.
Please see the changelog for the complete list of changes in this release.

== Changelog ==

= [2.1.0] 2024-11-01 =

* Version - This and future versions of the extension require TEC 6.7.0. It is no longer compatible with the legacy admin design.
* Fix - Make sure the settings are visible and work with the new TEC admin interface. [TECEXT-337]
* Fix - Changed a `sprintf()` to `print()` for outputting styling code directly into the markup, ensuring proper display of styles.
* Fix - Added a check so that some styling is only loaded on the pages where needed.
* Tweak - Updated plugin version requirement.

= [2.0.0] 2024-01-25 =

* Version - This and future versions of the extension require TEC 6.0. It is no longer compatible with the legacy views.
* Fix - Correct an issue where a divisor could be a non-integer, resulting in an error. [TEC-4975]
* Fix - Make sure that all options have a default value, so the extension can be used right after activation.
* Fix - Now the day and month names show up on the day-to-day navigation bar from the start.
* Fix - The event marker now properly shows up on days with events.
* Tweak - There is now no warning message when saving the settings with an empty Start Date field.
* Deprecated - Deprecated `enquque_daystrip_styles()` for `enqueue_daystrip_styles()` to correct a spelling error.

= [1.0.1] 2021-04-14 =

* Fix - Adjusted the template directory to the correct one.
* Tweak - Replaced Modern Tribe references with The Events Calendar

= [1.0.0] 2020-10-16 =

* Initial release
