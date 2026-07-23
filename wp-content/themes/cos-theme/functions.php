<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COS_THEME_DIR', get_stylesheet_directory() );
define( 'COS_THEME_URI', get_stylesheet_directory_uri() );

/**
 * 12 destinations per page on the Visit region/category archives, instead
 * of the site-wide default of 10 — scoped to just these two taxonomies so
 * it doesn't affect pagination elsewhere (News, search, etc.).
 */
function cos_taxonomy_archive_query( $query ) {
	if ( ! is_admin() && $query->is_main_query() && is_tax( array( 'cos_region', 'cos_category' ) ) ) {
		$query->set( 'posts_per_page', 12 );
	}
}
add_action( 'pre_get_posts', 'cos_taxonomy_archive_query' );

/**
 * Sold listings get their own "Sold" section rendered separately below the
 * live ones (see archive-cos_listing.php), so the main For Sale archive
 * query excludes them here instead of showing/paginating them twice.
 * Appends to whatever meta_query already exists (e.g. the language filter's)
 * rather than overwriting it.
 */
function cos_exclude_sold_from_listing_archive( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! is_post_type_archive( 'cos_listing' ) ) {
		return;
	}
	$meta_query   = $query->get( 'meta_query' );
	$meta_query   = is_array( $meta_query ) ? $meta_query : array();
	$meta_query[] = array(
		'relation' => 'OR',
		array( 'key' => 'cos_listing_sold', 'compare' => 'NOT EXISTS' ),
		array( 'key' => 'cos_listing_sold', 'value' => '1', 'compare' => '!=' ),
	);
	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'cos_exclude_sold_from_listing_archive' );

/**
 * Fixed brand-level primary navigation.
 * Homepage is reached via the clickable logo, not a nav item.
 */
/**
 * Returns [ 'label' => ..., 'url' => ..., 'cta' => bool ] rows rather than a
 * flat label => url map, so the "Support us" CTA button styling doesn't
 * depend on string-matching a label that's now translated per language.
 */
function cos_primary_nav_links() {
	$is_sv = 'sv' === COS_Language_Routing::current_lang();

	return array(
		array( 'label' => $is_sv ? 'Destinationer' : 'Destinations', 'url' => home_url( $is_sv ? '/sv/besok/' : '/visit/' ) ),
		array( 'label' => $is_sv ? 'Karta' : 'Map', 'url' => home_url( $is_sv ? '/sv/karta/' : '/map/' ) ),
		array( 'label' => $is_sv ? 'Till salu' : 'For Sale', 'url' => home_url( $is_sv ? '/sv/till-salu/' : '/for-sale/' ) ),
		array( 'label' => $is_sv ? 'Magasinet' : 'Journal', 'url' => home_url( $is_sv ? '/sv/magasinet/' : '/journal/' ) ),
		array( 'label' => $is_sv ? 'Butik' : 'Shop', 'url' => home_url( $is_sv ? '/sv/butik/' : '/shop/' ) ),
		array( 'label' => $is_sv ? 'Stöd oss' : 'Support us', 'url' => home_url( $is_sv ? '/sv/stod-oss/' : '/support-us/' ), 'cta' => true ),
	);
}

/**
 * the_custom_logo() always links to home_url( '/' ) with no built-in way to
 * change that — this keeps it pointing at the Swedish homepage while
 * browsing /sv/, matching every other nav link. No custom logo is uploaded
 * yet (the theme falls back to a text logo instead, handled directly in
 * header.php), but this keeps the same fix in place for whenever one is.
 */
add_filter( 'get_custom_logo', function ( $html ) {
	if ( 'sv' === COS_Language_Routing::current_lang() ) {
		$html = str_replace( home_url( '/' ) . '"', home_url( '/sv/' ) . '"', $html );
	}
	return $html;
} );

function cos_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 800,
	) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'cos-theme' ),
	) );
}
add_action( 'after_setup_theme', 'cos_theme_setup' );

/**
 * Open Graph / Twitter Card tags so links shared on Facebook, LinkedIn, X
 * etc. get a title, description, and image instead of a blank preview.
 * Uses the post's featured image when there is one; otherwise falls back to
 * the site-wide share image at assets/images/castlesofsweden-share.png
 * (1536×1024).
 */
