<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package Nature_INat_Observations
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$nature_inat_cache_keys = get_option( 'nature_inat_observations_cache_keys', array() );
if ( is_array( $nature_inat_cache_keys ) ) {
	foreach ( $nature_inat_cache_keys as $nature_inat_cache_key ) {
		delete_transient( sanitize_key( $nature_inat_cache_key ) );
	}
}

delete_transient( 'nature_inat_warm_sources_v1' );

$nature_inat_timestamp = wp_next_scheduled( 'nature_inat_observations_warm_cache' );
if ( $nature_inat_timestamp ) {
	wp_unschedule_event( $nature_inat_timestamp, 'nature_inat_observations_warm_cache' );
}

delete_option( 'nature_inat_observations_options' );
delete_option( 'nature_inat_observations_page_id' );
delete_option( 'nature_inat_observations_map_page_id' );
delete_option( 'nature_inat_observations_version' );
delete_option( 'nature_inat_observations_cache_keys' );
