<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class COS_REST_Map_Endpoint {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			'cos-core/v1',
			'/map-data',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_map_data' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function get_map_data( $request ) {
		$query_args = array(
			'post_type'      => 'cos_building',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		$taxonomy = $request->get_param( 'taxonomy' );
		$term     = $request->get_param( 'term' );

		if ( $taxonomy && $term && array_key_exists( $taxonomy, COS_Taxonomies::BUILDING_TAXONOMIES ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $term,
				),
			);
		}

		$posts = get_posts( $query_args );

		$data = array();

		foreach ( $posts as $post ) {
			$lat = get_post_meta( $post->ID, 'cos_lat', true );
			$lng = get_post_meta( $post->ID, 'cos_lng', true );

			if ( '' === $lat || '' === $lng ) {
				continue;
			}

			$data[] = array(
				'id'        => $post->ID,
				'name'      => get_the_title( $post ),
				'lat'       => (float) $lat,
				'lng'       => (float) $lng,
				'region'    => self::term_names( $post->ID, 'cos_region' ),
				'type'      => self::term_names( $post->ID, 'cos_building_type' ),
				'category'  => self::term_names( $post->ID, 'cos_category' ),
				'activity'  => self::term_names( $post->ID, 'cos_activity' ),
				'feature'   => self::term_names( $post->ID, 'cos_feature' ),
				'style'     => self::term_names( $post->ID, 'cos_architectural_style' ),
				'era'       => self::term_names( $post->ID, 'cos_era' ),
				'permalink' => get_permalink( $post ),
				'thumbnail' => get_the_post_thumbnail_url( $post, 'thumbnail' ),
			);
		}

		return rest_ensure_response( $data );
	}

	private static function term_names( $post_id, $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) || ! $terms ) {
			return array();
		}
		return array_map( 'html_entity_decode', wp_list_pluck( $terms, 'name' ) );
	}
}
