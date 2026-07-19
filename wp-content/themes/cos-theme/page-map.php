<?php get_header(); ?>

<div class="map-page">
	<aside class="map-filters">
		<label>
			<?php esc_html_e( 'Search', 'cos-theme' ); ?>
			<input type="text" id="cos-map-search" placeholder="<?php esc_attr_e( 'Search by name', 'cos-theme' ); ?>">
		</label>
		<label>
			<?php esc_html_e( 'Region', 'cos-theme' ); ?>
			<select id="cos-map-region"><option value=""><?php esc_html_e( 'All regions', 'cos-theme' ); ?></option></select>
		</label>
		<label>
			<?php esc_html_e( 'Building Type', 'cos-theme' ); ?>
			<select id="cos-map-type"><option value=""><?php esc_html_e( 'All types', 'cos-theme' ); ?></option></select>
		</label>
		<div class="map-filters__group">
			<span class="map-filters__group-label"><?php esc_html_e( 'Category', 'cos-theme' ); ?></span>
			<div id="cos-map-category" class="map-filters__checkbox-group"></div>
		</div>
		<label>
			<?php esc_html_e( 'Architectural Style', 'cos-theme' ); ?>
			<select id="cos-map-style"><option value=""><?php esc_html_e( 'All styles', 'cos-theme' ); ?></option></select>
		</label>
		<label>
			<?php esc_html_e( 'Era', 'cos-theme' ); ?>
			<select id="cos-map-era"><option value=""><?php esc_html_e( 'All eras', 'cos-theme' ); ?></option></select>
		</label>
		<p id="cos-map-count" class="card__meta"></p>
	</aside>
	<div id="cos-map"></div>
</div>

<?php get_footer(); ?>
