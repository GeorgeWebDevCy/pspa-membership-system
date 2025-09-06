<?php
/**
 * Template for public graduate profiles.
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
get_header();

$job        = function_exists( 'get_field' ) ? (string) get_field( 'gn_job_title', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_job_title', true );
$company    = function_exists( 'get_field' ) ? (string) get_field( 'gn_position_company', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_position_company', true );
$profession = function_exists( 'get_field' ) ? (string) get_field( 'gn_profession', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_profession', true );
$city       = function_exists( 'get_field' ) ? (string) get_field( 'gn_city', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_city', true );
$country    = function_exists( 'get_field' ) ? (string) get_field( 'gn_country', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_country', true );
$picture    = function_exists( 'get_field' ) ? get_field( 'gn_profile_picture', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_profile_picture', true );
$show_pic   = function_exists( 'get_field' ) ? get_field( 'gn_show_profile_picture', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_show_profile_picture', true );
?>
<div class="pspa-graduate-profile pspa-dashboard">
    <div class="pspa-graduate-header">
        <div class="pspa-graduate-avatar">
            <?php
            if ( $picture && ( null === $show_pic || $show_pic ) ) {
                echo wp_get_attachment_image( $picture, 'thumbnail' );
            } else {
                echo get_avatar( $pspa_user->ID, 128 );
            }
            ?>
        </div>
        <h1 class="pspa-graduate-name"><?php echo esc_html( $pspa_user->display_name ); ?></h1>
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
    <?php if ( function_exists( 'acf_get_fields' ) ) : ?>
        <div class="pspa-graduate-details">
            <?php
            $fields        = acf_get_fields( 'group_gn_graduate_profile' );
            $header_fields = array(
                'gn_job_title',
                'gn_position_company',
                'gn_profession',
                'gn_city',
                'gn_country',
                'gn_profile_picture',
            );
            if ( $fields ) {
                foreach ( $fields as $field ) {
                    if ( 'tab' === $field['type'] ) {
                        continue;
                    }
                    if ( 'gn_visibility_mode' === $field['name'] || 0 === strpos( $field['name'], 'gn_show_' ) ) {
                        continue;
                    }
                    if ( in_array( $field['name'], $header_fields, true ) ) {
                        continue;
                    }
                    $show = function_exists( 'get_field' ) ? get_field( 'gn_show_' . $field['name'], 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_show_' . $field['name'], true );
                    if ( null !== $show && ! $show ) {
                        continue;
                    }
                    $value = function_exists( 'get_field' ) ? get_field( $field['name'], 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, $field['name'], true );
                    if ( 'true_false' === $field['type'] ) {
                        if ( empty( $value ) ) {
                            continue;
                        }
                        printf(
                            '<p class="pspa-graduate-field pspa-graduate-field-%1$s"><strong>%2$s</strong></p>',
                            esc_attr( $field['name'] ),
                            esc_html( $field['label'] )
                        );
                        continue;
                    }
                    if ( empty( $value ) ) {
                        continue;
                    }
                    if ( is_array( $value ) ) {
                        $value = implode( ', ', array_map( 'strval', array_filter( $value ) ) );
                    }
                    printf(
                        '<p class="pspa-graduate-field pspa-graduate-field-%1$s"><strong>%2$s:</strong> %3$s</p>',
                        esc_attr( $field['name'] ),
                        esc_html( $field['label'] ),
                        esc_html( $value )
                    );
                }
            }
            ?>
        </div>
    <?php endif; ?>
</div>
<?php
get_footer();
