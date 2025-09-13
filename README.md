# PSPA Membership System

This plugin powers the PSPA membership system and integrates with WooCommerce and Advanced Custom Fields (ACF) Pro.

## Custom User Roles

The plugin registers two custom user roles:

- **Professional Catalogue** (`professionalcatalogue`)
- **System Admin** (`system-admin` or `sysadmin`)

## Graduate Profile Dashboard

The `Graduate Profile Dashboard` provides a single-page front-end form for graduates to manage their profile data. It is available under the WooCommerce "My Account" area via the `graduate-profile` endpoint, labeled "Προφίλ Απόφοιτου".

Key features:

 - **Per-field visibility toggles** – graduates can decide which profile fields are publicly visible.
 - **ACF-based form** – all profile fields are rendered using ACF Pro, with tabs hidden to keep the form on one page.
 - **Profile image uploads** – graduates are granted the `upload_files` capability to change their profile photo.
 - **Password updates** – graduates can change their account password.
 - **Name synchronization** – the user's WordPress first name, last name and display name are updated after saving the form.
- **WooCommerce integration** – registers a custom endpoint and navigation item under "My Account" so graduates can access the dashboard.
- **Global visibility mode lock** – the `gn_visibility_mode` field is hidden on the front end and cannot be changed by graduates.

Dependencies:

- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/)
- [WooCommerce](https://woocommerce.com/)
- [Advanced Access Manager](https://wordpress.org/plugins/advanced-access-manager/)

## Administrator Tools

System Admins and site administrators can search for graduates and edit any user profile directly from the `Προφίλ Απόφοιτου` endpoint. The search covers all graduate fields and opens an editable form with ACF data, E-mail and password controls. Search results are displayed using the same card layout as the Graduate Directory, and cards include an edit button for administrators. The interface uses the same `pspa-dashboard` styles as the graduate profile for a consistent appearance.

## Login by Details

The `[pspa_login_by_details]` shortcode renders a form asking for first name, last name and graduation year. When the details match a graduate record the user is logged in and redirected to the dashboard. The first successful login records the date in a read-only `gn_login_verified_date` field and subsequent attempts are blocked.

> **Note:** In versions prior to 0.0.25 the login logic ran inside the shortcode after page output had begun, so WordPress could not send the authentication cookie and the user remained logged out. The processing now runs on `template_redirect` before headers are sent, and extra logging records the user's status. Version 0.0.24 also ensured the cookie respects the current SSL state.

Starting with version 0.0.45 names are matched case- and accent-insensitively by combining the ACF first name and surname fields into a full name string. Earlier versions compared each field separately and only ignored casing, which caused valid submissions with tonal differences (e.g. Ιωάννης vs ΙΩΑΝΝΗΣ) to fail.

## Graduate Directory

The `[pspa_graduate_directory]` shortcode outputs a grid of graduates showing their profile photo, full name and graduation year. Clicking "Δείτε Περισσότερο" opens the graduate's public, non-editable profile. When logged in as an administrator or System Admin, cards also show an "Επεξεργασία" link to jump directly to the editing interface. Public profiles are also accessible directly at `/graduate/<username>/`.

## Role-based Redirection

Graduates and System Admins are redirected to the `Προφίλ Απόφοιτου` dashboard after login and are prevented from accessing the WordPress admin area.

## ACF Field Reference

The plugin registers a **Graduate Profile** field group in Advanced Custom Fields. The field definitions are exported to `acf-export-2025-09-06-with-profile.json` and include the following fields:

### Βασικά στοιχεία
- **Επώνυμο** (`gn_surname`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Επώνυμο** (`gn_show_surname`, true_false)
- **Όνομα** (`gn_first_name`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Όνομα** (`gn_show_first_name`, true_false)
- **Πατρώνυμο** (`gn_father_name`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Πατρώνυμο** (`gn_show_father_name`, true_false)
- **Αποφοίτηση (Έτος)** (`gn_graduation_year`, number)
- **Εμφάνιση στο δημόσιο προφίλ: Αποφοίτηση (Έτος)** (`gn_show_graduation_year`, true_false)
- **Α (ΠΟΒΙΩΣΑΣ) – Απεβίωσε** (`gn_deceased`, true_false)
- **Εμφάνιση στο δημόσιο προφίλ: Α (ΠΟΒΙΩΣΑΣ) – Απεβίωσε** (`gn_show_deceased`, true_false)
- **ΣΧΟΛΙΑ** (`gn_notes`, textarea)
- **Εμφάνιση στο δημόσιο προφίλ: ΣΧΟΛΙΑ** (`gn_show_notes`, true_false)
- **Φωτογραφία προφίλ** (`gn_profile_picture`, image)
- **Εμφάνιση στο δημόσιο προφίλ: Φωτογραφία προφίλ** (`gn_show_profile_picture`, true_false)
- **Ημερομηνία επαλήθευσης** (`gn_login_verified_date`, text)

### Επικοινωνία
 - **E-mail** (`gn_email`, email)
 - **Εμφάνιση στο δημόσιο προφίλ: E-mail** (`gn_show_email`, true_false)
- **Κινητό** (`gn_mobile`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Κινητό** (`gn_show_mobile`, true_false)
- **Τηλ. Εργασίας** (`gn_work_phone`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Τηλ. Εργασίας** (`gn_show_work_phone`, true_false)
- **Εργασίας 2** (`gn_work_phone_2`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Εργασίας 2** (`gn_show_work_phone_2`, true_false)
- **Τηλ. Κατοικίας** (`gn_home_phone`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Τηλ. Κατοικίας** (`gn_show_home_phone`, true_false)
- **Κατοικίας 2** (`gn_home_phone_2`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Κατοικίας 2** (`gn_show_home_phone_2`, true_false)
- **FAX** (`gn_fax`, text)
- **Εμφάνιση στο δημόσιο προφίλ: FAX** (`gn_show_fax`, true_false)

### Διεύθυνση
- **Οδός/Αριθμός** (`gn_street_address`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Οδός/Αριθμός** (`gn_show_street_address`, true_false)
- **Τ.Κ.** (`gn_postal_code`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Τ.Κ.** (`gn_show_postal_code`, true_false)
- **Περιοχή** (`gn_area`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Περιοχή** (`gn_show_area`, true_false)
- **Πόλη** (`gn_city`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Πόλη** (`gn_show_city`, true_false)
- **Κράτος** (`gn_country`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Κράτος** (`gn_show_country`, true_false)

### Σπουδές
- **ΣΠΟΥΔΕΣ Πτυχίο** (`gn_degree`, text)
- **Εμφάνιση στο δημόσιο προφίλ: ΣΠΟΥΔΕΣ Πτυχίο** (`gn_show_degree`, true_false)
- **ΣΠΟΥΔΕΣ ΑΕΙ (Ίδρυμα)** (`gn_university`, text)
- **Εμφάνιση στο δημόσιο προφίλ: ΣΠΟΥΔΕΣ ΑΕΙ (Ίδρυμα)** (`gn_show_university`, true_false)
- **Ειδικότητα** (`gn_specialization`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Ειδικότητα** (`gn_show_specialization`, true_false)

### Εργασία
- **Επάγγελμα** (`gn_profession`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Επάγγελμα** (`gn_show_profession`, true_false)
- **Τίτλος** (`gn_job_title`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Τίτλος** (`gn_show_job_title`, true_false)
- **Θέση-Εταιρεία** (`gn_position_company`, text)
- **Εμφάνιση στο δημόσιο προφίλ: Θέση-Εταιρεία** (`gn_show_position_company`, true_false)
