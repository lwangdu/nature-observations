<?php
/**
 * Admin settings screen.
 *
 * @package Nature_INat_Observations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings registration and admin tools.
 */
final class Nature_INat_Observations_Admin {
	const OPTION_NAME = 'nature_inat_observations_options';

	/**
	 * Hook admin actions.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_nature_inat_clear_cache', array( $this, 'handle_clear_cache' ) );
	}

	/**
	 * Get plugin options merged with defaults.
	 *
	 * @return array
	 */
	public static function get_options() {
		$defaults = array(
			'project_id'   => NATURE_INAT_DEFAULT_PROJECT_ID,
			'project_slug' => NATURE_INAT_DEFAULT_PROJECT_SLUG,
			'per_page'     => 100,
			'cache_ttl'    => HOUR_IN_SECONDS,
			'open_new_tab' => 1,
		);

		$options = get_option( self::OPTION_NAME, array() );

		return wp_parse_args( is_array( $options ) ? $options : array(), $defaults );
	}

	/**
	 * Add the settings page.
	 */
	public function add_options_page() {
		add_options_page(
			__( 'iNaturalist Observations', 'nature-inat-observations' ),
			__( 'iNaturalist Observations', 'nature-inat-observations' ),
			'manage_options',
			'nature-inat-observations',
			array( $this, 'render_options_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			'nature_inat_observations',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
				'default'           => self::get_options(),
			)
		);

		add_settings_section(
			'nature_inat_source',
			__( 'iNaturalist Source', 'nature-inat-observations' ),
			'__return_false',
			'nature-inat-observations'
		);

		add_settings_field(
			'project_slug',
			__( 'Project slug', 'nature-inat-observations' ),
			array( $this, 'render_project_slug_field' ),
			'nature-inat-observations',
			'nature_inat_source'
		);

		add_settings_field(
			'project_id',
			__( 'Project ID fallback', 'nature-inat-observations' ),
			array( $this, 'render_project_id_field' ),
			'nature-inat-observations',
			'nature_inat_source'
		);

		add_settings_field(
			'per_page',
			__( 'Observations per page', 'nature-inat-observations' ),
			array( $this, 'render_per_page_field' ),
			'nature-inat-observations',
			'nature_inat_source'
		);

		add_settings_field(
			'cache_ttl',
			__( 'Cache duration', 'nature-inat-observations' ),
			array( $this, 'render_cache_ttl_field' ),
			'nature-inat-observations',
			'nature_inat_source'
		);

		add_settings_field(
			'open_new_tab',
			__( 'Open links in new tab', 'nature-inat-observations' ),
			array( $this, 'render_open_new_tab_field' ),
			'nature-inat-observations',
			'nature_inat_source'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $options Raw options.
	 * @return array
	 */
	public function sanitize_options( $options ) {
		$options = is_array( $options ) ? $options : array();

		return array(
			'project_id'   => absint( $options['project_id'] ?? NATURE_INAT_DEFAULT_PROJECT_ID ),
			'project_slug' => sanitize_title( $options['project_slug'] ?? NATURE_INAT_DEFAULT_PROJECT_SLUG ),
			'per_page'     => min( Nature_INat_Observations_Cache::MAX_PER_PAGE, max( 1, absint( $options['per_page'] ?? 100 ) ) ),
			'cache_ttl'    => min( DAY_IN_SECONDS, max( 300, absint( $options['cache_ttl'] ?? HOUR_IN_SECONDS ) ) ),
			'open_new_tab' => empty( $options['open_new_tab'] ) ? 0 : 1,
		);
	}

	/**
	 * Render the project slug field.
	 */
	public function render_project_slug_field() {
		$options = self::get_options();
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[project_slug]" value="<?php echo esc_attr( $options['project_slug'] ); ?>" class="regular-text">
		<p class="description"><?php esc_html_e( 'Example: your-inaturalist-project-slug.', 'nature-inat-observations' ); ?></p>
		<?php
	}

	/**
	 * Render the project ID field.
	 */
	public function render_project_id_field() {
		$options = self::get_options();
		?>
		<input type="number" min="0" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[project_id]" value="<?php echo esc_attr( $options['project_id'] ); ?>" class="small-text">
		<p class="description"><?php esc_html_e( 'Used only when no project slug is set, or as a fallback reference.', 'nature-inat-observations' ); ?></p>
		<?php
	}

	/**
	 * Render the per-page field.
	 */
	public function render_per_page_field() {
		$options = self::get_options();
		?>
		<input type="number" min="1" max="<?php echo esc_attr( Nature_INat_Observations_Cache::MAX_PER_PAGE ); ?>" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[per_page]" value="<?php echo esc_attr( $options['per_page'] ); ?>" class="small-text">
		<?php
	}

	/**
	 * Render the cache TTL field.
	 */
	public function render_cache_ttl_field() {
		$options = self::get_options();
		?>
		<input type="number" min="300" max="<?php echo esc_attr( DAY_IN_SECONDS ); ?>" step="300" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[cache_ttl]" value="<?php echo esc_attr( $options['cache_ttl'] ); ?>" class="small-text">
		<p class="description"><?php esc_html_e( 'Seconds. Keep this at 3600 or higher for normal public pages.', 'nature-inat-observations' ); ?></p>
		<?php
	}

	/**
	 * Render the external-link setting field.
	 */
	public function render_open_new_tab_field() {
		$options = self::get_options();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[open_new_tab]" value="1" <?php checked( ! empty( $options['open_new_tab'] ) ); ?>>
			<?php esc_html_e( 'Open observation and project links in a new browser tab by default.', 'nature-inat-observations' ); ?>
		</label>
		<?php
	}

	/**
	 * Clear cached iNaturalist data.
	 */
	public function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to clear this cache.', 'nature-inat-observations' ) );
		}

		check_admin_referer( 'nature_inat_clear_cache' );

		$deleted = Nature_INat_Observations_Cache::clear_cache();
		$url     = add_query_arg(
			array(
				'page'               => 'nature-inat-observations',
				'nature_cache_clear' => '1',
				'nature_cache_count' => $deleted,
			),
			admin_url( 'options-general.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render the settings page.
	 */
	public function render_options_page() {
		$cache_cleared = isset( $_GET['nature_cache_clear'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cache_count   = isset( $_GET['nature_cache_count'] ) ? absint( wp_unslash( $_GET['nature_cache_count'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'iNaturalist Observations', 'nature-inat-observations' ); ?></h1>
			<?php if ( $cache_cleared ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						printf(
							/* translators: %s: number of deleted cache records. */
							esc_html__( 'iNaturalist cache cleared. Removed %s cache records.', 'nature-inat-observations' ),
							esc_html( number_format_i18n( $cache_count ) )
						);
						?>
					</p>
				</div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'nature_inat_observations' );
				do_settings_sections( 'nature-inat-observations' );
				submit_button();
				?>
			</form>
			<h2><?php esc_html_e( 'Cache tools', 'nature-inat-observations' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="nature_inat_clear_cache">
				<?php wp_nonce_field( 'nature_inat_clear_cache' ); ?>
				<?php submit_button( __( 'Clear iNaturalist cache', 'nature-inat-observations' ), 'secondary', 'submit', false ); ?>
			</form>
			<p><?php esc_html_e( 'Add the iNaturalist Observations block to a dedicated page, then set the reserve source in the block sidebar.', 'nature-inat-observations' ); ?></p>
			<h2><?php esc_html_e( 'Block settings', 'nature-inat-observations' ); ?></h2>
			<p><?php esc_html_e( 'Use Project slug for an iNaturalist project, Place ID for a reserve boundary, or User ID/login for an account feed. Leave Project slug blank and Project ID as 0 when using only a place or account source.', 'nature-inat-observations' ); ?></p>
			<p><?php esc_html_e( 'Shortcode support remains available for older pages, but the block is recommended for new reserve pages.', 'nature-inat-observations' ); ?></p>
		</div>
		<?php
	}
}
