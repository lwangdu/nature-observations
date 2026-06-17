<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UCNature_INat_Observations_Admin {
	const OPTION_NAME = 'ucnature_inat_observations_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_ucnature_inat_clear_cache', array( $this, 'handle_clear_cache' ) );
	}

	public static function get_options() {
		$defaults = array(
			'project_id'   => 3234,
			'project_slug' => 'stunt-ranch-santa-monica-mountains-reserve',
			'per_page'     => 100,
			'cache_ttl'    => HOUR_IN_SECONDS,
			'open_new_tab' => 1,
		);

		$options = get_option( self::OPTION_NAME, array() );

		return wp_parse_args( is_array( $options ) ? $options : array(), $defaults );
	}

	public function add_options_page() {
		add_options_page(
			__( 'iNaturalist Observations', 'ucnature-inat-observations' ),
			__( 'iNaturalist Observations', 'ucnature-inat-observations' ),
			'manage_options',
			'ucnature-inat-observations',
			array( $this, 'render_options_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'ucnature_inat_observations',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
				'default'           => self::get_options(),
			)
		);

		add_settings_section(
			'ucnature_inat_source',
			__( 'iNaturalist Source', 'ucnature-inat-observations' ),
			'__return_false',
			'ucnature-inat-observations'
		);

		add_settings_field(
			'project_slug',
			__( 'Project slug', 'ucnature-inat-observations' ),
			array( $this, 'render_project_slug_field' ),
			'ucnature-inat-observations',
			'ucnature_inat_source'
		);

		add_settings_field(
			'project_id',
			__( 'Project ID fallback', 'ucnature-inat-observations' ),
			array( $this, 'render_project_id_field' ),
			'ucnature-inat-observations',
			'ucnature_inat_source'
		);

		add_settings_field(
			'per_page',
			__( 'Observations per page', 'ucnature-inat-observations' ),
			array( $this, 'render_per_page_field' ),
			'ucnature-inat-observations',
			'ucnature_inat_source'
		);

		add_settings_field(
			'cache_ttl',
			__( 'Cache duration', 'ucnature-inat-observations' ),
			array( $this, 'render_cache_ttl_field' ),
			'ucnature-inat-observations',
			'ucnature_inat_source'
		);

		add_settings_field(
			'open_new_tab',
			__( 'Open links in new tab', 'ucnature-inat-observations' ),
			array( $this, 'render_open_new_tab_field' ),
			'ucnature-inat-observations',
			'ucnature_inat_source'
		);
	}

	public function sanitize_options( $options ) {
		$options = is_array( $options ) ? $options : array();

		return array(
			'project_id'   => absint( $options['project_id'] ?? 3234 ),
			'project_slug' => sanitize_title( $options['project_slug'] ?? 'stunt-ranch-santa-monica-mountains-reserve' ),
			'per_page'     => min( UCNature_INat_Observations_Cache::MAX_PER_PAGE, max( 1, absint( $options['per_page'] ?? 100 ) ) ),
			'cache_ttl'    => min( DAY_IN_SECONDS, max( 300, absint( $options['cache_ttl'] ?? HOUR_IN_SECONDS ) ) ),
			'open_new_tab' => empty( $options['open_new_tab'] ) ? 0 : 1,
		);
	}

	public function render_project_slug_field() {
		$options = self::get_options();
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[project_slug]" value="<?php echo esc_attr( $options['project_slug'] ); ?>" class="regular-text">
		<p class="description"><?php esc_html_e( 'Example: stunt-ranch-santa-monica-mountains-reserve.', 'ucnature-inat-observations' ); ?></p>
		<?php
	}

	public function render_project_id_field() {
		$options = self::get_options();
		?>
		<input type="number" min="0" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[project_id]" value="<?php echo esc_attr( $options['project_id'] ); ?>" class="small-text">
		<p class="description"><?php esc_html_e( 'Used only when no project slug is set, or as a fallback reference.', 'ucnature-inat-observations' ); ?></p>
		<?php
	}

	public function render_per_page_field() {
		$options = self::get_options();
		?>
		<input type="number" min="1" max="<?php echo esc_attr( UCNature_INat_Observations_Cache::MAX_PER_PAGE ); ?>" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[per_page]" value="<?php echo esc_attr( $options['per_page'] ); ?>" class="small-text">
		<?php
	}

	public function render_cache_ttl_field() {
		$options = self::get_options();
		?>
		<input type="number" min="300" max="<?php echo esc_attr( DAY_IN_SECONDS ); ?>" step="300" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[cache_ttl]" value="<?php echo esc_attr( $options['cache_ttl'] ); ?>" class="small-text">
		<p class="description"><?php esc_html_e( 'Seconds. Keep this at 3600 or higher for normal public pages.', 'ucnature-inat-observations' ); ?></p>
		<?php
	}

	public function render_open_new_tab_field() {
		$options = self::get_options();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[open_new_tab]" value="1" <?php checked( ! empty( $options['open_new_tab'] ) ); ?>>
			<?php esc_html_e( 'Open observation and project links in a new browser tab by default.', 'ucnature-inat-observations' ); ?>
		</label>
		<?php
	}

	public function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to clear this cache.', 'ucnature-inat-observations' ) );
		}

		check_admin_referer( 'ucnature_inat_clear_cache' );

		$deleted = UCNature_INat_Observations_Cache::clear_cache();
		$url     = add_query_arg(
			array(
				'page'                 => 'ucnature-inat-observations',
				'ucnature_cache_clear' => '1',
				'ucnature_cache_count' => $deleted,
			),
			admin_url( 'options-general.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	public function render_options_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'iNaturalist Observations', 'ucnature-inat-observations' ); ?></h1>
			<?php if ( isset( $_GET['ucnature_cache_clear'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						printf(
							/* translators: %s: number of deleted cache records. */
							esc_html__( 'iNaturalist cache cleared. Removed %s cache records.', 'ucnature-inat-observations' ),
							esc_html( number_format_i18n( absint( wp_unslash( $_GET['ucnature_cache_count'] ?? 0 ) ) ) )
						);
						?>
					</p>
				</div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ucnature_inat_observations' );
				do_settings_sections( 'ucnature-inat-observations' );
				submit_button();
				?>
			</form>
			<h2><?php esc_html_e( 'Cache tools', 'ucnature-inat-observations' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ucnature_inat_clear_cache">
				<?php wp_nonce_field( 'ucnature_inat_clear_cache' ); ?>
				<?php submit_button( __( 'Clear iNaturalist cache', 'ucnature-inat-observations' ), 'secondary', 'submit', false ); ?>
			</form>
			<p><?php esc_html_e( 'Add the iNaturalist Observations block to a dedicated page, then set the reserve source in the block sidebar.', 'ucnature-inat-observations' ); ?></p>
			<h2><?php esc_html_e( 'Block settings', 'ucnature-inat-observations' ); ?></h2>
			<p><?php esc_html_e( 'Use Project slug for an iNaturalist project, Place ID for a reserve boundary, or User ID/login for an account feed. Leave Project slug blank and Project ID as 0 when using only a place or account source.', 'ucnature-inat-observations' ); ?></p>
			<p><?php esc_html_e( 'Shortcode support remains available for older pages, but the block is recommended for new reserve pages.', 'ucnature-inat-observations' ); ?></p>
		</div>
		<?php
	}
}
