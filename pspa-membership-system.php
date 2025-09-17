<?php
/**
 * Plugin Name: PSPA Membership System
 * Description: Membership system for PSPA.
 * Version: 0.0.102
 * Author: George Nicolaou
 * Author URI: https://profiles.wordpress.org/orionaselite/
 *
 * @package PSPA\MembershipSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PSPA_MS_VERSION', '0.0.102' );

if ( ! defined( 'PSPA_MS_ENABLE_LOGGING' ) ) {
    define( 'PSPA_MS_ENABLE_LOGGING', defined( 'WP_DEBUG' ) && WP_DEBUG );
}

/**
 * Log a message using the legacy option-based logger.
 *
 * @param string $message Message to log.
 * @param string $level   Log level.
 * @param array  $context Additional context.
 */
function pspa_ms_log( $message, $level = 'info', $context = array() ) {
    if ( ! PSPA_MS_ENABLE_LOGGING ) {
        return;
    }

    if ( ! is_scalar( $message ) ) {
        $message = wp_json_encode( $message );
    }

    $entry = array(
        'time'    => current_time( 'mysql' ),
        'level'   => $level,
        'message' => (string) $message,
        'context' => $context,
    );

    $logs   = get_option( 'pspa_ms_logs', array() );
    $logs[] = $entry;

    if ( count( $logs ) > 200 ) {
        $logs = array_slice( $logs, -200 );
    }

    update_option( 'pspa_ms_logs', $logs );

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( strtoupper( $level ) . ': ' . $entry['message'] . ( ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '' ) );
    }
}

/**
 * Reset all plugin settings stored in the options table.
 *
 * Deletes any options that start with the `pspa_ms_` prefix, allowing the
 * plugin to return to a clean state without manual database edits.
 */
function pspa_ms_reset_settings() {
    global $wpdb;

    $like = $wpdb->esc_like( 'pspa_ms_' ) . '%';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command(
        'pspa-ms reset',
        static function () {
            pspa_ms_reset_settings();
            WP_CLI::success( 'PSPA Membership System settings reset.' );
        }
    );
}

/**
 * Normalize Greek names for flexible matching.
 *
 * Converts the string to uppercase and strips common accent marks so
 * comparisons can be made in a case-insensitive manner without being
 * affected by tonal differences.
 *
 * @param string $name Name to normalize.
 * @return string Normalized name.
 */
function pspa_ms_normalize_name( $name ) {
    $name = mb_strtoupper( $name, 'UTF-8' );
    $search  = array( 'Ά', 'Έ', 'Ή', 'Ί', 'Ό', 'Ύ', 'Ώ', 'Ϊ', 'Ϋ' );
    $replace = array( 'Α', 'Ε', 'Η', 'Ι', 'Ο', 'Υ', 'Ω', 'Ι', 'Υ' );
    return str_replace( $search, $replace, $name );
}
/**
 * Enqueue shared dashboard styles.
 */
function pspa_ms_enqueue_dashboard_styles() {
    wp_enqueue_style(
        'pspa-ms-dashboard',
        plugin_dir_url( __FILE__ ) . 'assets/css/dashboard.css',
        array(),
        PSPA_MS_VERSION
    );
}

/**
 * Enqueue styles for the WooCommerce account navigation.
 */
function pspa_ms_enqueue_woocommerce_nav_styles() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return;
    }

    wp_enqueue_style(
        'pspa-ms-woocommerce-nav',
        plugin_dir_url( __FILE__ ) . 'assets/css/woocommerce-navigation.css',
        array(),
        PSPA_MS_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'pspa_ms_enqueue_woocommerce_nav_styles' );

/**
 * Enqueue password strength meter.
 */
