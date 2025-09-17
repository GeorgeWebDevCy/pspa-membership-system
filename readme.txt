=== PSPA Membership System ===
Contributors: orionaselite
Tags: membership, woocommerce, acf, profile
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.0.89
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
This plugin powers the PSPA membership system and integrates with WooCommerce and Advanced Custom Fields (ACF) Pro. It provides a graduate dashboard, administrator search tools and a login-by-details shortcode.

Note: Versions prior to 0.0.25 processed the login form inside the shortcode after page output had begun, so WordPress could not send the authentication cookie and the user remained logged out. The handler now runs before headers are sent, allowing successful submissions to log users in and redirect correctly.

== Custom User Roles ==
The plugin registers two custom user roles:

* Professional Catalogue (`professionalcatalogue`)
* System Admin (`system-admin` or `sysadmin`)

== Installation ==
1. Upload `pspa-membership-system` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure that ACF Pro, WooCommerce, and Advanced Access Manager are installed and activated.

== Frequently Asked Questions ==

= What dependencies are required? =
The plugin requires Advanced Custom Fields Pro, WooCommerce, and Advanced Access Manager.

== Changelog ==

= 0.0.89 =
* Preserve the `login-details` query flag to stop canonical redirects from hiding the login failure popup.

= 0.0.88 =
* Redirect already verified login-by-details submissions to the graduate profile dashboard.
* Show a Greek popup when login-by-details submissions do not match any user.

= 0.0.87 =
* Remove the email and password fields from graduate profile editing forms so only ACF fields are shown.

= 0.0.86 =
* Display the WooCommerce "Account details" form before the graduate profile and block profile editing until an email and password are saved.
* Record the login verification date when the password changes via the WooCommerce account form.

= 0.0.85 =
* Prevent warnings when visibility filters receive a hidden field.

= 0.0.84 =
* Ensure password updates work for login-by-details users, logging outcomes and recording verification date when an email exists.

= 0.0.83 =
* Revert to code base 0.0.77.

= 0.0.77 =
* Remove verbose function argument logging.

= 0.0.76 =
* Fix log clearing by using distinct nonces.

= 0.0.75 =
* Remove verbose logging from ACF field filters.

= 0.0.74 =
* Reduce logging noise when updating graduate profiles and record entered data, password strength and WordPress errors.
* Remove duplicate password toggle from profile forms.

= 0.0.73 =
* Add password strength indicator to profile forms.
* Revert to option-based logging system.

= 0.0.72 =
* Log messages only when debugging is enabled.
* Ensure password changes persist on the `graduate-profile` endpoint.

= 0.0.71 =
* Add verbose logging to all functions for debugging.
* Bump version to 0.0.71.

= 0.0.70 =
* Fix password updates by using `wp_update_user` and logging failures.
* Bump version to 0.0.70.

= 0.0.69 =
* Update login verification date when email or password changes.
* Bump version to 0.0.69.

= 0.0.68 =
* Add show/hide toggle to password fields while preserving dashboard colors.
* Bump version to 0.0.68.

= 0.0.67 =
* Ensure password changes save reliably by using `wp_set_password` and verifying the update.
* Bump version to 0.0.67.

= 0.0.66 =
* Fix fatal error caused by unmatched braces in profile password update logic.
* Bump version to 0.0.66.

= 0.0.65 =
* Use `wp_update_user` for password changes and log failures.
* Reduce log noise by checking request method before login-by-details.
* Bump version to 0.0.65.

= 0.0.64 =
* Add verbose logging for login-by-details and profile updates.
* Bump version to 0.0.64.

= 0.0.63 =
* Fix password updates when saving the Graduate Profile form.
* Bump version to 0.0.63.

= 0.0.62 =
* Log admin profile update attempts and handle failures when updating passwords.
* Bump version to 0.0.62.

= 0.0.61 =
* Record verification date once a profile update leaves the user with both email and password.
* Bump version to 0.0.61.

= 0.0.60 =
* Add detailed logging for profile updates to troubleshoot password update failures.
* Ensure verification date records only after email and password saved.
* Bump version to 0.0.60.

= 0.0.59 =
* Require both email and password before recording verification date.
* Bump version to 0.0.59.

= 0.0.58 =
* Delay setting login-by-details verification date until the user saves email and password.
* Bump version to 0.0.58.

