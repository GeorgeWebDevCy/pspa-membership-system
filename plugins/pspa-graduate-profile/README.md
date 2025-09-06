# PSPA Graduate Profile Endpoint

Adds a WooCommerce My Account endpoint labeled "Προφίλ Απόφοιτου".

## Install

- Copy the folder `pspa-graduate-profile` to your WordPress `wp-content/plugins/` directory.
- In WP Admin → Plugins, activate "PSPA Graduate Profile Endpoint".
- The My Account menu will include "Προφίλ Απόφοιτου" at `/my-account/profil-apofitou/`.

If the endpoint 404s, visit Settings → Permalinks and click Save to flush rules.

## Updates (GitHub)

This plugin integrates with YahnisElsts/plugin-update-checker. To enable updates from GitHub:

1) Vendor the library into the plugin:

- Option A (recommended): place the library at `plugins/pspa-graduate-profile/vendor/plugin-update-checker/plugin-update-checker.php`
- Option B: place it at `plugins/pspa-graduate-profile/plugin-update-checker/plugin-update-checker.php`

2) Configure the repository URL (and optionally branch/auth):

- In `wp-config.php` or a must-use plugin, define:
  - `define('PSPA_GP_UPDATE_GITHUB_REPO', 'https://github.com/OWNER/REPO/');`
  - `define('PSPA_GP_UPDATE_BRANCH', 'main'); // optional`
  - `define('PSPA_GP_UPDATE_AUTH_TOKEN', 'ghp_xxx'); // optional for private repos`

Or hook via filters:

- `add_filter('pspa_gp_update_repo_url', fn() => 'https://github.com/OWNER/REPO/');`
- `add_filter('pspa_gp_update_branch', fn() => 'main');`
- `add_filter('pspa_gp_update_auth', fn() => 'ghp_xxx');`

3) Tag releases in GitHub with the plugin version (e.g., `1.0.1`) and upload a release asset if desired. The updater reads the plugin header `Version`.

Notes:

- If the library is missing, an admin notice appears prompting you to add it.
- For private repos, provide a GitHub token with `repo` scope via `PSPA_GP_UPDATE_AUTH_TOKEN`.
- Releases vs Branch: By default, the updater checks the configured branch; enabling release assets is supported.

## Customize

- Edit the content renderer in `pspa-graduate-profile.php` at the hook:
  - `woocommerce_account_profil-apofitou_endpoint`
  - Replace the placeholder HTML with your profile UI.
