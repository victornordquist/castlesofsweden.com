<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps a cached SEK -> USD/EUR exchange rate so listing prices can be
 * auto-converted for display without ever making a live API call on a
 * page request. Refreshed daily via WP-Cron from Frankfurter (ECB-backed,
 * free, no API key) — a manual entry only ever needs to be made in SEK.
 */
class COS_Currency {

	const OPTION_KEY = 'cos_currency_rates';
	const CRON_HOOK  = 'cos_update_currency_rates';
	const API_URL    = 'https://api.frankfurter.dev/v1/latest?base=SEK&symbols=USD,EUR';

	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'update_rates' ) );
	}

	public static function activate() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
		// Populate immediately rather than waiting up to 24 hours for the first cron run.
		if ( ! get_option( self::OPTION_KEY ) ) {
			self::update_rates();
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public static function update_rates() {
		$response = wp_remote_get( self::API_URL, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['rates']['USD'] ) || empty( $data['rates']['EUR'] ) ) {
			return false;
		}

		update_option(
			self::OPTION_KEY,
			array(
				'USD'     => (float) $data['rates']['USD'],
				'EUR'     => (float) $data['rates']['EUR'],
				'updated' => time(),
			),
			false
		);

		return true;
	}

	public static function get_rates() {
		return get_option( self::OPTION_KEY, array() );
	}

	/**
	 * Converts a SEK amount to the given currency ('USD' or 'EUR').
	 * Returns null if no cached rate is available yet.
	 */
	public static function convert_from_sek( $amount_sek, $currency ) {
		$rates = self::get_rates();
		if ( empty( $rates[ $currency ] ) ) {
			return null;
		}
		return $amount_sek * $rates[ $currency ];
	}
}
