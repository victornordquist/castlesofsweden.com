<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inline map-pin SVG, matching the stroke-currentColor convention used by
 * the footer social icons and header search icon. Shared by the homepage
 * "Use my location" button and the Map page's near-me filter checkbox.
 */
function cos_pin_icon_svg( $size = 18 ) {
	printf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
		(int) $size
	);
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
	$is_sv         = 'sv' === COS_Language_Routing::current_lang();
	$location      = get_post_meta( $post_id, 'cos_listing_location', true );
	$building_size = get_post_meta( $post_id, 'cos_listing_building_size', true );
	$land_size     = get_post_meta( $post_id, 'cos_listing_land_size', true );
	$price_sek     = (int) get_post_meta( $post_id, 'cos_listing_price_sek', true );

	$size_parts = array();
	if ( $building_size ) {
		if ( $is_sv ) {
			/* translators: %s: building size in square metres */
			$size_parts[] = sprintf( __( '%s m² byggnad', 'cos-theme' ), number_format_i18n( $building_size ) );
		} else {
			/* translators: %s: building size in square metres */
			$size_parts[] = sprintf( __( '%s m² building', 'cos-theme' ), number_format_i18n( $building_size ) );
		}
	}
	if ( $land_size ) {
		if ( $is_sv ) {
			/* translators: %s: land size in hectares */
			$size_parts[] = sprintf( __( '%s ha mark', 'cos-theme' ), number_format_i18n( $land_size ) );
		} else {
			/* translators: %s: land size in hectares */
			$size_parts[] = sprintf( __( '%s ha land', 'cos-theme' ), number_format_i18n( $land_size ) );
		}
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
			<span class="listing-card__cta"><?php echo esc_html( $is_sv ? 'Visa objekt' : 'View Listing' ); ?></span>
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
 * The photo tiles are shared between languages (they're generic scenery,
 * not text), but Swedish terms have their own auto-generated slugs (e.g.
 * "accommodation-sv") that don't match the physical /assets/images/{slug}.jpg
 * files, which are only named after the English slug. This resolves a term
 * to whichever slug actually has an image — its own, or its paired term's.
 */
function cos_term_image_slug( $term ) {
	$lang = get_term_meta( $term->term_id, 'cos_lang', true ) ?: 'en';
	if ( 'en' === $lang ) {
		return $term->slug;
	}
	$paired_id = (int) get_term_meta( $term->term_id, 'cos_translation_id', true );
	$paired    = $paired_id ? get_term( $paired_id, $term->taxonomy ) : null;
	return ( $paired && ! is_wp_error( $paired ) ) ? $paired->slug : $term->slug;
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
	$is_sv      = 'sv' === COS_Language_Routing::current_lang();
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
				if ( $is_sv ) {
					/* translators: %s: author name */
					printf( esc_html__( 'Text av %s', 'cos-theme' ), esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ) ) );
				} else {
					/* translators: %s: author name */
					printf( esc_html__( 'Words by %s', 'cos-theme' ), esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ) ) );
				}
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
	$is_sv = 'sv' === COS_Language_Routing::current_lang();
	?>
	<nav class="journal-subnav" aria-label="<?php echo esc_attr( $is_sv ? 'Magasinkategorier' : 'Journal categories' ); ?>">
		<div class="container">
			<ul>
				<?php foreach ( COS_JOURNAL_TOP_CATEGORIES as $slug ) :
					// cos_lang_filter=false bypasses the language filter so this
					// always resolves the canonical English term by its hardcoded
					// slug, regardless of site language — it's then swapped for
					// its Swedish pair below when needed.
					$en_terms = get_terms( array(
						'taxonomy'        => 'category',
						'slug'            => $slug,
						'hide_empty'      => false,
						'cos_lang_filter' => false,
					) );
					$term = ( ! is_wp_error( $en_terms ) && $en_terms ) ? $en_terms[0] : null;
					if ( ! $term ) {
						continue;
					}
					if ( $is_sv ) {
						$paired_id = (int) get_term_meta( $term->term_id, 'cos_translation_id', true );
						$paired    = $paired_id ? get_term( $paired_id, 'category' ) : null;
						if ( $paired && ! is_wp_error( $paired ) ) {
							$term = $paired;
						}
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
	$is_sv      = 'sv' === COS_Language_Routing::current_lang();
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
				if ( $is_sv ) {
					/* translators: %s: author name */
					printf( esc_html__( 'Text av %s', 'cos-theme' ), esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ) ) );
				} else {
					/* translators: %s: author name */
					printf( esc_html__( 'Words by %s', 'cos-theme' ), esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ) ) );
				}
				?>
			</p>
		</div>
	</a>
	<?php
}

/**
 * Like is_page( $slug ), but also matches that page's Swedish (or English)
 * translation counterpart — since e.g. the Map page is "map" in English but
 * "karta" in Swedish, a plain is_page( 'map' ) check would miss the Swedish
 * version entirely. Used for the conditional asset-enqueue checks in
 * functions.php.
 */
function cos_is_page_any_lang( $slug ) {
	if ( is_page( $slug ) ) {
		return true;
	}
	if ( ! is_page() ) {
		return false;
	}
	$other_page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $other_page ) {
		return false;
	}
	$paired_id = (int) get_post_meta( $other_page->ID, 'cos_translation_id', true );
	return $paired_id && is_page( $paired_id );
}

/**
 * The URL of the current page/post/term's counterpart in the other
 * language, for the nav language switcher. Falls back to that language's
 * homepage when nothing's been translated yet, so the switcher is never a
 * dead link.
 */
function cos_get_translation_link() {
	$target_lang = 'sv' === COS_Language_Routing::current_lang() ? 'en' : 'sv';
	$fallback    = 'sv' === $target_lang ? home_url( '/sv/' ) : home_url( '/' );

	if ( is_singular() ) {
		$paired_id = (int) get_post_meta( get_the_ID(), 'cos_translation_id', true );
		if ( $paired_id && get_post( $paired_id ) ) {
			return get_permalink( $paired_id );
		}
		return $fallback;
	}

	if ( is_tax() || is_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$paired_id = (int) get_term_meta( $term->term_id, 'cos_translation_id', true );
			if ( $paired_id && get_term( $paired_id, $term->taxonomy ) ) {
				return get_term_link( $paired_id, $term->taxonomy );
			}
		}
	}

	return $fallback;
}

/**
 * Renders the "EN / SV" nav toggle: the current language as plain text,
 * the other language linking to its counterpart (or that language's
 * homepage, if this page has no translation yet).
 */
function cos_render_language_switcher() {
	$current_lang = COS_Language_Routing::current_lang();
	$target_lang  = 'sv' === $current_lang ? 'en' : 'sv';
	?>
	<span class="lang-switcher">
		<span class="lang-switcher__current"><?php echo esc_html( strtoupper( $current_lang ) ); ?></span>
		<span class="lang-switcher__sep">/</span>
		<a class="lang-switcher__link" href="<?php echo esc_url( cos_get_translation_link() ); ?>"><?php echo esc_html( strtoupper( $target_lang ) ); ?></a>
	</span>
	<?php
}
