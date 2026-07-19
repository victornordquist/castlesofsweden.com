<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class COS_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_building' ) );
		add_action( 'init', array( __CLASS__, 'register_listing' ) );
		add_filter( 'rewrite_rules_array', array( __CLASS__, 'remove_attachment_rewrite_rules' ) );
	}

	/**
	 * WordPress auto-generates "visit/[^/]+/([^/]+)/?$ => attachment" rules for
	 * the cos_building CPT's rewrite slug. Since we don't use post-attachment
	 * permalinks, these rules only serve to greedily swallow any two-segment
	 * URL under /visit/ — including our taxonomy archives at /visit/areas/{term}/
	 * — which WordPress then 404s and "fixes" via fuzzy slug-guessing, landing
	 * on an unrelated building. Stripping them removes the collision entirely.
	 */
	public static function remove_attachment_rewrite_rules( $rules ) {
		foreach ( $rules as $pattern => $query ) {
			$is_our_cpt_prefix = 0 === strpos( $pattern, 'visit/[^/]+/' ) || 0 === strpos( $pattern, 'for-sale/[^/]+/' );
			if ( $is_our_cpt_prefix && false !== strpos( $query, 'attachment=' ) ) {
				unset( $rules[ $pattern ] );
			}
		}
		return $rules;
	}

	public static function register_building() {
		register_post_type(
			'cos_building',
			array(
				'labels'       => array(
					'name'               => __( 'Buildings', 'cos-core' ),
					'singular_name'      => __( 'Building', 'cos-core' ),
					'add_new_item'       => __( 'Add New Building', 'cos-core' ),
					'edit_item'          => __( 'Edit Building', 'cos-core' ),
					'all_items'          => __( 'All Buildings', 'cos-core' ),
					'search_items'       => __( 'Search Buildings', 'cos-core' ),
					'not_found'          => __( 'No buildings found', 'cos-core' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-building',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
				'rewrite'      => array(
					'slug'       => 'visit',
					'with_front' => false,
				),
			)
		);
	}

	public static function register_listing() {
		register_post_type(
			'cos_listing',
			array(
				'labels'       => array(
					'name'          => __( 'Listings', 'cos-core' ),
					'singular_name' => __( 'Listing', 'cos-core' ),
					'add_new_item'  => __( 'Add New Listing', 'cos-core' ),
					'edit_item'     => __( 'Edit Listing', 'cos-core' ),
					'all_items'     => __( 'All Listings', 'cos-core' ),
					'search_items'  => __( 'Search Listings', 'cos-core' ),
					'not_found'     => __( 'No listings found', 'cos-core' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-admin-home',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
				'rewrite'      => array(
					'slug'       => 'for-sale',
					'with_front' => false,
				),
			)
		);
	}
}
