<?php
/**
 * Main plugin coordinator.
 *
 * @package UCNature_INat_Observations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates the plugin services.
 */
final class UCNature_INat_Observations_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var UCNature_INat_Observations_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the plugin singleton.
	 *
	 * @return UCNature_INat_Observations_Plugin
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
		new UCNature_INat_Observations_Admin();
		new UCNature_INat_Observations_Renderer();
	}
}
