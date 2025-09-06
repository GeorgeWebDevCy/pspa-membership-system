<?php
/*
Plugin Name: PSPA Graduate Profile Endpoint
Description: Adds a "Προφίλ Απόφοιτου" endpoint to the WooCommerce My Account area.
Version: 1.0.0
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Author: PSPA
License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Slug (ASCII-safe) used in URLs: /my-account/profil-apofitou/
if ( ! defined( 'PSPA_GP_ENDPOINT' ) ) {
    define( 'PSPA_GP_ENDPOINT', 'profil-apofitou' );
}

// Optional: plugin version constant for convenience in tooling.
if ( ! defined( 'PSPA_GP_VERSION' ) ) {
    define( 'PSPA_GP_VERSION', '1.0.0' );
}

/**
 * Load textdomain
 */
function pspa_gp_load_textdomain() {
    load_plugin_textdomain( 'pspa-gp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'pspa_gp_load_textdomain' );

/**
 * Register endpoint
 */
function pspa_gp_add_endpoint() {
    // Add rewrite endpoint for pages (e.g. My Account)
    add_rewrite_endpoint( PSPA_GP_ENDPOINT, EP_ROOT | EP_PAGES );
}
add_action( 'init', 'pspa_gp_add_endpoint' );

/**
 * Add query var for endpoint
 */
function pspa_gp_query_vars( $vars ) {
    $vars[] = PSPA_GP_ENDPOINT;
    return $vars;
}
add_filter( 'query_vars', 'pspa_gp_query_vars' );

/**
 * Insert the endpoint link into the WooCommerce My Account menu
 */
function pspa_gp_account_menu_items( $items ) {
    $new = array();

    // Insert after Dashboard (or at end if not found)
    $inserted = false;
    foreach ( $items as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'dashboard' === $key ) {
            $new[ PSPA_GP_ENDPOINT ] = __( 'Προφίλ Απόφοιτου', 'pspa-gp' );
            $inserted = true;
        }
    }

    if ( ! $inserted ) {
        $new[ PSPA_GP_ENDPOINT ] = __( 'Προφίλ Απόφοιτου', 'pspa-gp' );
    }

    return $new;
}
add_filter( 'woocommerce_account_menu_items', 'pspa_gp_account_menu_items', 99 );

/**
 * Set the endpoint page title (heading on the account content area)
 */
function pspa_gp_endpoint_title( $title ) {
    return __( 'Προφίλ Απόφοιτου', 'pspa-gp' );
}
add_filter( 'woocommerce_endpoint_' . PSPA_GP_ENDPOINT . '_title', 'pspa_gp_endpoint_title' );

/**
 * Render the content for the endpoint
 */
function pspa_gp_endpoint_content() {
    echo '<h3>' . esc_html__( 'Προφίλ Απόφοιτου', 'pspa-gp' ) . '</h3>';
    echo '<p>' . esc_html__( 'Περιεχόμενο προφίλ αποφοίτου εδώ. Προσαρμόστε σύμφωνα με τις ανάγκες σας.', 'pspa-gp' ) . '</p>';
}
add_action( 'woocommerce_account_' . PSPA_GP_ENDPOINT . '_endpoint', 'pspa_gp_endpoint_content' );

/**
 * Activation: register endpoint then flush rewrite rules
 */
function pspa_gp_activate() {
    pspa_gp_add_endpoint();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pspa_gp_activate' );

/**
 * Deactivation: flush rewrite rules
 */
function pspa_gp_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pspa_gp_deactivate' );

/**
 * Optional: Admin notice if WooCommerce is missing
 */
function pspa_gp_plugins_loaded_check() {
    if ( is_admin() && ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Το πρόσθετο "Προφίλ Απόφοιτου" απαιτεί το WooCommerce για να λειτουργήσει.', 'pspa-gp' ) . '</p></div>';
        } );
    }
}
add_action( 'plugins_loaded', 'pspa_gp_plugins_loaded_check' );

