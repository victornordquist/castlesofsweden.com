<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>
	<div class="page-title-bar">
		<div class="container">
			<h1><?php the_title(); ?></h1>
		</div>
	</div>
	<div class="container section">
		<?php the_content(); ?>
	</div>
<?php endwhile; ?>

<?php get_footer(); ?>
