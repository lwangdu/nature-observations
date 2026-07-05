<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package Nature_INat_Observations
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$cache_keys = get_option( 'nature_inat_observations_cache_keys', array() );
if ( is_array( $cache_keys ) ) {
	foreach ( $cache_keys as $cache_key ) {
		delete_transient( sanitize_key( $cache_key ) );
	}
}

$timestamp = wp_next_scheduled( 'nature_inat_observations_warm_cache' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'nature_inat_observations_warm_cache' );
}

delete_option( 'nature_inat_observations_options' );
delete_option( 'nature_inat_observations_page_id' );
delete_option( 'nature_inat_observations_map_page_id' );
delete_option( 'nature_inat_observations_version' );
delete_option( 'nature_inat_observations_cache_keys' );