function pspa_ms_enqueue_password_strength() {
    wp_enqueue_script(
        'pspa-ms-password-strength',
        plugin_dir_url( __FILE__ ) . 'assets/js/password-strength.js',
        array( 'jquery', 'password-strength-meter' ),
        PSPA_MS_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'pspa_ms_enqueue_password_strength' );

/**
 * Retrieve the URL shown after a login-by-details failure.
 *
 * @return string Failure redirect URL.
 */
function pspa_ms_get_login_details_failure_redirect_url() {
    $default_url = home_url( '/αποτυχία-επικαιροποίησης/' );

    /**
     * Filters the login-by-details failure redirect URL.
     *
     * @param string $default_url Default failure page URL.
     */
    $redirect_url = apply_filters( 'pspa_ms_login_details_failure_redirect_url', $default_url );

    return (string) $redirect_url;
}

/**
 * Estimate password strength for logging.
 *
 * @param string $password Raw password.
 * @return string Strength label.
 */
function pspa_ms_password_strength( $password ) {
    $score = 0;

    if ( strlen( $password ) >= 8 ) {
        $score++;
    }
    if ( preg_match( '/[a-z]/', $password ) ) {
        $score++;
    }
    if ( preg_match( '/[A-Z]/', $password ) ) {
        $score++;
    }
    if ( preg_match( '/\d/', $password ) ) {
        $score++;
    }
    if ( preg_match( '/[^a-zA-Z0-9]/', $password ) ) {
        $score++;
    }

    if ( $score <= 2 ) {
        return 'weak';
    }

    if ( $score <= 4 ) {
        return 'medium';
    }

    return 'strong';
}

/**
 * Update a user's password by ID, email, or username.
 *
 * @param int|string $identifier   User ID, email address, or username.
 * @param string     $new_password Plaintext new password.
 * @return int|\WP_Error User ID on success, or WP_Error on failure.
 */
function pspa_ms_update_user_password( $identifier, $new_password ) {
    pspa_ms_log( sprintf( 'Password update attempt for identifier "%s"', $identifier ) );

    if ( empty( $new_password ) ) {
        pspa_ms_log( 'Password update failed: empty password.' );
        return new WP_Error( 'empty_password', 'Password cannot be empty.' );
    }

    if ( is_numeric( $identifier ) ) {
        $user = get_user_by( 'id', absint( $identifier ) );
    } elseif ( is_email( $identifier ) ) {
        $user = get_user_by( 'email', $identifier );
    } else {
        $user = get_user_by( 'login', $identifier );
    }

    if ( ! $user || ! $user->ID ) {
        pspa_ms_log( 'Password update failed: user not found for identifier ' . $identifier );
        return new WP_Error( 'user_not_found', 'No matching user found.' );
    }

    wp_set_password( $new_password, $user->ID );
    pspa_ms_log( 'Password updated for user ' . $user->ID );

    return (int) $user->ID;
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
    'https://github.com/GeorgeWebDevCy/pspa-membership-system/',
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
 * Register endpoint for listing paid members.
 */
function pspa_ms_register_paid_members_endpoint() {
    add_rewrite_endpoint( 'paid-members', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'pspa_ms_register_paid_members_endpoint' );

/**
 * Add query var for the paid members endpoint.
 *
 * @param array $vars Query vars.
 * @return array
 */
function pspa_ms_paid_members_query_vars( $vars ) {
    $vars[] = 'paid-members';
    return $vars;
}
add_filter( 'query_vars', 'pspa_ms_paid_members_query_vars' );

/**
 * Add Graduate Profile item to the My Account menu.
 *
 * @param array $items Menu items.
 * @return array
 */
function pspa_ms_add_graduate_profile_link( $items ) {
    $ordered_items = array();

    $first_key = array_key_first( $items );
    if ( null !== $first_key ) {
        $ordered_items[ $first_key ] = $items[ $first_key ];
        unset( $items[ $first_key ] );
    }

    if ( isset( $items['edit-account'] ) ) {
        $ordered_items['edit-account'] = $items['edit-account'];
        unset( $items['edit-account'] );
    }

    $ordered_items['graduate-profile'] = __( 'Προφίλ Απόφοιτου', 'pspa-membership-system' );

    foreach ( $items as $key => $label ) {
        $ordered_items[ $key ] = $label;
    }

    $current_user = wp_get_current_user();
    if (
        current_user_can( 'manage_options' ) ||
        in_array( 'system-admin', (array) $current_user->roles, true ) ||
        in_array( 'sysadmin', (array) $current_user->roles, true )
    ) {
        $admin_items = array();

        foreach ( $ordered_items as $key => $label ) {
            $admin_items[ $key ] = $label;

            if ( 'graduate-profile' === $key ) {
                $admin_items['paid-members'] = __( 'Πληρωμένες Συνδρομές', 'pspa-membership-system' );
            }
        }

        return $admin_items;
    }

    return $ordered_items;
}
add_filter( 'woocommerce_account_menu_items', 'pspa_ms_add_graduate_profile_link' );

/**
 * Output list of users who have paid their membership this year.
 */
function pspa_ms_paid_members_content() {
    if ( ! is_user_logged_in() ) {
        echo esc_html__( 'Πρέπει να είστε συνδεδεμένοι για να δείτε αυτή τη σελίδα.', 'pspa-membership-system' );
        return;
    }

    $current_user = wp_get_current_user();
    if (
        ! current_user_can( 'manage_options' ) &&
        ! in_array( 'system-admin', (array) $current_user->roles, true ) &&
        ! in_array( 'sysadmin', (array) $current_user->roles, true )
    ) {
        echo esc_html__( 'Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα.', 'pspa-membership-system' );
        return;
    }

    pspa_ms_enqueue_dashboard_styles();

    $year  = gmdate( 'Y' );
    $start = $year . '-01-01 00:00:00';
    $end   = $year . '-12-31 23:59:59';

    $orders = wc_get_orders(
        array(
            'status'    => array( 'completed', 'processing', 'on-hold' ),
            'limit'     => -1,
            'date_paid' => $start . '...' . $end,
        )
    );

    $user_ids = array();
    foreach ( $orders as $order ) {
        $uid = $order->get_user_id();
        if ( $uid ) {
            $user_ids[ $uid ] = true;
        }
    }

    echo '<div class="pspa-dashboard pspa-paid-members">';
    if ( empty( $user_ids ) ) {
        echo '<p>' . esc_html__( 'Δεν βρέθηκαν πληρωμένες συνδρομές για φέτος.', 'pspa-membership-system' ) . '</p>';
    } else {
        echo '<ul>';
        foreach ( array_keys( $user_ids ) as $uid ) {
            $user = get_user_by( 'id', $uid );
            if ( ! $user ) {
                continue;
            }
            echo '<li>' . esc_html( $user->display_name ) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
}
add_action( 'woocommerce_account_paid-members_endpoint', 'pspa_ms_paid_members_content' );

/**
 * Register public graduate profile rewrite rule.
 */
function pspa_ms_register_public_profile_route() {
    add_rewrite_rule( '^graduate/([^/]+)/?$', 'index.php?pspa_graduate=$matches[1]', 'top' );
}
add_action( 'init', 'pspa_ms_register_public_profile_route' );

/**
 * Add query var for public graduate profiles.
 *
 * @param array $vars Query vars.
 * @return array
 */
function pspa_ms_public_profile_query_vars( $vars ) {
    $vars[] = 'pspa_graduate';
    return $vars;
}
add_filter( 'query_vars', 'pspa_ms_public_profile_query_vars' );

/**
 * Load template for public graduate profiles.
 *
 * @param string $template Template path.
 * @return string
 */
function pspa_ms_public_profile_template( $template ) {
    $slug = get_query_var( 'pspa_graduate' );
    if ( $slug ) {
        $user = get_user_by( 'slug', $slug );
        if ( ! $user ) {
            return get_404_template();
        }
        if ( ! pspa_ms_user_is_visible_in_directory( $user->ID ) && ! pspa_ms_current_user_can_manage_directory_visibility() ) {
            return get_404_template();
        }
        set_query_var( 'pspa_graduate_user', $user );
        $new_template = plugin_dir_path( __FILE__ ) . 'templates/graduate-public-profile.php';
        if ( file_exists( $new_template ) ) {
            return $new_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'pspa_ms_public_profile_template' );

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
 * Ensure admins can edit all ACF fields even when empty.
 *
 * System admins and catalogue editors may need to populate missing data,
 * so remove validation and conditional logic that would otherwise prevent
 * saving the form with empty fields.
 *
 * @param array $field Field settings.
 * @return array
 */
function pspa_ms_unrestrict_acf_fields_for_admins( $field ) {
    if ( ! is_user_logged_in() ) {
        return $field;
    }

    $user  = wp_get_current_user();
    $roles = (array) $user->roles;

    if (
        current_user_can( 'manage_options' ) ||
        in_array( 'system-admin', $roles, true ) ||
        in_array( 'sysadmin', $roles, true )
    ) {
        $field['required']          = 0;
        $field['conditional_logic'] = 0;
    }

    return $field;
}
add_filter( 'acf/prepare_field', 'pspa_ms_unrestrict_acf_fields_for_admins', 5 );

/**
 * Hide "show on public profile" toggles when viewing public profiles.
 *
 * @param array $field Field settings.
 * @return array|false
 */
function pspa_ms_hide_public_visibility_toggles( $field ) {
    if ( ! is_array( $field ) || empty( $field['name'] ) ) {
        return $field;
    }

    if ( 0 === strpos( $field['name'], 'gn_show_' ) ) {
        if ( function_exists( 'is_account_page' ) && is_account_page() && false !== get_query_var( 'graduate-profile', false ) ) {
            return $field;
        }
        return false;
    }

    return $field;
}
add_filter( 'acf/prepare_field', 'pspa_ms_hide_public_visibility_toggles', 20 );

/**
 * Get the list of graduate profile fields reserved for administrators.
 *
 * @return string[]
 */
function pspa_ms_get_admin_only_field_names() {
    return array(
        'gn_initial_db_id',
        'gn_login_verified_date',
        'gn_deceased',
        'gn_show_deceased',
        'gn_directory_visible',
    );
}

/**
 * Fields hidden from Professional Catalogue users on the front end.
 *
 * @return string[]
 */
function pspa_ms_get_professional_catalogue_hidden_fields() {
    return array(
        'gn_initial_db_id',
        'gn_login_verified_date',
    );
}

/**
 * Determine if the current user has the Professional Catalogue role.
 *
 * @return bool
 */
function pspa_ms_current_user_is_professional_catalogue() {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $current_user = wp_get_current_user();

    return in_array( 'professionalcatalogue', (array) $current_user->roles, true );
}

/**
 * Determine if the current user can manage graduate directory visibility.
 *
 * Administrators and System Admins can override visibility restrictions.
 *
 * @return bool
 */
function pspa_ms_current_user_can_manage_directory_visibility() {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    if ( ! is_user_logged_in() ) {
        return false;
    }

    $current_user = wp_get_current_user();
    $roles        = (array) $current_user->roles;

    return in_array( 'system-admin', $roles, true ) || in_array( 'sysadmin', $roles, true );
}

/**
 * Check if a graduate is visible in the directory.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function pspa_ms_user_is_visible_in_directory( $user_id ) {
    if ( empty( $user_id ) ) {
        return false;
    }

    if ( function_exists( 'get_field' ) ) {
        $value = get_field( 'gn_directory_visible', 'user_' . $user_id );
    } else {
        $value = get_user_meta( $user_id, 'gn_directory_visible', true );
    }

    return (bool) $value;
}

/**
 * Hide admin-only fields from catalogue editors and graduate profile forms.
 *
 * @param array|false $field Field settings.
 * @return array|false
 */
function pspa_ms_hide_admin_only_fields( $field ) {
    if ( ! is_array( $field ) ) {
        return $field;
    }

    $admin_only = pspa_ms_get_admin_only_field_names();

    if ( ! in_array( $field['name'], $admin_only, true ) ) {
        return $field;
    }

    if ( pspa_ms_current_user_can_manage_directory_visibility() ) {
        return $field;
    }

    $is_catalogue = pspa_ms_current_user_is_professional_catalogue();
    $is_grad_form = function_exists( 'is_account_page' ) && is_account_page() && false !== get_query_var( 'graduate-profile', false );

    if ( $is_catalogue || $is_grad_form ) {
        return false;
    }

    return $field;
}
add_filter( 'acf/prepare_field', 'pspa_ms_hide_admin_only_fields', 30 );

/**
 * Render Graduate Profile endpoint content.
 */
function pspa_ms_graduate_profile_content() {
    if ( ! is_user_logged_in() ) {
        echo esc_html__( 'Πρέπει να είστε συνδεδεμένοι για να επεξεργαστείτε το προφίλ σας.', 'pspa-membership-system' );
        return;
    }

    $current_user = wp_get_current_user();

    if (
        current_user_can( 'manage_options' ) ||
        in_array( 'system-admin', (array) $current_user->roles, true ) ||
        in_array( 'sysadmin', (array) $current_user->roles, true )
    ) {
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

    if ( ! $user instanceof WP_User ) {
        return;
    }

    pspa_ms_enqueue_dashboard_styles();

    $verified       = get_user_meta( $user_id, 'gn_login_verified_date', true );
    $needs_email    = empty( $user->user_email );
    $needs_password = empty( $verified );

    if ( $needs_email || $needs_password ) {
        if ( $needs_email && $needs_password ) {
            $requirement_text = esc_html__( 'τη διεύθυνση email και έναν κωδικό πρόσβασης', 'pspa-membership-system' );
        } elseif ( $needs_email ) {
            $requirement_text = esc_html__( 'τη διεύθυνση email', 'pspa-membership-system' );
        } else {
            $requirement_text = esc_html__( 'έναν κωδικό πρόσβασης', 'pspa-membership-system' );
        }

        $edit_account_url = function_exists( 'wc_get_account_endpoint_url' )
            ? wc_get_account_endpoint_url( 'edit-account' )
            : '';

        $message = sprintf(
            esc_html__( 'Για να συνεχίσετε, προσθέστε %s από την ενότητα «Στοιχεία λογαριασμού» του λογαριασμού σας. Αφού αποθηκεύσετε τις αλλαγές, θα μπορείτε να ενημερώσετε το προφίλ σας.', 'pspa-membership-system' ),
            $requirement_text
        );

        echo '<div class="pspa-dashboard pspa-profile-requirements">';
        echo '<p>' . $message . '</p>';

        if ( $edit_account_url ) {
            echo '<p><a class="button" href="' . esc_url( $edit_account_url ) . '">';
            esc_html_e( 'Μετάβαση στα στοιχεία λογαριασμού', 'pspa-membership-system' );
            echo '</a></p>';
        }

        echo '</div>';
        return;
    }

    ?>
    <form class="woocommerce-EditAccountForm edit-account pspa-dashboard" method="post">
        <?php if ( function_exists( 'acf_form' ) ) : ?>
            <?php acf_form( array(
                'post_id'      => 'user_' . $user_id,
                'form'         => false,
                'field_groups' => array( 'group_gn_graduate_profile' ),
            ) ); ?>
        <?php endif; ?>
        <?php wp_nonce_field( 'pspa_graduate_profile', 'pspa_graduate_profile_nonce' ); ?>
        <p>
            <button type="submit" class="woocommerce-Button button" name="save_account_details" value="<?php esc_attr_e( 'Αποθήκευση αλλαγών', 'pspa-membership-system' ); ?>">
                <?php esc_html_e( 'Αποθήκευση αλλαγών', 'pspa-membership-system' ); ?>
            </button>
        </p>
    </form>
    <?php
}

/**
 * Add a success notice when graduate profile fields are saved via ACF.
 *
 * @param string $post_id Post identifier.
 */
function pspa_ms_handle_profile_form_save_notice( $post_id ) {
    $graduate_nonce = isset( $_POST['pspa_graduate_profile_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['pspa_graduate_profile_nonce'] ) )
        : '';
    $admin_nonce    = isset( $_POST['pspa_admin_edit_user_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['pspa_admin_edit_user_nonce'] ) )
        : '';

    $context = '';

    if ( $graduate_nonce && wp_verify_nonce( $graduate_nonce, 'pspa_graduate_profile' ) ) {
        $context = 'graduate';
    } elseif ( $admin_nonce && wp_verify_nonce( $admin_nonce, 'pspa_admin_edit_user' ) ) {
        $context = 'admin';
    } else {
        return;
    }

    if ( 0 !== strpos( $post_id, 'user_' ) ) {
        return;
    }

    $user_id = (int) substr( $post_id, 5 );

    if ( $user_id <= 0 ) {
        return;
    }

    if ( 'graduate' === $context ) {
        pspa_ms_log( 'Graduate profile fields updated for user ' . $user_id );
    } else {
        pspa_ms_log( 'Admin updated graduate profile fields for user ' . $user_id );
    }

    wc_add_notice( __( 'Το προφίλ ενημερώθηκε με επιτυχία.', 'pspa-membership-system' ) );
}
add_action( 'acf/save_post', 'pspa_ms_handle_profile_form_save_notice', 25 );

/**
 * Set the login verification date when the WooCommerce account form updates the password.
 *
 * @param int $user_id User ID.
 */
function pspa_ms_handle_edit_account_password_update( $user_id ) {
    if ( empty( $user_id ) ) {
        return;
    }

    if ( empty( $_POST['save_account_details'] ) ) {
        return;
    }

    $new_password = isset( $_POST['password_1'] ) ? wp_unslash( $_POST['password_1'] ) : '';

    if ( '' === trim( $new_password ) ) {
        return;
    }

    $user = get_user_by( 'id', $user_id );

    if ( ! $user || empty( $user->user_email ) ) {
        pspa_ms_log( 'Edit account password update skipped verification date for user ' . $user_id );
        return;
    }

    update_user_meta( $user_id, 'gn_login_verified_date', current_time( 'mysql' ) );
    pspa_ms_log( 'Verification date updated via edit account form for user ' . $user_id );
}
add_action( 'woocommerce_save_account_details', 'pspa_ms_handle_edit_account_password_update', 30, 1 );

/**
 * Render admin interface allowing search and editing of users.
 */
function pspa_ms_admin_profile_interface() {
    pspa_ms_enqueue_dashboard_styles();

    $edit_user_id = isset( $_GET['edit_user'] ) ? absint( $_GET['edit_user'] ) : 0;
    $add_user    = isset( $_GET['add_user'] );

    if ( $edit_user_id ) {
        pspa_ms_admin_edit_user_form( $edit_user_id );
        return;
    }

    if ( $add_user ) {
        pspa_ms_admin_add_user_form();
        return;
    }

    $add_url = add_query_arg( 'add_user', 1, pspa_ms_get_graduate_profile_edit_url() );
    echo '<div class="pspa-dashboard pspa-admin-dashboard">';
    echo '<p><a class="button" href="' . esc_url( $add_url ) . '">' . esc_html__( 'Προσθήκη χρήστη', 'pspa-membership-system' ) . '</a></p>';
    echo '</div>';

    echo pspa_ms_graduate_directory_shortcode();
}

/**
 * Render admin edit form for a specific user.
 *
 * @param int $user_id User ID being edited.
 */
function pspa_ms_admin_edit_user_form( $user_id ) {
    pspa_ms_enqueue_dashboard_styles();

    $user = get_user_by( 'id', $user_id );

    if ( ! $user ) {
        echo esc_html__( 'Μη έγκυρος χρήστης.', 'pspa-membership-system' );
        return;
    }

    echo '<div class="pspa-dashboard pspa-admin-edit-user">';
    $search_url = pspa_ms_get_graduate_profile_edit_url();
    echo '<p><a href="' . esc_url( $search_url ) . '">&larr; ' . esc_html__( 'Επιστροφή στην αναζήτηση', 'pspa-membership-system' ) . '</a></p>';

    ?>
    <form method="post">
        <?php if ( function_exists( 'acf_form' ) ) : ?>
            <div class="pspa-acf-fields">
                <?php acf_form( array(
                    'post_id'      => 'user_' . $user_id,
                    'form'         => false,
                    'field_groups' => array( 'group_gn_graduate_profile' ),
                ) ); ?>
            </div>
        <?php endif; ?>
        <?php wp_nonce_field( 'pspa_admin_edit_user', 'pspa_admin_edit_user_nonce' ); ?>
        <p>
            <button type="submit" class="woocommerce-Button button"><?php esc_html_e( 'Αποθήκευση αλλαγών', 'pspa-membership-system' ); ?></button>
        </p>
    </form>
    <?php
    echo '</div>';
}

/**
 * Render admin add user form.
 */
function pspa_ms_admin_add_user_form() {
    pspa_ms_enqueue_dashboard_styles();

    if (
        'POST' === $_SERVER['REQUEST_METHOD'] &&
        isset( $_POST['pspa_admin_add_user_nonce'] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pspa_admin_add_user_nonce'] ) ), 'pspa_admin_add_user' )
    ) {
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : wp_generate_password();
        $strength = $password ? pspa_ms_password_strength( $password ) : 'none';

        pspa_ms_log( sprintf(
            'Admin add user: email="%s", password_strength="%s"',
            $email ? $email : 'none',
            $strength
        ) );

        $first = isset( $_POST['acf']['field_gn_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['acf']['field_gn_first_name'] ) ) : '';
        $last  = isset( $_POST['acf']['field_gn_surname'] ) ? sanitize_text_field( wp_unslash( $_POST['acf']['field_gn_surname'] ) ) : '';
        $login = sanitize_user( $first . '.' . $last, true );
        if ( empty( $login ) ) {
            $login = sanitize_user( $email, true );
        }

        $user_id = wp_insert_user( array(
            'user_login' => $login,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => 'customer',
        ) );

        if ( ! is_wp_error( $user_id ) ) {
            if ( function_exists( 'acf_save_post' ) ) {
                acf_save_post( 'user_' . $user_id );
            }
            if ( function_exists( 'pspa_ms_sync_user_names' ) ) {
                pspa_ms_sync_user_names( 'user_' . $user_id );
            }
            wc_add_notice( __( 'Ο χρήστης δημιουργήθηκε με επιτυχία.', 'pspa-membership-system' ) );

            $edit_url = add_query_arg( 'edit_user', $user_id, pspa_ms_get_graduate_profile_edit_url() );
            wp_safe_redirect( $edit_url );
            exit;
        } else {
            pspa_ms_log( 'Admin add user failed: ' . $user_id->get_error_message() );
            wc_add_notice( $user_id->get_error_message(), 'error' );
        }
    }

    echo '<div class="pspa-dashboard pspa-admin-add-user">';
    $search_url = pspa_ms_get_graduate_profile_edit_url();
    echo '<p><a href="' . esc_url( $search_url ) . '">&larr; ' . esc_html__( 'Επιστροφή στην αναζήτηση', 'pspa-membership-system' ) . '</a></p>';
    ?>
    <form method="post">
        <?php if ( function_exists( 'acf_form' ) ) : ?>
            <div class="pspa-acf-fields">
                <?php acf_form( array(
                    'post_id'      => 'user_0',
                    'form'         => false,
                    'field_groups' => array( 'group_gn_graduate_profile' ),
                ) ); ?>
            </div>
        <?php endif; ?>
        <p class="form-row form-row-wide">
            <label for="email"><?php esc_html_e( 'Διεύθυνση E-mail', 'pspa-membership-system' ); ?></label>
            <input type="email" name="email" id="email" value="" />
        </p>
        <p class="form-row form-row-wide">
            <label for="password"><?php esc_html_e( 'Κωδικός', 'pspa-membership-system' ); ?></label>
            <span class="password-input">
                <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="new-password" />
            </span>
        </p>
        <?php wp_nonce_field( 'pspa_admin_add_user', 'pspa_admin_add_user_nonce' ); ?>
        <p>
            <button type="submit" class="woocommerce-Button button"><?php esc_html_e( 'Δημιουργία χρήστη', 'pspa-membership-system' ); ?></button>
        </p>
    </form>
    <?php
    echo '</div>';
}

/**
 * Prevent canonical redirects from stripping the login details status flag.
 *
 * WordPress canonical redirects can drop unknown query parameters, causing the
 * `login-details` flag to disappear and the SweetAlert popup to never render.
 * When the flag is present and the redirect target omits it we cancel the
 * redirect so the original URL loads intact.
 *
 * @param string|false $redirect_url  Canonical URL or false to abort redirect.
 * @param string       $requested_url Requested URL.
 * @return string|false
 */
function pspa_ms_preserve_login_details_query( $redirect_url, $requested_url ) {
    if ( false === $redirect_url || ! isset( $_GET['login-details'] ) ) {
        return $redirect_url;
    }

    $requested_parts = wp_parse_url( $requested_url );
    if ( empty( $requested_parts['query'] ) ) {
        return $redirect_url;
    }

    $requested_query = array();
    parse_str( $requested_parts['query'], $requested_query );

    if ( ! array_key_exists( 'login-details', $requested_query ) ) {
        return $redirect_url;
    }

    $login_status = $requested_query['login-details'];
    if ( ! in_array( $login_status, array( 'failed', 'verified' ), true ) ) {
        return $redirect_url;
    }

    $redirect_parts = wp_parse_url( $redirect_url );
    $redirect_query = array();

    if ( isset( $redirect_parts['query'] ) ) {
        parse_str( $redirect_parts['query'], $redirect_query );
    }

    if ( ! array_key_exists( 'login-details', $redirect_query ) || $redirect_query['login-details'] !== $login_status ) {
        $failure_url   = pspa_ms_get_login_details_failure_redirect_url();
        $failure_parts = $failure_url ? wp_parse_url( $failure_url ) : array();
        $redirect_path = isset( $redirect_parts['path'] ) ? rawurldecode( untrailingslashit( $redirect_parts['path'] ) ) : '';
        $failure_path  = isset( $failure_parts['path'] ) ? rawurldecode( untrailingslashit( $failure_parts['path'] ) ) : '';

        if ( $failure_path && $redirect_path === $failure_path ) {
            return $redirect_url;
        }

        return false;
    }

    return $redirect_url;
}
add_filter( 'redirect_canonical', 'pspa_ms_preserve_login_details_query', 10, 2 );

/**
 * Redirect login-by-details failures to a dedicated information page.
 */
function pspa_ms_redirect_login_details_failures() {
    if ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
        return;
    }

    if ( ! isset( $_GET['login-details'] ) || 'failed' !== $_GET['login-details'] ) {
        return;
    }

    $target_url = pspa_ms_get_login_details_failure_redirect_url();
    if ( empty( $target_url ) ) {
        return;
    }

    $request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $request_path = $request_uri ? wp_parse_url( $request_uri, PHP_URL_PATH ) : '';
    $request_path = $request_path ? rawurldecode( untrailingslashit( $request_path ) ) : '';
    $target_path  = wp_parse_url( $target_url, PHP_URL_PATH );
    $target_path  = $target_path ? rawurldecode( untrailingslashit( $target_path ) ) : '';

    if ( $target_path && $request_path === $target_path ) {
        return;
    }

    wp_safe_redirect( $target_url );
    exit;
}
add_action( 'template_redirect', 'pspa_ms_redirect_login_details_failures', 11 );

/**
 * Handle login submissions before output is sent.
 */
function pspa_ms_handle_login_by_details() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        return;
    }

    if ( is_user_logged_in() ) {
        pspa_ms_log( 'Login-by-details aborted: user already logged in' );
        return;
    }

    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        return;
    }

    pspa_ms_log( 'Login-by-details POST detected' );

    $nonce = isset( $_POST['pspa_login_details_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['pspa_login_details_nonce'] ) )
        : '';

    if ( empty( $nonce ) ) {
        pspa_ms_log( 'Login-by-details aborted: missing nonce' );
        return;
    }

    if ( ! wp_verify_nonce( $nonce, 'pspa_login_details' ) ) {
        pspa_ms_log( 'Login-by-details aborted: invalid nonce' );
        return;
    }

    $first = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
    $last  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
    $year  = isset( $_POST['graduation_year'] ) ? sanitize_text_field( wp_unslash( $_POST['graduation_year'] ) ) : '';

    pspa_ms_log( sprintf( 'Login attempt: %s %s (%s)', $first, $last, $year ) );
    pspa_ms_log( sprintf( 'Sanitized fields: first="%s", last="%s", year="%s"', $first, $last, $year ) );

    $full_name        = trim( $first . ' ' . $last );
    $normalized_full  = pspa_ms_normalize_name( $full_name );
    pspa_ms_log( 'Normalized full name: ' . $normalized_full );

    global $wpdb;

    $meta_expr = "UPPER(CONCAT(fn.meta_value, ' ', ln.meta_value))";
    $replacements = array( 'Ά'=>'Α', 'Έ'=>'Ε', 'Ή'=>'Η', 'Ί'=>'Ι', 'Ό'=>'Ο', 'Ύ'=>'Υ', 'Ώ'=>'Ω', 'Ϊ'=>'Ι', 'Ϋ'=>'Υ' );
    foreach ( $replacements as $search => $replace ) {
        $meta_expr = "REPLACE({$meta_expr}, '{$search}', '{$replace}')";
    }

    $sql = "SELECT fn.user_id
            FROM {$wpdb->usermeta} fn
            INNER JOIN {$wpdb->usermeta} ln ON fn.user_id = ln.user_id
            INNER JOIN {$wpdb->usermeta} gr ON fn.user_id = gr.user_id
            WHERE fn.meta_key = 'gn_first_name'
              AND ln.meta_key = 'gn_surname'
              AND gr.meta_key = 'gn_graduation_year'
              AND {$meta_expr} = %s
              AND gr.meta_value = %s
            LIMIT 1";

    pspa_ms_log( 'Lookup SQL: ' . $sql );
    $user_id = $wpdb->get_var( $wpdb->prepare( $sql, $normalized_full, $year ) );

    pspa_ms_log( 'User ID query result: ' . ( $user_id ? $user_id : 'none' ) );

    if ( $user_id ) {
        $verified = get_user_meta( $user_id, 'gn_login_verified_date', true );
        pspa_ms_log( 'Existing verification date for user ' . $user_id . ': ' . ( $verified ? $verified : 'none' ) );
        if ( $verified ) {
            pspa_ms_log( 'Login blocked: user already verified' );
            $redirect = pspa_ms_get_graduate_profile_edit_url();
            pspa_ms_log( 'Redirecting verified user to graduate profile: ' . $redirect );
            wp_safe_redirect( $redirect );
            exit;
        }

        $user = get_user_by( 'id', $user_id );
        if ( $user ) {
            pspa_ms_log( 'Login success for user ID ' . $user->ID . ' (email: ' . $user->user_email . ')' );
            wp_clear_auth_cookie();
            pspa_ms_log( 'User logged in before auth cookie: ' . ( is_user_logged_in() ? 'true' : 'false' ) );
            // Ensure the auth cookie respects the current SSL state so that
            // browsers do not reject it when the site forces HTTPS.
            wp_set_auth_cookie( $user->ID, true, is_ssl() );
            pspa_ms_log( 'Auth cookie set using SSL: ' . ( is_ssl() ? 'true' : 'false' ) );
            wp_set_current_user( $user->ID, $user->user_login );
            if ( function_exists( 'wc_set_customer_auth_cookie' ) ) {
                wc_set_customer_auth_cookie( $user->ID );
                pspa_ms_log( 'wc_set_customer_auth_cookie called for user ' . $user->ID );
            } else {
                pspa_ms_log( 'wc_set_customer_auth_cookie unavailable' );
            }
            pspa_ms_log( 'User logged in status after auth cookie: ' . ( is_user_logged_in() ? 'true' : 'false' ) );
            do_action( 'wp_login', $user->user_login, $user );
            wp_safe_redirect( add_query_arg( 'edit_user', $user->ID, pspa_ms_get_graduate_profile_edit_url() ) );
            exit;
        } else {
            pspa_ms_log( 'User lookup failed for ID ' . $user_id );
        }
    }

    pspa_ms_log( 'Login failed: no matching user' );
    $failure_url = pspa_ms_get_login_details_failure_redirect_url();

    if ( $failure_url ) {
        pspa_ms_log( 'Redirecting failed login to: ' . $failure_url );
        wp_safe_redirect( $failure_url );
        exit;
    }

    $referer = wp_get_referer() ? wp_get_referer() : home_url();
    pspa_ms_log( 'Failure page unavailable, redirecting back to referer: ' . $referer );
    wp_safe_redirect( add_query_arg( 'login-details', 'failed', $referer ) );
    exit;
}
add_action( 'template_redirect', 'pspa_ms_handle_login_by_details' );

/**
 * Shortcode: login by details.
 *
 * @return string
 */
function pspa_ms_login_by_details_shortcode() {
    if ( is_user_logged_in() ) {
        return '<p>' . esc_html__( 'Είστε ήδη επαληθευμένοι.', 'pspa-membership-system' ) . '</p>';
    }

    pspa_ms_enqueue_dashboard_styles();

    $output = '';

    if ( isset( $_GET['login-details'] ) ) {
        if ( 'failed' === $_GET['login-details'] ) {
            wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true );
            $title   = esc_js( __( 'Μη έγκυρα στοιχεία', 'pspa-membership-system' ) );
            $message = esc_js( __( 'Τα στοιχεία που εισαγάγατε είναι λανθασμένα. Παρακαλούμε δοκιμάστε ξανά την επαλήθευση.', 'pspa-membership-system' ) );
            $script  = 'document.addEventListener("DOMContentLoaded", function(){Swal.fire({icon:"error",title:"' . $title . '",text:"' . $message . '"});});';
            wp_add_inline_script( 'sweetalert2', $script );
            $output .= '<p>' . esc_html__( 'Τα στοιχεία που εισαγάγατε είναι λανθασμένα. Παρακαλούμε δοκιμάστε ξανά την επαλήθευση.', 'pspa-membership-system' ) . '</p>';
        } elseif ( 'verified' === $_GET['login-details'] ) {
            $email      = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
            $login_url  = wp_login_url();
            $reset_url  = wp_lostpassword_url();
            wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true );
            $title   = esc_js( __( 'Ήδη επαληθευμένοι', 'pspa-membership-system' ) );
            $message = sprintf(
                'Ο λογαριασμός με email <strong>%s</strong> έχει ήδη επαληθευτεί. Παρακαλούμε <a href="%s">συνδεθείτε</a> ή <a href="%s">επαναφέρετε τον κωδικό σας</a> αν τον ξεχάσατε.',
                esc_html( $email ),
                esc_url( $login_url ),
                esc_url( $reset_url )
            );
            $script = 'document.addEventListener("DOMContentLoaded", function(){Swal.fire({icon:"info",title:"' . $title . '",html:"' . esc_js( $message ) . '"});});';
            wp_add_inline_script( 'sweetalert2', $script );
            $output .= '<p>' . esc_html__( 'Έχετε ήδη επαληθευτεί.', 'pspa-membership-system' ) . '</p>';
        }
    }

    ob_start();
    ?>
    <form method="post" class="pspa-login-by-details pspa-dashboard">
        <p class="form-row form-row-wide">
            <label for="first_name"><?php esc_html_e( 'Όνομα', 'pspa-membership-system' ); ?></label>
            <input type="text" name="first_name" id="first_name" required />
        </p>
        <p class="form-row form-row-wide">
            <label for="last_name"><?php esc_html_e( 'Επώνυμο', 'pspa-membership-system' ); ?></label>
            <input type="text" name="last_name" id="last_name" required />
        </p>
        <p class="form-row form-row-wide">
            <label for="graduation_year"><?php esc_html_e( 'Έτος Αποφοίτησης', 'pspa-membership-system' ); ?></label>
            <input type="text" name="graduation_year" id="graduation_year" required />
        </p>
        <?php wp_nonce_field( 'pspa_login_details', 'pspa_login_details_nonce' ); ?>
        <p>
            <button type="submit" class="woocommerce-Button button"><?php esc_html_e( 'Σύνδεση', 'pspa-membership-system' ); ?></button>
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
    add_shortcode( 'pspa_graduate_finder', 'pspa_ms_graduate_finder_shortcode' );
    add_shortcode( 'pspa_graduate_directory', 'pspa_ms_graduate_directory_shortcode' );
}
add_action( 'init', 'pspa_ms_register_shortcodes' );

/**
 * Register the admin page for viewing PSPA logs.
 */
function pspa_ms_register_logs_page() {
    add_menu_page(
        __( 'PSPA Logs', 'pspa-membership-system' ),
        __( 'PSPA Logs', 'pspa-membership-system' ),
        'manage_options',
        'pspa-ms-logs',
        'pspa_ms_render_logs_page',
        'dashicons-list-view',
        99
    );
}
add_action( 'admin_menu', 'pspa_ms_register_logs_page' );

/**
 * Render the logs page.
 */
function pspa_ms_render_logs_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $cleared = false;
    $reset   = false;

    if ( isset( $_POST['pspa_ms_clear_logs'] ) ) {
        check_admin_referer( 'pspa_ms_clear_logs', 'pspa_ms_clear_logs_nonce' );
        delete_option( 'pspa_ms_logs' );
        $cleared = true;
    }

    if ( isset( $_POST['pspa_ms_reset_settings'] ) ) {
        check_admin_referer( 'pspa_ms_reset_settings', 'pspa_ms_reset_settings_nonce' );
        pspa_ms_reset_settings();
        $reset = true;
    }

    $logs = get_option( 'pspa_ms_logs', array() );

    echo '<div class="wrap"><h1>' . esc_html__( 'PSPA Logs', 'pspa-membership-system' ) . '</h1>';

    if ( $cleared ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Logs cleared.', 'pspa-membership-system' ) . '</p></div>';
    }

    if ( $reset ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'PSPA Membership System settings reset.', 'pspa-membership-system' ) . '</p></div>';
    }

    echo '<form method="post" style="margin-bottom:1em;">';
    wp_nonce_field( 'pspa_ms_clear_logs', 'pspa_ms_clear_logs_nonce' );
    wp_nonce_field( 'pspa_ms_reset_settings', 'pspa_ms_reset_settings_nonce' );
    submit_button( __( 'Clear Logs', 'pspa-membership-system' ), 'secondary', 'pspa_ms_clear_logs', false );
    submit_button( __( 'Reset PSPA Settings', 'pspa-membership-system' ), 'secondary', 'pspa_ms_reset_settings', false );
    echo '</form>';

    if ( empty( $logs ) ) {
        echo '<p>' . esc_html__( 'No log entries found.', 'pspa-membership-system' ) . '</p></div>';
        return;
    }

    echo '<table class="widefat"><thead><tr><th>' . esc_html__( 'Time', 'pspa-membership-system' ) . '</th><th>' . esc_html__( 'Level', 'pspa-membership-system' ) . '</th><th>' . esc_html__( 'Message', 'pspa-membership-system' ) . '</th></tr></thead><tbody>';

    foreach ( $logs as $log ) {
        $msg = $log['message'];
        if ( ! empty( $log['context'] ) ) {
            $msg .= ' ' . wp_json_encode( $log['context'] );
        }
        echo '<tr><td>' . esc_html( $log['time'] ) . '</td><td>' . esc_html( strtoupper( $log['level'] ) ) . '</td><td>' . esc_html( $msg ) . '</td></tr>';
    }

    echo '</tbody></table></div>';
}

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
 * Prevent editing of the Initial DB ID field.
 *
 * @param array $field Field settings.
 * @return array
 */
function pspa_ms_lock_initial_db_id_field( $field ) {
    $field['readonly'] = true;
    $field['disabled'] = true;
    return $field;
}
add_filter( 'acf/prepare_field/name=gn_initial_db_id', 'pspa_ms_lock_initial_db_id_field' );

/**
 * Preserve the Initial DB ID value once set.
 *
 * @param mixed $value   Proposed value.
 * @param mixed $post_id Post ID.
 * @param array $field   Field settings.
 * @return mixed
 */
function pspa_ms_preserve_initial_db_id( $value, $post_id, $field ) {
    $user_id = is_string( $post_id ) && 0 === strpos( $post_id, 'user_' )
        ? (int) substr( $post_id, 5 )
        : (int) $post_id;

    $existing = get_user_meta( $user_id, 'gn_initial_db_id', true );
    return '' === $existing ? $value : $existing;
}
add_filter( 'acf/update_value/name=gn_initial_db_id', 'pspa_ms_preserve_initial_db_id', 10, 3 );

/**
 * Prevent editing of the Login Verification Date field.
 *
 * @param array $field Field settings.
 * @return array
 */
function pspa_ms_lock_login_verified_date_field( $field ) {
    $field['readonly'] = true;
    $field['disabled'] = true;
    return $field;
}
add_filter( 'acf/prepare_field/name=gn_login_verified_date', 'pspa_ms_lock_login_verified_date_field' );

/**
 * Preserve the Login Verification Date once set.
 *
 * @param mixed $value   Proposed value.
 * @param mixed $post_id Post ID.
 * @param array $field   Field settings.
 * @return mixed
 */
function pspa_ms_preserve_login_verified_date( $value, $post_id, $field ) {
    $user_id = is_string( $post_id ) && 0 === strpos( $post_id, 'user_' )
        ? (int) substr( $post_id, 5 )
        : (int) $post_id;

    $existing = get_user_meta( $user_id, 'gn_login_verified_date', true );
    return '' === $existing ? $value : $existing;
}
add_filter( 'acf/update_value/name=gn_login_verified_date', 'pspa_ms_preserve_login_verified_date', 10, 3 );

/**
 * Determine the next available Initial DB ID.
 *
 * Ensures continuity with any IDs that may have been imported.
 *
 * @return int Next ID.
 */
function pspa_ms_get_next_initial_db_id() {
    global $wpdb;

    $next = (int) get_option( 'pspa_ms_next_initial_db_id', 1 );
    $max  = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT MAX(meta_value+0) FROM {$wpdb->usermeta} WHERE meta_key = %s",
            'gn_initial_db_id'
        )
    );

    if ( $max >= $next ) {
        $next = $max + 1;
    }

    update_option( 'pspa_ms_next_initial_db_id', $next + 1 );

    return $next;
}

/**
 * Assign incremental Initial DB ID to new users.
 *
 * @param int $user_id New user ID.
 */
function pspa_ms_assign_initial_db_id( $user_id ) {
    if ( get_user_meta( $user_id, 'gn_initial_db_id', true ) ) {
        return;
    }

    $next = pspa_ms_get_next_initial_db_id();
    update_user_meta( $user_id, 'gn_initial_db_id', $next );
}
add_action( 'user_register', 'pspa_ms_assign_initial_db_id' );

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
    if (
        isset( $user->ID ) && (
            in_array( 'system-admin', (array) $user->roles, true ) ||
            in_array( 'sysadmin', (array) $user->roles, true ) ||
            in_array( 'professionalcatalogue', (array) $user->roles, true )
        )
    ) {
        return add_query_arg( 'edit_user', $user->ID, pspa_ms_get_graduate_profile_edit_url() );
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
        if (
            in_array( 'system-admin', (array) $user->roles, true ) ||
            in_array( 'sysadmin', (array) $user->roles, true ) ||
            in_array( 'professionalcatalogue', (array) $user->roles, true )
        ) {
            wp_safe_redirect( add_query_arg( 'edit_user', $user->ID, pspa_ms_get_graduate_profile_edit_url() ) );
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
    $can_view_hidden = pspa_ms_current_user_can_manage_directory_visibility();

    if ( $can_view_hidden ) {
        $sql    = "SELECT DISTINCT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value <> '' ORDER BY meta_value ASC";
        $params = array( $meta_key );
    } else {
        $sql = "SELECT DISTINCT m.meta_value
            FROM {$wpdb->usermeta} m
            INNER JOIN {$wpdb->usermeta} vis ON m.user_id = vis.user_id
            WHERE m.meta_key = %s
              AND m.meta_value <> ''
              AND vis.meta_key = %s
              AND vis.meta_value = %s
            ORDER BY m.meta_value ASC";
        $params = array( $meta_key, 'gn_directory_visible', '1' );
    }

    return $wpdb->get_col( $wpdb->prepare( $sql, $params ) );
}

/**
 * Get the Graduate Profile dashboard URL.
 *
 * Builds the URL to the `graduate-profile` My Account endpoint. Falls back to
 * a query-arg based URL when the endpoint matches the My Account page slug,
 * avoiding canonical redirects that drop the endpoint.
 *
 * @return string
 */
function pspa_ms_get_graduate_profile_edit_url() {
    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );

    if ( function_exists( 'wc_get_endpoint_url' ) ) {
        $url = wc_get_endpoint_url( 'graduate-profile', '', $account_url );
    } else {
        $url = trailingslashit( $account_url ) . 'graduate-profile/';
    }

    if ( untrailingslashit( $url ) === untrailingslashit( $account_url ) ) {
        $url = add_query_arg( 'graduate-profile', '', $account_url );
    }

    return $url;
}

/**
 * Render a graduate profile card.
 *
 * @param int $user_id User ID.
 * @return string
 */
function pspa_ms_get_public_profile_url( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return '';
    }
    return home_url( '/graduate/' . $user->user_nicename . '/' );
}

/**
 * Render a graduate profile card.
 *
 * @param int   $user_id User ID.
 * @param array $args    {
 *     Optional. Additional arguments.
 *
 *     @type string|array $extra_classes Extra CSS classes to append to the card wrapper.
 * }
 * @return string
 */
function pspa_ms_render_graduate_card( $user_id, $args = array() ) {
    if ( ! pspa_ms_current_user_can_manage_directory_visibility() && ! pspa_ms_user_is_visible_in_directory( $user_id ) ) {
        return '';
    }

    $defaults = array(
        'extra_classes' => array(),
    );

    $args = wp_parse_args( $args, $defaults );

    if ( is_string( $args['extra_classes'] ) ) {
        $args['extra_classes'] = preg_split( '/\s+/', $args['extra_classes'] );
    }

    if ( ! is_array( $args['extra_classes'] ) ) {
        $args['extra_classes'] = array();
    }

    $card_classes = array( 'pspa-graduate-card' );

    foreach ( $args['extra_classes'] as $class ) {
        if ( ! $class ) {
            continue;
        }

        $card_classes[] = sanitize_html_class( $class );
    }

    $card_classes = array_unique( array_filter( $card_classes ) );

    $first      = function_exists( 'get_field' ) ? (string) get_field( 'gn_first_name', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_first_name', true );
    $last       = function_exists( 'get_field' ) ? (string) get_field( 'gn_surname', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_surname', true );
    $name       = trim( $first . ' ' . $last );
    $job        = function_exists( 'get_field' ) ? (string) get_field( 'gn_job_title', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_job_title', true );
    $company    = function_exists( 'get_field' ) ? (string) get_field( 'gn_position_company', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_position_company', true );
    $profession = function_exists( 'get_field' ) ? (string) get_field( 'gn_profession', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_profession', true );
    $city       = function_exists( 'get_field' ) ? (string) get_field( 'gn_city', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_city', true );
    $country    = function_exists( 'get_field' ) ? (string) get_field( 'gn_country', 'user_' . $user_id ) : get_user_meta( $user_id, 'gn_country', true );

    $profile_url  = pspa_ms_get_public_profile_url( $user_id );
    $current_user = wp_get_current_user();
    $can_edit     = current_user_can( 'manage_options' ) ||
        in_array( 'system-admin', (array) $current_user->roles, true ) ||
        in_array( 'sysadmin', (array) $current_user->roles, true );

    if ( $can_edit ) {
        $edit_url = add_query_arg( 'edit_user', $user_id, pspa_ms_get_graduate_profile_edit_url() );
    }

    ob_start();
    ?>
    <div class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>">
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
            <div class="pspa-graduate-actions">
                <a class="pspa-graduate-more" href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'Δείτε Περισσότερα', 'pspa-membership-system' ); ?></a>
                <?php if ( $can_edit ) : ?>
                    <a class="pspa-graduate-edit" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Επεξεργασία', 'pspa-membership-system' ); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a graduate card for the graduate finder.
 *
 * @param int $user_id User ID.
 * @return string
 */
function pspa_ms_render_graduate_finder_card( $user_id ) {
    return pspa_ms_render_graduate_card(
        $user_id,
        array(
            'extra_classes' => 'pspa-graduate-card--finder',
        )
    );
}

/**
 * Shortcode callback for the graduate finder.
 *
 * @return string
 */
function pspa_ms_graduate_finder_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>' . esc_html__( 'Πρέπει να είστε συνδεδεμένοι για να αναζητήσετε αποφοίτους.', 'pspa-membership-system' ) . '</p>';
    }

    pspa_ms_enqueue_dashboard_styles();

    wp_enqueue_style( 'pspa-ms-graduate-directory', plugin_dir_url( __FILE__ ) . 'assets/css/graduate-directory.css', array(), PSPA_MS_VERSION );
    wp_enqueue_style( 'pspa-ms-graduate-finder', plugin_dir_url( __FILE__ ) . 'assets/css/graduate-finder.css', array( 'pspa-ms-graduate-directory' ), PSPA_MS_VERSION );
    wp_enqueue_script( 'pspa-ms-graduate-finder', plugin_dir_url( __FILE__ ) . 'assets/js/graduate-finder.js', array( 'jquery' ), PSPA_MS_VERSION, true );

    wp_localize_script(
        'pspa-ms-graduate-finder',
        'pspaMsFinder',
        array(
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'pspa_ms_finder' ),
            'errorMessage' => esc_html__( 'Παρουσιάστηκε σφάλμα κατά τη φόρτωση των αποφοίτων.', 'pspa-membership-system' ),
        )
    );

    $form_id    = wp_unique_id( 'pspa-graduate-finder-form-' );
    $results_id = wp_unique_id( 'pspa-graduate-finder-results-' );

    ob_start();
    ?>
    <div class="pspa-graduate-finder pspa-dashboard">
        <form id="<?php echo esc_attr( $form_id ); ?>" class="pspa-graduate-finder__filters" novalidate>
            <label class="pspa-graduate-finder__field" for="<?php echo esc_attr( $form_id ); ?>-first">
                <span class="pspa-graduate-finder__label"><?php esc_html_e( 'Όνομα', 'pspa-membership-system' ); ?></span>
                <input type="text" id="<?php echo esc_attr( $form_id ); ?>-first" name="first_name" placeholder="<?php esc_attr_e( 'Όνομα', 'pspa-membership-system' ); ?>" autocomplete="off" />
            </label>
            <label class="pspa-graduate-finder__field" for="<?php echo esc_attr( $form_id ); ?>-last">
                <span class="pspa-graduate-finder__label"><?php esc_html_e( 'Επώνυμο', 'pspa-membership-system' ); ?></span>
                <input type="text" id="<?php echo esc_attr( $form_id ); ?>-last" name="last_name" placeholder="<?php esc_attr_e( 'Επώνυμο', 'pspa-membership-system' ); ?>" autocomplete="off" />
            </label>
            <label class="pspa-graduate-finder__field" for="<?php echo esc_attr( $form_id ); ?>-year">
                <span class="pspa-graduate-finder__label"><?php esc_html_e( 'Έτος Αποφοίτησης', 'pspa-membership-system' ); ?></span>
                <input type="text" id="<?php echo esc_attr( $form_id ); ?>-year" name="graduation_year" placeholder="<?php esc_attr_e( 'Έτος Αποφοίτησης', 'pspa-membership-system' ); ?>" inputmode="numeric" autocomplete="off" />
            </label>
        </form>
        <div id="<?php echo esc_attr( $results_id ); ?>" class="pspa-graduate-finder__results">
            <p class="pspa-graduate-finder__status"><?php esc_html_e( 'Φόρτωση...', 'pspa-membership-system' ); ?></p>
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
        return '<p>' . esc_html__( 'Πρέπει να είστε συνδεδεμένοι για να δείτε τον κατάλογο αποφοίτων.', 'pspa-membership-system' ) . '</p>';
    }

    pspa_ms_enqueue_dashboard_styles();

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
    <div class="pspa-graduate-directory pspa-dashboard">
        <form id="pspa-graduate-filters">
            <input type="text" name="full_name" placeholder="<?php esc_attr_e( 'Πλήρες Όνομα', 'pspa-membership-system' ); ?>" />
            <input type="text" name="graduation_year" placeholder="<?php esc_attr_e( 'Έτος Αποφοίτησης', 'pspa-membership-system' ); ?>" />
            <select name="profession">
                <option value=""><?php esc_html_e( 'Όλα τα Επαγγέλματα', 'pspa-membership-system' ); ?></option>
                <?php foreach ( $professions as $p ) : ?>
                    <option value="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="job_title">
                <option value=""><?php esc_html_e( 'Όλοι οι Τίτλοι Εργασίας', 'pspa-membership-system' ); ?></option>
                <?php foreach ( $jobs as $j ) : ?>
                    <option value="<?php echo esc_attr( $j ); ?>"><?php echo esc_html( $j ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="city">
                <option value=""><?php esc_html_e( 'Όλες οι Πόλεις', 'pspa-membership-system' ); ?></option>
                <?php foreach ( $cities as $c ) : ?>
                    <option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="country">
                <option value=""><?php esc_html_e( 'Όλες οι Χώρες', 'pspa-membership-system' ); ?></option>
                <?php foreach ( $countries as $co ) : ?>
                    <option value="<?php echo esc_attr( $co ); ?>"><?php echo esc_html( $co ); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div id="pspa-graduate-results"><p><?php esc_html_e( 'Φόρτωση...', 'pspa-membership-system' ); ?></p></div>
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
        'profession'      => 'gn_profession',
        'job_title'       => 'gn_job_title',
        'city'            => 'gn_city',
        'country'         => 'gn_country',
        'graduation_year' => 'gn_graduation_year',
    );

    $meta_query = array( 'relation' => 'AND' );
    $can_view_hidden = pspa_ms_current_user_can_manage_directory_visibility();

    if ( ! $can_view_hidden ) {
        $meta_query[] = array(
            'key'   => 'gn_directory_visible',
            'value' => '1',
        );
    }

    foreach ( $fields as $request => $key ) {
        if ( ! empty( $_POST[ $request ] ) ) {
            $meta_query[] = array(
                'key'   => $key,
                'value' => sanitize_text_field( wp_unslash( $_POST[ $request ] ) ),
            );
        }
    }

    if ( ! empty( $_POST['full_name'] ) ) {
        $full_name = sanitize_text_field( wp_unslash( $_POST['full_name'] ) );
        $parts     = preg_split( '/\s+/u', $full_name );

        $compare = $can_view_hidden ? '=' : 'LIKE';

        $name_query = array( 'relation' => 'AND' );

        foreach ( $parts as $part ) {
            if ( '' === $part ) {
                continue;
            }

            $name_query[] = array(
                'relation' => 'OR',
                array(
                    'key'     => 'gn_first_name',
                    'value'   => $part,
                    'compare' => $compare,
                ),
                array(
                    'key'     => 'gn_surname',
                    'value'   => $part,
                    'compare' => $compare,
                ),
            );
        }

        if ( count( $name_query ) > 1 ) {
            $meta_query[] = $name_query;
        }
    }

    $page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
    $per_page = 50;
    $args     = array(
        'number'     => $per_page,
        'offset'     => ( $page - 1 ) * $per_page,
        'meta_query' => $meta_query,
        'count_total'=> true,
    );

    $users       = new WP_User_Query( $args );
    $total_users = (int) $users->get_total();
    $total_pages = (int) ceil( $total_users / $per_page );
    $html        = '';

    if ( ! empty( $users->get_results() ) ) {
        foreach ( $users->get_results() as $user ) {
            $html .= pspa_ms_render_graduate_card( $user->ID );
        }

        if ( $total_pages > 1 ) {
            $html .= '<nav class="pspa-dir-pagination">';
            if ( $page > 1 ) {
                $html .= '<a href="#" class="prev" data-page="' . ( $page - 1 ) . '">&laquo; ' . esc_html__( 'Προηγούμενη', 'pspa-membership-system' ) . '</a>';
            }
            $html .= '<span class="current">' . sprintf( esc_html__( 'Σελίδα %1$d από %2$d', 'pspa-membership-system' ), $page, $total_pages ) . '</span>';
            if ( $page < $total_pages ) {
                $html .= '<a href="#" class="next" data-page="' . ( $page + 1 ) . '">' . esc_html__( 'Επόμενη', 'pspa-membership-system' ) . ' &raquo;</a>';
            }
            $html .= '</nav>';
        }
    } else {
        $html = '<p>' . esc_html__( 'Δεν βρέθηκαν απόφοιτοι.', 'pspa-membership-system' ) . '</p>';
    }

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_pspa_ms_filter_graduates', 'pspa_ms_ajax_filter_graduates' );

/**
 * AJAX handler for graduate finder searches.
 */
function pspa_ms_ajax_filter_graduate_finder() {
    check_ajax_referer( 'pspa_ms_finder', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error();
    }

    $meta_query      = array( 'relation' => 'AND' );
    $can_view_hidden = pspa_ms_current_user_can_manage_directory_visibility();

    if ( ! $can_view_hidden ) {
        $meta_query[] = array(
            'key'   => 'gn_directory_visible',
            'value' => '1',
        );
    }

    $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
    if ( '' !== $first_name ) {
        $meta_query[] = array(
            'key'     => 'gn_first_name',
            'value'   => $first_name,
            'compare' => 'LIKE',
        );
    }

    $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
    if ( '' !== $last_name ) {
        $meta_query[] = array(
            'key'     => 'gn_surname',
            'value'   => $last_name,
            'compare' => 'LIKE',
        );
    }

    $graduation_year = isset( $_POST['graduation_year'] ) ? sanitize_text_field( wp_unslash( $_POST['graduation_year'] ) ) : '';
    if ( '' !== $graduation_year ) {
        $meta_query[] = array(
            'key'     => 'gn_graduation_year',
            'value'   => $graduation_year,
            'compare' => 'LIKE',
        );
    }

    $page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
    $per_page = 12;

    $query_args = array(
        'number'      => $per_page,
        'offset'      => ( $page - 1 ) * $per_page,
        'meta_query'  => $meta_query,
        'count_total' => true,
        'orderby'     => 'display_name',
        'order'       => 'ASC',
        'role__in'    => array( 'professionalcatalogue' ),
    );

    $users_query = new WP_User_Query( $query_args );
    $results     = $users_query->get_results();
    $total_users = (int) $users_query->get_total();
    $total_pages = (int) ceil( $total_users / $per_page );
    $html        = '';

    if ( ! empty( $results ) ) {
        foreach ( $results as $user ) {
            $html .= pspa_ms_render_graduate_finder_card( $user->ID );
        }

        if ( '' === trim( $html ) ) {
            $html = '<p>' . esc_html__( 'Δεν βρέθηκαν απόφοιτοι.', 'pspa-membership-system' ) . '</p>';
        } elseif ( $total_pages > 1 ) {
            $html .= '<nav class="pspa-finder-pagination" aria-label="' . esc_attr__( 'Σελιδοποίηση αποφοίτων', 'pspa-membership-system' ) . '">';
            if ( $page > 1 ) {
                $html .= '<a href="#" class="prev" data-page="' . ( $page - 1 ) . '">&laquo; ' . esc_html__( 'Προηγούμενη', 'pspa-membership-system' ) . '</a>';
            }
            $html .= '<span class="current">' . sprintf( esc_html__( 'Σελίδα %1$d από %2$d', 'pspa-membership-system' ), $page, $total_pages ) . '</span>';
            if ( $page < $total_pages ) {
                $html .= '<a href="#" class="next" data-page="' . ( $page + 1 ) . '">' . esc_html__( 'Επόμενη', 'pspa-membership-system' ) . ' &raquo;</a>';
            }
            $html .= '</nav>';
        }
    } else {
        $html = '<p>' . esc_html__( 'Δεν βρέθηκαν απόφοιτοι.', 'pspa-membership-system' ) . '</p>';
    }

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_pspa_ms_filter_graduate_finder', 'pspa_ms_ajax_filter_graduate_finder' );

/**
 * Flush rewrite rules on activation and deactivation.
 */
function pspa_ms_flush_rewrite_rules() {
    pspa_ms_register_graduate_profile_endpoint();
    pspa_ms_register_public_profile_route();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pspa_ms_flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

