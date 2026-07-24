<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pushes newsletter signups (captured locally by COS_Newsletter) into a
 * Mailchimp audience. Single opt-in, matching the signup form's existing
 * "Thanks for signing up!" UX — visitors are added as 'subscribed'
 * immediately rather than sent a separate confirmation email.
 *
 * Deliberately POST-only, never PUT-based upsert: Mailchimp treats any
 * email that was ever a member (including a since-unsubscribed one) as
 * "already exists" on POST, which subscribe() treats as success without
 * changing their status. Using PUT would silently resubscribe someone who
 * previously unsubscribed without fresh consent.
 */
class COS_Mailchimp {

	const OPTION_API_KEY     = 'cos_mailchimp_api_key';
	const OPTION_AUDIENCE_ID = 'cos_mailchimp_audience_id';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
	}

	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=cos_subscriber',
			__( 'Mailchimp Settings', 'cos-core' ),
			__( 'Mailchimp', 'cos-core' ),
			'manage_options',
			'cos-mailchimp-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cos-core' ) );
		}

		if ( isset( $_POST['cos_mailchimp_save'] ) && check_admin_referer( 'cos_mailchimp_settings' ) ) {
			update_option( self::OPTION_API_KEY, sanitize_text_field( wp_unslash( $_POST[ self::OPTION_API_KEY ] ?? '' ) ) );
			update_option( self::OPTION_AUDIENCE_ID, sanitize_text_field( wp_unslash( $_POST[ self::OPTION_AUDIENCE_ID ] ?? '' ) ) );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'cos-core' ) . '</p></div>';
		}

		$api_key     = get_option( self::OPTION_API_KEY, '' );
		$audience_id = get_option( self::OPTION_AUDIENCE_ID, '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mailchimp Settings', 'cos-core' ); ?></h1>
			<p>
				<?php
				echo wp_kses(
					__( 'Find your API key under Mailchimp <strong>Account &rarr; Extras &rarr; API keys</strong>. Find your Audience ID under <strong>Audience &rarr; Settings &rarr; Audience name and defaults</strong>.', 'cos-core' ),
					array( 'strong' => array() )
				);
				?>
			</p>
			<?php if ( ! $api_key || ! $audience_id ) : ?>
				<p><em><?php esc_html_e( 'Mailchimp is not configured yet — signups are only being saved locally (Newsletter Subscribers) until both fields below are filled in.', 'cos-core' ); ?></em></p>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'cos_mailchimp_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="cos_mailchimp_api_key"><?php esc_html_e( 'API Key', 'cos-core' ); ?></label></th>
						<td><input type="password" id="cos_mailchimp_api_key" name="<?php echo esc_attr( self::OPTION_API_KEY ); ?>" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" autocomplete="off"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cos_mailchimp_audience_id"><?php esc_html_e( 'Audience ID', 'cos-core' ); ?></label></th>
						<td><input type="text" id="cos_mailchimp_audience_id" name="<?php echo esc_attr( self::OPTION_AUDIENCE_ID ); ?>" value="<?php echo esc_attr( $audience_id ); ?>" class="regular-text"></td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" name="cos_mailchimp_save" value="1" class="button button-primary"><?php esc_html_e( 'Save Settings', 'cos-core' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	public static function is_configured() {
		return (bool) get_option( self::OPTION_API_KEY ) && (bool) get_option( self::OPTION_AUDIENCE_ID );
	}

	/**
	 * Adds (or confirms already-added) $email to the configured audience.
	 * Returns true on success/already-a-member, WP_Error otherwise. Callers
	 * should treat a WP_Error as non-fatal — the visitor-facing form already
	 * has a local fallback record regardless of this call's outcome.
	 */
	public static function subscribe( $email ) {
		$api_key     = get_option( self::OPTION_API_KEY );
		$audience_id = get_option( self::OPTION_AUDIENCE_ID );

		$key_parts = explode( '-', $api_key );
		$dc        = end( $key_parts );

		$response = wp_remote_post(
			"https://{$dc}.api.mailchimp.com/3.0/lists/{$audience_id}/members",
			array(
				// Synchronous inside a visitor's form submit (unlike COS_Currency's
				// 15s background-cron call) — kept short so a slow/hung request
				// can't make a real visitor wait long for their redirect.
				'timeout' => 5,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $api_key ),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'email_address' => $email,
						'status'        => 'subscribed',
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 === $code || 201 === $code ) {
			return true;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Fires even for an email that was previously a member and later
		// unsubscribed/removed — that's fine, it just means no action is
		// needed here (see class docblock for why we don't upsert via PUT).
		if ( 400 === $code && isset( $body['title'] ) && 'Member Exists' === $body['title'] ) {
			return true;
		}

		return new WP_Error(
			'cos_mailchimp_error',
			$body['detail'] ?? __( 'Unknown Mailchimp error', 'cos-core' )
		);
	}
}
