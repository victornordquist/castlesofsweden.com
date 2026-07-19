<?php get_header(); ?>

<div class="page-title-bar page-title-bar--white page-title-bar--center">
	<div class="container">
		<h1><?php the_title(); ?></h1>
	</div>
</div>

<?php cos_journal_subnav(); ?>

<?php
$paged = max( 1, (int) get_query_var( 'paged' ) );

$featured_id    = 0;
$featured_query = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => 1,
	'meta_key'       => 'cos_featured',
	'meta_value'     => '1',
) );
if ( ! $featured_query->have_posts() ) {
	$featured_query = new WP_Query( array(
		'post_type'      => 'post',
		'posts_per_page' => 1,
	) );
}
if ( $featured_query->have_posts() ) {
	$featured_query->the_post();
	$featured_id = get_the_ID();

	if ( 1 === $paged ) :
		?>
		<div class="container">
			<a class="journal-featured" href="<?php the_permalink(); ?>">
				<div class="journal-featured__image">
					<?php echo get_the_post_thumbnail( $featured_id, 'large' ); ?>
				</div>
				<div class="journal-featured__body">
					<?php $categories = get_the_category(); ?>
					<?php if ( $categories ) : ?>
						<p class="journal-featured__category"><?php echo esc_html( $categories[0]->name ); ?></p>
					<?php endif; ?>
					<h2 class="journal-featured__title"><?php the_title(); ?></h2>
					<p class="journal-featured__author">
						<?php
						/* translators: %s: author name */
						printf( esc_html__( 'Words by %s', 'cos-theme' ), esc_html( get_the_author() ) );
						?>
					</p>
				</div>
			</a>
		</div>
		<?php
	endif;
}
wp_reset_postdata();
?>

<div class="container section">
	<?php
	$journal_query = new WP_Query( array(
		'post_type'      => 'post',
		'posts_per_page' => 12,
		'paged'          => $paged,
		'post__not_in'   => $featured_id ? array( $featured_id ) : array(),
	) );
	?>

	<?php if ( $journal_query->have_posts() ) : ?>
		<div class="card-grid journal-grid">
			<?php while ( $journal_query->have_posts() ) : $journal_query->the_post(); ?>
				<?php cos_journal_card( get_the_ID() ); ?>
			<?php endwhile; ?>
		</div>

		<?php if ( $journal_query->max_num_pages > 1 ) : ?>
			<nav class="pagination">
				<?php if ( $paged > 1 ) : ?>
					<a class="page-numbers" href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1 ) ); ?>"><?php esc_html_e( '← Newer', 'cos-theme' ); ?></a>
				<?php endif; ?>
				<?php if ( $paged < $journal_query->max_num_pages ) : ?>
					<a class="page-numbers" href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1 ) ); ?>"><?php esc_html_e( 'Older →', 'cos-theme' ); ?></a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No articles yet. Check back soon.', 'cos-theme' ); ?></p>
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>

<?php get_footer(); ?>
