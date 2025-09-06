<?php
/**
 * Plugin Name: PSPA Membership System
 * Description: Membership system for PSPA.
 * Version: 0.0.1
 * Author: PSPA
 *
 * @package PSPA\MembershipSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

