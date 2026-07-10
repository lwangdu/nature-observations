<?php
/**
 * Observation card template.
 *
 * @package Field_Observation_Showcase
 *
 * @var array $observation Observation data.
 * @var bool  $open_links_in_new_tab Whether observation links open in a new tab.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field_observation_showcase_quality_label = 'research' === $observation['quality_grade']
	? __( 'Research Grade', 'field-observation-showcase' )
	: ucwords( str_replace( '_', ' ', $observation['quality_grade'] ) );

if ( 'unknown' === $observation['quality_grade'] ) {
	$field_observation_showcase_quality_label = __( 'Unknown status', 'field-observation-showcase' );
}

$field_observation_showcase_show_scientific_name = ! empty( $observation['scientific_name'] ) && 0 !== strcasecmp( $observation['common_name'], $observation['scientific_name'] );
$field_observation_showcase_observed_timestamp   = ! empty( $observation['observed_on'] ) ? strtotime( $observation['observed_on'] ) : false;
?>
<article class="field-observation-showcase-card" aria-label="<?php echo esc_attr( $observation['common_name'] ); ?>">
	<div class="field-observation-showcase-card__media">
		<?php if ( $observation['photo_url'] ) : ?>
			<?php if ( ! empty( $observation['url'] ) ) : ?>
				<a class="field-observation-showcase-card__media-link" href="<?php echo esc_url( $observation['url'] ); ?>"<?php echo $open_links_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<img src="<?php echo esc_url( $observation['photo_url'] ); ?>" alt="<?php echo esc_attr( $observation['photo_alt'] ); ?>" loading="lazy" decoding="async">
					<?php if ( $open_links_in_new_tab ) : ?>
						<span class="screen-reader-text"> <?php esc_html_e( 'opens in a new tab', 'field-observation-showcase' ); ?></span>
					<?php endif; ?>
				</a>
			<?php else : ?>
				<img src="<?php echo esc_url( $observation['photo_url'] ); ?>" alt="<?php echo esc_attr( $observation['photo_alt'] ); ?>" loading="lazy" decoding="async">
			<?php endif; ?>
		<?php else : ?>
			<span class="field-observation-showcase-card__placeholder"><?php esc_html_e( 'No photo', 'field-observation-showcase' ); ?></span>
		<?php endif; ?>
	</div>
	<div class="field-observation-showcase-card__body">
		<?php if ( ! empty( $observation['taxon_group'] ) ) : ?>
			<p class="field-observation-showcase-card__group"><?php echo esc_html( $observation['taxon_group'] ); ?></p>
		<?php endif; ?>
		<h3 class="field-observation-showcase-card__name">
			<?php if ( ! empty( $observation['url'] ) ) : ?>
				<a href="<?php echo esc_url( $observation['url'] ); ?>"<?php echo $open_links_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<?php echo esc_html( $observation['common_name'] ); ?>
					<?php if ( $open_links_in_new_tab ) : ?>
						<span class="screen-reader-text"> <?php esc_html_e( 'opens in a new tab', 'field-observation-showcase' ); ?></span>
					<?php endif; ?>
				</a>
			<?php else : ?>
				<?php echo esc_html( $observation['common_name'] ); ?>
			<?php endif; ?>
		</h3>
		<?php if ( $field_observation_showcase_show_scientific_name ) : ?>
			<p class="field-observation-showcase-card__scientific"><em><?php echo esc_html( $observation['scientific_name'] ); ?></em></p>
		<?php endif; ?>
		<div class="field-observation-showcase-card__details">
			<div class="field-observation-showcase-card__meta">
				<?php if ( false !== $field_observation_showcase_observed_timestamp ) : ?>
					<time datetime="<?php echo esc_attr( $observation['observed_on'] ); ?>"><?php echo esc_html( date_i18n( get_option( 'date_format' ), $field_observation_showcase_observed_timestamp ) ); ?></time>
				<?php endif; ?>
				<p><?php echo esc_html( $observation['observer'] ); ?></p>
			</div>
			<p class="field-observation-showcase-card__grade"><?php echo esc_html( $field_observation_showcase_quality_label ); ?></p>
		</div>
	</div>
</article>
