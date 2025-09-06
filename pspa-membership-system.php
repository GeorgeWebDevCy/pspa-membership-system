<?php
/**
 * Plugin Name: PSPA Membership System
 * Description: Membership system for PSPA.
 * Version: 0.0.9
 * Author: George Nicolaou
 * Author URI: https://profiles.wordpress.org/orionaselite/
 *
 * @package PSPA\MembershipSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PSPA_MS_VERSION', '0.0.9' );

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
 * Prepare ACF for front-end forms when viewing the graduate profile endpoint.
 */
function pspa_ms_maybe_acf_form_head() {
    if ( ! function_exists( 'acf_form_head' ) ) {
        return;
    }

    if ( is_account_page() && false !== get_query_var( 'graduate-profile', false ) ) {
        acf_form_head();
    }
}
add_action( 'template_redirect', 'pspa_ms_maybe_acf_form_head' );

/**
 * Hide the "Ρυθμίσεις ορατότητας" tab and associated control field.
 *
 * The graduate profile field group uses ACF tabs for internal organization but
 * the front-end form should display as a single page. This filter removes the
 * visibility settings tab (`tab_gn_visibility`) and the `gn_visibility_mode`
 * field so no user can access them.
 *
 * @param array $field Field settings.
 * @return false
 */
function pspa_ms_hide_visibility_fields( $field ) {
    return false;
}
add_filter( 'acf/prepare_field/key=tab_gn_visibility', 'pspa_ms_hide_visibility_fields' );
add_filter( 'acf/prepare_field/name=gn_visibility_mode', 'pspa_ms_hide_visibility_fields' );

/**
 * Render Graduate Profile endpoint content.
 */
function pspa_ms_graduate_profile_content() {
    if ( ! is_user_logged_in() ) {
        echo esc_html__( 'You need to be logged in to edit your profile.', 'pspa-membership-system' );
        return;
    }

    $current_user = wp_get_current_user();

    if ( current_user_can( 'manage_options' ) || in_array( 'system-admin', (array) $current_user->roles, true ) ) {
        pspa_ms_admin_profile_interface();
        return;
    }

    pspa_ms_simple_profile_form( $current_user->ID );
}
add_action( 'woocommerce_account_graduate-profile_endpoint', 'pspa_ms_graduate_profile_content' );

/**
 * Render the simple profile form for graduates.
 *
 * @param int $user_id User ID.
 */
