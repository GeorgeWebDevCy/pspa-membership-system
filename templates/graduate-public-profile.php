<?php
/**
 * Template for public graduate profiles.
 *
 * Displays the graduate profile in a LinkedIn-style layout.
 * Displays the graduate profile using the same field layout as the
 * `graduate-profile` My Account endpoint but in read-only mode.
 *
 * @package PSPA\MembershipSystem
 */

$pspa_user = get_query_var( 'pspa_graduate_user' );
$profile_view = get_query_var( 'pspa_profile_view' );
if ( ! is_string( $profile_view ) ) {
    $profile_view = '';
}
$profile_view   = sanitize_key( $profile_view );
$is_finder_view = ( 'finder' === $profile_view );

$can_view_hidden = function_exists( 'pspa_ms_current_user_can_manage_directory_visibility' )
    ? pspa_ms_current_user_can_manage_directory_visibility()
    : current_user_can( 'manage_options' );

if (
    ! $pspa_user instanceof WP_User ||
    ( ! $can_view_hidden && function_exists( 'pspa_ms_user_is_visible_in_directory' ) && ! pspa_ms_user_is_visible_in_directory( $pspa_user->ID ) )
) {
    if ( ! headers_sent() ) {
        status_header( 404 );
    }
    get_header();
    echo '<div class="pspa-dashboard"><p>' . esc_html__( 'Ο απόφοιτος δεν βρέθηκε.', 'pspa-membership-system' ) . '</p></div>';
    get_footer();
    return;
}

pspa_ms_enqueue_dashboard_styles();
wp_enqueue_style(
    'pspa-graduate-profile',
    plugins_url( 'assets/css/graduate-profile.css', dirname( __DIR__ ) . '/pspa-membership-system.php' ),
    array( 'pspa-ms-dashboard' ),
    PSPA_MS_VERSION
);
get_header();

$uid       = $pspa_user->ID;
$user_key  = 'user_' . $uid;
$visibility = function_exists( 'get_field' ) ? get_field( 'gn_visibility_mode', $user_key ) : get_user_meta( $uid, 'gn_visibility_mode', true );

$fields             = function_exists( 'acf_get_fields' ) ? acf_get_fields( 'group_gn_graduate_profile' ) : array();
$header_field_names = array( 'gn_profile_picture', 'gn_first_name', 'gn_surname', 'gn_job_title', 'gn_position_company', 'gn_graduation_year', 'gn_country' );
$header             = array( 'picture' => '', 'name' => array(), 'headline' => array(), 'location' => array() );
$hide_catalogue_fields = function_exists( 'pspa_ms_current_user_is_professional_catalogue' ) && pspa_ms_current_user_is_professional_catalogue();
$catalogue_hidden_fields = $hide_catalogue_fields && function_exists( 'pspa_ms_get_professional_catalogue_hidden_fields' )
    ? pspa_ms_get_professional_catalogue_hidden_fields()
    : array();
$admin_hidden_fields = array();
if ( ! $can_view_hidden ) {
    $admin_hidden_fields = function_exists( 'pspa_ms_get_admin_only_field_names' )
        ? pspa_ms_get_admin_only_field_names()
        : array( 'gn_initial_db_id', 'gn_login_verified_date', 'gn_directory_visible', 'gn_deceased', 'gn_show_deceased' );
}

$should_show_field = static function ( $field_name ) use ( $uid, $user_key, $visibility ) {
    if ( 'hide_all' === $visibility ) {
        return false;
    }

    if ( 'show_all' === $visibility ) {
        return true;
    }

    $toggle_suffix = 0 === strpos( $field_name, 'gn_' ) ? substr( $field_name, 3 ) : $field_name;
    $toggle_name   = 'gn_show_' . $toggle_suffix;

    $show = function_exists( 'get_field' ) ? get_field( $toggle_name, $user_key ) : get_user_meta( $uid, $toggle_name, true );

    if ( null === $show || '' === $show ) {
        return true;
    }

    return (bool) $show;
};

