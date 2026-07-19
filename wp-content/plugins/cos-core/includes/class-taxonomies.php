<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class COS_Taxonomies {

	/**
	 * taxonomy key => [ singular label, plural label, rewrite slug ]
	 */
	const BUILDING_TAXONOMIES = array(
		'cos_region'               => array( 'Region', 'Regions', 'visit/areas' ),
		'cos_building_type'        => array( 'Building Type', 'Building Types', 'building-type' ),
		'cos_category'             => array( 'Category', 'Categories', 'visit/things-to-do' ),
		'cos_activity'             => array( 'Activity', 'Activities', 'activity' ),
		'cos_feature'              => array( 'Feature', 'Features', 'feature' ),
		'cos_architectural_style'  => array( 'Architectural Style', 'Architectural Styles', 'architectural-style' ),
		'cos_era'                  => array( 'Era', 'Eras', 'era' ),
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_all' ) );
	}

	public static function register_all() {
		foreach ( self::BUILDING_TAXONOMIES as $key => list( $singular, $plural, $slug ) ) {
			register_taxonomy(
				$key,
				'cos_building',
				array(
					'labels'            => array(
						'name'          => __( $plural, 'cos-core' ),
						'singular_name' => __( $singular, 'cos-core' ),
					),
					'hierarchical'      => false,
					'public'            => true,
					'show_in_rest'      => true,
					'show_admin_column' => true,
					'rewrite'           => array( 'slug' => $slug ),
				)
			);
		}
	}
}
