<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Routes the /sv/ URL tree. Swedish content has its own real, distinct
 * slugs (different words from their English counterparts), so these rules
 * feed WordPress's native slug resolution directly rather than trying to
 * "translate a Swedish URL back to English" — no collision is possible
 * since the two languages never share a slug.
 *
 * Rule ordering matters far less than it looks: rather than fight
 * add_rewrite_rule()'s 'top' insertion order, the generic single-segment
 * catch-all (rule for top-level pages/posts) explicitly excludes every
 * reserved section prefix via a negative lookahead, so it can never shadow
 * the more specific rules regardless of registration order.
 */
class COS_Language_Routing {

	const LANG_QUERY_VAR = 'cos_lang';
	const SLUG_QUERY_VAR = 'cos_sv_slug';

	/**
	 * Static section slugs: [ english rewrite base => swedish rewrite base ].
	 * Covers CPT archive bases and top-level structural bases (not
	 * individual entity slugs, which come from the entities themselves).
	 */
	const SECTION_SLUGS = array(
		'visit'    => 'besok',
		'for-sale' => 'till-salu',
		'shop'     => 'butik',
		'product'  => 'produkt',
	);

	/**
	 * Static page slugs: [ english page slug => swedish page slug ]. Every
	 * one of these must be a genuinely different word from its English
	 * counterpart — WordPress enforces unique slugs per post type regardless
	 * of language, so the Swedish page can never literally reuse "journal"
	 * while the English page still holds that slug.
	 */
	const PAGE_SLUGS = array(
		'visit'       => 'besok',
		'map'         => 'karta',
		'journal'     => 'magasinet',
		'support-us'  => 'stod-oss',
		'search'      => 'sok',
	);

	/**
	 * Taxonomy rewrite bases: [ taxonomy => [ english base, swedish base ] ].
	 */
	const TAXONOMY_SLUGS = array(
		'cos_region'              => array( 'visit/areas', 'besok/omraden' ),
		'cos_building_type'       => array( 'building-type', 'byggnadstyp' ),
		'cos_category'            => array( 'visit/things-to-do', 'besok/saker-att-gora' ),
		'cos_activity'            => array( 'activity', 'aktivitet' ),
		'cos_feature'             => array( 'feature', 'funktioner' ),
		'cos_architectural_style' => array( 'architectural-style', 'arkitektonisk-stil' ),
		'cos_era'                 => array( 'era', 'epok' ),
		'product_cat'             => array( 'product-category', 'produktkategori' ),
		'category'                => array( 'category', 'kategori' ),
	);