foreach ( $header_field_names as $name ) {
    if ( ! $should_show_field( $name ) ) {
        continue;
    }

    if ( $admin_hidden_fields && in_array( $name, $admin_hidden_fields, true ) ) {
        continue;
    }
    if ( $hide_catalogue_fields && in_array( $name, $catalogue_hidden_fields, true ) ) {
        continue;
    }

    $value = function_exists( 'get_field' ) ? get_field( $name, $user_key ) : get_user_meta( $uid, $name, true );

    switch ( $name ) {
        case 'gn_profile_picture':
            $img_id = is_array( $value ) ? ( $value['ID'] ?? 0 ) : $value;
            if ( $img_id ) {
                $header['picture'] = wp_get_attachment_image( $img_id, 'medium' );
            }
            break;
        case 'gn_first_name':
        case 'gn_surname':
            if ( $value ) {
                $header['name'][] = $value;
            }
            break;
        case 'gn_job_title':
        case 'gn_position_company':
            if ( $value ) {
                $header['headline'][] = $value;
            }
            break;
        case 'gn_graduation_year':
        case 'gn_country':
            if ( $value ) {
                $header['location'][] = $value;
            }
            break;
    }
}

if ( $is_finder_view ) {
    $header['headline'] = array();
    $header['location'] = array();
}

$can_display_field = static function ( $field_name ) use ( $should_show_field, $admin_hidden_fields, $hide_catalogue_fields, $catalogue_hidden_fields ) {
    if ( ! $should_show_field( $field_name ) ) {
        return false;
    }

    if ( $admin_hidden_fields && in_array( $field_name, $admin_hidden_fields, true ) ) {
        return false;
    }

    if ( $hide_catalogue_fields && in_array( $field_name, $catalogue_hidden_fields, true ) ) {
        return false;
    }

    return true;
};

$get_raw_field_value = static function ( $field_name ) use ( $uid, $user_key ) {
    if ( function_exists( 'get_field' ) ) {
        return get_field( $field_name, $user_key );
    }

    return get_user_meta( $uid, $field_name, true );
};

$get_scalar_field_value = static function ( $field_name ) use ( $can_display_field, $get_raw_field_value ) {
    if ( ! $can_display_field( $field_name ) ) {
        return '';
    }

    $value = $get_raw_field_value( $field_name );

    if ( is_string( $value ) || is_numeric( $value ) ) {
        return (string) $value;
    }

    return '';
};

$finder_graduation_year = '';
$finder_email           = '';
$finder_mobile          = '';
$finder_home_phone      = '';

