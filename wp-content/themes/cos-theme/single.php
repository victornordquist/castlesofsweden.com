<?php
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<?php cos_journal_subnav(); ?>

<?php while ( have_posts() ) : the_post();
	$categories       = get_the_category();
	$thumbnail_id     = get_post_thumbnail_id();
	$thumbnail_caption = $thumbnail_id ? get_the_post_thumbnail_caption( $thumbnail_id ) : '';
	$image_credit     = get_post_meta( get_the_ID(), 'cos_image_credit', true );

	$structured_data = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'Article',
		'headline'      => get_the_title(),
		'url'           => get_permalink(),
		'datePublished' => get_the_date( 'c' ),
		'dateModified'  => get_the_modified_date( 'c' ),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => get_permalink(),
		),
		'author' => array(
			'@type' => 'Person',
			'name'  => cos_journal_author_name( get_the_ID() ),
		),
		'publisher' => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'logo'  => array(
				'@type' => 'ImageObject',
				'url'   => has_custom_logo()
					? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' )
					: ( COS_THEME_URI . '/assets/images/castlesofsweden-share.png' ),
			),
		),
	);

	$structured_data_description = wp_strip_all_tags( get_the_excerpt() );
	if ( $structured_data_description ) {
		$structured_data['description'] = $structured_data_description;
	}

	if ( has_post_thumbnail() ) {
		$structured_data['image'] = get_the_post_thumbnail_url( get_the_ID(), 'full' );
	}
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>

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
						esc_html( cos_journal_author_name( get_the_ID() ) ),
						esc_html( get_the_date() )
					);
				} else {
					printf(
						/* translators: 1: author name, 2: publish date */
						esc_html__( 'Words by %1$s · %2$s', 'cos-theme' ),
						esc_html( cos_journal_author_name( get_the_ID() ) ),
						esc_html( get_the_date() )
					);
				}
				?>
			</p>
		</div>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="news-article__featured">
				<div class="news-article__featured-image">
					<?php the_post_thumbnail( 'large' ); ?>
					<?php if ( $image_credit ) : ?>
						<p class="news-article__credit">
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
				<?php if ( $thumbnail_caption ) : ?>
					<figcaption><?php echo esc_html( $thumbnail_caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="news-article__content">
			<?php the_content(); ?>
		</div>

		<?php cos_journal_author_box( get_the_ID() ); ?>
	</article>
<?php endwhile; ?>

<?php get_footer(); ?>