function cos_social_share_meta() {
	if ( is_admin() || is_feed() || is_search() ) {
		return;
	}

	$is_sv = 'sv' === COS_Language_Routing::current_lang();

	if ( is_singular() ) {
		$title       = get_the_title();
		$excerpt     = get_the_excerpt();
		$description = $excerpt ? wp_strip_all_tags( $excerpt ) : get_bloginfo( 'description' );
		$type        = 'post' === get_post_type() ? 'article' : 'website';
	} elseif ( is_category() || is_tax() ) {
		$title             = single_term_title( '', false );
		$term_description  = term_description();
		$description       = $term_description ? wp_strip_all_tags( $term_description ) : get_bloginfo( 'description' );
		$type              = 'website';
	} elseif ( is_front_page() ) {
		$title       = get_bloginfo( 'name' );
		$description = get_bloginfo( 'description' );
		$type        = 'website';
	} else {
		$title       = wp_strip_all_tags( wp_get_document_title() );
		$description = get_bloginfo( 'description' );
		$type        = 'website';
	}

	$image_url    = COS_THEME_URI . '/assets/images/castlesofsweden-share.png';
	$image_width  = 1536;
	$image_height = 1024;

	if ( is_singular() && has_post_thumbnail() ) {
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $thumbnail ) {
			list( $image_url, $image_width, $image_height ) = $thumbnail;
		}
	}

	if ( is_singular() && 'cos_building' === get_post_type() && COS_Share_Image_Generator::exists( get_the_ID() ) ) {
		$image_url    = COS_Share_Image_Generator::get_url( get_the_ID() );
		$image_width  = 1200;
		$image_height = 630;
	}

	$current_url = home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	?>
	<meta property="og:type" content="<?php echo esc_attr( $type ); ?>">
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
	<?php if ( $description ) : ?>
		<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
	<?php endif; ?>
	<meta property="og:url" content="<?php echo esc_url( $current_url ); ?>">
	<meta property="og:image" content="<?php echo esc_url( $image_url ); ?>">
	<meta property="og:image:width" content="<?php echo esc_attr( $image_width ); ?>">
	<meta property="og:image:height" content="<?php echo esc_attr( $image_height ); ?>">
	<meta property="og:locale" content="<?php echo esc_attr( $is_sv ? 'sv_SE' : 'en_US' ); ?>">

	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
	<?php if ( $description ) : ?>
		<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
	<?php endif; ?>
	<meta name="twitter:image" content="<?php echo esc_url( $image_url ); ?>">
	<?php
}
add_action( 'wp_head', 'cos_social_share_meta', 1 );

/**
 * GA4, gated behind Complianz consent: the type="text/plain" data-category
 * markers keep these scripts inert until the visitor accepts the
 * "statistics" cookie category, which Complianz then activates client-side.
 * The external script deliberately uses data-cmplz-src rather than src —
 * Complianz's activation JS reads that specific attribute name to find the
 * URL to fetch once consent is granted; a plain src is never picked up.
 */
function cos_ga4_tracking() {
	?>
	<script type="text/plain" data-category="statistics" data-cmplz-src="https://www.googletagmanager.com/gtag/js?id=G-KG46F7WJQG"></script>
	<script type="text/plain" data-category="statistics">
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', 'G-KG46F7WJQG');
	</script>
	<?php
}
add_action( 'wp_head', 'cos_ga4_tracking', 1 );

/**
 * Wraps WooCommerce's default archive/single templates in the same
 * .container.section shell used by every other page on the site, instead of
 * WooCommerce's own unstyled wrapper markup.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
add_action( 'woocommerce_before_main_content', 'cos_woocommerce_wrapper_start', 10 );
add_action( 'woocommerce_after_main_content', 'cos_woocommerce_wrapper_end', 10 );
function cos_woocommerce_wrapper_start() {
	echo '<div class="container section woocommerce-page">';
}
function cos_woocommerce_wrapper_end() {
	echo '</div>';
}

/**
 * Replaces the default shop/category page title ("Shop") with our own
 * page-title-bar, matching every other archive on the site.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_all_notices', 10 );
add_action( 'woocommerce_before_main_content', 'woocommerce_output_all_notices', 15 );
add_action( 'woocommerce_before_main_content', 'cos_woocommerce_title_bar', 5 );

/**
 * WooCommerce's shop page title comes from the "Shop" page setting, which
 * has no Swedish counterpart — override it directly when on the shop root
 * in a Swedish context rather than trying to localize a WP page title.
 */
add_filter( 'woocommerce_page_title', function ( $title ) {
	if ( is_shop() && 'sv' === COS_Language_Routing::current_lang() ) {
		return 'Butik';
	}
	return $title;
} );

