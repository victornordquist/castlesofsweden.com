<?php
/**
 * Template Name: Search
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
				esc_html_e( 'Sök bland destinationer, kategorier, landskap med mera.', 'cos-theme' );
			} else {
				esc_html_e( 'Search destinations, categories, regions and more.', 'cos-theme' );
			}
			?>
		</p>
	</div>
</div>

<div class="container section">
	<?php get_template_part( 'template-parts/search-full' ); ?>
</div>

<?php get_footer(); ?>
