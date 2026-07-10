<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package Field_Observation_Showcase
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$field_observation_showcase_cache_keys = get_option( 'field_observation_showcase_cache_keys', array() );
if ( is_array( $field_observation_showcase_cache_keys ) ) {
	foreach ( $field_observation_showcase_cache_keys as $field_observation_showcase_cache_key ) {
		delete_transient( sanitize_key( $field_observation_showcase_cache_key ) );
	}
}

delete_transient( 'field_observation_showcase_warm_sources_v1' );

$field_observation_showcase_timestamp = wp_next_scheduled( 'field_observation_showcase_warm_cache' );
if ( $field_observation_showcase_timestamp ) {
	wp_unschedule_event( $field_observation_showcase_timestamp, 'field_observation_showcase_warm_cache' );
}

delete_option( 'field_observation_showcase_options' );
delete_option( 'field_observation_showcase_page_id' );
delete_option( 'field_observation_showcase_map_page_id' );
delete_option( 'field_observation_showcase_version' );
delete_option( 'field_observation_showcase_cache_keys' );