	public static function init() {
		add_filter( 'determine_locale', array( __CLASS__, 'filter_locale' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );
		add_action( 'parse_request', array( __CLASS__, 'resolve_generic_slug' ) );
		add_filter( 'template_include', array( __CLASS__, 'force_swedish_front_page' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_language_filter' ) );
		add_filter( 'get_terms_args', array( __CLASS__, 'apply_term_language_filter' ), 10, 2 );
		foreach ( COS_Language_Fields::taxonomies() as $taxonomy ) {
			add_filter( "rest_{$taxonomy}_query", array( __CLASS__, 'show_all_terms_in_rest_editor' ) );
		}

		add_filter( 'post_type_link', array( __CLASS__, 'filter_post_link' ), 10, 2 );
		add_filter( 'page_link', array( __CLASS__, 'filter_page_link' ), 10, 2 );
		add_filter( 'post_link', array( __CLASS__, 'filter_post_link' ), 10, 2 );
		add_filter( 'term_link', array( __CLASS__, 'filter_term_link' ), 10, 3 );

		add_action( 'wp_head', array( __CLASS__, 'output_hreflang_tags' ), 5 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'prevent_pagination_redirect_on_sv_routes' ) );
	}

	/**
	 * WordPress's canonical redirect "corrects" paginated Swedish URLs like
	 * /sv/magasinet/?paged=2 to the native /page/N/ path structure — but the
	 * custom /sv/ rewrite rules never registered that sub-path (pagination
	 * there works via the plain ?paged= query string instead), so the
	 * "corrected" URL 404s. Suppressing the redirect keeps the working
	 * ?paged= form intact rather than adding a parallel set of rewrite rules
	 * just to match a URL shape nothing else requires.
	 */
	public static function prevent_pagination_redirect_on_sv_routes( $redirect_url ) {
		if ( 'sv' === get_query_var( self::LANG_QUERY_VAR ) && get_query_var( 'paged' ) ) {
			return false;
		}
		return $redirect_url;
	}

	/**
	 * hreflang alternate tags for any singular post/page, taxonomy term
	 * archive, or the homepage that has a translated counterpart — output
	 * in both directions (self + other) on both language versions, the
	 * standard reciprocal hreflang pattern search engines expect.
	 */
	public static function output_hreflang_tags() {
		$current_url = null;
		$paired_url  = null;

		if ( is_singular() ) {
			$current_url = get_permalink();
			$paired_id   = (int) get_post_meta( get_the_ID(), COS_Language_Fields::PAIR_META_KEY, true );
			if ( $paired_id && get_post( $paired_id ) ) {
				$paired_url = get_permalink( $paired_id );
			}
		} elseif ( is_tax() || is_category() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$current_url = get_term_link( $term );
				$paired_id   = (int) get_term_meta( $term->term_id, COS_Language_Fields::PAIR_META_KEY, true );
				if ( $paired_id && get_term( $paired_id, $term->taxonomy ) ) {
					$paired_url = get_term_link( $paired_id, $term->taxonomy );
				}
			}
		} elseif ( is_front_page() ) {
			$is_sv       = self::is_swedish_request();
			$current_url = $is_sv ? home_url( '/sv/' ) : home_url( '/' );
			$paired_url  = $is_sv ? home_url( '/' ) : home_url( '/sv/' );
		}

		if ( ! $current_url || is_wp_error( $current_url ) || ! $paired_url || is_wp_error( $paired_url ) ) {
			return;
		}

		$current_lang = self::is_swedish_request() ? 'sv' : 'en';
		$other_lang   = 'sv' === $current_lang ? 'en' : 'sv';

		printf( '<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr( $current_lang ), esc_url( $current_url ) );
		printf( '<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr( $other_lang ), esc_url( $paired_url ) );
	}

	/**
	 * Switches WordPress's active locale for the whole request whenever the
	 * URL starts with /sv/ — this is what makes WooCommerce's own UI chrome
	 * (cart, checkout, account, order emails) render in Swedish via its
	 * official language pack, with no hand-translation of WooCommerce's own
	 * strings needed. Reads the raw request path rather than the parsed
	 * cos_lang query var, since determine_locale() fires before rewrite
	 * rules are parsed.
	 */
	public static function filter_locale( $locale ) {
		if ( ! is_admin() && isset( $_SERVER['REQUEST_URI'] ) && preg_match( '#^/sv(/|$|\?)#', $_SERVER['REQUEST_URI'] ) ) {
			return 'sv_SE';
		}
		return $locale;
	}

	public static function register_query_var( $vars ) {
		$vars[] = self::LANG_QUERY_VAR;
		$vars[] = self::SLUG_QUERY_VAR;
		return $vars;
	}

	/**
	 * WordPress resolves a bare slug through two entirely separate paths —
	 * `pagename` for hierarchical Pages, `name` for everything else — and a
	 * raw custom rewrite rule doesn't get the fallback-between-them behaviour
	 * that WordPress's own generated %postname% rule has. The generic
	 * catch-all rule below routes here first (via the neutral cos_sv_slug
	 * var) so we can check which one actually applies before the main query
	 * runs.
	 */
	public static function resolve_generic_slug( $wp ) {
		if ( empty( $wp->query_vars[ self::SLUG_QUERY_VAR ] ) ) {
			return;
		}
		$slug = $wp->query_vars[ self::SLUG_QUERY_VAR ];
		unset( $wp->query_vars[ self::SLUG_QUERY_VAR ] );

		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page ) {
			$wp->query_vars['pagename'] = $slug;
		} else {
			$wp->query_vars['name'] = $slug;
		}
	}

	/**
	 * The current request's language — 'sv' only once a Swedish rewrite
	 * rule has actually matched, 'en' otherwise (including in the admin,
	 * where content's own cos_lang meta is used instead — see
	 * COS_Language_Fields — not the request language).
	 */
	public static function current_lang() {
		if ( is_admin() ) {
			return 'en';
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_GET['lang'] ) ) {
			return 'sv' === $_GET['lang'] ? 'sv' : 'en';
		}
		return 'sv' === get_query_var( self::LANG_QUERY_VAR ) ? 'sv' : 'en';
	}

	public static function is_swedish_request() {
		return 'sv' === self::current_lang();
	}

	private static function reserved_first_segments() {
		$segments = array_values( self::SECTION_SLUGS );
		foreach ( self::TAXONOMY_SLUGS as list( $en, $sv ) ) {
			$segments[] = strtok( $sv, '/' ); // first path segment only
		}
		return array_unique( $segments );
	}

	public static function register_rewrite_rules() {
		$lang = self::LANG_QUERY_VAR;

		// Homepage.
		add_rewrite_rule( '^sv/?$', "index.php?{$lang}=sv", 'top' );

		// Taxonomy archives (each may itself be a nested path, e.g. besok/omraden).
		foreach ( self::TAXONOMY_SLUGS as $taxonomy => list( $en_base, $sv_base ) ) {
			add_rewrite_rule(
				'^sv/' . $sv_base . '/([^/]+)/?$',
				"index.php?taxonomy={$taxonomy}&term=\$matches[1]&{$lang}=sv",
				'top'
			);
		}
		// Paginated form (/page/N/) must be registered BEFORE the plain rule
		// below — for 'top' rules, earlier registration wins ties, and the
		// plain rule's .+? (needed to support hierarchical child category
		// paths) is loose enough to otherwise swallow "/page/2" into
		// category_name itself instead of a separate paged value.
		add_rewrite_rule(
			'^sv/kategori/(.+?)/page/([0-9]+)/?$',
			"index.php?category_name=\$matches[1]&{$lang}=sv&paged=\$matches[2]",
			'top'
		);
		// WP core category archives support hierarchical child paths.
		add_rewrite_rule(
			'^sv/kategori/(.+?)/?$',
			"index.php?category_name=\$matches[1]&{$lang}=sv",
			'top'
		);

		// cos_listing (For Sale) archive + single.
		add_rewrite_rule( '^sv/till-salu/?$', "index.php?post_type=cos_listing&{$lang}=sv", 'top' );
		add_rewrite_rule( '^sv/till-salu/([^/]+)/?$', "index.php?post_type=cos_listing&name=\$matches[1]&{$lang}=sv", 'top' );

		// WooCommerce shop archive + single product.
		add_rewrite_rule( '^sv/butik/?$', "index.php?post_type=product&{$lang}=sv", 'top' );
		add_rewrite_rule( '^sv/produkt/([^/]+)/?$', "index.php?post_type=product&name=\$matches[1]&{$lang}=sv", 'top' );

		// The Visit page itself (bare /sv/besok/) — reserved by the taxonomy
		// sub-paths above, so it needs its own rule rather than falling
		// through to the generic catch-all below.
		add_rewrite_rule( '^sv/besok/?$', "index.php?pagename=besok&{$lang}=sv", 'top' );

		// cos_building single, excluding its own taxonomy sub-paths.
		add_rewrite_rule(
			'^sv/besok/(?!omraden|saker-att-gora)([^/]+)/?$',
			"index.php?post_type=cos_building&name=\$matches[1]&{$lang}=sv",
			'top'
		);

		// Generic catch-all: top-level pages (karta, magasinet, stod-oss, sok
		// — anything not already claimed above) and top-level posts (journal
		// articles). Routed through the neutral cos_sv_slug var so
		// resolve_generic_slug() can decide Page vs. Post before the main
		// query runs (see that method for why). Reserved section words are
		// excluded so this can never shadow the more specific rules above.
		$reserved = implode( '|', array_map( 'preg_quote', self::reserved_first_segments() ) );
		$slug_var = self::SLUG_QUERY_VAR;
		add_rewrite_rule(
			'^sv/(?!' . $reserved . ')([^/]+)/?$',
			"index.php?{$slug_var}=\$matches[1]&{$lang}=sv",
			'top'
		);
		// Paginated form, e.g. /sv/magasinet/page/2/. Safe to register after
		// the plain rule above (unlike the category rule's ordering, this
		// one's [^/]+ can't span the extra /page/N segment, so there's no
		// ambiguity between the two either way).
		add_rewrite_rule(
			'^sv/(?!' . $reserved . ')([^/]+)/page/([0-9]+)/?$',
			"index.php?{$slug_var}=\$matches[1]&{$lang}=sv&paged=\$matches[2]",
			'top'
		);
	}

	/**
	 * The homepage rule sets no other query var, which is normally enough
	 * for is_front_page() — but front-page.php is a template file (not tied
	 * to a Page post), so this is a defensive belt-and-braces check rather
	 * than trusting core's detection alone with an unfamiliar extra query var.
	 */
	public static function force_swedish_front_page( $template ) {
		if ( 'sv' === get_query_var( self::LANG_QUERY_VAR ) && '' === get_query_var( 'name' ) && '' === get_query_var( 'pagename' ) && ! get_query_var( 'post_type' ) && ! get_query_var( 'taxonomy' ) && '' === get_query_var( 's' ) ) {
			$front = locate_template( 'front-page.php' );
			if ( $front ) {
				return $front;
			}
		}
		return $template;
	}

	/**
	 * Constrains every content query — main query AND every secondary
	 * WP_Query throughout the theme/REST endpoints (page-journal.php's
	 * featured/grid queries, the map and search endpoints, etc.) — to the
	 * current request's language, applied automatically rather than
	 * requiring each of those call sites to opt in individually. Only
	 * queries for one of our translatable post types are touched; anything
	 * else (attachments, nav menu items, WooCommerce's internal order
	 * queries) passes through untouched.
	 *
	 * Both directions need explicit handling: on an English request,
	 * Swedish-tagged content must be actively excluded, not just left
	 * unmatched by an absent filter — otherwise it leaks into English
	 * archives alongside the untagged (implicitly English) posts.
	 */
	/**
	 * A REST request only carries language context when it explicitly opts
	 * in via this site's own ?lang= param (used by its own front-end
	 * fetches, e.g. the search and map-data endpoints). Every other REST
	 * usage — most importantly the block editor's own core REST calls,
	 * which have no idea this site's language concept exists — has no such
	 * context, and is_swedish_request() would otherwise silently default to
	 * English there. That's not just a visibility bug: the block editor
	 * trusts a post's own save response to reflect its current state, so if
	 * that response is wrongly filtered to "no Swedish term assigned", the
	 * editor's UI shows the assignment as gone, and a subsequent save
	 * (autosave or manual) will persist that emptiness — silently
	 * overwriting a real assignment with nothing. Confirmed to have
	 * happened in production: a building's Swedish region assignment was
	 * wiped this way shortly after being saved. Leave these requests
	 * unfiltered entirely, the same principle as the existing
	 * cos_lang_filter=false escape hatch, rather than guess at a language
	 * that was never actually specified.
	 */
	private static function is_language_agnostic_rest_request() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST && ! isset( $_GET['lang'] );
	}

