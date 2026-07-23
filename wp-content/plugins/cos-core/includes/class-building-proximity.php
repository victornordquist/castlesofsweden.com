<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Computes "nearby" buildings by great-circle distance, backed by a cached
 * geo index so a building page render never has to touch postmeta for all
 * ~600 buildings on every view — only right after an edit invalidates it.
 */
class COS_Building_Proximity {

	// v2: bumped after discovering the underlying get_posts() call was
	// being silently narrowed to a single language by the site's global
	// pre_get_posts language filter, so any index cached under the old key
	// may have been permanently missing one language's entries.
	const TRANSIENT_KEY = 'cos_building_geo_index_v2';
	const CACHE_TTL      = DAY_IN_SECONDS;

	public static function init() {
		add_action( 'save_post_cos_building', array( __CLASS__, 'clear_index' ) );
		add_action( 'trashed_post', array( __CLASS__, 'maybe_clear_index' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'maybe_clear_index' ) );
		add_action( 'deleted_post', array( __CLASS__, 'maybe_clear_index' ) );
	}

	public static function clear_index() {
		delete_transient( self::TRANSIENT_KEY );
	}

	public static function maybe_clear_index( $post_id ) {
		if ( 'cos_building' === get_post_type( $post_id ) ) {
			self::clear_index();
		}
	}

	private static function get_index() {
		$index = get_transient( self::TRANSIENT_KEY );
		if ( is_array( $index ) ) {
			return $index;
		}

		$posts = get_posts(
			array(
				'post_type'       => 'cos_building',
				'posts_per_page'  => -1,
				'post_status'     => 'publish',
				// This index needs both languages present at once (it does
				// its own per-entry language matching in get_nearby()) —
				// without this, the site's global language query filter
				// would silently narrow this to whichever language the
				// current frontend request happens to be.
				'cos_lang_filter' => false,
			)
		);

		$index = array();
		foreach ( $posts as $post ) {
			$lat = get_post_meta( $post->ID, 'cos_lat', true );
			$lng = get_post_meta( $post->ID, 'cos_lng', true );

			if ( '' === $lat || '' === $lng ) {
				continue;
			}

			$index[ $post->ID ] = array(
				'lat'  => (float) $lat,
				'lng'  => (float) $lng,
				'lang' => get_post_meta( $post->ID, 'cos_lang', true ) ?: 'en',
			);
		}

		set_transient( self::TRANSIENT_KEY, $index, self::CACHE_TTL );
		return $index;
	}

	/**
	 * Returns up to $limit post IDs nearest to ($lat,$lng), same-language,
	 * excluding $post_id itself. Empty array if no coordinates given.
	 */
	public static function get_nearby( $post_id, $lat, $lng, $lang, $limit = 3 ) {
		if ( '' === $lat || '' === $lng ) {
			return array();
		}

		$lat = (float) $lat;
		$lng = (float) $lng;

		$candidates = array();
		foreach ( self::get_index() as $id => $entry ) {
			if ( (int) $id === (int) $post_id || $entry['lang'] !== $lang ) {
				continue;
			}
			$candidates[ $id ] = self::haversine_km( $lat, $lng, $entry['lat'], $entry['lng'] );
		}

		asort( $candidates );
		return array_slice( array_keys( $candidates ), 0, $limit );
	}

	private static function haversine_km( $lat1, $lng1, $lat2, $lng2 ) {
		$earth_radius_km = 6371;
		$delta_lat       = deg2rad( $lat2 - $lat1 );
		$delta_lng       = deg2rad( $lng2 - $lng1 );

		$a = sin( $delta_lat / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $delta_lng / 2 ) ** 2;

		return $earth_radius_km * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
	}
}
