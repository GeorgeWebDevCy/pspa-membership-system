<?php
/**
 * Plugin Name: PSPA Membership System
 * Description: Membership system for PSPA.
 * Version: 0.0.2
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

/**
 * Register the Graduate Profile endpoint.
 */
function pspa_ms_register_graduate_profile_endpoint() {
    add_rewrite_endpoint( 'graduate-profile', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'pspa_ms_register_graduate_profile_endpoint' );

/**
 * Add query var for the Graduate Profile endpoint.
 *
 * @param array $vars Query vars.
 * @return array
 */
function pspa_ms_graduate_profile_query_vars( $vars ) {
    $vars[] = 'graduate-profile';
    return $vars;
}
add_filter( 'query_vars', 'pspa_ms_graduate_profile_query_vars' );

/**
 * Add Graduate Profile item to the My Account menu.
 *
 * @param array $items Menu items.
 * @return array
 */
function pspa_ms_add_graduate_profile_link( $items ) {
    $items['graduate-profile'] = __( 'Προφίλ Απόφοιτου', 'pspa-membership-system' );
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'pspa_ms_add_graduate_profile_link' );

/**
 * Render Graduate Profile endpoint content.
 */
function pspa_ms_graduate_profile_content() {
    if ( ! is_user_logged_in() ) {
        echo esc_html__( 'You need to be logged in to edit your profile.', 'pspa-membership-system' );
        return;
    }

    $user_id = get_current_user_id();
    $user    = wp_get_current_user();

    if (
        'POST' === $_SERVER['REQUEST_METHOD'] &&
        isset( $_POST['pspa_graduate_profile_nonce'] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pspa_graduate_profile_nonce'] ) ), 'pspa_graduate_profile' )
    ) {
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
        $last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
        $email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        wp_update_user(
            array(
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'user_email'   => $email,
                'display_name' => trim( $first_name . ' ' . $last_name ),
            )
        );

        wc_add_notice( __( 'Profile updated successfully.', 'pspa-membership-system' ) );
        $user = wp_get_current_user();
    }

    ?>
    <form class="woocommerce-EditAccountForm edit-account" method="post">
        <p class="form-row form-row-first">
            <label for="first_name"><?php esc_html_e( 'First name', 'pspa-membership-system' ); ?></label>
            <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" />
        </p>
        <p class="form-row form-row-last">
            <label for="last_name"><?php esc_html_e( 'Last name', 'pspa-membership-system' ); ?></label>
            <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" />
        </p>
        <p class="form-row form-row-wide">
            <label for="email"><?php esc_html_e( 'Email address', 'pspa-membership-system' ); ?></label>
            <input type="email" name="email" id="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
        </p>
        <?php wp_nonce_field( 'pspa_graduate_profile', 'pspa_graduate_profile_nonce' ); ?>
        <p>
            <button type="submit" class="woocommerce-Button button">
                <?php esc_html_e( 'Save changes', 'pspa-membership-system' ); ?>
            </button>
        </p>
    </form>
    <?php
}
add_action( 'woocommerce_account_graduate-profile_endpoint', 'pspa_ms_graduate_profile_content' );

/**
 * Flush rewrite rules on activation and deactivation.
 */
function pspa_ms_flush_rewrite_rules() {
    pspa_ms_register_graduate_profile_endpoint();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pspa_ms_flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

