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
$visibility = function_exists( 'get_field' ) ? get_field( 'gn_visibility_mode', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_visibility_mode', true );
$show_job   = function_exists( 'get_field' ) ? get_field( 'gn_show_job_title', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_show_job_title', true );
$show_comp  = function_exists( 'get_field' ) ? get_field( 'gn_show_position_company', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_show_position_company', true );
$show_prof  = function_exists( 'get_field' ) ? get_field( 'gn_show_profession', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_show_profession', true );
$show_city  = function_exists( 'get_field' ) ? get_field( 'gn_show_city', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_show_city', true );
$show_country = function_exists( 'get_field' ) ? get_field( 'gn_show_country', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_show_country', true );
?>
<div class="pspa-graduate-profile pspa-dashboard">
    <div class="pspa-graduate-header">
        <div class="pspa-graduate-avatar">
            <?php
            if ( $picture && 'hide_all' !== $visibility && ( 'show_all' === $visibility || null === $show_pic || $show_pic ) ) {
                echo wp_get_attachment_image( $picture, 'thumbnail' );
            } else {
                echo get_avatar( $pspa_user->ID, 128 );
            }
            ?>
        </div>
        <h1 class="pspa-graduate-name"><?php echo esc_html( $pspa_user->display_name ); ?></h1>
        <?php
        $job_display     = ( $job && 'hide_all' !== $visibility && ( 'show_all' === $visibility || null === $show_job || $show_job ) ) ? $job : '';
        $company_display = ( $company && 'hide_all' !== $visibility && ( 'show_all' === $visibility || null === $show_comp || $show_comp ) ) ? $company : '';
        if ( $job_display || $company_display ) :
        ?>
            <p class="pspa-graduate-title"><?php echo esc_html( trim( $job_display . ( $company_display ? ' - ' . $company_display : '' ) ) ); ?></p>
        <?php endif; ?>
        <?php if ( $profession && 'hide_all' !== $visibility && ( 'show_all' === $visibility || null === $show_prof || $show_prof ) ) : ?>
            <p class="pspa-graduate-profession"><?php echo esc_html( $profession ); ?></p>
        <?php endif; ?>
        <?php
        $city_display    = ( $city && 'hide_all' !== $visibility && ( 'show_all' === $visibility || null === $show_city || $show_city ) ) ? $city : '';
        $country_display = ( $country && 'hide_all' !== $visibility && ( 'show_all' === $visibility || null === $show_country || $show_country ) ) ? $country : '';
        if ( $city_display || $country_display ) :
        ?>
            <p class="pspa-graduate-location"><?php echo esc_html( trim( $city_display . ( $country_display ? ', ' . $country_display : '' ) ) ); ?></p>
        <?php endif; ?>
    </div>
    <?php if ( function_exists( 'get_fields' ) && 'hide_all' !== $visibility ) : ?>
        <div class="pspa-graduate-details">
            <?php
            $fields        = get_fields( 'user_' . $pspa_user->ID );
            $header_fields = array(
                'gn_job_title',
                'gn_position_company',
                'gn_profession',
                'gn_city',
                'gn_country',
                'gn_profile_picture',
            );
            if ( $fields ) {
                foreach ( $fields as $name => $value ) {
                    if ( 'gn_visibility_mode' === $name || 0 === strpos( $name, 'gn_show_' ) ) {
                        continue;
                    }
                    if ( in_array( $name, $header_fields, true ) ) {
                        continue;
                    }
                    if ( 'show_all' !== $visibility ) {
                        $show = function_exists( 'get_field' ) ? get_field( 'gn_show_' . $name, 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_show_' . $name, true );
                        if ( null !== $show && ! $show ) {
                            continue;
                        }
                    }
                    $field_obj = function_exists( 'acf_get_field' ) ? acf_get_field( $name ) : false;
                    $label     = $field_obj ? $field_obj['label'] : $name;
                    if ( is_bool( $value ) ) {
                        if ( ! $value ) {
                            continue;
                        }
                        printf(
                            '<p class="pspa-graduate-field pspa-graduate-field-%1$s"><strong>%2$s</strong></p>',
                            esc_attr( $name ),
                            esc_html( $label )
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
                        esc_attr( $name ),
                        esc_html( $label ),
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
