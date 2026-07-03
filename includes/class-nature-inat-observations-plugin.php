<?php
/**
 * Main plugin coordinator.
 *
 * @package Nature_INat_Observations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates the plugin services.
 */
final class Nature_INat_Observations_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Nature_INat_Observations_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the plugin singleton.
	 *
	 * @return Nature_INat_Observations_Plugin
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
		new Nature_INat_Observations_Admin();
		new Nature_INat_Observations_Renderer();
		add_action( 'nature_inat_observations_warm_cache', array( 'Nature_INat_Observations_Cache', 'warm_default_cache' ) );
	}
}
