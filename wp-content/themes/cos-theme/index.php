<?php get_header(); ?>

<div class="container section">
	<?php if ( have_posts() ) : ?>
		<div class="card-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php cos_card( get_permalink(), get_the_title(), get_the_post_thumbnail_url( get_the_ID(), 'medium' ), get_the_date() ); ?>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'cos-theme' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
