<?php
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<?php while ( have_posts() ) : the_post();
	$post_id       = get_the_ID();
	$region        = get_the_terms( $post_id, 'cos_region' );
	$building_type = get_the_terms( $post_id, 'cos_building_type' );
	$categories    = get_the_terms( $post_id, 'cos_category' );
	$activities    = get_the_terms( $post_id, 'cos_activity' );
	$features      = get_the_terms( $post_id, 'cos_feature' );
	$style         = get_the_terms( $post_id, 'cos_architectural_style' );
	$era           = get_the_terms( $post_id, 'cos_era' );

	$english_name  = get_post_meta( $post_id, 'cos_english_name', true );
	$tagline       = get_post_meta( $post_id, 'cos_tagline', true );
	$architects    = get_post_meta( $post_id, 'cos_architects', true );
	$builder       = get_post_meta( $post_id, 'cos_builder', true );
	$year_built    = get_post_meta( $post_id, 'cos_year_built', true );
	$rebuilt_year  = get_post_meta( $post_id, 'cos_rebuilt_year', true );
	$wikipedia_url = get_post_meta( $post_id, 'cos_wikipedia_url', true );
	$website_url   = get_post_meta( $post_id, 'cos_website_url', true );
	$instagram_url = get_post_meta( $post_id, 'cos_instagram_url', true );
	$map_link      = get_post_meta( $post_id, 'cos_map_link', true );
	$image_credit  = get_post_meta( $post_id, 'cos_image_credit', true );
	$lat           = get_post_meta( $post_id, 'cos_lat', true );
	$lng           = get_post_meta( $post_id, 'cos_lng', true );

	$why_visit       = get_post_meta( $post_id, 'cos_why_visit', true );
	$opening_hours   = get_post_meta( $post_id, 'cos_opening_hours', true );
	$admission       = get_post_meta( $post_id, 'cos_admission', true );
	$parking         = get_post_meta( $post_id, 'cos_parking', true );
	$accessibility   = get_post_meta( $post_id, 'cos_accessibility', true );
	$guided_tours    = get_post_meta( $post_id, 'cos_guided_tours', true );
	$related_raw     = get_post_meta( $post_id, 'cos_related_buildings', true );
	$gallery_ids     = array_filter( (array) get_post_meta( $post_id, 'cos_building_gallery', true ) );

	$has_visitor_info = $opening_hours || $admission || $parking || $accessibility || $guided_tours
		|| ( ! is_wp_error( $categories ) && $categories )
		|| ( ! is_wp_error( $activities ) && $activities )
		|| ( ! is_wp_error( $features ) && $features );

	$related_posts = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', $related_raw ) ) ) as $related_slug ) {
		$related_post = get_page_by_path( $related_slug, OBJECT, 'cos_building' );
		if ( $related_post ) {
			$related_posts[] = $related_post;
		}
	}

	$thumbnail_url      = get_the_post_thumbnail_url( $post_id, 'full' );
	$marker_thumbnail_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
	?>

	<div
		class="page-title-bar page-title-bar--image page-title-bar--building<?php echo $thumbnail_url ? '' : ' page-title-bar--placeholder'; ?>"
		<?php if ( $thumbnail_url ) : ?>
			style="background-image: linear-gradient(90deg, rgba(30,26,20,0.6) 0%, rgba(30,26,20,0.15) 65%), url('<?php echo esc_url( $thumbnail_url ); ?>');"
		<?php endif; ?>
	>
		<div class="container">
			<h1><?php the_title(); ?></h1>
			<?php if ( ! is_wp_error( $region ) && $region ) : ?>
				<p class="page-title-bar__region"><?php echo esc_html( implode( ', ', wp_list_pluck( $region, 'name' ) ) ); ?></p>
			<?php endif; ?>
			<?php if ( $tagline ) : ?>
				<p class="page-title-bar__tagline"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>
			<div class="page-title-bar__actions">
				<?php if ( $website_url ) : ?>
					<a class="button" href="<?php echo esc_url( $website_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $is_sv ? 'Officiell webbplats' : 'Official website' ); ?></a>
				<?php endif; ?>
				<?php if ( $map_link ) : ?>
					<a class="button button--outline" href="<?php echo esc_url( $map_link ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $is_sv ? 'Vägbeskrivning' : 'How to get here' ); ?></a>
				<?php endif; ?>
				<button
					type="button"
					class="button button--outline save-building-button"
					data-save-building-id="<?php echo esc_attr( $post_id ); ?>"
					data-label-save="<?php echo esc_attr( $is_sv ? 'Spara' : 'Save' ); ?>"
					data-label-saved="<?php echo esc_attr( $is_sv ? 'Sparad' : 'Saved' ); ?>"
				><?php echo esc_html( $is_sv ? 'Spara' : 'Save' ); ?></button>
			</div>
		</div>
		<?php if ( $image_credit ) : ?>
			<p class="page-title-bar__credit">
				<?php
				if ( $is_sv ) {
					/* translators: %s: photo credit */
					printf( esc_html__( 'Foto: %s', 'cos-theme' ), esc_html( $image_credit ) );
				} else {
					/* translators: %s: photo credit */
					printf( esc_html__( 'Photo: %s', 'cos-theme' ), esc_html( $image_credit ) );
				}
				?>
			</p>
		<?php endif; ?>
	</div>

	<div class="container section">
		<div class="building-layout">
			<div class="building-layout__main">
				<?php if ( $why_visit ) : ?>
					<div class="building-why-visit">
						<div class="building-why-visit__icon">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M4 21V10l3-2 3 2v3h4v-3l3-2 3 2v11H4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
								<path d="M11 21v-5h2v5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
							</svg>
						</div>
						<div class="building-why-visit__body">
							<?php
							/* translators: %s: building name */
							$why_visit_heading = $is_sv ? sprintf( 'Varför besöka %s?', get_the_title() ) : sprintf( 'Why Visit %s?', get_the_title() );
							?>
							<h2><?php echo esc_html( $why_visit_heading ); ?></h2>
							<p><?php echo esc_html( $why_visit ); ?></p>
						</div>
					</div>
				<?php endif; ?>

				<h2><?php echo esc_html( $is_sv ? 'Historia' : 'History' ); ?></h2>

				<?php if ( trim( get_the_content() ) !== '' ) : ?>
					<?php the_content(); ?>
				<?php else : ?>
					<p class="building-content-placeholder">
						<?php
						if ( $is_sv ) {
							printf(
								/* translators: %s: building name */
								esc_html__( 'Vi skriver fortfarande berättelsen om %s. Den fullständiga historien och besöksinformationen kommer att finnas här snart — under tiden hittar du allt vi vet så här långt i informationen bredvid.', 'cos-theme' ),
								esc_html( get_the_title() )
							);
						} else {
							printf(
								/* translators: %s: building name */
								esc_html__( 'We\'re still writing the story of %s. Its full history and visitor details will appear here soon — in the meantime, everything we know so far is in the details alongside.', 'cos-theme' ),
								esc_html( get_the_title() )
							);
						}
						?>
					</p>
				<?php endif; ?>

				<?php if ( $wikipedia_url ) : ?>
					<a class="building-layout__wiki-link" href="<?php echo esc_url( $wikipedia_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $is_sv ? 'Läs mer på Wikipedia' : 'Read more on Wikipedia' ); ?></a>
				<?php endif; ?>
			</div>

			<aside class="building-layout__sidebar">
				<?php if ( $has_visitor_info ) : ?>
					<div class="building-info-box building-info-box--spaced">
						<h3><?php echo esc_html( $is_sv ? 'Besöksinformation' : 'Visitor Information' ); ?></h3>
						<dl class="building-info-box__list">
							<?php if ( ! is_wp_error( $categories ) && $categories ) : ?>
								<div class="building-info-box__row">
									<dt><?php echo esc_html( $is_sv ? 'Kategorier' : 'Categories' ); ?></dt>
									<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $categories, 'name' ) ) ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $opening_hours ) : ?>
								<div class="building-info-box__row">
									<dt><?php echo esc_html( $is_sv ? 'Öppettider' : 'Opening Hours' ); ?></dt>
									<dd><?php echo nl2br( esc_html( $opening_hours ) ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $admission ) : ?>
								<div class="building-info-box__row">
									<dt><?php echo esc_html( $is_sv ? 'Inträde' : 'Admission' ); ?></dt>
									<dd><?php echo esc_html( $admission ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $parking ) : ?>
								<div class="building-info-box__row">
									<dt><?php echo esc_html( $is_sv ? 'Parkering' : 'Parking' ); ?></dt>
									<dd><?php echo esc_html( $parking ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( ! is_wp_error( $activities ) && $activities ) : ?>
								<div class="building-info-box__row">
									<dt><?php echo esc_html( $is_sv ? 'Aktiviteter' : 'Activities' ); ?></dt>
									<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $activities, 'name' ) ) ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( ! is_wp_error( $features ) && $features ) : ?>
								<div class="building-info-box__row">
									<dt><?php echo esc_html( $is_sv ? 'Faciliteter' : 'Facilities' ); ?></dt>
									<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $features, 'name' ) ) ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $accessibility ) : ?>
								<div class="building-info-box__row">
									<dt><?php echo esc_html( $is_sv ? 'Tillgänglighet' : 'Accessibility' ); ?></dt>
									<dd><?php echo esc_html( $accessibility ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $guided_tours ) : ?>
								<div class="building-info-box__row">
									<dt><?php echo esc_html( $is_sv ? 'Guidade turer' : 'Guided Tours' ); ?></dt>
									<dd><?php echo esc_html( $guided_tours ); ?></dd>
								</div>
							<?php endif; ?>
						</dl>
					</div>
				<?php endif; ?>

				<div class="building-info-box">
					<h3><?php echo esc_html( $is_sv ? 'Byggnadsinformation' : 'Building Information' ); ?></h3>
					<dl class="building-info-box__list">
						<?php if ( ! is_wp_error( $building_type ) && $building_type ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Byggnadstyp' : 'Building Type' ); ?></dt>
								<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $building_type, 'name' ) ) ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! is_wp_error( $style ) && $style ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Arkitektonisk stil' : 'Architectural Style' ); ?></dt>
								<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $style, 'name' ) ) ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $architects ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Arkitekt(er)' : 'Architect(s)' ); ?></dt>
								<dd><?php echo esc_html( $architects ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $builder ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Byggherre' : 'Builder' ); ?></dt>
								<dd><?php echo esc_html( $builder ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $year_built ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Byggår' : 'Year Built' ); ?></dt>
								<dd><?php echo esc_html( $year_built ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $rebuilt_year ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Ombyggnadsår' : 'Rebuilt' ); ?></dt>
								<dd><?php echo esc_html( $rebuilt_year ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>

					<?php if ( $instagram_url ) : ?>
						<div class="building-info-box__actions">
							<a href="<?php echo esc_url( $instagram_url ); ?>" target="_blank" rel="noopener">Instagram</a>
						</div>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</div>
	<?php if ( ! empty( $gallery_ids ) ) : ?>
		<div class="container section photo-gallery">
			<h2 class="photo-gallery__title"><?php echo esc_html( $is_sv ? 'Bildgalleri' : 'Gallery' ); ?></h2>
			<div class="photo-gallery__grid">
				<?php foreach ( $gallery_ids as $gallery_id ) :
					$full  = wp_get_attachment_image_src( $gallery_id, 'full' );
					$thumb = wp_get_attachment_image_src( $gallery_id, 'medium_large' );
					if ( ! $full || ! $thumb ) {
						continue;
					}
					?>
					<a href="<?php echo esc_url( $full[0] ); ?>" class="photo-gallery__item">
						<img src="<?php echo esc_url( $thumb[0] ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $lat && $lng ) : ?>
		<div class="container section">
			<div class="building-layout">
				<div>
					<h2><?php echo esc_html( $is_sv ? 'Plats' : 'Location' ); ?></h2>
					<div class="building-location__map">
						<div id="cos-building-map" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>" data-thumbnail="<?php echo esc_attr( $marker_thumbnail_url ); ?>"></div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $related_posts ) ) : ?>
		<div class="container section">
			<h2><?php echo esc_html( $is_sv ? 'Utforska även dessa destinationer' : 'Also Explore These Destinations' ); ?></h2>
			<div class="card-grid">
				<?php foreach ( $related_posts as $related_post ) : ?>
					<?php cos_building_card( $related_post->ID ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
<?php endwhile; ?>

<?php get_footer(); ?>
