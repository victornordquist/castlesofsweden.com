<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets a registered WP user (or an admin editing their profile) upload a
 * local photo as their avatar. Hooks into pre_get_avatar_data so it's picked
 * up by every core get_avatar()/get_avatar_url() call — Journal author
 * boxes, comments, admin user lists — without those call sites needing to
 * know about it. Falls through to Gravatar when no photo has been set.
 */
class COS_User_Avatar {

	const META_KEY = 'cos_local_avatar';

	public static function init() {
		add_action( 'show_user_profile', array( __CLASS__, 'render_fields' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'render_fields' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save_fields' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_fields' ) );
		add_filter( 'pre_get_avatar_data', array( __CLASS__, 'filter_avatar_data' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script(
			'cos-image-picker-admin',
			COS_CORE_URL . 'assets/js/admin-image-picker.js',
			array( 'jquery' ),
			filemtime( COS_CORE_DIR . 'assets/js/admin-image-picker.js' ),
			true
		);
		wp_enqueue_style(
			'cos-image-picker-admin',
			COS_CORE_URL . 'assets/css/admin-image-picker.css',
			array(),
			filemtime( COS_CORE_DIR . 'assets/css/admin-image-picker.css' )
		);
	}

	public static function render_fields( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		wp_nonce_field( 'cos_local_avatar_save', 'cos_local_avatar_nonce' );
		$image_id = (int) get_user_meta( $user->ID, self::META_KEY, true );
		$thumb    = $image_id ? wp_get_attachment_image_src( $image_id, 'thumbnail' ) : false;
		?>
		<h2><?php esc_html_e( 'Avatar', 'cos-core' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Photo', 'cos-core' ); ?></th>
				<td>
					<div class="cos-image-picker" data-title="<?php esc_attr_e( 'Select avatar photo', 'cos-core' ); ?>" data-button-text="<?php esc_attr_e( 'Use this photo', 'cos-core' ); ?>">
						<div class="cos-image-picker__preview" <?php echo $thumb ? '' : 'style="display:none;"'; ?>>
							<img src="<?php echo $thumb ? esc_url( $thumb[0] ) : ''; ?>" alt="" />
							<button type="button" class="button cos-image-picker__remove"><?php esc_html_e( 'Remove Photo', 'cos-core' ); ?></button>
						</div>
						<button type="button" class="button cos-image-picker__select" <?php echo $thumb ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Select Photo', 'cos-core' ); ?></button>
						<input type="hidden" class="cos-image-picker__input" name="cos_local_avatar" value="<?php echo esc_attr( $image_id ); ?>" />
					</div>
					<p class="description"><?php esc_html_e( 'Used instead of Gravatar wherever this person is shown as an author.', 'cos-core' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function save_fields( $user_id ) {
		if ( ! isset( $_POST['cos_local_avatar_nonce'] ) ||
			! wp_verify_nonce( $_POST['cos_local_avatar_nonce'], 'cos_local_avatar_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		update_user_meta( $user_id, self::META_KEY, isset( $_POST['cos_local_avatar'] ) ? absint( $_POST['cos_local_avatar'] ) : 0 );
	}

	public static function filter_avatar_data( $args, $id_or_email ) {
		$user_id = self::resolve_user_id( $id_or_email );
		if ( ! $user_id ) {
			return $args;
		}

		$image_id = (int) get_user_meta( $user_id, self::META_KEY, true );
		if ( ! $image_id ) {
			return $args;
		}

		$size = isset( $args['size'] ) ? (int) $args['size'] : 96;
		$url  = wp_get_attachment_image_url( $image_id, array( $size, $size ) );
		if ( ! $url ) {
			return $args;
		}

		$args['url']          = $url;
		$args['found_avatar'] = true;
		return $args;
	}

	private static function resolve_user_id( $id_or_email ) {
		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}
		if ( $id_or_email instanceof WP_User ) {
			return $id_or_email->ID;
		}
		if ( $id_or_email instanceof WP_Post ) {
			return (int) $id_or_email->post_author;
		}
		if ( $id_or_email instanceof WP_Comment && $id_or_email->user_id ) {
			return (int) $id_or_email->user_id;
		}
		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			return $user ? $user->ID : 0;
		}
		return 0;
	}
}