if ( $is_finder_view ) {
    $finder_graduation_year = $get_scalar_field_value( 'gn_graduation_year' );
    $finder_mobile          = $get_scalar_field_value( 'gn_mobile' );
    $finder_home_phone      = $get_scalar_field_value( 'gn_home_phone' );

    if ( $can_display_field( 'gn_email' ) ) {
        $email_value = $get_raw_field_value( 'gn_email' );

        if ( is_string( $email_value ) || is_numeric( $email_value ) ) {
            $finder_email = (string) $email_value;
        }

        if ( '' === $finder_email && $pspa_user instanceof WP_User ) {
            $finder_email = $pspa_user->user_email;
        }

        $finder_email = sanitize_email( $finder_email );
    }
}
?>
<div class="pspa-graduate-profile pspa-linkedin-profile<?php echo $is_finder_view ? ' pspa-linkedin-profile--finder' : ''; ?>">
    <div class="profile-header">
        <?php if ( $header['picture'] ) : ?>
            <div class="profile-picture"><?php echo $header['picture']; ?></div>
        <?php endif; ?>
        <div class="profile-basics">
            <?php if ( $header['name'] ) : ?>
                <h1 class="profile-name"><?php echo esc_html( implode( ' ', $header['name'] ) ); ?></h1>
            <?php endif; ?>
            <?php if ( $header['headline'] && ! $is_finder_view ) : ?>
                <p class="profile-headline"><?php echo esc_html( implode( ', ', $header['headline'] ) ); ?></p>
            <?php endif; ?>
            <?php if ( $header['location'] && ! $is_finder_view ) : ?>
                <p class="profile-location"><?php echo esc_html( implode( ', ', $header['location'] ) ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php if ( $is_finder_view ) : ?>
        <?php if ( $finder_graduation_year || $finder_email || $finder_mobile || $finder_home_phone ) : ?>
            <div class="profile-section profile-section--finder">
                <div class="profile-fields">
                    <?php if ( $finder_graduation_year ) : ?>
                        <div class="profile-field profile-field-gn_graduation_year">
                            <span class="label"><?php esc_html_e( 'Έτος Αποφοίτησης', 'pspa-membership-system' ); ?></span>
                            <span class="value"><?php echo esc_html( $finder_graduation_year ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $finder_home_phone ) : ?>
                        <div class="profile-field profile-field-gn_home_phone">
                            <span class="label"><?php esc_html_e( 'Τηλ. Κατοικίας', 'pspa-membership-system' ); ?></span>
                            <span class="value"><?php echo esc_html( $finder_home_phone ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $finder_email ) : ?>
                        <div class="profile-field profile-field-gn_email">
                            <span class="label"><?php esc_html_e( 'E-mail', 'pspa-membership-system' ); ?></span>
                            <span class="value"><a href="mailto:<?php echo esc_attr( $finder_email ); ?>"><?php echo esc_html( $finder_email ); ?></a></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $finder_mobile ) : ?>
                        <div class="profile-field profile-field-gn_mobile">
                            <span class="label"><?php esc_html_e( 'Κινητό', 'pspa-membership-system' ); ?></span>
                            <span class="value"><?php echo esc_html( $finder_mobile ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <?php
        $current_section_open = false;
        foreach ( $fields as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            if ( 'tab' === $field['type'] ) {
                if ( isset( $field['key'] ) && 'tab_gn_visibility' === $field['key'] ) {
                    if ( $current_section_open ) {
                        echo '</div></section>';
                        $current_section_open = false;
                    }
                    continue;
                }
                if ( $current_section_open ) {
                    echo '</div></section>';
                }
                echo '<section class="profile-section section-' . esc_attr( $field['name'] ) . '"><h2>' . esc_html( $field['label'] ) . '</h2><div class="profile-fields">';
                $current_section_open = true;
                continue;
            }

            if ( empty( $field['name'] ) ) {
                continue;
            }

            if ( $admin_hidden_fields && in_array( $field['name'], $admin_hidden_fields, true ) ) {
                continue;
            }

            if ( $hide_catalogue_fields && in_array( $field['name'], $catalogue_hidden_fields, true ) ) {
                continue;
            }

            if ( 0 === strpos( $field['name'], 'gn_show_' ) || 'gn_visibility_mode' === $field['name'] ) {
                continue;
            }

            if ( in_array( $field['name'], $header_field_names, true ) ) {
                continue;
            }

            if ( ! $should_show_field( $field['name'] ) ) {
                continue;
            }

            $value = function_exists( 'get_field' ) ? get_field( $field['name'], $user_key ) : get_user_meta( $uid, $field['name'], true );

            if ( 'image' === $field['type'] ) {
                $img_id     = is_array( $value ) ? ( $value['ID'] ?? 0 ) : $value;
                $value_html = $img_id ? wp_get_attachment_image( $img_id, 'medium' ) : '';
            } elseif ( 'true_false' === $field['type'] ) {
                $value_html = $value ? esc_html__( 'Yes', 'pspa-membership-system' ) : '';
            } else {
                $value_html = esc_html( (string) $value );
            }

            echo '<div class="profile-field profile-field-' . esc_attr( $field['name'] ) . '"><span class="label">' . esc_html( $field['label'] ) . '</span><span class="value">' . $value_html . '</span></div>';
        }
        if ( $current_section_open ) {
            echo '</div></section>';
        }
        ?>
    <?php endif; ?>
</div>
<?php
get_footer();

