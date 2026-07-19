<?php get_header(); ?>

<div class="page-title-bar">
	<div class="container">
		<h1><?php esc_html_e( 'For Sale', 'cos-theme' ); ?></h1>
		<p><?php esc_html_e( 'Historic castles, manors and estates currently for sale in Sweden.', 'cos-theme' ); ?></p>
	</div>
</div>

<div class="container section">
	<?php if ( have_posts() ) : ?>
		<div class="card-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php cos_listing_card( get_the_ID() ); ?>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No listings are currently available. Check back soon.', 'cos-theme' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
