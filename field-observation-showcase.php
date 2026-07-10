<?php
/**
 * Plugin Name: Field Observation Showcase
 * Description: Field Observation Showcase lets organizations display observations from their iNaturalist projects on their WordPress website using cached API requests and a Gutenberg block. It provides a fast, easy way to showcase project observations while minimizing API calls.
 * Version: 0.2.8
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Lobsang Wangdu
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: field-observation-showcase
 *
 * @package Field_Observation_Showcase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FIELD_OBSERVATION_SHOWCASE_VERSION', '0.2.8' );
define( 'FIELD_OBSERVATION_SHOWCASE_PATH', plugin_dir_path( __FILE__ ) );
define( 'FIELD_OBSERVATION_SHOWCASE_URL', plugin_dir_url( __FILE__ ) );
define( 'FIELD_OBSERVATION_SHOWCASE_PAGE_OPTION', 'field_observation_showcase_page_id' );
define( 'FIELD_OBSERVATION_SHOWCASE_MAP_PAGE_OPTION', 'field_observation_showcase_map_page_id' );
define( 'FIELD_OBSERVATION_SHOWCASE_VERSION_OPTION', 'field_observation_showcase_version' );
define( 'FIELD_OBSERVATION_SHOWCASE_CACHE_KEYS_OPTION', 'field_observation_showcase_cache_keys' );
define( 'FIELD_OBSERVATION_SHOWCASE_DEFAULT_PROJECT_ID', 0 );
define( 'FIELD_OBSERVATION_SHOWCASE_DEFAULT_PROJECT_SLUG', '' );

require_once FIELD_OBSERVATION_SHOWCASE_PATH . 'includes/class-field-observation-showcase-plugin.php';
require_once FIELD_OBSERVATION_SHOWCASE_PATH . 'includes/class-field-observation-showcase-admin.php';
require_once FIELD_OBSERVATION_SHOWCASE_PATH . 'includes/class-field-observation-showcase-renderer.php';
require_once FIELD_OBSERVATION_SHOWCASE_PATH . 'includes/class-field-observation-showcase-cache.php';

add_action(
	'plugins_loaded',
	function () {
		Field_Observation_Showcase_Plugin::instance();
	}
);

register_activation_hook( __FILE__, 'field_observation_showcase_activate' );
register_deactivation_hook( __FILE__, 'field_observation_showcase_deactivate' );

add_action( 'admin_init', 'field_observation_showcase_maybe_create_pages' );

/**
 * Create default pages and store the installed version on activation.
 */
function field_observation_showcase_activate() {
	field_observation_showcase_create_default_pages();
	field_observation_showcase_schedule_cache_warmer();
	update_option( FIELD_OBSERVATION_SHOWCASE_VERSION_OPTION, FIELD_OBSERVATION_SHOWCASE_VERSION );
}

/**
 * Unschedule cache warming on deactivation.
 */
function field_observation_showcase_deactivate() {
	field_observation_showcase_unschedule_cache_warmer();
}

/**
 * Create default pages after plugin updates for already-active installs.
 */
function field_observation_showcase_maybe_create_pages() {
	field_observation_showcase_schedule_cache_warmer();

	if ( FIELD_OBSERVATION_SHOWCASE_VERSION === get_option( FIELD_OBSERVATION_SHOWCASE_VERSION_OPTION ) ) {
		return;
	}

	field_observation_showcase_create_default_pages();
	update_option( FIELD_OBSERVATION_SHOWCASE_VERSION_OPTION, FIELD_OBSERVATION_SHOWCASE_VERSION );
}

/**
 * Schedule hourly background warming for default iNaturalist caches.
 */
function field_observation_showcase_schedule_cache_warmer() {
	if ( ! wp_next_scheduled( 'field_observation_showcase_warm_cache' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'field_observation_showcase_warm_cache' );
	}
}

/**
 * Remove scheduled cache warming.
 */
function field_observation_showcase_unschedule_cache_warmer() {
	$timestamp = wp_next_scheduled( 'field_observation_showcase_warm_cache' );

	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'field_observation_showcase_warm_cache' );
	}
}

/**
 * Create starter observation pages as drafts.
 */
function field_observation_showcase_create_default_pages() {
	field_observation_showcase_create_page(
		FIELD_OBSERVATION_SHOWCASE_PAGE_OPTION,
		'inaturalist-observations',
		__( 'iNaturalist Observations', 'field-observation-showcase' ),
		'<!-- wp:paragraph --><p>Nature sites support remarkable biodiversity, and community science platforms like iNaturalist help document those living communities over time. This page highlights recent observations recorded for this reserve.</p><!-- /wp:paragraph -->' . "\n\n" . '<!-- wp:field-observation-showcase/observations ' . wp_json_encode( array( 'perPage' => 100 ) ) . ' /-->'
	);

	field_observation_showcase_create_page(
		FIELD_OBSERVATION_SHOWCASE_MAP_PAGE_OPTION,
		'map-of-observations',
		__( 'Map of Observations', 'field-observation-showcase' ),
		'<!-- wp:field-observation-showcase/observations-map ' . wp_json_encode( array( 'perPage' => 200 ) ) . ' /-->'
	);
}

/**
 * Create a WordPress page when it does not already exist.
 *
 * @param string $option_name Option key used to store the page ID.
 * @param string $slug        Page slug.
 * @param string $title       Page title.
 * @param string $content     Page content.
 */
function field_observation_showcase_create_page( $option_name, $slug, $title, $content ) {
	$page_id = absint( get_option( $option_name ) );

	if ( $page_id && 'page' === get_post_type( $page_id ) ) {
		return;
	}

	$existing_page = get_page_by_path( $slug );
	if ( $existing_page instanceof WP_Post ) {
		update_option( $option_name, $existing_page->ID );
		return;
	}

	$page_id = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'draft',
			'post_type'    => 'page',
		),
		true
	);

	if ( ! is_wp_error( $page_id ) ) {
		update_option( $option_name, absint( $page_id ) );
	}
}