function cos_woocommerce_title_bar() {
	$is_sv = 'sv' === COS_Language_Routing::current_lang();
	?>
	<div class="page-title-bar page-title-bar--blue">
		<div class="container">
			<h1><?php woocommerce_page_title(); ?></h1>
			<?php if ( is_shop() ) : ?>
				<?php if ( $is_sv ) : ?>
					<p>Ta hem en del av Sveriges kulturarv. Vår butik erbjuder ett utvalt sortiment av böcker, inredning, accessoarer och mer, inspirerat av Sveriges enastående slott, palats, trädgårdar och historiska interiörer.</p>
					<p>Varje produkt är utvald för sin kvalitet, hantverk och koppling till svensk historia och design. Oavsett om du letar efter en meningsfull present eller något speciellt till ditt eget hem hoppas vi att vår kollektion inspirerar dig.</p>
				<?php else : ?>
					<p><?php esc_html_e( 'Bring a piece of Sweden\'s heritage into your home. Our shop offers a curated selection of books, interior design, accessories and more inspired by Sweden\'s remarkable castles, palaces, gardens and historic interiors.', 'cos-theme' ); ?></p>
					<p><?php esc_html_e( 'Each product is chosen for its quality, craftsmanship, and connection to Swedish history and design. Whether you\'re looking for a meaningful gift or something special for your own home, we hope our collection inspires you.', 'cos-theme' ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

/**
 * WooCommerce's default stylesheet lays the product grid out with
 * float/percentage widths (e.g. width: 22.1% for a 4-column grid), which
 * fights with our own CSS Grid layout below. We style everything ourselves,
 * so drop WC's stylesheets entirely rather than fight the cascade.
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Single product pages: no reviews tab, no related products, and the full
 * description moves into the summary column (below the add-to-cart button
 * and category meta) instead of living in a "Description" tab underneath.
 */
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	unset( $tabs['reviews'] );
	unset( $tabs['description'] );
	return $tabs;
} );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
add_action( 'woocommerce_single_product_summary', 'cos_woocommerce_summary_description', 45 );
function cos_woocommerce_summary_description() {
	if ( trim( get_the_content() ) === '' ) {
		return;
	}
	echo '<div class="woocommerce-product-details__description">';
	the_content();
	echo '</div>';
}

/**
 * The shop only carries a handful of products, so the overview drops the
 * breadcrumb and result count, keeping only the sort dropdown.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

/**
 * The shop only carries a handful of products, so the overview stays to
 * image, name and price — no ratings or an "Add to cart" button in the
 * grid (that only appears on the single product page).
 */
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

/**
 * Cache-busts our own theme assets by file modification time, so edits are
 * picked up immediately instead of being served stale under an unchanged
 * ?ver= query string (theme Version stays fixed across day-to-day edits).
 */
function cos_asset_version( $relative_path ) {
	$path = COS_THEME_DIR . $relative_path;
	return file_exists( $path ) ? filemtime( $path ) : wp_get_theme()->get( 'Version' );
}

