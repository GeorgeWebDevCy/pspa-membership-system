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
?>
<div class="pspa-graduate-profile pspa-dashboard">
    <div class="pspa-graduate-header">
        <div class="pspa-graduate-avatar"><?php echo get_avatar( $pspa_user->ID, 128 ); ?></div>
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
</div>
<?php
get_footer();
