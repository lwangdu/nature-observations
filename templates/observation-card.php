<?php
/**
 * Observation card template.
 *
 * @package Nature_INat_Observations
 *
 * @var array $observation Observation data.
 * @var bool  $open_links_in_new_tab Whether observation links open in a new tab.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quality_label = 'research' === $observation['quality_grade']
	? __( 'Research Grade', 'nature-inat-observations' )
	: ucwords( str_replace( '_', ' ', $observation['quality_grade'] ) );

if ( 'unknown' === $observation['quality_grade'] ) {
	$quality_label = __( 'Unknown status', 'nature-inat-observations' );
}

$show_scientific_name = ! empty( $observation['scientific_name'] ) && 0 !== strcasecmp( $observation['common_name'], $observation['scientific_name'] );
$observed_timestamp   = ! empty( $observation['observed_on'] ) ? strtotime( $observation['observed_on'] ) : false;
?>
<article class="nature-inat-card" aria-label="<?php echo esc_attr( $observation['common_name'] ); ?>">
	<div class="nature-inat-card__media">
		<?php if ( $observation['photo_url'] ) : ?>
			<?php if ( ! empty( $observation['url'] ) ) : ?>
				<a class="nature-inat-card__media-link" href="<?php echo esc_url( $observation['url'] ); ?>"<?php echo $open_links_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<img src="<?php echo esc_url( $observation['photo_url'] ); ?>" alt="<?php echo esc_attr( $observation['photo_alt'] ); ?>" loading="lazy" decoding="async">
					<?php if ( $open_links_in_new_tab ) : ?>
						<span class="screen-reader-text"> <?php esc_html_e( 'opens in a new tab', 'nature-inat-observations' ); ?></span>
					<?php endif; ?>
				</a>
			<?php else : ?>
				<img src="<?php echo esc_url( $observation['photo_url'] ); ?>" alt="<?php echo esc_attr( $observation['photo_alt'] ); ?>" loading="lazy" decoding="async">
			<?php endif; ?>
		<?php else : ?>
			<span class="nature-inat-card__placeholder"><?php esc_html_e( 'No photo', 'nature-inat-observations' ); ?></span>
		<?php endif; ?>
	</div>
	<div class="nature-inat-card__body">
		<?php if ( ! empty( $observation['taxon_group'] ) ) : ?>
			<p class="nature-inat-card__group"><?php echo esc_html( $observation['taxon_group'] ); ?></p>
		<?php endif; ?>
		<h3 class="nature-inat-card__name">
			<?php if ( ! empty( $observation['url'] ) ) : ?>
				<a href="<?php echo esc_url( $observation['url'] ); ?>"<?php echo $open_links_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<?php echo esc_html( $observation['common_name'] ); ?>
					<?php if ( $open_links_in_new_tab ) : ?>
						<span class="screen-reader-text"> <?php esc_html_e( 'opens in a new tab', 'nature-inat-observations' ); ?></span>
					<?php endif; ?>
				</a>
			<?php else : ?>
				<?php echo esc_html( $observation['common_name'] ); ?>
			<?php endif; ?>
		</h3>
		<?php if ( $show_scientific_name ) : ?>
			<p class="nature-inat-card__scientific"><em><?php echo esc_html( $observation['scientific_name'] ); ?></em></p>
		<?php endif; ?>
		<div class="nature-inat-card__details">
			<div class="nature-inat-card__meta">
				<?php if ( false !== $observed_timestamp ) : ?>
					<time datetime="<?php echo esc_attr( $observation['observed_on'] ); ?>"><?php echo esc_html( date_i18n( get_option( 'date_format' ), $observed_timestamp ) ); ?></time>
				<?php endif; ?>
				<p><?php echo esc_html( $observation['observer'] ); ?></p>
			</div>
			<p class="nature-inat-card__grade"><?php echo esc_html( $quality_label ); ?></p>
		</div>
	</div>
</article>
