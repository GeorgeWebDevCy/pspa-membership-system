<?php
/**
 * Plugin Name: PSPA Membership System
 * Description: Membership system for PSPA.
 * Version: 0.0.1
 * Author: George Nicolaou
 * Author URI: https://profiles.wordpress.org/orionaselite/
 *
 * @package PSPA\MembershipSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ensure required plugins are active.
 */
function pspa_ms_check_dependencies() {
    if ( ! is_admin() ) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    $required_plugins = array(
        'advanced-custom-fields-pro/acf.php' => 'Advanced Custom Fields Pro',
        'woocommerce/woocommerce.php'        => 'WooCommerce',
        'advanced-access-manager/aam.php'    => 'Advanced Access Manager',
    );

    $missing_plugins = array();

    foreach ( $required_plugins as $file => $name ) {
        if ( ! is_plugin_active( $file ) ) {
            $missing_plugins[] = $name;
        }
    }

    if ( ! empty( $missing_plugins ) ) {
        add_action(
            'admin_notices',
            static function () use ( $missing_plugins ) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    'PSPA Membership System requires the following plugins to be active: %s.',
                    esc_html( implode( ', ', $missing_plugins ) )
                );
                echo '</p></div>';
            }
        );

        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

pspa_ms_check_dependencies();

// Load the plugin update checker library.
require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Initialize the update checker.
$pspa_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/PSPA/pspa-membership-system/',
    __FILE__,
    'pspa-membership-system'
);

// Optional: set the branch to check for updates.
$pspa_update_checker->setBranch( 'main' );

