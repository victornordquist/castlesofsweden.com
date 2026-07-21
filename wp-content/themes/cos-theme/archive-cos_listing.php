<?php
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<div class="page-title-bar">
	<div class="container">
		<h1><?php echo esc_html( $is_sv ? 'Till salu' : 'For Sale' ); ?></h1>
		<p>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Historiska slott, herrgårdar och gods till salu i Sverige.', 'cos-theme' );
			} else {
				esc_html_e( 'Historic castles, manors and estates currently for sale in Sweden.', 'cos-theme' );
			}
			?>
		</p>
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
		<p>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Inga objekt är tillgängliga just nu. Titta gärna in igen snart.', 'cos-theme' );
			} else {
				esc_html_e( 'No listings are currently available. Check back soon.', 'cos-theme' );
			}
			?>
		</p>
	<?php endif; ?>
</div>

<?php
$sold_listings = new WP_Query( array(
	'post_type'      => 'cos_listing',
	'posts_per_page' => -1,
	'meta_key'       => 'cos_listing_sold',
	'meta_value'     => '1',
) );
if ( $sold_listings->have_posts() ) :
	?>
	<div class="container section section--beige">
		<h2><?php echo esc_html( $is_sv ? 'Sålda objekt' : 'Sold' ); ?></h2>
		<div class="card-grid">
			<?php while ( $sold_listings->have_posts() ) : $sold_listings->the_post(); ?>
				<?php cos_listing_card( get_the_ID() ); ?>
			<?php endwhile; ?>
		</div>
	</div>
	<?php
	wp_reset_postdata();
endif;
?>

<?php get_footer(); ?>