function cos_enqueue_assets() {
	wp_enqueue_style( 'cos-theme-style', get_stylesheet_uri(), array(), cos_asset_version( '/style.css' ) );
	wp_enqueue_script( 'cos-theme-main', COS_THEME_URI . '/assets/js/main.js', array(), cos_asset_version( '/assets/js/main.js' ), true );

	$is_sv             = 'sv' === COS_Language_Routing::current_lang();
	$is_map_page          = cos_is_page_any_lang( 'map' );
	$is_term_page         = is_tax( array( 'cos_region', 'cos_category' ) );
	$is_building_page     = is_singular( array( 'cos_building', 'cos_listing' ) );
	$is_front_page        = is_front_page();
	$is_saved_places_page = cos_is_page_any_lang( 'saved-places' );

	if ( $is_map_page || $is_term_page || $is_building_page || $is_saved_places_page ) {
		wp_enqueue_style( 'leaflet', COS_THEME_URI . '/assets/vendor/leaflet/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'leaflet', COS_THEME_URI . '/assets/vendor/leaflet/leaflet.js', array(), '1.9.4', true );
	}

	if ( $is_map_page ) {
		wp_enqueue_script( 'cos-map', COS_THEME_URI . '/assets/js/map.js', array( 'leaflet' ), cos_asset_version( '/assets/js/map.js' ), true );

		$map_regions     = get_terms( array( 'taxonomy' => 'cos_region', 'hide_empty' => false ) );
		$map_categories  = get_terms( array( 'taxonomy' => 'cos_category', 'hide_empty' => false ) );

		// html_entity_decode to match the map-data REST endpoint's term_names(),
		// which decodes term names (e.g. "Food &amp; Drink" -> "Food & Drink") —
		// the values these slug maps resolve to must match what populateFilterOptions()
		// actually put in the <option>/checkbox values client-side, or the lookup silently fails.
		$map_region_names    = array_map( 'html_entity_decode', wp_list_pluck( $map_regions, 'name', 'slug' ) );
		$map_category_names  = array_map( 'html_entity_decode', wp_list_pluck( $map_categories, 'name', 'slug' ) );

		wp_localize_script( 'cos-map', 'cosMapData', array(
			'endpoint'         => esc_url_raw( add_query_arg( 'lang', $is_sv ? 'sv' : 'en', rest_url( 'cos-core/v1/map-data' ) ) ),
			'buildingsLabel'   => $is_sv ? __( 'destinationer', 'cos-theme' ) : __( 'destinations', 'cos-theme' ),
			'viewDetailsLabel' => $is_sv ? __( 'Visa detaljer', 'cos-theme' ) : __( 'View details', 'cos-theme' ),
			'regions'          => $map_region_names,
			'categories'       => $map_category_names,
			'nearMeLabels'     => $is_sv ? array(
				'locating'    => __( 'Söker plats…', 'cos-theme' ),
				'denied'      => __( 'Kunde inte hämta din plats.', 'cos-theme' ),
				'unsupported' => __( 'Platsdelning stöds inte i din webbläsare.', 'cos-theme' ),
			) : array(
				'locating'    => __( 'Locating…', 'cos-theme' ),
				'denied'      => __( 'Could not get your location.', 'cos-theme' ),
				'unsupported' => __( 'Location isn\'t supported in your browser.', 'cos-theme' ),
			),
		) );
	}

	if ( $is_term_page ) {
		wp_enqueue_script( 'cos-term-map', COS_THEME_URI . '/assets/js/term-map.js', array( 'leaflet' ), cos_asset_version( '/assets/js/term-map.js' ), true );

		wp_localize_script( 'cos-term-map', 'cosTermMapData', array(
			'endpoint'         => esc_url_raw( add_query_arg( 'lang', $is_sv ? 'sv' : 'en', rest_url( 'cos-core/v1/map-data' ) ) ),
			'viewDetailsLabel' => $is_sv ? __( 'Visa detaljer', 'cos-theme' ) : __( 'View details', 'cos-theme' ),
		) );
	}

	if ( $is_building_page ) {
		wp_enqueue_script( 'cos-building-map', COS_THEME_URI . '/assets/js/building-map.js', array( 'leaflet' ), cos_asset_version( '/assets/js/building-map.js' ), true );
	}

	$has_listing_gallery  = is_singular( 'cos_listing' ) && get_post_meta( get_the_ID(), 'cos_listing_gallery', true );
	$has_building_gallery = is_singular( 'cos_building' ) && get_post_meta( get_the_ID(), 'cos_building_gallery', true );
	if ( $has_listing_gallery || $has_building_gallery ) {
		wp_enqueue_script( 'cos-gallery-lightbox', COS_THEME_URI . '/assets/js/gallery-lightbox.js', array(), cos_asset_version( '/assets/js/gallery-lightbox.js' ), true );
	}

	if ( $is_front_page ) {
		wp_enqueue_script( 'cos-hero-search', COS_THEME_URI . '/assets/js/hero-search.js', array(), cos_asset_version( '/assets/js/hero-search.js' ), true );

		wp_localize_script( 'cos-hero-search', 'cosHeroSearchData', array(
			'mapUrl' => esc_url( home_url( $is_sv ? '/sv/karta/' : '/map/' ) ),
			'labels' => $is_sv ? array(
				'locating'    => __( 'Söker plats…', 'cos-theme' ),
				'denied'      => __( 'Kunde inte hämta din plats. Kontrollera platsbehörigheten eller välj ett landskap istället.', 'cos-theme' ),
				'unsupported' => __( 'Platsdelning stöds inte i din webbläsare.', 'cos-theme' ),
			) : array(
				'locating'    => __( 'Locating…', 'cos-theme' ),
				'denied'      => __( 'Could not get your location. Check your location permissions or choose a region instead.', 'cos-theme' ),
				'unsupported' => __( 'Location isn\'t supported in your browser.', 'cos-theme' ),
			),
		) );
	}

	// Sitewide: every page has the nav search trigger (and its overlay), not just /search/ and the homepage hero.
	wp_enqueue_script( 'cos-search', COS_THEME_URI . '/assets/js/search.js', array(), cos_asset_version( '/assets/js/search.js' ), true );

	wp_localize_script( 'cos-search', 'cosSearchData', array(
		'endpoint'      => esc_url_raw( add_query_arg( 'lang', $is_sv ? 'sv' : 'en', rest_url( 'cos-core/v1/search' ) ) ),
		'searchPageUrl' => home_url( $is_sv ? '/sv/?s=' : '/?s=' ),
		'labels'        => $is_sv ? array(
			'destinations' => __( 'Destinationer', 'cos-theme' ),
			'terms'        => __( 'Kategorier & Landskap', 'cos-theme' ),
			'articles'     => __( 'Artiklar', 'cos-theme' ),
			'listings'     => __( 'Till salu', 'cos-theme' ),
			'products'     => __( 'Butik', 'cos-theme' ),
			'noResults'    => __( 'Inga resultat hittades.', 'cos-theme' ),
			'viewAll'      => __( 'Se alla resultat för "%s"', 'cos-theme' ),
		) : array(
			'destinations' => __( 'Destinations', 'cos-theme' ),
			'terms'        => __( 'Categories & Regions', 'cos-theme' ),
			'articles'     => __( 'Articles', 'cos-theme' ),
			'listings'     => __( 'For Sale', 'cos-theme' ),
			'products'     => __( 'Shop', 'cos-theme' ),
			'noResults'    => __( 'No results found.', 'cos-theme' ),
			'viewAll'      => __( 'See all results for "%s"', 'cos-theme' ),
		),
	) );

	// Sitewide: the nav's saved-places heart icon needs its count badge kept
	// up to date on every page, not just where the Save button itself lives.
	// Leaflet is only actually registered on pages matched by the conditional
	// above (this script is enqueued sitewide for the nav save-count badge),
	// so only declare the dependency where it's actually there to avoid
	// referencing an unregistered handle everywhere else.
	wp_enqueue_script(
		'cos-saved-buildings',
		COS_THEME_URI . '/assets/js/saved-buildings.js',
		$is_saved_places_page ? array( 'leaflet' ) : array(),
		cos_asset_version( '/assets/js/saved-buildings.js' ),
		true
	);

	wp_localize_script( 'cos-saved-buildings', 'cosSavedBuildingsData', array(
		'buildingsEndpoint'     => esc_url_raw( rest_url( 'wp/v2/cos_building' ) ),
		'regionsEndpoint'       => esc_url_raw( rest_url( 'wp/v2/cos_region' ) ),
		'buildingTypesEndpoint' => esc_url_raw( rest_url( 'wp/v2/cos_building_type' ) ),
		'stylesEndpoint'        => esc_url_raw( rest_url( 'wp/v2/cos_architectural_style' ) ),
		'erasEndpoint'          => esc_url_raw( rest_url( 'wp/v2/cos_era' ) ),
		'lang'                  => $is_sv ? 'sv' : 'en',
		'labels'                => array(
			'empty'           => $is_sv ? __( 'Du har inte sparat några platser än.', 'cos-theme' ) : __( 'You haven\'t saved any places yet.', 'cos-theme' ),
			'savedCount'      => $is_sv ? __( '%d sparade', 'cos-theme' ) : __( '%d saved', 'cos-theme' ),
			'clearAll'        => $is_sv ? __( 'Rensa alla sparade platser', 'cos-theme' ) : __( 'Clear all saved places', 'cos-theme' ),
			'clearAllConfirm' => $is_sv ? __( 'Ta bort alla sparade platser?', 'cos-theme' ) : __( 'Remove all saved places?', 'cos-theme' ),
			'removeItem'      => $is_sv ? __( 'Ta bort från sparade platser', 'cos-theme' ) : __( 'Remove from saved places', 'cos-theme' ),
			'compareButton'   => $is_sv ? __( 'Jämför', 'cos-theme' ) : __( 'Compare', 'cos-theme' ),
			'compareHint'     => $is_sv ? __( 'Välj minst 2 platser för att jämföra dem.', 'cos-theme' ) : __( 'Select at least 2 places to compare.', 'cos-theme' ),
			'compareMax'      => $is_sv ? __( 'Du kan jämföra upp till 3 platser åt gången.', 'cos-theme' ) : __( 'You can compare up to 3 places at a time.', 'cos-theme' ),
			'clearSelection'  => $is_sv ? __( 'Rensa', 'cos-theme' ) : __( 'Clear', 'cos-theme' ),
			'closeComparison' => $is_sv ? __( 'Stäng jämförelsen', 'cos-theme' ) : __( 'Close comparison', 'cos-theme' ),
			'selectedCount'   => $is_sv ? __( '%d valda', 'cos-theme' ) : __( '%d selected', 'cos-theme' ),
			'compareFields'   => array(
				'region'        => $is_sv ? __( 'Region', 'cos-theme' ) : __( 'Region', 'cos-theme' ),
				'buildingType'  => $is_sv ? __( 'Byggnadstyp', 'cos-theme' ) : __( 'Building Type', 'cos-theme' ),
				'style'         => $is_sv ? __( 'Arkitektonisk stil', 'cos-theme' ) : __( 'Architectural Style', 'cos-theme' ),
				'era'           => $is_sv ? __( 'Epok', 'cos-theme' ) : __( 'Era', 'cos-theme' ),
				'yearBuilt'     => $is_sv ? __( 'Byggår', 'cos-theme' ) : __( 'Year Built', 'cos-theme' ),
				'admission'     => $is_sv ? __( 'Inträde', 'cos-theme' ) : __( 'Admission', 'cos-theme' ),
				'openingHours'  => $is_sv ? __( 'Öppettider', 'cos-theme' ) : __( 'Opening Hours', 'cos-theme' ),
				'parking'       => $is_sv ? __( 'Parkering', 'cos-theme' ) : __( 'Parking', 'cos-theme' ),
				'accessibility' => $is_sv ? __( 'Tillgänglighet', 'cos-theme' ) : __( 'Accessibility', 'cos-theme' ),
				'guidedTours'   => $is_sv ? __( 'Guidade turer', 'cos-theme' ) : __( 'Guided Tours', 'cos-theme' ),
			),
			'tripHeading'          => $is_sv ? __( 'Din resplan', 'cos-theme' ) : __( 'Your trip', 'cos-theme' ),
			'tripMoveUp'           => $is_sv ? __( 'Flytta upp', 'cos-theme' ) : __( 'Move up', 'cos-theme' ),
			'tripMoveDown'         => $is_sv ? __( 'Flytta ner', 'cos-theme' ) : __( 'Move down', 'cos-theme' ),
			'tripToNext'           => $is_sv ? __( 'till nästa stopp', 'cos-theme' ) : __( 'to next stop', 'cos-theme' ),
			'tripTotal'            => $is_sv ? __( 'Total sträcka: %s', 'cos-theme' ) : __( 'Total distance: %s', 'cos-theme' ),
			'tripCopyLink'         => $is_sv ? __( 'Kopiera länk', 'cos-theme' ) : __( 'Copy link', 'cos-theme' ),
			'tripLinkCopied'       => $is_sv ? __( 'Länk kopierad!', 'cos-theme' ) : __( 'Link copied!', 'cos-theme' ),
			'tripCopyLinkFallback' => $is_sv ? __( 'Kopiera den här länken:', 'cos-theme' ) : __( 'Copy this link:', 'cos-theme' ),
			'tripOpenMaps'         => $is_sv ? __( 'Öppna i Google Maps', 'cos-theme' ) : __( 'Open in Google Maps', 'cos-theme' ),
			'tripSaveAll'          => $is_sv ? __( 'Spara alla i mina sparade platser', 'cos-theme' ) : __( 'Save all to my saved places', 'cos-theme' ),
			'tripSaveAllDone'      => $is_sv ? __( 'Sparat!', 'cos-theme' ) : __( 'Saved!', 'cos-theme' ),
			'tripSharedNotice'     => $is_sv ? __( 'Du tittar på en delad resplan.', 'cos-theme' ) : __( 'You\'re viewing a shared trip itinerary.', 'cos-theme' ),
			'tripSharedEmpty'      => $is_sv ? __( 'Den här resplanen kunde inte laddas.', 'cos-theme' ) : __( 'This trip couldn\'t be loaded.', 'cos-theme' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'cos_enqueue_assets' );

require_once COS_THEME_DIR . '/inc/template-tags.php';