/**
 * Update checker bootstrap using YahnisElsts/plugin-update-checker
 * Configure with:
 * - define('PSPA_GP_UPDATE_GITHUB_REPO', 'https://github.com/owner/repo/');
 * - define('PSPA_GP_UPDATE_BRANCH', 'main'); // optional
 * - define('PSPA_GP_UPDATE_AUTH_TOKEN', 'ghp_xxx'); // optional for private repo
 * Or via filters: 'pspa_gp_update_repo_url', 'pspa_gp_update_branch', 'pspa_gp_update_auth'.
 */
function pspa_gp_init_updater() {
    // Detect library path: vendor/ or bundled folder
    $paths = array(
        __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php',
        __DIR__ . '/plugin-update-checker/plugin-update-checker.php',
        dirname( __DIR__ ) . '/plugin-update-checker/plugin-update-checker.php',
    );

    $puc_bootstrap = null;
    foreach ( $paths as $candidate ) {
        if ( file_exists( $candidate ) ) {
            $puc_bootstrap = $candidate;
            break;
        }
    }

    // Allow 3rd parties to provide path programmatically
    $puc_bootstrap = apply_filters( 'pspa_gp_update_lib_path', $puc_bootstrap );

    if ( ! $puc_bootstrap || ! file_exists( $puc_bootstrap ) ) {
        // Admin notice about missing library
        if ( is_admin() ) {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-warning"><p>' . esc_html__( 'Το πρόσθετο "Προφίλ Απόφοιτου": Το library Plugin Update Checker δεν βρέθηκε. Προσθέστε το φάκελο plugin-update-checker ή vendor.', 'pspa-gp' ) . '</p></div>';
            } );
        }
        return;
    }

    require_once $puc_bootstrap;

    // Repository URL (default to official repo; can be overridden via constant/filter)
    $repo_url = defined( 'PSPA_GP_UPDATE_GITHUB_REPO' ) ? PSPA_GP_UPDATE_GITHUB_REPO : 'https://github.com/GeorgeWebDevCy/pspa-membership-system/';
    $repo_url = apply_filters( 'pspa_gp_update_repo_url', $repo_url );

    if ( empty( $repo_url ) ) {
        if ( is_admin() ) {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-info"><p>' . esc_html__( 'Ρύθμιση ενημερώσεων: Ορίστε το PSPA_GP_UPDATE_GITHUB_REPO ή το φίλτρο pspa_gp_update_repo_url για να ενεργοποιήσετε τα updates.', 'pspa-gp' ) . '</p></div>';
            } );
        }
        return;
    }

    // Build update checker instance
    $updateChecker = Puc_v4_Factory::buildUpdateChecker(
        $repo_url,
        __FILE__,
        'pspa-graduate-profile'
    );

    // Optional: set branch (default: master or main depending on repo)
    $branch = defined( 'PSPA_GP_UPDATE_BRANCH' ) ? PSPA_GP_UPDATE_BRANCH : 'main';
    $branch = apply_filters( 'pspa_gp_update_branch', $branch );
    if ( ! empty( $branch ) && method_exists( $updateChecker, 'setBranch' ) ) {
        $updateChecker->setBranch( $branch );
    }

    // Optional: authentication token (for private repos)
    $auth = defined( 'PSPA_GP_UPDATE_AUTH_TOKEN' ) ? PSPA_GP_UPDATE_AUTH_TOKEN : '';
    $auth = apply_filters( 'pspa_gp_update_auth', $auth );
    if ( ! empty( $auth ) && method_exists( $updateChecker, 'setAuthentication' ) ) {
        $updateChecker->setAuthentication( $auth );
    }

    // Optional: prefer release assets
    if ( method_exists( $updateChecker, 'getVcsApi' ) ) {
        $api = $updateChecker->getVcsApi();
        if ( $api && method_exists( $api, 'enableReleaseAssets' ) ) {
            $api->enableReleaseAssets();
        }
    }
}
add_action( 'plugins_loaded', 'pspa_gp_init_updater', 20 );
