<?php
get_header();
$is_sv           = 'sv' === COS_Language_Routing::current_lang();
$cos_term        = get_queried_object();
$cos_description = term_description();
?>

<div class="term-header">
	<div class="term-header__text">
		<h1><?php single_term_title(); ?></h1>
		<?php if ( $cos_description ) : ?>
			<div class="term-header__description"><?php echo wp_kses_post( wpautop( $cos_description ) ); ?></div>
		<?php endif; ?>
	</div>
	<div class="term-header__map">
		<div id="cos-term-map" data-taxonomy="cos_category" data-term="<?php echo esc_attr( $cos_term->slug ); ?>"></div>
	</div>
</div>

<div class="container section">
	<?php if ( have_posts() ) : ?>
		<div class="card-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php cos_building_card( get_the_ID() ); ?>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php echo esc_html( $is_sv ? 'Inga byggnader hittades.' : 'No buildings found.' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
