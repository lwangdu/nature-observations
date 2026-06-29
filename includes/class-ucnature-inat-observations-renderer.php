<?php
/**
 * Block, shortcode, and front-end rendering.
 *
 * @package UCNature_INat_Observations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders observation blocks and shortcodes.
 */
final class UCNature_INat_Observations_Renderer {
	/**
	 * Hook asset, block, and shortcode registration.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_assets' ), 5 );
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_interactivity_assets' ) );
		add_shortcode( 'ucnature_inat_observations', array( $this, 'render_shortcode' ) );
		add_shortcode( 'ucnature_inat_observations_map', array( $this, 'render_map_shortcode' ) );
	}

	/**
	 * Register front-end styles and scripts.
	 */
	public function register_assets() {
		$frontend_css_path = UCNATURE_INAT_PATH . 'assets/css/frontend.css';
		$block_js_path     = UCNATURE_INAT_PATH . 'assets/js/block.js';
		$map_js_path       = UCNATURE_INAT_PATH . 'assets/js/map.js';
		$view_js_path      = UCNATURE_INAT_PATH . 'assets/js/view.js';
		$leaflet_css_path  = UCNATURE_INAT_PATH . 'assets/vendor/leaflet/leaflet.css';
		$leaflet_js_path   = UCNATURE_INAT_PATH . 'assets/vendor/leaflet/leaflet.js';

		wp_register_style(
			'leaflet',
			UCNATURE_INAT_URL . 'assets/vendor/leaflet/leaflet.css',
			array(),
			file_exists( $leaflet_css_path ) ? filemtime( $leaflet_css_path ) : '1.9.4'
		);

		wp_register_style(
			'ucnature-inat-observations',
			UCNATURE_INAT_URL . 'assets/css/frontend.css',
			array(),
			file_exists( $frontend_css_path ) ? filemtime( $frontend_css_path ) : UCNATURE_INAT_VERSION
		);

		wp_register_script(
			'leaflet',
			UCNATURE_INAT_URL . 'assets/vendor/leaflet/leaflet.js',
			array(),
			file_exists( $leaflet_js_path ) ? filemtime( $leaflet_js_path ) : '1.9.4',
			true
		);

		wp_register_script(
			'ucnature-inat-observations-map',
			UCNATURE_INAT_URL . 'assets/js/map.js',
			array( 'leaflet' ),
			file_exists( $map_js_path ) ? filemtime( $map_js_path ) : UCNATURE_INAT_VERSION,
			true
		);

		if ( function_exists( 'wp_register_script_module' ) ) {
			wp_register_script_module(
				'ucnature-inat-observations-view',
				UCNATURE_INAT_URL . 'assets/js/view.js',
				array(
					array(
						'id'     => '@wordpress/interactivity',
						'import' => 'static',
					),
				),
				file_exists( $view_js_path ) ? filemtime( $view_js_path ) : UCNATURE_INAT_VERSION
			);
		}
	}

	/**
	 * Register dynamic blocks.
	 */
	public function register_block() {
		$options       = UCNature_INat_Observations_Admin::get_options();
		$block_js_path = UCNATURE_INAT_PATH . 'assets/js/block.js';

		wp_register_script(
			'ucnature-inat-observations-block',
			UCNATURE_INAT_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'ucnature-inat-observations-map' ),
			file_exists( $block_js_path ) ? filemtime( $block_js_path ) : UCNATURE_INAT_VERSION,
			true
		);
		wp_add_inline_script(
			'ucnature-inat-observations-block',
			'window.ucnatureINatObservations = ' . wp_json_encode(
				array(
					'maxPerPage'        => UCNature_INat_Observations_Cache::MAX_PER_PAGE,
					'openLinksInNewTab' => ! empty( $options['open_new_tab'] ),
				)
			) . ';',
			'before'
		);