	public static function apply_language_filter( $query ) {
		// is_admin() alone doesn't cover every administrative context —
		// WP-CLI in particular runs with is_admin() === false, which would
		// otherwise silently hide Swedish drafts from `wp post list` and
		// any other CLI-driven query with no /sv/ URL to signal language.
		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) || self::is_language_agnostic_rest_request() ) {
			return;
		}

		// Same escape hatch as apply_term_language_filter() — pass
		// array( 'cos_lang_filter' => false ) for a query that intentionally
		// needs both languages at once (e.g. COS_Building_Proximity building
		// a cross-language-aware index). Without this, any such query run
		// during a normal frontend request silently gets narrowed to
		// whichever language that request happens to be, which previously
		// meant a cached cross-language index ended up permanently
		// one-language-only depending on which page first triggered a
		// rebuild after invalidation.
		if ( false === $query->get( 'cos_lang_filter' ) ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		if ( empty( $post_type ) ) {
			$taxonomy = $query->get( 'taxonomy' );
			if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
				$post_type = get_taxonomy( $taxonomy )->object_type;
			} else {
				$post_type = 'post';
			}
		}

		if ( ! array_intersect( (array) $post_type, COS_Language_Fields::POST_TYPES ) ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' );
		$meta_query = is_array( $meta_query ) ? $meta_query : array();

		if ( self::is_swedish_request() ) {
			$meta_query[] = array( 'key' => COS_Language_Fields::LANG_META_KEY, 'value' => 'sv' );
		} else {
			$meta_query[] = array(
				'relation' => 'OR',
				array( 'key' => COS_Language_Fields::LANG_META_KEY, 'compare' => 'NOT EXISTS' ),
				array( 'key' => COS_Language_Fields::LANG_META_KEY, 'value' => 'sv', 'compare' => '!=' ),
			);
		}
		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Same idea as apply_language_filter(), but for get_terms() — the
	 * category/region tile grids on the front page and Visit page query
	 * taxonomy terms directly, not posts, so they need their own filter to
	 * show only same-language terms (with their translated names) instead
	 * of every English and Swedish term mixed together.
	 */
	public static function apply_term_language_filter( $args, $taxonomies ) {
		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) || self::is_language_agnostic_rest_request() ) {
			return $args;
		}
		// Escape hatch for code that intentionally needs to resolve a term by
		// its canonical (English) slug regardless of the current site
		// language — e.g. looking up a hardcoded slug before switching to its
		// paired translation. Pass array( 'cos_lang_filter' => false ).
		if ( isset( $args['cos_lang_filter'] ) && false === $args['cos_lang_filter'] ) {
			return $args;
		}
		if ( ! array_intersect( (array) $taxonomies, COS_Language_Fields::taxonomies() ) ) {
			return $args;
		}

		$meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();

		if ( self::is_swedish_request() ) {
			$meta_query[] = array( 'key' => COS_Language_Fields::LANG_META_KEY, 'value' => 'sv' );
		} else {
			$meta_query[] = array(
				'relation' => 'OR',
				array( 'key' => COS_Language_Fields::LANG_META_KEY, 'compare' => 'NOT EXISTS' ),
				array( 'key' => COS_Language_Fields::LANG_META_KEY, 'value' => 'sv', 'compare' => '!=' ),
			);
		}
		$args['meta_query'] = $meta_query;
		return $args;
	}

	/**
	 * The block editor's native taxonomy panel (and its search-as-you-type
	 * box) doesn't use any of this site's own code — it fetches terms
	 * straight from WordPress's own REST Terms Controller. That controller
	 * runs entirely outside is_admin() (REST requests never set that flag,
	 * even when the JS calling them is running inside wp-admin), so neither
	 * apply_term_language_filter() nor COS_Language_Fields'
	 * force_show_empty_terms_in_admin() ever engage for it — the former
	 * still applies its EN/SV meta_query split there (only is_admin() is
	 * checked, not this route specifically), and since a REST request has
	 * no /sv/ URL or lang param to resolve, is_swedish_request() always
	 * comes back English there, permanently hiding every Swedish-tagged
	 * term from the block editor, not just leaving it unlabeled. Bypass
	 * both restrictions specifically for this route so editors can find
	 * and select every language variant of a term, the same as they
	 * already can in the classic term-list screens.
	 */
	public static function show_all_terms_in_rest_editor( $args ) {
		$args['cos_lang_filter'] = false;
		$args['hide_empty']      = false;
		return $args;
	}

	/* ---------------------------------------------------------------------
	 * Permalink generation: prepend the correct /sv/{section}/ prefix for
	 * Swedish-tagged content so every existing get_permalink()/
	 * get_term_link() call in the theme produces a correct URL untouched.
	 * ------------------------------------------------------------------- */

	public static function filter_post_link( $url, $post ) {
		$post = get_post( $post );
		if ( ! $post || 'sv' !== ( get_post_meta( $post->ID, COS_Language_Fields::LANG_META_KEY, true ) ?: 'en' ) ) {
			return $url;
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );
		$path = ltrim( (string) $path, '/' );

		if ( 'cos_building' === $post->post_type ) {
			return home_url( '/sv/besok/' . trailingslashit( $post->post_name ) );
		}
		if ( 'cos_listing' === $post->post_type ) {
			return home_url( '/sv/till-salu/' . trailingslashit( $post->post_name ) );
		}
		if ( 'product' === $post->post_type ) {
			return home_url( '/sv/produkt/' . trailingslashit( $post->post_name ) );
		}
		// Regular posts sit at the top level under /sv/, mirroring English.
		return home_url( '/sv/' . trailingslashit( $post->post_name ) );
	}

	public static function filter_page_link( $url, $post_id ) {
		if ( 'sv' !== ( get_post_meta( $post_id, COS_Language_Fields::LANG_META_KEY, true ) ?: 'en' ) ) {
			return $url;
		}
		$post = get_post( $post_id );
		return home_url( '/sv/' . trailingslashit( $post->post_name ) );
	}

	public static function filter_term_link( $url, $term, $taxonomy ) {
		if ( ! isset( self::TAXONOMY_SLUGS[ $taxonomy ] ) ) {
			return $url;
		}
		if ( 'sv' !== ( get_term_meta( $term->term_id, COS_Language_Fields::LANG_META_KEY, true ) ?: 'en' ) ) {
			return $url;
		}
		list( , $sv_base ) = self::TAXONOMY_SLUGS[ $taxonomy ];
		return home_url( '/sv/' . $sv_base . '/' . trailingslashit( $term->slug ) );
	}
}
