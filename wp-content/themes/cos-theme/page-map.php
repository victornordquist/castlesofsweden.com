<?php
/**
 * Template Name: Map
 */
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<div class="map-page">
	<aside class="map-filters">
		<label>
			<?php echo esc_html( $is_sv ? 'Sök' : 'Search' ); ?>
			<input type="text" id="cos-map-search" placeholder="<?php echo esc_attr( $is_sv ? 'Sök efter namn' : 'Search by name' ); ?>">
		</label>
		<label>
			<?php echo esc_html( $is_sv ? 'Landskap' : 'Region' ); ?>
			<select id="cos-map-region"><option value=""><?php echo esc_html( $is_sv ? 'Alla landskap' : 'All regions' ); ?></option></select>
		</label>
		<label>
			<?php echo esc_html( $is_sv ? 'Byggnadstyp' : 'Building Type' ); ?>
			<select id="cos-map-type"><option value=""><?php echo esc_html( $is_sv ? 'Alla typer' : 'All types' ); ?></option></select>
		</label>
		<div class="map-filters__group">
			<span class="map-filters__group-label"><?php echo esc_html( $is_sv ? 'Kategori' : 'Category' ); ?></span>
			<div id="cos-map-category" class="map-filters__checkbox-group"></div>
		</div>
		<div class="map-filters__group">
			<span class="map-filters__group-label"><?php echo esc_html( $is_sv ? 'Aktiviteter' : 'Activities' ); ?></span>
			<div id="cos-map-activity" class="map-filters__checkbox-group"></div>
		</div>
		<div class="map-filters__group">
			<span class="map-filters__group-label"><?php echo esc_html( $is_sv ? 'Faciliteter' : 'Facilities' ); ?></span>
			<div id="cos-map-feature" class="map-filters__checkbox-group"></div>
		</div>
		<label>
			<?php echo esc_html( $is_sv ? 'Arkitektonisk stil' : 'Architectural Style' ); ?>
			<select id="cos-map-style"><option value=""><?php echo esc_html( $is_sv ? 'Alla stilar' : 'All styles' ); ?></option></select>
		</label>
		<label>
			<?php echo esc_html( $is_sv ? 'Epok' : 'Era' ); ?>
			<select id="cos-map-era"><option value=""><?php echo esc_html( $is_sv ? 'Alla epoker' : 'All eras' ); ?></option></select>
		</label>
		<div class="map-filters__group map-filters__near-me">
			<label class="map-filters__checkbox">
				<input type="checkbox" id="cos-map-near-me">
				<?php cos_pin_icon_svg( 14 ); ?>
				<?php echo esc_html( $is_sv ? 'Använd min plats' : 'Use my location' ); ?>
			</label>
			<select id="cos-map-near-me-radius">
				<option value="10"><?php echo esc_html( $is_sv ? 'Inom 10 km' : 'Within 10 km' ); ?></option>
				<option value="25" selected><?php echo esc_html( $is_sv ? 'Inom 25 km' : 'Within 25 km' ); ?></option>
				<option value="50"><?php echo esc_html( $is_sv ? 'Inom 50 km' : 'Within 50 km' ); ?></option>
				<option value="100"><?php echo esc_html( $is_sv ? 'Inom 100 km' : 'Within 100 km' ); ?></option>
			</select>
			<p id="cos-map-near-me-status" aria-live="polite" hidden class="card__meta"></p>
		</div>
		<p id="cos-map-count" class="card__meta"></p>
	</aside>
	<div id="cos-map"></div>
</div>

<?php get_footer(); ?>