function pspa_ms_simple_profile_form( $user_id ) {
    $user = get_user_by( 'id', $user_id );

    if (
        'POST' === $_SERVER['REQUEST_METHOD'] &&
        isset( $_POST['pspa_graduate_profile_nonce'] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pspa_graduate_profile_nonce'] ) ), 'pspa_graduate_profile' )
    ) {
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

        $update_data = array( 'ID' => $user_id );

        if ( ! empty( $email ) ) {
            $update_data['user_email'] = $email;
        }

        if ( ! empty( $password ) ) {
            $update_data['user_pass'] = $password;
        }

        if ( count( $update_data ) > 1 ) {
            wp_update_user( $update_data );
        }

        wc_add_notice( __( 'Profile updated successfully.', 'pspa-membership-system' ) );
        $user = wp_get_current_user();
    }

    ?>
    <style>
    .pspa-dashboard{--bg:#f2ece4;--card:#fffaf5;--ink:#3b2b22;--line:#e4d6c8}
    .pspa-dashboard .acf-field{background:var(--card);border:1px solid var(--line);padding:10px 12px;margin-bottom:12px;border-radius:14px}
    .pspa-dashboard .acf-label label{color:var(--ink);font-weight:600}
    .pspa-dashboard input[type="text"],
    .pspa-dashboard input[type="email"],
    .pspa-dashboard input[type="number"],
    .pspa-dashboard input[type="password"],
    .pspa-dashboard select,
    .pspa-dashboard textarea{width:100%;background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 12px}
    </style>
    <form class="woocommerce-EditAccountForm edit-account pspa-dashboard" method="post">
        <?php if ( function_exists( 'acf_form' ) ) : ?>
            <?php acf_form( array(
                'post_id'      => 'user_' . $user_id,
                'form'         => false,
                'field_groups' => array( 'group_gn_graduate_profile' ),
            ) ); ?>
        <?php endif; ?>
        <p class="form-row form-row-wide">
            <label for="email"><?php esc_html_e( 'Email address', 'pspa-membership-system' ); ?></label>
            <input type="email" name="email" id="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
        </p>
        <p class="form-row form-row-wide">
            <label for="password"><?php esc_html_e( 'New password', 'pspa-membership-system' ); ?></label>
            <input type="password" name="password" id="password" />
        </p>
        <?php wp_nonce_field( 'pspa_graduate_profile', 'pspa_graduate_profile_nonce' ); ?>
        <p>
            <button type="submit" class="woocommerce-Button button">
                <?php esc_html_e( 'Αποθήκευση αλλαγών', 'pspa-membership-system' ); ?>
            </button>
        </p>
    </form>
    <?php
}

/**
 * Render admin interface allowing search and editing of users.
 */
function pspa_ms_admin_profile_interface() {
    $edit_user_id = isset( $_GET['edit_user'] ) ? absint( $_GET['edit_user'] ) : 0;

    if ( $edit_user_id ) {
        pspa_ms_admin_edit_user_form( $edit_user_id );
        return;
    }

    $search_term = isset( $_POST['pspa_user_search'] ) ? sanitize_text_field( wp_unslash( $_POST['pspa_user_search'] ) ) : '';

    ?>
    <form method="post" style="margin-bottom:20px;">
        <p>
            <input type="text" name="pspa_user_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search users', 'pspa-membership-system' ); ?>" />
            <button type="submit" class="button"><?php esc_html_e( 'Search', 'pspa-membership-system' ); ?></button>
        </p>
    </form>
    <?php

    if ( ! empty( $search_term ) ) {
        global $wpdb;
        $like      = '%' . $wpdb->esc_like( $search_term ) . '%';
        $user_ids  = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_value LIKE %s", $like ) );
        $user_ids2 = get_users(
            array(
                'search'         => '*' . esc_attr( $search_term ) . '*',
                'fields'         => 'ID',
                'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'user_url' ),
            )
        );

        $user_ids = array_unique( array_merge( $user_ids, $user_ids2 ) );

        if ( ! empty( $user_ids ) ) {
            echo '<ul class="pspa-user-search-results">';
            foreach ( $user_ids as $id ) {
                $u = get_user_by( 'id', $id );
                echo '<li><a href="' . esc_url( add_query_arg( 'edit_user', $id ) ) . '">' . esc_html( $u->display_name . ' (' . $u->user_email . ')' ) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . esc_html__( 'No users found.', 'pspa-membership-system' ) . '</p>';
        }
    }
}

/**
 * Render admin edit form for a specific user.
 *
 * @param int $user_id User ID being edited.
 */
function pspa_ms_admin_edit_user_form( $user_id ) {
    $user = get_user_by( 'id', $user_id );

    if ( ! $user ) {
        echo esc_html__( 'Invalid user.', 'pspa-membership-system' );
        return;
    }

    if (
        'POST' === $_SERVER['REQUEST_METHOD'] &&
        isset( $_POST['pspa_admin_edit_user_nonce'] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pspa_admin_edit_user_nonce'] ) ), 'pspa_admin_edit_user' )
    ) {
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
        $last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
        $email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $password   = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

        $update_data = array(
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'user_email'   => $email,
            'display_name' => trim( $first_name . ' ' . $last_name ),
        );

        if ( ! empty( $password ) ) {
            $update_data['user_pass'] = $password;
        }

        wp_update_user( $update_data );

        wc_add_notice( __( 'Profile updated successfully.', 'pspa-membership-system' ) );
        $user = get_user_by( 'id', $user_id );
    }

    echo '<p><a href="' . esc_url( remove_query_arg( 'edit_user' ) ) . '">&larr; ' . esc_html__( 'Back to search', 'pspa-membership-system' ) . '</a></p>';

    ?>
    <form method="post" class="pspa-admin-edit-user">
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
        <p class="form-row form-row-wide">
            <label for="password"><?php esc_html_e( 'New password', 'pspa-membership-system' ); ?></label>
            <input type="password" name="password" id="password" />
        </p>
        <?php if ( function_exists( 'acf_form' ) ) : ?>
            <div class="pspa-acf-fields">
                <?php acf_form( array( 'post_id' => 'user_' . $user_id, 'form' => false ) ); ?>
            </div>
        <?php endif; ?>
        <?php wp_nonce_field( 'pspa_admin_edit_user', 'pspa_admin_edit_user_nonce' ); ?>
        <p>
            <button type="submit" class="woocommerce-Button button"><?php esc_html_e( 'Αποθήκευση αλλαγών', 'pspa-membership-system' ); ?></button>
        </p>
    </form>
    <?php
}

/**
 * Shortcode: login by details.
 *
 * @return string
 */
function pspa_ms_login_by_details_shortcode() {
    if ( is_user_logged_in() ) {
        return '';
    }

    $output = '';

    if (
        'POST' === $_SERVER['REQUEST_METHOD'] &&
        isset( $_POST['pspa_login_details_nonce'] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pspa_login_details_nonce'] ) ), 'pspa_login_details' )
    ) {
        $first = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
        $last  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
        $year  = isset( $_POST['graduation_year'] ) ? sanitize_text_field( wp_unslash( $_POST['graduation_year'] ) ) : '';

        $query = new WP_User_Query(
            array(
                'number'     => 1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'gn_first_name',
                        'value'   => $first,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'gn_surname',
                        'value'   => $last,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'gn_graduation_year',
                        'value'   => $year,
                        'compare' => '=',
                    ),
                ),
            )
        );

        $users = $query->get_results();

        if ( ! empty( $users ) ) {
            $user = $users[0];
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true );
            /**
             * Fire the login hook so other plugins can perform actions on login.
             */
            do_action( 'wp_login', $user->user_login, $user );
            wp_safe_redirect( wc_get_account_endpoint_url( 'graduate-profile' ) );
            exit;
        } else {
            $output .= '<p>' . esc_html__( 'No matching user found.', 'pspa-membership-system' ) . '</p>';
        }
    }

    ob_start();
    ?>
    <form method="post" class="pspa-login-by-details">
        <p>
            <label for="first_name"><?php esc_html_e( 'First Name', 'pspa-membership-system' ); ?></label>
            <input type="text" name="first_name" id="first_name" required />
        </p>
        <p>
            <label for="last_name"><?php esc_html_e( 'Last Name', 'pspa-membership-system' ); ?></label>
            <input type="text" name="last_name" id="last_name" required />
        </p>
        <p>
            <label for="graduation_year"><?php esc_html_e( 'Graduation Year', 'pspa-membership-system' ); ?></label>
            <input type="text" name="graduation_year" id="graduation_year" required />
        </p>
        <?php wp_nonce_field( 'pspa_login_details', 'pspa_login_details_nonce' ); ?>
        <p>
            <button type="submit" class="button"><?php esc_html_e( 'Log In', 'pspa-membership-system' ); ?></button>
        </p>
    </form>
    <?php
    $output .= ob_get_clean();
    return $output;
}

/**
 * Register plugin shortcodes.
 */
function pspa_ms_register_shortcodes() {
    add_shortcode( 'pspa_login_by_details', 'pspa_ms_login_by_details_shortcode' );
    add_shortcode( 'pspa_graduate_directory', 'pspa_ms_graduate_directory_shortcode' );
}
add_action( 'init', 'pspa_ms_register_shortcodes' );

/**
 * Sync first, last and display names with ACF fields after saving.
 *
 * @param string $post_id Post identifier.
 */
function pspa_ms_sync_user_names( $post_id ) {
    if ( 0 !== strpos( $post_id, 'user_' ) ) {
        return;
    }

    $uid   = (int) substr( $post_id, 5 );
    $first = trim( (string) get_field( 'gn_first_name', 'user_' . $uid ) );
    $last  = trim( (string) get_field( 'gn_surname', 'user_' . $uid ) );
    $data  = array( 'ID' => $uid );

    if ( '' !== $first ) {
        $data['first_name'] = $first;
    }

    if ( '' !== $last ) {
        $data['last_name'] = $last;
    }

    if ( '' !== $first || '' !== $last ) {
        $data['display_name'] = trim( $first . ' ' . $last );
    }

    if ( count( $data ) > 1 ) {
        wp_update_user( $data );
    }
}
add_action( 'acf/save_post', 'pspa_ms_sync_user_names', 20 );

/**
 * Force graduates and system admins to stay on the front end.
 *
 * @param string  $redirect_to Redirect destination.
 * @param string  $request     Request parameter.
 * @param WP_User $user        User object.
 *
 * @return string
 */
function pspa_ms_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->ID ) && ( in_array( 'system-admin', (array) $user->roles, true ) || in_array( 'professionalcatalogue', (array) $user->roles, true ) ) ) {
        return wc_get_account_endpoint_url( 'graduate-profile' );
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'pspa_ms_login_redirect', 10, 3 );

