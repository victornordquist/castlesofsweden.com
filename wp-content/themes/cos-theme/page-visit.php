<?php
/**
 * Template Name: Visit
 */
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<div class="page-title-bar">
	<div class="container">
		<h1><?php the_title(); ?></h1>
		<p>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Utforska Sveriges slott, herrgårdar och palats utifrån vad du vill göra eller efter landskap.', 'cos-theme' );
			} else {
				esc_html_e( 'Browse Sweden\'s castles, manors and palaces by what you want to do, or by region.', 'cos-theme' );
			}
			?>
		</p>
	</div>
</div>

<div class="container section">
	<div class="visit-groups">
		<div>
			<h2>
				<?php
				if ( $is_sv ) {
					esc_html_e( 'Aktiviteter', 'cos-theme' );
				} else {
					esc_html_e( 'Things to Do', 'cos-theme' );
				}
				?>
			</h2>
			<div class="tile-grid">
				<?php
				$categories = get_terms( array( 'taxonomy' => 'cos_category', 'hide_empty' => true ) );
				foreach ( $categories as $term ) :
					cos_image_tile( $term, cos_tile_image_url( 'categories', cos_term_image_slug( $term ) ), $is_sv ? __( 'destinationer', 'cos-theme' ) : __( 'destinations', 'cos-theme' ) );
				endforeach;
				?>
			</div>
		</div>

		<div>
			<h2>
				<?php
				if ( $is_sv ) {
					esc_html_e( 'Landskap', 'cos-theme' );
				} else {
					esc_html_e( 'Regions', 'cos-theme' );
				}
				?>
			</h2>
			<div class="tile-grid">
				<?php
				$regions = get_terms( array( 'taxonomy' => 'cos_region', 'hide_empty' => true, 'orderby' => 'name' ) );
				foreach ( $regions as $term ) :
					cos_image_tile( $term, cos_tile_image_url( 'regions', cos_term_image_slug( $term ) ), $is_sv ? __( 'destinationer', 'cos-theme' ) : __( 'destinations', 'cos-theme' ) );
				endforeach;
				?>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>
