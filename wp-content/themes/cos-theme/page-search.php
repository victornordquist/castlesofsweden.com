<?php get_header(); ?>

<div class="page-title-bar">
	<div class="container">
		<h1><?php the_title(); ?></h1>
		<p><?php esc_html_e( 'Search destinations, categories, regions and more.', 'cos-theme' ); ?></p>
	</div>
</div>

<div class="container section">
	<?php get_template_part( 'template-parts/search-full' ); ?>
</div>

<?php get_footer(); ?>
