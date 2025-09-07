=== PSPA Membership System ===
Contributors: orionaselite
Tags: membership, woocommerce, acf, profile
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.4
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
= 1.0.4 =
* Start admin autocomplete after typing 3 characters.
* Search includes graduate profile and ACF fields.
* Bump version to 1.0.4.
= 1.0.3 =
* Expand admin search autocomplete to match ACF profile fields.
* Bump version to 1.0.3.
= 1.0.2 =
* Load first 12 users with pagination on the system admin dashboard.
* Add autocomplete to the graduate search box.
* Bump version to 1.0.2.
= 1.0.1 =
* Show system admin search results with graduate card layout.
* Add edit button to graduate cards for administrators and system admins.
* Bump version to 1.0.1.
= 1.0.0 =
* Cache filter options to reduce database queries.
* Align login-by-details form styling with other dashboard forms.
* Bump version to 1.0.0.

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
