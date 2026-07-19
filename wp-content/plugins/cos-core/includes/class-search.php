<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggregates search results across the site, destinations always first.
 * Shared by the REST endpoint (instant search) and the theme's search.php
 * (full results page) so ranking/matching logic lives in one place.
 */
class COS_Search {

	const DEFAULT_LIMIT = 6;
	const ALL_TYPES      = array( 'destinations', 'terms', 'articles', 'listings', 'products' );

	public static function run( $query, $args = array() ) {
		$query = trim( (string) $query );
		$limit = isset( $args['limit'] ) ? max( 1, (int) $args['limit'] ) : self::DEFAULT_LIMIT;
		$types = ( ! empty( $args['types'] ) ) ? array_intersect( $args['types'], self::ALL_TYPES ) : self::ALL_TYPES;

		$results = array();

		if ( '' === $query ) {
			return $results;
		}

		if ( in_array( 'destinations', $types, true ) ) {
			$destinations = self::search_destinations( $query, $limit );
			if ( $destinations ) {
				$results['destinations'] = $destinations;
			}
		}

		if ( in_array( 'terms', $types, true ) ) {
			$terms = self::search_terms( $query, $limit );
			if ( $terms ) {
				$results['terms'] = $terms;
			}
		}

		if ( in_array( 'articles', $types, true ) ) {
			$articles = self::search_post_type( $query, 'post', $limit );
			if ( $articles ) {
				$results['articles'] = $articles;
			}
		}

		if ( in_array( 'listings', $types, true ) && post_type_exists( 'cos_listing' ) ) {
			$listings = self::search_post_type( $query, 'cos_listing', $limit );
			if ( $listings ) {
				$results['listings'] = $listings;
			}
		}

		if ( in_array( 'products', $types, true ) && post_type_exists( 'product' ) ) {
			$products = self::search_post_type( $query, 'product', $limit );
			if ( $products ) {
				$results['products'] = $products;
			}
		}

		return $results;
	}

	private static function search_destinations( $query, $limit ) {
		$by_title = get_posts( array(
			'post_type'      => 'cos_building',
			'post_status'    => 'publish',
			's'              => $query,
			'posts_per_page' => $limit,
			'fields'         => 'ids',
		) );

		// post_title is the Swedish name, so the English name (stored as meta)
		// needs its own lookup to keep it searchable too.
		$by_english_name = get_posts( array(
			'post_type'      => 'cos_building',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'cos_english_name',
					'value'   => $query,
					'compare' => 'LIKE',
				),
			),
		) );

		$by_term = array();
		foreach ( self::find_matching_terms( $query ) as $term ) {
			$by_term = array_merge( $by_term, get_posts( array(
				'post_type'      => 'cos_building',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => $term->taxonomy,
						'field'    => 'term_id',
						'terms'    => $term->term_id,
					),
				),
			) ) );
		}

		$ids = array_slice( array_unique( array_merge( $by_title, $by_english_name, $by_term ) ), 0, $limit );

		return array_map( array( __CLASS__, 'format_building' ), $ids );
	}

	private static function find_matching_terms( $query ) {
		$terms = get_terms( array(
			'taxonomy'   => array_keys( COS_Taxonomies::BUILDING_TAXONOMIES ),
			'name__like' => $query,
			'hide_empty' => true,
		) );

		return is_wp_error( $terms ) ? array() : $terms;
	}

	private static function search_terms( $query, $limit ) {
		$terms = array_slice( self::find_matching_terms( $query ), 0, $limit );

		return array_map( function ( $term ) {
			$taxonomy = get_taxonomy( $term->taxonomy );
			return array(
				'name'      => html_entity_decode( $term->name ),
				'taxonomy'  => $taxonomy ? $taxonomy->labels->singular_name : $term->taxonomy,
				'permalink' => get_term_link( $term ),
			);
		}, $terms );
	}

	private static function search_post_type( $query, $post_type, $limit ) {
		$posts = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			's'              => $query,
			'posts_per_page' => $limit,
		) );

		return array_map( array( __CLASS__, 'format_post' ), $posts );
	}

	private static function format_building( $post_id ) {
		$region = get_the_terms( $post_id, 'cos_region' );
		$type   = get_the_terms( $post_id, 'cos_building_type' );

		return array(
			'id'        => $post_id,
			'title'     => get_the_title( $post_id ),
			'region'    => ( ! is_wp_error( $region ) && $region ) ? html_entity_decode( $region[0]->name ) : '',
			'type'      => ( ! is_wp_error( $type ) && $type ) ? html_entity_decode( $type[0]->name ) : '',
			'permalink' => get_permalink( $post_id ),
			'thumbnail' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ),
		);
	}

	private static function format_post( $post ) {
		return array(
			'id'        => $post->ID,
			'title'     => get_the_title( $post ),
			'permalink' => get_permalink( $post ),
			'thumbnail' => get_the_post_thumbnail_url( $post, 'thumbnail' ),
		);
	}
}