= 0.0.57 =
* Hide admin-only ACF fields from catalogue editors and graduate profile forms.
* Bump version to 0.0.57.

= 0.0.56 =
* Apply dashboard styling to Login By Details form.
* Bump version to 0.0.56.

= 0.0.55 =
* Add tab for administrators listing users who purchased this year.
* Bump version to 0.0.55.

= 0.0.54 =
* Redirect Login By Details to the user's Graduate Profile edit page.
* Bump version to 0.0.54.

= 0.0.53 =
* Use WooCommerce form-row and button styles for Login by Details.
* Bump version to 0.0.53.

= 0.0.52 =
* Show alert with email when login-by-details user already verified.
* Bump version to 0.0.52.

= 0.0.51 =
* Fix Login By Details redirect to the Graduate Profile dashboard.
* Bump version to 0.0.51.
= 0.0.50 =
* Add admin button to reset plugin settings.
* Bump version to 0.0.50.

= 0.0.49 =
* Add WP-CLI command to reset plugin settings.
* Bump version to 0.0.49.

= 0.0.48 =
* Add admin interface for creating users.
* Bump version to 0.0.48.

= 0.0.47 =
* Record login-by-details verification date and block repeated use.
* Bump version to 0.0.47.

= 0.0.46 =
* Add auto-incrementing Initial DB ID field and lock it from edits.
* Bump version to 0.0.46.

= 0.0.45 =
* Normalize full name from ACF first name and surname fields for flexible, accent-insensitive login-by-details matching.
* Bump version to 0.0.45.

= 0.0.44 =
* Match login-by-details submissions case-insensitively and fix login process.
* Add extra logging around login-by-details.
* Bump version to 0.0.44.

= 0.0.43 =
* Allow full name searches to match first or last names individually.
* Bump version to 0.0.43.

= 0.0.42 =
* Match single-name searches exactly for System Admins while retaining Unicode support.
* Bump version to 0.0.42.
= 0.0.41 =
* Match searches for full names against the ACF first name and surname fields with multibyte (e.g. Greek) character support.
* Bump version to 0.0.41.

= 0.0.40 =
* Ensure full name filter applies to graduate directory searches.
* Bump version to 0.0.40.

= 0.0.39 =
* Search full name using ACF first and surname fields.
* Bump version to 0.0.39.

= 0.0.38 =
* Make graduation year filter a text field.
* Bump version to 0.0.38.

= 0.0.37 =
* Add full name and graduation year filters to the Graduate Directory.
* Give administrators the same directory interface for searching.
* Bump version to 0.0.37.

= 0.0.36 =
* Use ACF first and last name fields for displaying user names.
* Synchronize WordPress name fields with ACF values.
* Bump version to 0.0.36.

= 0.0.35 =
* Move graduate profile endpoint to second position in account navigation.
* Bump version to 0.0.35.

= 0.0.34 =
* Standardize "E-mail" capitalization.
* Bump version to 0.0.34.

= 0.0.33 =
* Fix update checker to use the new GitHub repository.
* Bump version to 0.0.33.

= 0.0.32 =
* Render admin search results using the graduate card layout and expose an edit link on cards for administrators.
* Bump version to 0.0.32.

= 0.0.31 =
* Restrict catalogue graduates from accessing the admin search interface.
* Bump version to 0.0.31.

= 0.0.30 =
* Style WooCommerce account navigation to match the dashboard.
* Bump version to 0.0.30.

= 0.0.29 =
* Ensure admin profile edit links always point to the My Account graduate profile endpoint.
* Bump version to 0.0.29.

= 0.0.28 =
* Recognize the `sysadmin` role and grant it the graduate editing dashboard.
* Show all ACF fields to system administrators and catalogue editors, disabling required validation so empty fields can be filled later.
* Bump version to 0.0.28.

= 0.0.27 =
* Allow System Admins and Professional Catalogue users to edit all graduate profile fields using the unified interface.
* Let graduates update their password from the front-end profile form.

= 0.0.26 =
* Apply dashboard styling to the System Admin user search and edit forms for a consistent front-end experience.

= 0.0.25 =
* Handle login-by-details submissions on `template_redirect` so authentication cookies and redirects work reliably.
* Log the user's authentication status to aid debugging.

