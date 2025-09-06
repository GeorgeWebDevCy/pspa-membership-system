=== PSPA Membership System ===
Contributors: orionaselite
Tags: membership, woocommerce, acf, profile
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.0.12
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
This plugin powers the PSPA membership system and integrates with WooCommerce and Advanced Custom Fields (ACF) Pro. It provides a graduate dashboard, administrator search tools and a login-by-details shortcode.

== Custom User Roles ==
The plugin registers two custom user roles:

* Professional Catalogue (`professionalcatalogue`)
* System Admin (`system-admin`)

== Installation ==
1. Upload `pspa-membership-system` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure that ACF Pro, WooCommerce, and Advanced Access Manager are installed and activated.

== Frequently Asked Questions ==

= What dependencies are required? =
The plugin requires Advanced Custom Fields Pro, WooCommerce, and Advanced Access Manager.

== Changelog ==
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
