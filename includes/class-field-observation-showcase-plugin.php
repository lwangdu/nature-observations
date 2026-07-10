<?php
/**
 * Main plugin coordinator.
 *
 * @package Field_Observation_Showcase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates the plugin services.
 */
final class Field_Observation_Showcase_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Field_Observation_Showcase_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the plugin singleton.
	 *
	 * @return Field_Observation_Showcase_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register plugin services.
	 */
	private function __construct() {
		new Field_Observation_Showcase_Admin();
		new Field_Observation_Showcase_Renderer();
		add_action( 'field_observation_showcase_warm_cache', array( 'Field_Observation_Showcase_Cache', 'warm_default_cache' ) );
		add_action( 'save_post', array( 'Field_Observation_Showcase_Cache', 'clear_warm_sources_cache' ) );
	}
}
