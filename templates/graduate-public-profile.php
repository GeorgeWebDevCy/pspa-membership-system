<?php
/**
 * Template for public graduate profiles.
 *
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
get_header();

$visibility = function_exists( 'get_field' ) ? get_field( 'gn_visibility_mode', 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_visibility_mode', true );

// Hide fields according to individual visibility settings.
$prepare_field = static function( $field ) use ( $pspa_user, $visibility ) {
    if ( 'hide_all' === $visibility ) {
        return false;
    }

    if ( 'show_all' !== $visibility ) {
        $show = function_exists( 'get_field' ) ? get_field( 'gn_show_' . $field['name'], 'user_' . $pspa_user->ID ) : get_user_meta( $pspa_user->ID, 'gn_show_' . $field['name'], true );

        if ( null !== $show && ! $show ) {
            return false;
        }
    }

    return $field;
};

add_filter( 'acf/prepare_field', $prepare_field );
?>
<div class="pspa-graduate-profile pspa-dashboard">
    <?php
    if ( function_exists( 'acf_form' ) ) {
        acf_form( array(
            'post_id'      => 'user_' . $pspa_user->ID,
            'form'         => false,
            'field_groups' => array( 'group_gn_graduate_profile' ),
        ) );
    }
    ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var container = document.querySelector('.pspa-graduate-profile');
    if (!container) {
        return;
    }
    container.querySelectorAll('input, select, textarea, button').forEach(function(el) {
        el.setAttribute('disabled', 'disabled');
    });
    container.querySelectorAll('.acf-actions').forEach(function(el){
        el.remove();
    });
});
</script>
<?php
remove_filter( 'acf/prepare_field', $prepare_field );
get_footer();

