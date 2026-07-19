<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class COS_Meta_Fields {

	/**
	 * meta key => [ label, type, sanitize_callback ]
	 */
	const BUILDING_FIELDS = array(
		'cos_english_name'    => array( 'English Name', 'string', 'sanitize_text_field' ),
		'cos_architects'      => array( 'Architect(s)', 'string', 'sanitize_text_field' ),
		'cos_builder'         => array( 'Builder', 'string', 'sanitize_text_field' ),
		'cos_year_built'      => array( 'Year Built', 'integer', 'absint' ),
		'cos_rebuilt_year'    => array( 'Rebuilt Year', 'integer', 'absint' ),
		'cos_wikipedia_url'   => array( 'Wikipedia Link', 'string', 'esc_url_raw' ),
		'cos_website_url'     => array( 'Website', 'string', 'esc_url_raw' ),
		'cos_instagram_url'   => array( 'Instagram', 'string', 'esc_url_raw' ),
		'cos_map_link'        => array( 'Google Maps Link', 'string', 'esc_url_raw' ),
		'cos_lat'             => array( 'Latitude', 'number', array( __CLASS__, 'sanitize_float' ) ),
		'cos_lng'             => array( 'Longitude', 'number', array( __CLASS__, 'sanitize_float' ) ),
		'cos_image_credit'    => array( 'Image Credit', 'string', 'sanitize_text_field' ),
		'cos_image_source_url' => array( 'Image Source URL', 'string', 'esc_url_raw' ),
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_cos_building', array( __CLASS__, 'save_meta_box' ) );
	}

	public static function sanitize_float( $value ) {
		return is_numeric( $value ) ? (float) $value : '';
	}

	public static function register_meta() {
		foreach ( self::BUILDING_FIELDS as $key => list( $label, $type, $sanitize ) ) {
			register_post_meta(
				'cos_building',
				$key,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $type,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	public static function add_meta_box() {
		add_meta_box(
			'cos_building_details',
			__( 'Building Details', 'cos-core' ),
			array( __CLASS__, 'render_meta_box' ),
			'cos_building',
			'normal',
			'high'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'cos_building_details_save', 'cos_building_details_nonce' );
		echo '<table class="form-table"><tbody>';
		foreach ( self::BUILDING_FIELDS as $key => list( $label, $type ) ) {
			$value = get_post_meta( $post->ID, $key, true );
			printf(
				'<tr><th><label for="%1$s">%2$s</label></th><td><input type="text" id="%1$s" name="%1$s" value="%3$s" class="regular-text" /></td></tr>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( $value )
			);
		}
		echo '</tbody></table>';
	}

	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['cos_building_details_nonce'] ) ||
			! wp_verify_nonce( $_POST['cos_building_details_nonce'], 'cos_building_details_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( self::BUILDING_FIELDS as $key => list( $label, $type, $sanitize ) ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$value = call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) );
			update_post_meta( $post_id, $key, $value );
		}
	}
}
