<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class COS_REST_Search_Endpoint {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			'cos-core/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'search' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}

	public static function search( $request ) {
		$query = $request->get_param( 'q' );
		$limit = $request->get_param( 'limit' );
		$types = $request->get_param( 'types' );

		$args = array();
		if ( $limit ) {
			$args['limit'] = (int) $limit;
		}
		if ( $types ) {
			$args['types'] = array_map( 'trim', explode( ',', $types ) );
		}

		return rest_ensure_response( COS_Search::run( $query, $args ) );
	}
}
