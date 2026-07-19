<?php
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<?php cos_journal_subnav(); ?>

<?php while ( have_posts() ) : the_post();
	$categories       = get_the_category();
	$thumbnail_id     = get_post_thumbnail_id();
	$thumbnail_caption = $thumbnail_id ? get_the_post_thumbnail_caption( $thumbnail_id ) : '';
	?>

	<article class="container section news-article">
		<div class="news-article__header">
			<?php if ( $categories ) : ?>
				<p class="news-article__category">
					<?php echo esc_html( implode( ', ', wp_list_pluck( $categories, 'name' ) ) ); ?>
				</p>
			<?php endif; ?>

			<h1><?php the_title(); ?></h1>

			<p class="news-article__meta">
				<?php
				if ( $is_sv ) {
					printf(
						/* translators: 1: author name, 2: publish date */
						esc_html__( 'Text av %1$s · %2$s', 'cos-theme' ),
						esc_html( get_the_author() ),
						esc_html( get_the_date() )
					);
				} else {
					printf(
						/* translators: 1: author name, 2: publish date */
						esc_html__( 'Words by %1$s · %2$s', 'cos-theme' ),
						esc_html( get_the_author() ),
						esc_html( get_the_date() )
					);
				}
				?>
			</p>
		</div>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="news-article__featured">
				<?php the_post_thumbnail( 'large' ); ?>
				<?php if ( $thumbnail_caption ) : ?>
					<figcaption><?php echo esc_html( $thumbnail_caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="news-article__content">
			<?php the_content(); ?>
		</div>
	</article>
<?php endwhile; ?>

<?php get_footer(); ?>
