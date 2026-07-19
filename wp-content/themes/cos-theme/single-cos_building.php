<?php
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<?php while ( have_posts() ) : the_post();
	$post_id       = get_the_ID();
	$region        = get_the_terms( $post_id, 'cos_region' );
	$building_type = get_the_terms( $post_id, 'cos_building_type' );
	$categories    = get_the_terms( $post_id, 'cos_category' );
	$style         = get_the_terms( $post_id, 'cos_architectural_style' );
	$era           = get_the_terms( $post_id, 'cos_era' );

	$english_name  = get_post_meta( $post_id, 'cos_english_name', true );
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

	$thumbnail_url      = get_the_post_thumbnail_url( $post_id, 'full' );
	$marker_thumbnail_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
	?>

	<div
		class="page-title-bar page-title-bar--image<?php echo $thumbnail_url ? '' : ' page-title-bar--placeholder'; ?>"
		<?php if ( $thumbnail_url ) : ?>
			style="background-image: linear-gradient(90deg, rgba(30,26,20,0.6) 0%, rgba(30,26,20,0.15) 65%), url('<?php echo esc_url( $thumbnail_url ); ?>');"
		<?php endif; ?>
	>
		<div class="container">
			<h1><?php the_title(); ?></h1>
			<?php if ( $english_name && $english_name !== get_the_title() ) : ?>
				<p class="page-title-bar__english-name"><?php echo esc_html( $english_name ); ?></p>
			<?php endif; ?>
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

				<?php if ( $lat && $lng ) : ?>
					<div class="building-layout__map">
						<div id="cos-building-map" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>" data-thumbnail="<?php echo esc_attr( $marker_thumbnail_url ); ?>"></div>
					</div>
				<?php endif; ?>
			</div>

			<aside class="building-layout__sidebar">
				<div class="building-info-box">
					<dl class="building-info-box__list">
						<?php if ( ! is_wp_error( $region ) && $region ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Landskap' : 'Region' ); ?></dt>
								<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $region, 'name' ) ) ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! is_wp_error( $building_type ) && $building_type ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Byggnadstyp' : 'Building Type' ); ?></dt>
								<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $building_type, 'name' ) ) ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! is_wp_error( $categories ) && $categories ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Kategorier' : 'Categories' ); ?></dt>
								<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $categories, 'name' ) ) ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! is_wp_error( $style ) && $style ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Arkitektonisk stil' : 'Architectural Style' ); ?></dt>
								<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $style, 'name' ) ) ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! is_wp_error( $era ) && $era ) : ?>
							<div class="building-info-box__row">
								<dt><?php echo esc_html( $is_sv ? 'Epok' : 'Era' ); ?></dt>
								<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $era, 'name' ) ) ); ?></dd>
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

					<?php if ( $website_url || $map_link || $instagram_url ) : ?>
						<div class="building-info-box__actions">
							<?php if ( $website_url ) : ?><a class="button" href="<?php echo esc_url( $website_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $is_sv ? 'Officiell webbplats' : 'Official website' ); ?></a><?php endif; ?>
							<?php if ( $map_link ) : ?><a class="button" href="<?php echo esc_url( $map_link ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $is_sv ? 'Vägbeskrivning' : 'How to get here' ); ?></a><?php endif; ?>
							<?php if ( $instagram_url ) : ?><a href="<?php echo esc_url( $instagram_url ); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</div>
<?php endwhile; ?>

<?php get_footer(); ?>
