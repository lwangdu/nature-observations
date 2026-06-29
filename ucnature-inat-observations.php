<?php
/**
 * Plugin Name: UC Nature iNaturalist Observations
 * Description: Displays iNaturalist project observations in WordPress using cached API requests and a block editor interface.
 * Version: 0.2.1
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Lobsang Wangdu
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ucnature-inat-observations
 *
 * @package UCNature_INat_Observations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UCNATURE_INAT_VERSION', '0.2.1' );
define( 'UCNATURE_INAT_PATH', plugin_dir_path( __FILE__ ) );
define( 'UCNATURE_INAT_URL', plugin_dir_url( __FILE__ ) );
define( 'UCNATURE_INAT_PAGE_OPTION', 'ucnature_inat_observations_page_id' );
define( 'UCNATURE_INAT_MAP_PAGE_OPTION', 'ucnature_inat_observations_map_page_id' );
define( 'UCNATURE_INAT_VERSION_OPTION', 'ucnature_inat_observations_version' );

require_once UCNATURE_INAT_PATH . 'includes/class-ucnature-inat-observations-plugin.php';
require_once UCNATURE_INAT_PATH . 'includes/class-ucnature-inat-observations-admin.php';
require_once UCNATURE_INAT_PATH . 'includes/class-ucnature-inat-observations-renderer.php';
require_once UCNATURE_INAT_PATH . 'includes/class-ucnature-inat-observations-cache.php';

add_action(
	'plugins_loaded',
	function () {
		UCNature_INat_Observations_Plugin::instance();
	}
);

register_activation_hook( __FILE__, 'ucnature_inat_observations_activate' );

add_action( 'admin_init', 'ucnature_inat_observations_maybe_create_pages' );

/**
 * Create default pages and store the installed version on activation.
 */
function ucnature_inat_observations_activate() {
	ucnature_inat_observations_create_default_pages();
	update_option( UCNATURE_INAT_VERSION_OPTION, UCNATURE_INAT_VERSION );
}

/**
 * Create default pages after plugin updates for already-active installs.
 */
function ucnature_inat_observations_maybe_create_pages() {
	if ( UCNATURE_INAT_VERSION === get_option( UCNATURE_INAT_VERSION_OPTION ) ) {
		return;
	}

	ucnature_inat_observations_create_default_pages();
	update_option( UCNATURE_INAT_VERSION_OPTION, UCNATURE_INAT_VERSION );
}

/**
 * Create starter observation pages as drafts.
 */
function ucnature_inat_observations_create_default_pages() {
	ucnature_inat_observations_create_page(
		UCNATURE_INAT_PAGE_OPTION,
		'inaturalist-observations',
		__( 'iNaturalist Observations', 'ucnature-inat-observations' ),
		'<!-- wp:paragraph --><p>UC Nature sites support remarkable biodiversity, and community science platforms like iNaturalist help document those living communities over time. This page highlights recent observations recorded for this reserve.</p><!-- /wp:paragraph -->' . "\n\n" . '<!-- wp:ucnature-inat/observations {"projectSlug":"stunt-ranch-santa-monica-mountains-reserve","projectId":3234,"perPage":100} /-->'
	);

	ucnature_inat_observations_create_page(
		UCNATURE_INAT_MAP_PAGE_OPTION,
		'map-of-observations',
		__( 'Map of Observations', 'ucnature-inat-observations' ),
		'<!-- wp:ucnature-inat/observations-map {"projectSlug":"stunt-ranch-santa-monica-mountains-reserve","projectId":3234,"perPage":200} /-->'
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
function ucnature_inat_observations_create_page( $option_name, $slug, $title, $content ) {
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