= 0.0.24 =
* Ensure login-by-details sets a secure auth cookie so sessions persist on HTTPS sites.
= 0.0.23 =
* Add logging and admin page for debugging login-by-details.
= 0.0.22 =
* Improve graduate public profile styling and remove visibility settings section.
= 0.0.21 =
* Fix fatal error in graduate public profile template.
= 0.0.20 =
* Display public graduate profiles in a LinkedIn-style layout and show visible fields even when empty.

= 0.0.19 =
* Render public graduate profiles using the dashboard layout in read-only mode.

= 0.0.18 =
* Ensure public graduate profile displays all ACF fields reliably.

= 0.0.17 =
* Honor profile visibility mode and field visibility settings on the public profile template.

= 0.0.16 =
* Display all graduate profile ACF fields on the public profile template without allowing edits.

= 0.0.15 =
* Display all graduate profile ACF fields on public profile template.

= 0.0.14 =
* Add `/graduate/` rewrite rule and public profile template.
= 0.0.13 =
* Link graduate cards to dedicated public profiles instead of author pages.
* Add AJAX pagination to the graduate directory for faster initial load.

= 0.0.12 =
* Fix duplicated "Α (ΠΟΒΙΩΣΑΣ) – Απεβίωσε" field on public profile.
= 0.0.11 =
* Διορθώθηκε η σύνδεση μέσω `[pspa_login_by_details]` και εμφανίζεται μήνυμα όταν είστε ήδη επαληθευμένοι.
= 0.0.10 =
* Μεταφράστηκαν όλα τα κουμπιά και οι προεπιλεγμένες επιλογές στα ελληνικά.
* Εφαρμόστηκε ενιαία εμφάνιση στα shortcodes και οι κάρτες αποφοίτων είναι πλέον κλικαμπλ.
* Προστέθηκε κουμπί "Δείτε Περισσότερο" στις κάρτες αποφοίτων.
= 0.0.9 =
* Fix fatal error during activation caused by duplicate shortcode definitions.
= 0.0.8 =

* Add graduate directory with AJAX filters restricted to logged-in users.
* Add `[pspa_graduate_directory]` shortcode to display graduate cards and view non-editable profiles.

= 0.0.7 =
* Fix `[pspa_login_by_details]` shortcode not rendering and ensure login actions run.

= 0.0.6 =
* Translate "Save changes" buttons to Greek.

= 0.0.5 =
* Hide the "Ρυθμίσεις ορατότητας" tab from the graduate profile form.

= 0.0.4 =
* Render all graduate profile fields via ACF on the front-end form.
* Added name synchronization with WordPress user fields after saving.

= 0.0.3 =
* Added administrator search and editing interface on the graduate profile endpoint.
* Implemented `[pspa_login_by_details]` shortcode for logging in by first name, last name and graduation year.
* Added role-based redirection to the graduate profile and blocked backend access.
= 0.0.2 =
* Added "Προφίλ Απόφοιτου" WooCommerce endpoint for graduates to edit their personal details.
= 0.0.1 =
* Initial release.

== Upgrade Notice ==
= 0.0.12 =
Επιλύει διπλή εμφάνιση του πεδίου "Α (ΠΟΒΙΩΣΑΣ) – Απεβίωσε" στο δημόσιο προφίλ.
= 0.0.11 =
Διορθώνει τη σύνδεση μέσω στοιχείων και εμφανίζει μήνυμα επαλήθευσης για συνδεδεμένους χρήστες.
= 0.0.10 =
Βελτιωμένη εμπειρία χρήστη με ελληνικά κουμπιά, ενοποιημένο στυλ και κλικαμπλ κάρτες αποφοίτων.
= 0.0.9 =
Resolves a fatal error preventing plugin activation.
= 0.0.8 =
Introduces a LinkedIn-style graduate directory with dynamic filters.

Introduces a graduate directory shortcode and public profile view.

= 0.0.7 =
Resolves missing login form and triggers login hooks when authenticating by details.

= 0.0.6 =
Translates "Save changes" buttons to Greek.

= 0.0.5 =
Removes the visibility settings tab from the profile dashboard.

= 0.0.4 =
Displays full ACF profile fields and syncs user names.

= 0.0.3 =
Introduces administrator editing, login-by-details and role-based redirects.
= 0.0.2 =
Adds a WooCommerce endpoint for graduates to update their profile.
= 0.0.1 =
Initial release.
