<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quality_label = 'research' === $observation['quality_grade']
	? __( 'Research Grade', 'ucnature-inat-observations' )
	: ucwords( str_replace( '_', ' ', $observation['quality_grade'] ) );

if ( 'unknown' === $observation['quality_grade'] ) {
	$quality_label = __( 'Unknown status', 'ucnature-inat-observations' );
}

$show_scientific_name = ! empty( $observation['scientific_name'] ) && 0 !== strcasecmp( $observation['common_name'], $observation['scientific_name'] );
?>
<article class="ucnature-inat-card" aria-label="<?php echo esc_attr( $observation['common_name'] ); ?>">
	<div class="ucnature-inat-card__media">
		<?php if ( $observation['photo_url'] ) : ?>
			<img src="<?php echo esc_url( $observation['photo_url'] ); ?>" alt="<?php echo esc_attr( $observation['photo_alt'] ); ?>" loading="lazy" decoding="async">
		<?php else : ?>
			<span class="ucnature-inat-card__placeholder"><?php esc_html_e( 'No photo', 'ucnature-inat-observations' ); ?></span>
		<?php endif; ?>
	</div>
	<div class="ucnature-inat-card__body">
		<?php if ( ! empty( $observation['taxon_group'] ) ) : ?>
			<p class="ucnature-inat-card__group"><?php echo esc_html( $observation['taxon_group'] ); ?></p>
		<?php endif; ?>
		<h3 class="ucnature-inat-card__name">
			<?php if ( ! empty( $observation['url'] ) ) : ?>
				<a href="<?php echo esc_url( $observation['url'] ); ?>"<?php echo $open_links_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<?php echo esc_html( $observation['common_name'] ); ?>
					<?php if ( $open_links_in_new_tab ) : ?>
						<span class="screen-reader-text"> <?php esc_html_e( 'opens in a new tab', 'ucnature-inat-observations' ); ?></span>
					<?php endif; ?>
				</a>
			<?php else : ?>
				<?php echo esc_html( $observation['common_name'] ); ?>
			<?php endif; ?>
		</h3>
		<?php if ( $show_scientific_name ) : ?>
			<p class="ucnature-inat-card__scientific"><em><?php echo esc_html( $observation['scientific_name'] ); ?></em></p>
		<?php endif; ?>
		<div class="ucnature-inat-card__details">
			<div class="ucnature-inat-card__meta">
				<?php if ( $observation['observed_on'] ) : ?>
					<time datetime="<?php echo esc_attr( $observation['observed_on'] ); ?>"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $observation['observed_on'] ) ) ); ?></time>
				<?php endif; ?>
				<p><?php echo esc_html( $observation['observer'] ); ?></p>
			</div>
			<p class="ucnature-inat-card__grade"><?php echo esc_html( $quality_label ); ?></p>
		</div>
	</div>
</article>