		register_block_type(
			'ucnature-inat/observations',
			array(
				'api_version'     => 2,
				'editor_script'   => 'ucnature-inat-observations-block',
				'style'           => 'ucnature-inat-observations',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'projectId'         => array(
						'type'    => 'number',
						'default' => absint( $options['project_id'] ),
					),
					'projectSlug'       => array(
						'type'    => 'string',
						'default' => $options['project_slug'],
					),
					'placeId'           => array(
						'type'    => 'number',
						'default' => 0,
					),
					'userId'            => array(
						'type'    => 'string',
						'default' => '',
					),
					'perPage'           => array(
						'type'    => 'number',
						'default' => absint( $options['per_page'] ),
					),
					'openLinksInNewTab' => array(
						'type'    => 'boolean',
						'default' => ! empty( $options['open_new_tab'] ),
					),
					'title'             => array(
						'type'    => 'string',
						'default' => '',
					),
					'summary'           => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		register_block_type(
			'ucnature-inat/observations-map',
			array(
				'api_version'     => 2,
				'editor_script'   => 'ucnature-inat-observations-block',
				'style'           => array( 'leaflet', 'ucnature-inat-observations' ),
				'render_callback' => array( $this, 'render_map_block' ),
				'attributes'      => array(
					'projectId'         => array(
						'type'    => 'number',
						'default' => absint( $options['project_id'] ),
					),
					'projectSlug'       => array(
						'type'    => 'string',
						'default' => $options['project_slug'],
					),
					'placeId'           => array(
						'type'    => 'number',
						'default' => 0,
					),
					'userId'            => array(
						'type'    => 'string',
						'default' => '',
					),
					'perPage'           => array(
						'type'    => 'number',
						'default' => min( UCNature_INat_Observations_Cache::MAX_PER_PAGE, 200 ),
					),
					'openLinksInNewTab' => array(
						'type'    => 'boolean',
						'default' => ! empty( $options['open_new_tab'] ),
					),
					'title'             => array(
						'type'    => 'string',
						'default' => '',
					),
					'summary'           => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Enqueue Interactivity API assets early when the map view is in post content.
	 */
	public function enqueue_interactivity_assets() {
		if ( ! function_exists( 'wp_enqueue_script_module' ) || ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if (
			has_block( 'ucnature-inat/observations', $post ) ||
			has_block( 'ucnature-inat/observations-map', $post ) ||
			has_shortcode( $post->post_content, 'ucnature_inat_observations' ) ||
			has_shortcode( $post->post_content, 'ucnature_inat_observations_map' )
		) {
			wp_enqueue_script_module( 'ucnature-inat-observations-view' );
		}
	}

	/**
	 * Render the observations shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$options = UCNature_INat_Observations_Admin::get_options();
		$atts    = shortcode_atts(
			array(
				'project_id'   => $options['project_id'],
				'project_slug' => $options['project_slug'],
				'place_id'     => 0,
				'user_id'      => '',
				'per_page'     => $options['per_page'],
				'open_links'   => ! empty( $options['open_new_tab'] ),
				'title'        => __( 'iNaturalist Observations', 'ucnature-inat-observations' ),
				'summary'      => __( 'Live observations from this reserve on iNaturalist.', 'ucnature-inat-observations' ),
			),
			$atts,
			'ucnature_inat_observations'
		);

		return $this->render(
			array(
				'project_id'   => $atts['project_id'],
				'project_slug' => $atts['project_slug'],
				'place_id'     => $atts['place_id'],
				'user_id'      => $atts['user_id'],
				'per_page'     => $atts['per_page'],
				'open_links'   => $atts['open_links'],
				'title'        => $atts['title'],
				'summary'      => $atts['summary'],
			)
		);
	}

	/**
	 * Render the observations block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		return $this->render(
			array(
				'project_id'   => $attributes['projectId'] ?? 3234,
				'project_slug' => $attributes['projectSlug'] ?? '',
				'place_id'     => $attributes['placeId'] ?? 0,
				'user_id'      => $attributes['userId'] ?? '',
				'per_page'     => $attributes['perPage'] ?? 100,
				'open_links'   => $attributes['openLinksInNewTab'] ?? UCNature_INat_Observations_Admin::get_options()['open_new_tab'],
				'title'        => $attributes['title'] ?? '',
				'summary'      => $attributes['summary'] ?? '',
			)
		);
	}

	/**
	 * Render the map shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_map_shortcode( $atts ) {
		$options = UCNature_INat_Observations_Admin::get_options();
		$atts    = shortcode_atts(
			array(
				'project_id'   => $options['project_id'],
				'project_slug' => $options['project_slug'],
				'place_id'     => 0,
				'user_id'      => '',
				'per_page'     => min( UCNature_INat_Observations_Cache::MAX_PER_PAGE, 200 ),
				'open_links'   => ! empty( $options['open_new_tab'] ),
				'title'        => '',
				'summary'      => '',
			),
			$atts,
			'ucnature_inat_observations_map'
		);

		return $this->render_map(
			array(
				'project_id'   => $atts['project_id'],
				'project_slug' => $atts['project_slug'],
				'place_id'     => $atts['place_id'],
				'user_id'      => $atts['user_id'],
				'per_page'     => $atts['per_page'],
				'open_links'   => $atts['open_links'],
				'title'        => $atts['title'],
				'summary'      => $atts['summary'],
			)
		);
	}

	/**
	 * Render the map block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_map_block( $attributes ) {
		return $this->render_map(
			array(
				'project_id'   => $attributes['projectId'] ?? 3234,
				'project_slug' => $attributes['projectSlug'] ?? '',
				'place_id'     => $attributes['placeId'] ?? 0,
				'user_id'      => $attributes['userId'] ?? '',
				'per_page'     => $attributes['perPage'] ?? 200,
				'open_links'   => $attributes['openLinksInNewTab'] ?? UCNature_INat_Observations_Admin::get_options()['open_new_tab'],
				'title'        => $attributes['title'] ?? '',
				'summary'      => $attributes['summary'] ?? '',
			)
		);
	}

	/**
	 * Render the map view.
	 *
	 * @param array $args Render arguments.
	 * @return string
	 */
	private function render_map( $args ) {
		wp_enqueue_style( 'leaflet' );
		wp_enqueue_style( 'ucnature-inat-observations' );
		wp_enqueue_script( 'ucnature-inat-observations-map' );

		$use_interactivity_api = function_exists( 'wp_enqueue_script_module' ) && function_exists( 'wp_interactivity_data_wp_context' );
		if ( $use_interactivity_api ) {
			wp_enqueue_script_module( 'ucnature-inat-observations-view' );
		}

		$raw_group  = isset( $_GET['ucnature_inat_group'] ) ? sanitize_text_field( wp_unslash( $_GET['ucnature_inat_group'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$group      = UCNature_INat_Observations_Cache::sanitize_group( $raw_group );
		$query_args = array(
			'project_id'   => $args['project_id'],
			'project_slug' => $args['project_slug'],
			'place_id'     => $args['place_id'],
			'user_id'      => $args['user_id'],
			'per_page'     => $args['per_page'],
			'page'         => 1,
			'group'        => $group,
			'geo'          => true,
		);
		$data       = UCNature_INat_Observations_Cache::get_observations( $query_args );
		$boundary   = UCNature_INat_Observations_Cache::get_source_boundary( $query_args );

		$heading_id            = wp_unique_id( 'ucnature-inat-map-heading-' );
		$map_id                = wp_unique_id( 'ucnature-inat-map-' );
		$rail_id               = wp_unique_id( 'ucnature-inat-map-thumbs-' );
		$title                 = sanitize_text_field( $args['title'] );
		$summary               = sanitize_text_field( $args['summary'] );
		$has_header            = '' !== $title || '' !== $summary;
		$labelledby            = '' !== $title ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : '';
		$open_links_in_new_tab = ! in_array( $args['open_links'], array( false, 0, '0', 'false', 'no', 'off' ), true );
		$observations          = ! is_wp_error( $data ) ? $this->mappable_observations( $data['results'] ?? array() ) : array();
		$boundary_data         = ! is_wp_error( $boundary ) ? $boundary : array();
		$carousel_context      = $use_interactivity_api ? wp_interactivity_data_wp_context(
			array(
				'railId'      => $rail_id,
				'hasPrevious' => false,
				'hasNext'     => count( $observations ) > 1,
			),
			'ucnature-inat/observations-map'
		) : '';

		ob_start();
		?>
		<section class="ucnature-inat ucnature-inat-map-view"<?php echo $labelledby; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $has_header ) : ?>
				<div class="ucnature-inat__header">
					<?php if ( '' !== $title ) : ?>
						<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="ucnature-inat__title"><?php echo esc_html( $title ); ?></h2>
					<?php endif; ?>
					<?php if ( '' !== $summary ) : ?>
						<p class="ucnature-inat__summary"><?php echo esc_html( $summary ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php $this->render_filters( $group ); ?>
			<?php if ( is_wp_error( $data ) ) : ?>
				<p class="ucnature-inat__notice"><?php echo esc_html( $data->get_error_message() ); ?></p>
			<?php elseif ( empty( $observations ) && empty( $boundary_data ) ) : ?>
				<p class="ucnature-inat__notice"><?php esc_html_e( 'No mapped observations found for this filter.', 'ucnature-inat-observations' ); ?></p>
			<?php else : ?>
				<div class="ucnature-inat-map" data-observations="<?php echo esc_attr( wp_json_encode( $observations ) ); ?>" data-boundary="<?php echo esc_attr( wp_json_encode( $boundary_data ) ); ?>">
					<div id="<?php echo esc_attr( $map_id ); ?>" class="ucnature-inat-map__canvas" data-map-id="<?php echo esc_attr( $map_id ); ?>"></div>
					<div class="ucnature-inat-map__recent" aria-label="<?php esc_attr_e( 'Recent mapped observations', 'ucnature-inat-observations' ); ?>">
						<h3 class="ucnature-inat-map__recent-title"><?php esc_html_e( 'Recent Observations', 'ucnature-inat-observations' ); ?></h3>
						<div class="ucnature-inat-map__carousel"<?php echo $use_interactivity_api ? ' data-wp-interactive="ucnature-inat/observations-map" ' . $carousel_context . ' data-wp-init="callbacks.initCarousel"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<button class="ucnature-inat-map__nav ucnature-inat-map__nav--prev" type="button" aria-label="<?php esc_attr_e( 'Previous observations', 'ucnature-inat-observations' ); ?>"<?php echo $use_interactivity_api ? ' data-wp-on--click="actions.scrollPrevious" data-wp-bind--disabled="state.isPreviousDisabled"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<span aria-hidden="true">‹</span>
							</button>
							<div id="<?php echo esc_attr( $rail_id ); ?>" class="ucnature-inat-map__thumbs" tabindex="0">
								<?php if ( empty( $observations ) ) : ?>
									<p class="ucnature-inat-map__empty"><?php esc_html_e( 'No recent mapped observations found for this filter.', 'ucnature-inat-observations' ); ?></p>
								<?php else : ?>
									<?php foreach ( array_slice( $observations, 0, 12 ) as $observation ) : ?>
										<?php
										$thumb_context = $use_interactivity_api ? wp_interactivity_data_wp_context(
											array(
												'observationId' => absint( $observation['id'] ),
											),
											'ucnature-inat/observations-map'
										) : '';
										?>
										<a class="ucnature-inat-map__thumb" href="<?php echo esc_url( $observation['url'] ); ?>"<?php echo $open_links_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?><?php echo $use_interactivity_api ? ' ' . $thumb_context . ' data-wp-on--focus="actions.selectObservation" data-wp-on--mouseenter="actions.selectObservation" data-wp-on--pointerdown="actions.selectObservation" data-wp-class--is-active="state.isActiveObservation" data-wp-bind--aria-current="state.activeObservationAriaCurrent"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
											<?php if ( '' !== $observation['photo_url'] ) : ?>
												<img src="<?php echo esc_url( $observation['photo_url'] ); ?>" alt="<?php echo esc_attr( $observation['photo_alt'] ); ?>">
											<?php else : ?>
												<span><?php echo esc_html( $observation['common_name'] ); ?></span>
											<?php endif; ?>
										</a>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
							<button class="ucnature-inat-map__nav ucnature-inat-map__nav--next" type="button" aria-label="<?php esc_attr_e( 'Next observations', 'ucnature-inat-observations' ); ?>"<?php echo $use_interactivity_api ? ' data-wp-on--click="actions.scrollNext" data-wp-bind--disabled="state.isNextDisabled"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<span aria-hidden="true">›</span>
							</button>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the observations grid view.
	 *
	 * @param array $args Render arguments.
	 * @return string
	 */
	private function render( $args ) {
		wp_enqueue_style( 'ucnature-inat-observations' );

		$use_interactivity_api = function_exists( 'wp_enqueue_script_module' ) && function_exists( 'wp_interactivity_data_wp_context' );
		if ( $use_interactivity_api ) {
			wp_enqueue_script_module( 'ucnature-inat-observations-view' );
		}

		$raw_group  = isset( $_GET['ucnature_inat_group'] ) ? sanitize_text_field( wp_unslash( $_GET['ucnature_inat_group'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_page   = isset( $_GET['ucnature_inat_page'] ) ? absint( wp_unslash( $_GET['ucnature_inat_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$group      = UCNature_INat_Observations_Cache::sanitize_group( $raw_group );
		$page       = max( 1, absint( $raw_page ) );
		$query_args = array(
			'project_id'   => $args['project_id'],
			'project_slug' => $args['project_slug'],
			'place_id'     => $args['place_id'],
			'user_id'      => $args['user_id'],
			'per_page'     => $args['per_page'],
			'page'         => $page,
			'group'        => $group,
		);
		$data       = UCNature_INat_Observations_Cache::get_observations( $query_args );

		$heading_id            = wp_unique_id( 'ucnature-inat-heading-' );
		$title                 = sanitize_text_field( $args['title'] );
		$summary               = sanitize_text_field( $args['summary'] );
		$has_header            = '' !== $title || '' !== $summary;
		$labelledby            = '' !== $title ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : '';
		$stats                 = is_wp_error( $data ) ? null : UCNature_INat_Observations_Cache::get_source_stats( $query_args );
		$stats_title           = ! is_wp_error( $stats ) && ! empty( $stats['label'] ) ? $stats['label'] : __( 'iNaturalist', 'ucnature-inat-observations' );
		$open_links_in_new_tab = ! in_array( $args['open_links'], array( false, 0, '0', 'false', 'no', 'off' ), true );
		$section_context       = $use_interactivity_api ? wp_interactivity_data_wp_context(
			array(
				'isLoading' => false,
			),
			'ucnature-inat/observations'
		) : '';

		ob_start();
		?>
		<section class="ucnature-inat"<?php echo $labelledby; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $use_interactivity_api ? ' data-wp-interactive="ucnature-inat/observations" ' . $section_context . ' data-wp-bind--aria-busy="state.isPaginationLoading" data-wp-class--is-loading="state.isPaginationLoading"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $has_header ) : ?>
				<div class="ucnature-inat__header">
					<?php if ( '' !== $title ) : ?>
						<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="ucnature-inat__title"><?php echo esc_html( $title ); ?></h2>
					<?php endif; ?>
					<?php if ( '' !== $summary ) : ?>
						<p class="ucnature-inat__summary"><?php echo esc_html( $summary ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( is_wp_error( $data ) ) : ?>
				<p class="ucnature-inat__notice"><?php echo esc_html( $data->get_error_message() ); ?></p>
			<?php elseif ( empty( $data['results'] ) ) : ?>
				<p class="ucnature-inat__notice"><?php esc_html_e( 'No observations found for this filter.', 'ucnature-inat-observations' ); ?></p>
			<?php else : ?>
				<?php $this->render_stats_cards( UCNature_INat_Observations_Cache::displayed_stats( $data ), $stats, $stats_title, $open_links_in_new_tab ); ?>
				<?php $this->render_filters( $group ); ?>
				<div class="ucnature-inat__grid">
					<?php foreach ( $data['results'] as $observation ) : ?>
						<?php include UCNATURE_INAT_PATH . 'templates/observation-card.php'; ?>
					<?php endforeach; ?>
				</div>
				<?php $this->render_pagination( $data ); ?>
			<?php endif; ?>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render observation summary cards.
	 *
	 * @param array       $displayed_stats       Displayed-page stats.
	 * @param array|false $source_stats          Source stats.
	 * @param string      $title                 Source title.
	 * @param bool        $open_links_in_new_tab Whether links open in a new tab.
	 */
	private function render_stats_cards( $displayed_stats, $source_stats, $title, $open_links_in_new_tab ) {
		?>
		<div class="ucnature-inat-stats" aria-label="<?php esc_attr_e( 'iNaturalist observation summary', 'ucnature-inat-observations' ); ?>">
			<div class="ucnature-inat-stats__card">
				<p class="ucnature-inat-stats__eyebrow"><?php esc_html_e( 'Showing on this page', 'ucnature-inat-observations' ); ?></p>
				<div class="ucnature-inat-stats__metrics">
					<?php $this->render_stat_metric( $displayed_stats['observations'], __( 'Observations', 'ucnature-inat-observations' ) ); ?>
					<?php $this->render_stat_metric( $displayed_stats['species'], __( 'Species', 'ucnature-inat-observations' ) ); ?>
					<?php $this->render_stat_metric( $displayed_stats['observers'], __( 'Observers', 'ucnature-inat-observations' ) ); ?>
				</div>
			</div>

			<?php if ( ! is_wp_error( $source_stats ) && is_array( $source_stats ) ) : ?>
				<div class="ucnature-inat-stats__card ucnature-inat-stats__card--source">
					<p class="ucnature-inat-stats__eyebrow">
						<?php
						printf(
							/* translators: %s: iNaturalist source title. */
							esc_html__( '%s - All time', 'ucnature-inat-observations' ),
							esc_html( $title )
						);
						?>
					</p>
					<div class="ucnature-inat-stats__metrics">
						<?php $this->render_stat_metric( $source_stats['observations'], __( 'Observations', 'ucnature-inat-observations' ) ); ?>
						<?php $this->render_stat_metric( $source_stats['species'], __( 'Species', 'ucnature-inat-observations' ) ); ?>
						<?php $this->render_stat_metric( $source_stats['identifiers'], __( 'Identifiers', 'ucnature-inat-observations' ) ); ?>
						<?php $this->render_stat_metric( $source_stats['observers'], __( 'Observers', 'ucnature-inat-observations' ) ); ?>
					</div>
					<?php if ( ! empty( $source_stats['url'] ) ) : ?>
						<a class="ucnature-inat-stats__link" href="<?php echo esc_url( $source_stats['url'] ); ?>"<?php echo $open_links_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
							<?php esc_html_e( 'View full project on iNaturalist', 'ucnature-inat-observations' ); ?>
							<span aria-hidden="true">→</span>
							<?php if ( $open_links_in_new_tab ) : ?>
								<span class="screen-reader-text"> <?php esc_html_e( 'opens in a new tab', 'ucnature-inat-observations' ); ?></span>
							<?php endif; ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render one stat metric.
	 *
	 * @param int    $value Metric value.
	 * @param string $label Metric label.
	 */
	private function render_stat_metric( $value, $label ) {
		?>
		<div class="ucnature-inat-stats__metric">
			<strong><?php echo esc_html( number_format_i18n( absint( $value ) ) ); ?></strong>
			<span><?php echo esc_html( $label ); ?></span>
		</div>
		<?php
	}

	/**
	 * Convert normalized observations into map marker data.
	 *
	 * @param array $observations Observations.
	 * @return array
	 */
	private function mappable_observations( $observations ) {
		$markers = array();

		foreach ( $observations as $observation ) {
			if ( ! isset( $observation['latitude'], $observation['longitude'] ) || null === $observation['latitude'] || null === $observation['longitude'] ) {
				continue;
			}

			$markers[] = array(
				'id'              => absint( $observation['id'] ),
				'lat'             => (float) $observation['latitude'],
				'lng'             => (float) $observation['longitude'],
				'url'             => esc_url_raw( $observation['url'] ),
				'photo_url'       => esc_url_raw( $observation['photo_url'] ),
				'photo_alt'       => sanitize_text_field( $observation['photo_alt'] ),
				'common_name'     => sanitize_text_field( $observation['common_name'] ),
				'scientific_name' => sanitize_text_field( $observation['scientific_name'] ),
				'taxon_group'     => sanitize_text_field( $observation['taxon_group'] ),
				'observed_on'     => sanitize_text_field( $observation['observed_on'] ),
				'observer'        => sanitize_text_field( $observation['observer'] ),
			);
		}

		return $markers;
	}

	/**
	 * Render pagination controls.
	 *
	 * @param array $data Observation data.
	 */
	private function render_pagination( $data ) {
		$total_results = absint( $data['total_results'] ?? 0 );
		$per_page      = max( 1, absint( $data['per_page'] ?? 100 ) );
		$current_page  = max( 1, absint( $data['page'] ?? 1 ) );
		$total_pages   = (int) ceil( $total_results / $per_page );

		if ( $total_pages <= 1 ) {
			return;
		}

		$start      = max( 1, $current_page - 2 );
		$end        = min( $total_pages, $current_page + 2 );
		$link_attrs = function_exists( 'wp_enqueue_script_module' )
			? ' data-wp-on--mouseenter="actions.prefetchPaginationPage" data-wp-on--focus="actions.prefetchPaginationPage" data-wp-on--click="actions.setPaginationLoading"'
			: '';
		?>
		<nav class="ucnature-inat-pagination" aria-label="<?php esc_attr_e( 'Observations pagination', 'ucnature-inat-observations' ); ?>">
			<p class="ucnature-inat-pagination__summary">
				<?php
				printf(
					/* translators: 1: current page, 2: total pages, 3: total observations. */
					esc_html__( 'Page %1$s of %2$s (%3$s observations)', 'ucnature-inat-observations' ),
					esc_html( number_format_i18n( $current_page ) ),
					esc_html( number_format_i18n( $total_pages ) ),
					esc_html( number_format_i18n( $total_results ) )
				);
				?>
			</p>
			<div class="ucnature-inat-pagination__links">
				<?php if ( $current_page > 1 ) : ?>
					<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( $current_page - 1 ) ); ?>"<?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Previous', 'ucnature-inat-observations' ); ?></a>
				<?php endif; ?>

				<?php if ( $start > 1 ) : ?>
					<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( 1 ) ); ?>"<?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>1</a>
					<?php if ( $start > 2 ) : ?>
						<span class="ucnature-inat-pagination__ellipsis" aria-hidden="true">...</span>
					<?php endif; ?>
				<?php endif; ?>

				<?php for ( $page = $start; $page <= $end; $page++ ) : ?>
					<?php if ( $page === $current_page ) : ?>
						<span class="ucnature-inat-pagination__link is-current" aria-current="page"><?php echo esc_html( number_format_i18n( $page ) ); ?></span>
					<?php else : ?>
						<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( $page ) ); ?>"<?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( number_format_i18n( $page ) ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>

				<?php if ( $end < $total_pages ) : ?>
					<?php if ( $end < $total_pages - 1 ) : ?>
						<span class="ucnature-inat-pagination__ellipsis" aria-hidden="true">...</span>
					<?php endif; ?>
					<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( $total_pages ) ); ?>"<?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( number_format_i18n( $total_pages ) ); ?></a>
				<?php endif; ?>

				<?php if ( $current_page < $total_pages ) : ?>
					<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( $current_page + 1 ) ); ?>"<?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Next', 'ucnature-inat-observations' ); ?></a>
				<?php endif; ?>
			</div>
		</nav>
		<?php
	}

	/**
	 * Build a pagination URL.
	 *
	 * @param int $page Page number.
	 * @return string
	 */
	private function pagination_url( $page ) {
		if ( $page <= 1 ) {
			return remove_query_arg( 'ucnature_inat_page' );
		}

		return add_query_arg( 'ucnature_inat_page', absint( $page ) );
	}

	/**
	 * Render observation filter links.
	 *
	 * @param string $active_group Active group key.
	 */
	private function render_filters( $active_group ) {
		?>
		<nav class="ucnature-inat__filters" aria-label="<?php esc_attr_e( 'Observation filters', 'ucnature-inat-observations' ); ?>">
			<?php foreach ( UCNature_INat_Observations_Cache::group_options() as $group => $label ) : ?>
				<?php
				$url = remove_query_arg( array( 'ucnature_inat_group', 'ucnature_inat_page' ) );
				if ( '' !== $group ) {
					$url = add_query_arg( 'ucnature_inat_group', $group, $url );
				}
				?>
				<a class="ucnature-inat__filter<?php echo $group === $active_group ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $group === $active_group ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}
}
