<?php
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<div class="page-title-bar page-title-bar--white page-title-bar--center">
	<div class="container">
		<h1><?php echo esc_html( $is_sv ? 'Magasinet' : 'The Journal' ); ?></h1>
		<p class="page-title-bar__category">
			<?php
			$current_term = get_queried_object();
			if ( $current_term instanceof WP_Term && $current_term->parent ) {
				$parent_term = get_term( $current_term->parent, 'category' );
				if ( $parent_term && ! is_wp_error( $parent_term ) ) {
					echo esc_html( $parent_term->name ) . ' &gt; ' . esc_html( $current_term->name );
				} else {
					single_cat_title();
				}
			} else {
				single_cat_title();
			}
			?>
		</p>
	</div>
</div>

<?php cos_journal_subnav(); ?>

<div class="container section">
	<?php if ( have_posts() ) : ?>
		<div class="card-grid journal-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php cos_journal_card( get_the_ID() ); ?>
			<?php endwhile; ?>
		</div>

		<?php the_posts_pagination( array(
			'prev_text' => $is_sv ? esc_html__( '← Nyare', 'cos-theme' ) : esc_html__( '← Newer', 'cos-theme' ),
			'next_text' => $is_sv ? esc_html__( 'Äldre →', 'cos-theme' ) : esc_html__( 'Older →', 'cos-theme' ),
		) ); ?>
	<?php else : ?>
		<p>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Inga artiklar i den här kategorin än. Titta gärna in igen snart.', 'cos-theme' );
			} else {
				esc_html_e( 'No articles in this category yet. Check back soon.', 'cos-theme' );
			}
			?>
		</p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
