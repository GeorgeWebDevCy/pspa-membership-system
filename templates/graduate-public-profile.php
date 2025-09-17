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

if ( ! $pspa_user instanceof WP_User ) {
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

$uid        = $pspa_user->ID;
$visibility = function_exists( 'get_field' ) ? get_field( 'gn_visibility_mode', 'user_' . $uid ) : get_user_meta( $uid, 'gn_visibility_mode', true );

$fields             = function_exists( 'acf_get_fields' ) ? acf_get_fields( 'group_gn_graduate_profile' ) : array();
$header_field_names = array( 'gn_profile_picture', 'gn_first_name', 'gn_surname', 'gn_job_title', 'gn_position_company', 'gn_city', 'gn_country' );
$header             = array( 'picture' => '', 'name' => array(), 'headline' => array(), 'location' => array() );
$hide_catalogue_fields = function_exists( 'pspa_ms_current_user_is_professional_catalogue' ) && pspa_ms_current_user_is_professional_catalogue();
$catalogue_hidden_fields = $hide_catalogue_fields && function_exists( 'pspa_ms_get_professional_catalogue_hidden_fields' )
    ? pspa_ms_get_professional_catalogue_hidden_fields()
    : array();

foreach ( $header_field_names as $name ) {
    if ( 'hide_all' === $visibility ) {
        break;
    }
    if ( 'show_all' !== $visibility ) {
        $show = function_exists( 'get_field' ) ? get_field( 'gn_show_' . $name, 'user_' . $uid ) : get_user_meta( $uid, 'gn_show_' . $name, true );
        if ( null !== $show && ! $show ) {
            continue;
        }
    }
    if ( $hide_catalogue_fields && in_array( $name, $catalogue_hidden_fields, true ) ) {
        continue;
    }

    $value = function_exists( 'get_field' ) ? get_field( $name, 'user_' . $uid ) : get_user_meta( $uid, $name, true );

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
        case 'gn_city':
        case 'gn_country':
            if ( $value ) {
                $header['location'][] = $value;
            }
            break;
    }
}
?>
<div class="pspa-graduate-profile pspa-linkedin-profile">
    <div class="profile-header">
        <?php if ( $header['picture'] ) : ?>
            <div class="profile-picture"><?php echo $header['picture']; ?></div>
        <?php endif; ?>
        <div class="profile-basics">
            <?php if ( $header['name'] ) : ?>
                <h1 class="profile-name"><?php echo esc_html( implode( ' ', $header['name'] ) ); ?></h1>
            <?php endif; ?>
            <?php if ( $header['headline'] ) : ?>
                <p class="profile-headline"><?php echo esc_html( implode( ', ', $header['headline'] ) ); ?></p>
            <?php endif; ?>
            <?php if ( $header['location'] ) : ?>
                <p class="profile-location"><?php echo esc_html( implode( ', ', $header['location'] ) ); ?></p>
            <?php endif; ?>
        </div>
    </div>
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

        if ( $hide_catalogue_fields && in_array( $field['name'], $catalogue_hidden_fields, true ) ) {
            continue;
        }

        if ( 0 === strpos( $field['name'], 'gn_show_' ) || 'gn_visibility_mode' === $field['name'] ) {
            continue;
        }

        if ( in_array( $field['name'], $header_field_names, true ) ) {
            continue;
        }

        if ( 'hide_all' === $visibility ) {
            continue;
        }

        if ( 'show_all' !== $visibility ) {
            $show = function_exists( 'get_field' ) ? get_field( 'gn_show_' . $field['name'], 'user_' . $uid ) : get_user_meta( $uid, 'gn_show_' . $field['name'], true );
            if ( null !== $show && ! $show ) {
                continue;
            }
        }

        $value = function_exists( 'get_field' ) ? get_field( $field['name'], 'user_' . $uid ) : get_user_meta( $uid, $field['name'], true );

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
</div>
<?php
get_footer();

