<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a card linking to a permalink — the whole card is one clickable
 * link. Shared by any plain post-like listing (news, search result groups).
 */
function cos_card( $permalink, $title, $thumbnail_url, $meta = '' ) {
	?>
	<a class="card" href="<?php echo esc_url( $permalink ); ?>">
		<div class="card__image">
			<?php if ( $thumbnail_url ) : ?>
				<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="">
			<?php endif; ?>
		</div>
		<div class="card__body">
			<h3 class="card__title"><?php echo esc_html( $title ); ?></h3>
			<?php if ( $meta ) : ?>
				<p class="card__meta"><?php echo esc_html( $meta ); ?></p>
			<?php endif; ?>
		</div>
	</a>
	<?php
}

/**
 * Renders a single building card. Expects $post to be set up (in the loop or via setup_postdata).
 */
function cos_building_card( $post_id ) {
	$region = get_the_terms( $post_id, 'cos_region' );
	?>
	<a class="card" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
		<div class="card__image">
			<?php echo get_the_post_thumbnail( $post_id, 'medium', array( 'alt' => get_the_title( $post_id ) ) ); ?>
		</div>
		<div class="card__body">
			<h3 class="card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
			<?php if ( ! is_wp_error( $region ) && $region ) : ?>
				<p class="card__meta"><?php echo esc_html( implode( ', ', wp_list_pluck( $region, 'name' ) ) ); ?></p>
			<?php endif; ?>
		</div>
	</a>
	<?php
}

/**
 * Formats a currency amount as an abbreviated "M" (millions) or "K"
 * (thousands) figure — e.g. $2.7M — for the compact secondary price line.
 */
function cos_format_price_abbreviated( $amount, $symbol ) {
	if ( $amount >= 1000000 ) {
		return $symbol . number_format( $amount / 1000000, 1 ) . 'M';
	}
	if ( $amount >= 1000 ) {
		return $symbol . number_format( $amount / 1000, 0 ) . 'K';
	}
	return $symbol . number_format( $amount, 0 );
}

/**
 * Renders a listing's price block: the SEK amount as entered, plus an
 * auto-converted USD/EUR line in smaller text underneath (converted via
 * COS_Currency's cached exchange rate — never a live API call per page view).
 */
function cos_listing_price_html( $price_sek ) {
	if ( ! $price_sek ) {
		return;
	}
	$usd = class_exists( 'COS_Currency' ) ? COS_Currency::convert_from_sek( $price_sek, 'USD' ) : null;
	$eur = class_exists( 'COS_Currency' ) ? COS_Currency::convert_from_sek( $price_sek, 'EUR' ) : null;
	?>
	<div class="listing-price">
		<span class="listing-price__sek"><?php echo esc_html( number_format_i18n( $price_sek ) . ' SEK' ); ?></span>
		<?php if ( $usd || $eur ) : ?>
			<span class="listing-price__converted">
				<?php
				$parts = array();
				if ( $usd ) {
					$parts[] = cos_format_price_abbreviated( $usd, '$' );
				}
				if ( $eur ) {
					$parts[] = cos_format_price_abbreviated( $eur, '€' );
				}
				echo esc_html( implode( ' · ', $parts ) );
				?>
			</span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders a single for-sale listing card — image, name, location, sizes,
 * price (with auto-converted USD/EUR), and a "View Listing" link.
 */
function cos_listing_card( $post_id ) {
	$location      = get_post_meta( $post_id, 'cos_listing_location', true );
	$building_size = get_post_meta( $post_id, 'cos_listing_building_size', true );
	$land_size     = get_post_meta( $post_id, 'cos_listing_land_size', true );
	$price_sek     = (int) get_post_meta( $post_id, 'cos_listing_price_sek', true );

	$size_parts = array();
	if ( $building_size ) {
		/* translators: %s: building size in square metres */
		$size_parts[] = sprintf( __( '%s m² building', 'cos-theme' ), number_format_i18n( $building_size ) );
	}
	if ( $land_size ) {
		/* translators: %s: land size in hectares */
		$size_parts[] = sprintf( __( '%s ha land', 'cos-theme' ), number_format_i18n( $land_size ) );
	}
	?>
	<a class="card listing-card" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
		<div class="card__image">
			<?php echo get_the_post_thumbnail( $post_id, 'medium', array( 'alt' => get_the_title( $post_id ) ) ); ?>
		</div>
		<div class="card__body">
			<h3 class="card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
			<?php if ( $location ) : ?>
				<p class="card__meta"><?php echo esc_html( $location ); ?></p>
			<?php endif; ?>
			<?php if ( $size_parts ) : ?>
				<p class="listing-card__sizes"><?php echo esc_html( implode( ' · ', $size_parts ) ); ?></p>
			<?php endif; ?>
			<?php cos_listing_price_html( $price_sek ); ?>
			<span class="listing-card__cta"><?php esc_html_e( 'View Listing', 'cos-theme' ); ?></span>
		</div>
	</a>
	<?php
}

/**
 * Returns the URL for a category/region tile background image, or false if
 * none has been added yet at assets/images/{categories|regions}/{slug}.jpg.
 */
function cos_tile_image_url( $folder, $slug ) {
	$path = COS_THEME_DIR . '/assets/images/' . $folder . '/' . $slug . '.jpg';
	return file_exists( $path ) ? COS_THEME_URI . '/assets/images/' . $folder . '/' . $slug . '.jpg' : false;
}

/**
 * Renders a large image tile linking to a taxonomy term archive. Shared by
 * the front page's category grid and the Visit page's category/region grids
 * so they always look the same.
 */
function cos_image_tile( $term, $image_url, $count_label = '' ) {
	$classes = 'image-tile' . ( $image_url ? '' : ' image-tile--no-image' );
	?>
	<a
		class="<?php echo esc_attr( $classes ); ?>"
		href="<?php echo esc_url( get_term_link( $term ) ); ?>"
		<?php if ( $image_url ) : ?>
			style="background-image: linear-gradient(180deg, rgba(30,26,20,0.1) 0%, rgba(30,26,20,0.75) 100%), url('<?php echo esc_url( $image_url ); ?>');"
		<?php endif; ?>
	>
		<span class="image-tile__label">
			<?php echo esc_html( $term->name ); ?>
			<?php if ( $count_label ) : ?>
				<span class="image-tile__meta"><?php echo esc_html( $term->count . ' ' . $count_label ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<?php
}

/**
 * Renders a Journal article card for the 3-up grid on the Journal overview:
 * category first (styled like the featured article's category label), then
 * title, then byline — no date.
 */
function cos_journal_card( $post_id ) {
	$categories = get_the_category( $post_id );
	?>
	<a class="card journal-card" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
		<div class="card__image">
			<?php echo get_the_post_thumbnail( $post_id, 'medium', array( 'alt' => get_the_title( $post_id ) ) ); ?>
		</div>
		<div class="card__body">
			<?php if ( $categories ) : ?>
				<p class="journal-card__category"><?php echo esc_html( $categories[0]->name ); ?></p>
			<?php endif; ?>
			<h3 class="card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
			<p class="journal-card__author">
				<?php
				/* translators: %s: author name */
				printf( esc_html__( 'Words by %s', 'cos-theme' ), esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ) ) );
				?>
			</p>
		</div>
	</a>
	<?php
}

