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
		<label>
			<?php echo esc_html( $is_sv ? 'Arkitektonisk stil' : 'Architectural Style' ); ?>
			<select id="cos-map-style"><option value=""><?php echo esc_html( $is_sv ? 'Alla stilar' : 'All styles' ); ?></option></select>
		</label>
		<label>
			<?php echo esc_html( $is_sv ? 'Epok' : 'Era' ); ?>
			<select id="cos-map-era"><option value=""><?php echo esc_html( $is_sv ? 'Alla epoker' : 'All eras' ); ?></option></select>
		</label>
		<p id="cos-map-count" class="card__meta"></p>
	</aside>
	<div id="cos-map"></div>
</div>

<?php get_footer(); ?>
