<?php
/**
 * Template Name: Saved Places
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
				esc_html_e( 'Jämför dina sparade resmål sida vid sida och planera din resa bland Sveriges slott och herrgårdar.', 'cos-theme' );
			} else {
				esc_html_e( 'Compare your saved destinations side by side and plan your trip across Sweden\'s castles and manors.', 'cos-theme' );
			}
			?>
		</p>
	</div>
</div>

<div class="container section">
	<div id="cos-saved-places"></div>
</div>

<?php get_footer(); ?>
