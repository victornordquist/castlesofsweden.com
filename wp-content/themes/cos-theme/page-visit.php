<?php get_header(); ?>

<div class="page-title-bar">
	<div class="container">
		<h1><?php the_title(); ?></h1>
		<p><?php esc_html_e( 'Browse Sweden\'s castles, manors and palaces by what you want to do, or by region.', 'cos-theme' ); ?></p>
	</div>
</div>

<div class="container section">
	<div class="visit-groups">
		<div>
			<h2><?php esc_html_e( 'Things to Do', 'cos-theme' ); ?></h2>
			<div class="tile-grid">
				<?php
				$categories = get_terms( array( 'taxonomy' => 'cos_category', 'hide_empty' => true ) );
				foreach ( $categories as $term ) :
					cos_image_tile( $term, cos_tile_image_url( 'categories', $term->slug ), __( 'destinations', 'cos-theme' ) );
				endforeach;
				?>
			</div>
		</div>

		<div>
			<h2><?php esc_html_e( 'Regions', 'cos-theme' ); ?></h2>
			<div class="tile-grid">
				<?php
				$regions = get_terms( array( 'taxonomy' => 'cos_region', 'hide_empty' => true, 'orderby' => 'name' ) );
				foreach ( $regions as $term ) :
					cos_image_tile( $term, cos_tile_image_url( 'regions', $term->slug ), __( 'destinations', 'cos-theme' ) );
				endforeach;
				?>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>
