<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
	$post_id       = get_the_ID();
	$location      = get_post_meta( $post_id, 'cos_listing_location', true );
	$building_size = get_post_meta( $post_id, 'cos_listing_building_size', true );
	$land_size     = get_post_meta( $post_id, 'cos_listing_land_size', true );
	$price_sek     = (int) get_post_meta( $post_id, 'cos_listing_price_sek', true );
	$image_credit  = get_post_meta( $post_id, 'cos_listing_image_credit', true );
	$broker_name   = get_post_meta( $post_id, 'cos_listing_broker_name', true );
	$broker_url    = get_post_meta( $post_id, 'cos_listing_broker_url', true );
	$lat           = get_post_meta( $post_id, 'cos_listing_lat', true );
	$lng           = get_post_meta( $post_id, 'cos_listing_lng', true );

	$thumbnail_url         = get_the_post_thumbnail_url( $post_id, 'full' );
	$marker_thumbnail_url  = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
	$gallery_ids           = array_filter( (array) get_post_meta( $post_id, 'cos_listing_gallery', true ) );
	?>

	<div
		class="page-title-bar page-title-bar--image<?php echo $thumbnail_url ? '' : ' page-title-bar--placeholder'; ?>"
		<?php if ( $thumbnail_url ) : ?>
			style="background-image: linear-gradient(90deg, rgba(30,26,20,0.6) 0%, rgba(30,26,20,0.15) 65%), url('<?php echo esc_url( $thumbnail_url ); ?>');"
		<?php endif; ?>
	>
		<div class="container">
			<h1><?php the_title(); ?></h1>
			<?php if ( $location ) : ?>
				<p class="page-title-bar__english-name"><?php echo esc_html( $location ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $image_credit ) : ?>
			<p class="page-title-bar__credit">
				<?php
				/* translators: %s: photo credit */
				printf( esc_html__( 'Photo: %s', 'cos-theme' ), esc_html( $image_credit ) );
				?>
			</p>
		<?php endif; ?>
	</div>

	<div class="container section">
		<div class="building-layout">
			<div class="building-layout__main">
				<?php if ( trim( get_the_content() ) !== '' ) : ?>
					<?php the_content(); ?>
				<?php else : ?>
					<p class="building-content-placeholder">
						<?php
						printf(
							/* translators: %s: listing name */
							esc_html__( 'Full details for %s are coming soon.', 'cos-theme' ),
							esc_html( get_the_title() )
						);
						?>
					</p>
				<?php endif; ?>

				<?php if ( $lat && $lng ) : ?>
					<div class="building-layout__map">
						<div id="cos-building-map" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>" data-thumbnail="<?php echo esc_attr( $marker_thumbnail_url ); ?>"></div>
					</div>
				<?php endif; ?>
			</div>

			<aside class="building-layout__sidebar">
				<div class="building-info-box">
					<?php if ( $price_sek ) : ?>
						<div class="building-info-box__price">
							<?php cos_listing_price_html( $price_sek ); ?>
						</div>
					<?php endif; ?>

					<dl class="building-info-box__list">
						<?php if ( $location ) : ?>
							<div class="building-info-box__row">
								<dt><?php esc_html_e( 'Location', 'cos-theme' ); ?></dt>
								<dd><?php echo esc_html( $location ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $building_size ) : ?>
							<div class="building-info-box__row">
								<dt><?php esc_html_e( 'Building Size', 'cos-theme' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( $building_size ) . ' m²' ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $land_size ) : ?>
							<div class="building-info-box__row">
								<dt><?php esc_html_e( 'Land Size', 'cos-theme' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( $land_size ) . ' ha' ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $broker_name ) : ?>
							<div class="building-info-box__row">
								<dt><?php esc_html_e( 'Broker', 'cos-theme' ); ?></dt>
								<dd><?php echo esc_html( $broker_name ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>

					<?php if ( $broker_url ) : ?>
						<div class="building-info-box__actions">
							<a class="button" href="<?php echo esc_url( $broker_url ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Contact broker', 'cos-theme' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</div>

	<?php if ( ! empty( $gallery_ids ) ) : ?>
		<div class="container section listing-gallery">
			<h2 class="listing-gallery__title"><?php esc_html_e( 'Photo Gallery', 'cos-theme' ); ?></h2>
			<div class="listing-gallery__grid">
				<?php foreach ( $gallery_ids as $gallery_id ) :
					$full  = wp_get_attachment_image_src( $gallery_id, 'full' );
					$thumb = wp_get_attachment_image_src( $gallery_id, 'medium_large' );
					if ( ! $full || ! $thumb ) {
						continue;
					}
					?>
					<a href="<?php echo esc_url( $full[0] ); ?>" class="listing-gallery__item">
						<img src="<?php echo esc_url( $thumb[0] ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
<?php endwhile; ?>

<?php get_footer(); ?>
