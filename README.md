# PSPA Membership System

This plugin powers the PSPA membership system and integrates with WooCommerce and Advanced Custom Fields (ACF) Pro.

## Graduate Profile Dashboard

The `Graduate Profile Dashboard` provides a single-page front-end form for graduates to manage their profile data. It is available under the WooCommerce "My Account" area via the `graduate-profile` endpoint.

Key features:

- **Per-field visibility toggles** – graduates can decide which profile fields are publicly visible.
- **ACF-based form** – all profile fields are rendered using ACF Pro, with tabs hidden to keep the form on one page.
- **Profile image uploads** – graduates are granted the `upload_files` capability to change their profile photo.
- **Name synchronization** – the user's WordPress first name, last name and display name are updated after saving the form.
- **WooCommerce integration** – registers a custom endpoint and navigation item under "My Account" so graduates can access the dashboard.
- **Global visibility mode lock** – the `gn_visibility_mode` field is hidden on the front end and cannot be changed by graduates.

Dependencies:

- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/)
- [WooCommerce](https://woocommerce.com/)