/**
 * Block backend access for graduates and system admins.
 */
function pspa_ms_block_admin_access() {
    if ( is_admin() && ! wp_doing_ajax() && is_user_logged_in() ) {
        $user = wp_get_current_user();
        if ( in_array( 'system-admin', (array) $user->roles, true ) || in_array( 'professionalcatalogue', (array) $user->roles, true ) ) {
            wp_safe_redirect( wc_get_account_endpoint_url( 'graduate-profile' ) );
            exit;
        }
    }
}
add_action( 'init', 'pspa_ms_block_admin_access' );

/**
 * Get unique user meta values for filters.
 *
 * @param string $meta_key User meta key.
 * @return array
 */
function pspa_ms_get_unique_user_meta_values( $meta_key ) {
    global $wpdb;
    $values = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value <> '' ORDER BY meta_value ASC", $meta_key ) );
    return $values;
}

/**
 * Render a graduate profile card.
 *
 * @param int $user_id User ID.
 * @return string
 */
function pspa_ms_render_graduate_card( $user_id ) {
    $name      = get_the_author_meta( 'display_name', $user_id );
    $job       = function_exists( 'get_field' ) ? (string) get_field( 'gn_job_title', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_job_title', true );
    $company   = function_exists( 'get_field' ) ? (string) get_field( 'gn_position_company', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_position_company', true );
    $profession = function_exists( 'get_field' ) ? (string) get_field( 'gn_profession', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_profession', true );
    $city      = function_exists( 'get_field' ) ? (string) get_field( 'gn_city', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_city', true );
    $country   = function_exists( 'get_field' ) ? (string) get_field( 'gn_country', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_country', true );

    ob_start();
    ?>
    <div class="pspa-graduate-card">
        <div class="pspa-graduate-avatar"><?php echo get_avatar( $user_id, 96 ); ?></div>
        <div class="pspa-graduate-details">
            <h3 class="pspa-graduate-name"><?php echo esc_html( $name ); ?></h3>
            <?php if ( $job || $company ) : ?>
                <p class="pspa-graduate-title"><?php echo esc_html( trim( $job . ( $company ? ' - ' . $company : '' ) ) ); ?></p>
            <?php endif; ?>
            <?php if ( $profession ) : ?>
                <p class="pspa-graduate-profession"><?php echo esc_html( $profession ); ?></p>
            <?php endif; ?>
            <?php if ( $city || $country ) : ?>
                <p class="pspa-graduate-location"><?php echo esc_html( trim( $city . ( $country ? ', ' . $country : '' ) ) ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode callback for the graduate directory.
 *
 * @return string
 */
function pspa_ms_graduate_directory_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>' . esc_html__( 'You must be logged in to view the graduate directory.', 'pspa-membership-system' ) . '</p>';
    }

    wp_enqueue_style( 'pspa-ms-graduate-directory', plugin_dir_url( __FILE__ ) . 'assets/css/graduate-directory.css', array(), PSPA_MS_VERSION );
    wp_enqueue_script( 'pspa-ms-graduate-directory', plugin_dir_url( __FILE__ ) . 'assets/js/graduate-directory.js', array( 'jquery' ), PSPA_MS_VERSION, true );
    wp_localize_script( 'pspa-ms-graduate-directory', 'pspaMsDir', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'pspa_ms_dir' ),
    ) );

    $professions = pspa_ms_get_unique_user_meta_values( 'gn_profession' );
    $jobs        = pspa_ms_get_unique_user_meta_values( 'gn_job_title' );
    $cities      = pspa_ms_get_unique_user_meta_values( 'gn_city' );
    $countries   = pspa_ms_get_unique_user_meta_values( 'gn_country' );

    ob_start();
    ?>
    <div class="pspa-graduate-directory">
        <form id="pspa-graduate-filters">
            <select name="profession">
                <option value=""><?php esc_html_e( 'All Occupations', 'pspa-membership-system' ); ?></option>
                <?php foreach ( $professions as $p ) : ?>
                    <option value="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="job_title">
                <option value=""><?php esc_html_e( 'All Job Titles', 'pspa-membership-system' ); ?></option>
                <?php foreach ( $jobs as $j ) : ?>
                    <option value="<?php echo esc_attr( $j ); ?>"><?php echo esc_html( $j ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="city">
                <option value=""><?php esc_html_e( 'All Towns', 'pspa-membership-system' ); ?></option>
                <?php foreach ( $cities as $c ) : ?>
                    <option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="country">
                <option value=""><?php esc_html_e( 'All Countries', 'pspa-membership-system' ); ?></option>
                <?php foreach ( $countries as $co ) : ?>
                    <option value="<?php echo esc_attr( $co ); ?>"><?php echo esc_html( $co ); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div id="pspa-graduate-results"><p><?php esc_html_e( 'Loading...', 'pspa-membership-system' ); ?></p></div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * AJAX handler for filtering graduates.
 */
function pspa_ms_ajax_filter_graduates() {
    check_ajax_referer( 'pspa_ms_dir', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error();
    }

    $fields = array(
        'profession' => 'gn_profession',
        'job_title'  => 'gn_job_title',
        'city'       => 'gn_city',
        'country'    => 'gn_country',
    );

    $meta_query = array( 'relation' => 'AND' );

    foreach ( $fields as $request => $key ) {
        if ( ! empty( $_POST[ $request ] ) ) {
            $meta_query[] = array(
                'key'   => $key,
                'value' => sanitize_text_field( wp_unslash( $_POST[ $request ] ) ),
            );
        }
    }

    $args = array(
        'number'     => -1,
        'meta_query' => $meta_query,
    );

    $users = new WP_User_Query( $args );
    $html  = '';

    if ( ! empty( $users->get_results() ) ) {
        foreach ( $users->get_results() as $user ) {
            $html .= pspa_ms_render_graduate_card( $user->ID );
        }
    } else {
        $html = '<p>' . esc_html__( 'No graduates found.', 'pspa-membership-system' ) . '</p>';
    }

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_pspa_ms_filter_graduates', 'pspa_ms_ajax_filter_graduates' );

/**
 * Flush rewrite rules on activation and deactivation.
 */
function pspa_ms_flush_rewrite_rules() {
    pspa_ms_register_graduate_profile_endpoint();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pspa_ms_flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