/**
 * Curated top-level Journal categories, in display order. Each must exist as
 * a 'category' term; any missing slug is silently skipped so the site doesn't
 * break if one is renamed or deleted from the admin.
 */
const COS_JOURNAL_TOP_CATEGORIES = array(
	'gardens-interiors',
	'architecture',
	'history',
	'life-style',
	'art-culture',
	'royals',
);

/**
 * Secondary Journal navigation: the curated top-level categories above, each
 * with a hover dropdown of its subcategories (if any). Rendered on the
 * Journal overview (below the page title bar) and on single article pages
 * (below the main site header).
 */
function cos_journal_subnav() {
	?>
	<nav class="journal-subnav" aria-label="<?php esc_attr_e( 'Journal categories', 'cos-theme' ); ?>">
		<div class="container">
			<ul>
				<?php foreach ( COS_JOURNAL_TOP_CATEGORIES as $slug ) :
					$term = get_term_by( 'slug', $slug, 'category' );
					if ( ! $term ) {
						continue;
					}
					$children = get_categories( array( 'parent' => $term->term_id, 'hide_empty' => false ) );
					?>
					<li class="<?php echo $children ? 'has-children' : ''; ?>">
						<a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
						<?php if ( $children ) : ?>
							<ul class="journal-subnav__dropdown">
								<?php foreach ( $children as $child ) : ?>
									<li><a href="<?php echo esc_url( get_term_link( $child ) ); ?>"><?php echo esc_html( $child->name ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</nav>
	<?php
}

/**
 * Renders a full-bleed image card for the homepage's "Latest Articles" row:
 * category, title, and byline overlaid bottom-left on the featured image.
 */
function cos_front_journal_card( $post_id ) {
	$categories = get_the_category( $post_id );
	$thumbnail  = get_the_post_thumbnail_url( $post_id, 'large' );
	?>
	<a
		class="front-journal-card"
		href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
		<?php if ( $thumbnail ) : ?>
			style="background-image: linear-gradient(180deg, rgba(20,16,12,0) 40%, rgba(20,16,12,0.85) 100%), url('<?php echo esc_url( $thumbnail ); ?>');"
		<?php endif; ?>
	>
		<div class="front-journal-card__body">
			<?php if ( $categories ) : ?>
				<p class="front-journal-card__category"><?php echo esc_html( $categories[0]->name ); ?></p>
			<?php endif; ?>
			<h3 class="front-journal-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
			<p class="front-journal-card__author">
				<?php
				/* translators: %s: author name */
				printf( esc_html__( 'Words by %s', 'cos-theme' ), esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ) ) );
				?>
			</p>
		</div>
	</a>
	<?php
}
