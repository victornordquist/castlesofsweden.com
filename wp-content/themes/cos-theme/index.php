<?php
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<div class="container section">
	<?php if ( have_posts() ) : ?>
		<div class="card-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php cos_card( get_permalink(), get_the_title(), get_the_post_thumbnail_url( get_the_ID(), 'medium' ), get_the_date() ); ?>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<p><?php echo esc_html( $is_sv ? 'Inget hittades.' : 'Nothing found.' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
