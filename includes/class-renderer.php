<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UCNature_iNat_Observations_Renderer {
	public function __construct() {
		add_action( 'init', array( $this, 'register_assets' ), 5 );
		add_action( 'init', array( $this, 'register_block' ) );
		add_shortcode( 'ucnature_inat_observations', array( $this, 'render_shortcode' ) );
	}

	public function register_assets() {
		wp_register_style(
			'ucnature-inat-observations',
			UCNATURE_INAT_URL . 'assets/css/frontend.css',
			array(),
			UCNATURE_INAT_VERSION
		);
	}

	public function register_block() {
		$options = UCNature_iNat_Observations_Admin::get_options();

		wp_register_script(
			'ucnature-inat-observations-block',
			UCNATURE_INAT_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
			UCNATURE_INAT_VERSION,
			true
		);
		wp_add_inline_script(
			'ucnature-inat-observations-block',
			'window.ucnatureINatObservations = ' . wp_json_encode(
				array(
					'maxPerPage' => UCNature_iNat_Observations_Cache::MAX_PER_PAGE,
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
					'projectId'   => array(
						'type'    => 'number',
						'default' => absint( $options['project_id'] ),
					),
					'projectSlug' => array(
						'type'    => 'string',
						'default' => $options['project_slug'],
					),
					'placeId'     => array(
						'type'    => 'number',
						'default' => 0,
					),
					'userId'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'perPage'     => array(
						'type'    => 'number',
						'default' => absint( $options['per_page'] ),
					),
					'title'       => array(
						'type'    => 'string',
						'default' => '',
					),
					'summary'     => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	public function render_shortcode( $atts ) {
		$options = UCNature_iNat_Observations_Admin::get_options();
		$atts    = shortcode_atts(
			array(
				'project_id'   => $options['project_id'],
				'project_slug' => $options['project_slug'],
				'place_id'     => 0,
				'user_id'      => '',
				'per_page'     => $options['per_page'],
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
				'title'        => $atts['title'],
				'summary'      => $atts['summary'],
			)
		);
	}

	public function render_block( $attributes ) {
		return $this->render(
			array(
				'project_id'   => $attributes['projectId'] ?? 3234,
				'project_slug' => $attributes['projectSlug'] ?? '',
				'place_id'     => $attributes['placeId'] ?? 0,
				'user_id'      => $attributes['userId'] ?? '',
				'per_page'     => $attributes['perPage'] ?? 100,
				'title'        => $attributes['title'] ?? '',
				'summary'      => $attributes['summary'] ?? '',
			)
		);
	}

	private function render( $args ) {
		wp_enqueue_style( 'ucnature-inat-observations' );

		$group      = UCNature_iNat_Observations_Cache::sanitize_group( wp_unslash( $_GET['ucnature_inat_group'] ?? '' ) );
		$page       = max( 1, absint( wp_unslash( $_GET['ucnature_inat_page'] ?? 1 ) ) );
		$query_args = array(
			'project_id'   => $args['project_id'],
			'project_slug' => $args['project_slug'],
			'place_id'     => $args['place_id'],
			'user_id'      => $args['user_id'],
			'per_page'     => $args['per_page'],
			'page'         => $page,
			'group'        => $group,
		);
		$data       = UCNature_iNat_Observations_Cache::get_observations( $query_args );

		$heading_id = wp_unique_id( 'ucnature-inat-heading-' );
		$title      = sanitize_text_field( $args['title'] );
		$summary    = sanitize_text_field( $args['summary'] );
		$has_header = '' !== $title || '' !== $summary;
		$labelledby = '' !== $title ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : '';
		$stats_title = '' !== $title ? $title : __( 'iNaturalist', 'ucnature-inat-observations' );
		$stats      = is_wp_error( $data ) ? null : UCNature_iNat_Observations_Cache::get_source_stats( $query_args );

		ob_start();
		?>
		<section class="ucnature-inat"<?php echo $labelledby; ?>>
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
				<?php $this->render_stats_cards( UCNature_iNat_Observations_Cache::displayed_stats( $data ), $stats, $stats_title ); ?>
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

	private function render_stats_cards( $displayed_stats, $source_stats, $title ) {
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
						<a class="ucnature-inat-stats__link" href="<?php echo esc_url( $source_stats['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'View full project on iNaturalist', 'ucnature-inat-observations' ); ?>
							<span aria-hidden="true">→</span>
							<span class="screen-reader-text"> <?php esc_html_e( 'opens in a new tab', 'ucnature-inat-observations' ); ?></span>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_stat_metric( $value, $label ) {
		?>
		<div class="ucnature-inat-stats__metric">
			<strong><?php echo esc_html( number_format_i18n( absint( $value ) ) ); ?></strong>
			<span><?php echo esc_html( $label ); ?></span>
		</div>
		<?php
	}

	private function render_pagination( $data ) {
		$total_results = absint( $data['total_results'] ?? 0 );
		$per_page      = max( 1, absint( $data['per_page'] ?? 100 ) );
		$current_page  = max( 1, absint( $data['page'] ?? 1 ) );
		$total_pages   = (int) ceil( $total_results / $per_page );

		if ( $total_pages <= 1 ) {
			return;
		}

		$start = max( 1, $current_page - 2 );
		$end   = min( $total_pages, $current_page + 2 );
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
					<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( $current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'ucnature-inat-observations' ); ?></a>
				<?php endif; ?>

				<?php if ( $start > 1 ) : ?>
					<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( 1 ) ); ?>">1</a>
					<?php if ( $start > 2 ) : ?>
						<span class="ucnature-inat-pagination__ellipsis" aria-hidden="true">...</span>
					<?php endif; ?>
				<?php endif; ?>

				<?php for ( $page = $start; $page <= $end; $page++ ) : ?>
					<?php if ( $page === $current_page ) : ?>
						<span class="ucnature-inat-pagination__link is-current" aria-current="page"><?php echo esc_html( number_format_i18n( $page ) ); ?></span>
					<?php else : ?>
						<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( $page ) ); ?>"><?php echo esc_html( number_format_i18n( $page ) ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>

				<?php if ( $end < $total_pages ) : ?>
					<?php if ( $end < $total_pages - 1 ) : ?>
						<span class="ucnature-inat-pagination__ellipsis" aria-hidden="true">...</span>
					<?php endif; ?>
					<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( $total_pages ) ); ?>"><?php echo esc_html( number_format_i18n( $total_pages ) ); ?></a>
				<?php endif; ?>

				<?php if ( $current_page < $total_pages ) : ?>
					<a class="ucnature-inat-pagination__link" href="<?php echo esc_url( $this->pagination_url( $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'ucnature-inat-observations' ); ?></a>
				<?php endif; ?>
			</div>
		</nav>
		<?php
	}

	private function pagination_url( $page ) {
		if ( $page <= 1 ) {
			return remove_query_arg( 'ucnature_inat_page' );
		}

		return add_query_arg( 'ucnature_inat_page', absint( $page ) );
	}

	private function render_filters( $active_group ) {
		?>
		<nav class="ucnature-inat__filters" aria-label="<?php esc_attr_e( 'Observation filters', 'ucnature-inat-observations' ); ?>">
			<?php foreach ( UCNature_iNat_Observations_Cache::group_options() as $group => $label ) : ?>
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
