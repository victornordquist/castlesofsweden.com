<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Self-hosted newsletter signup capture. Stores subscribers as a private
 * CPT so signups aren't lost before a real ESP (Mailchimp, Klaviyo, etc.)
 * is chosen — swapping in a real provider later only touches handle_subscribe().
 */
class COS_Newsletter {

	const POST_TYPE = 'cos_subscriber';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'admin_post_nopriv_cos_subscribe', array( __CLASS__, 'handle_subscribe' ) );
		add_action( 'admin_post_cos_subscribe', array( __CLASS__, 'handle_subscribe' ) );
	}

	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Newsletter Subscribers', 'cos-core' ),
					'singular_name' => __( 'Subscriber', 'cos-core' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-email',
				'supports'     => array( 'title' ),
				'capabilities' => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap' => true,
			)
		);
	}

	public static function handle_subscribe() {
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$redirect = remove_query_arg( 'cos_subscribe', $redirect );

		if ( ! isset( $_POST['cos_subscribe_nonce'] ) || ! wp_verify_nonce( $_POST['cos_subscribe_nonce'], 'cos_subscribe' ) ) {
			wp_safe_redirect( add_query_arg( 'cos_subscribe', 'error', $redirect ) );
			exit;
		}

		// Honeypot: real visitors never fill this hidden field.
		if ( ! empty( $_POST['cos_website'] ) ) {
			wp_safe_redirect( add_query_arg( 'cos_subscribe', 'success', $redirect ) );
			exit;
		}

		$email = isset( $_POST['cos_email'] ) ? sanitize_email( wp_unslash( $_POST['cos_email'] ) ) : '';

		if ( ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'cos_subscribe', 'invalid', $redirect ) );
			exit;
		}

		$existing = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'title'          => $email,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! $existing ) {
			wp_insert_post(
				array(
					'post_type'   => self::POST_TYPE,
					'post_title'  => $email,
					'post_status' => 'publish',
				)
			);
		}

		wp_safe_redirect( add_query_arg( 'cos_subscribe', 'success', $redirect ) );
		exit;
	}
}
