<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optional guest-author override for Journal articles — lets an editor
 * credit a byline, bio, and photo without that person needing a WP user
 * account. When set, these take precedence over the WP author's own
 * display name/description/avatar wherever the theme renders authorship;
 * see cos_journal_author_name() and cos_journal_author_box() in
 * inc/template-tags.php.
 */
class COS_Author_Meta_Fields {

	const NAME_META_KEY  = 'cos_guest_author_name';
	const BIO_META_KEY   = 'cos_guest_author_bio';
	const IMAGE_META_KEY = 'cos_guest_author_image';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_post', array( __CLASS__, 'save_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	public static function register_meta() {
		register_post_meta(
			'post',
			self::NAME_META_KEY,
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_post_meta(
			'post',
			self::BIO_META_KEY,
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_post_meta(
			'post',
			self::IMAGE_META_KEY,
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	public static function add_meta_box() {
		add_meta_box(
			'cos_guest_author',
			__( 'Guest Author', 'cos-core' ),
			array( __CLASS__, 'render_meta_box' ),
			'post',
			'normal',
			'default'
		);
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'post' !== get_post_type() ) {
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

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'cos_guest_author_save', 'cos_guest_author_nonce' );

		$name     = get_post_meta( $post->ID, self::NAME_META_KEY, true );
		$bio      = get_post_meta( $post->ID, self::BIO_META_KEY, true );
		$image_id = (int) get_post_meta( $post->ID, self::IMAGE_META_KEY, true );
		$thumb    = $image_id ? wp_get_attachment_image_src( $image_id, 'thumbnail' ) : false;
		?>
		<p class="description">
			<?php esc_html_e( 'Fill these in to credit a byline that isn’t a WordPress user — e.g. a freelance contributor. Leave blank to use the WordPress author assigned above.', 'cos-core' ); ?>
		</p>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="cos_guest_author_name"><?php esc_html_e( 'Name', 'cos-core' ); ?></label></th>
					<td><input type="text" id="cos_guest_author_name" name="cos_guest_author_name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="cos_guest_author_bio"><?php esc_html_e( 'Bio', 'cos-core' ); ?></label></th>
					<td><textarea id="cos_guest_author_bio" name="cos_guest_author_bio" rows="4" class="large-text"><?php echo esc_textarea( $bio ); ?></textarea></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Photo', 'cos-core' ); ?></th>
					<td>
						<div class="cos-image-picker" data-title="<?php esc_attr_e( 'Select author photo', 'cos-core' ); ?>" data-button-text="<?php esc_attr_e( 'Use this photo', 'cos-core' ); ?>">
							<div class="cos-image-picker__preview" <?php echo $thumb ? '' : 'style="display:none;"'; ?>>
								<img src="<?php echo $thumb ? esc_url( $thumb[0] ) : ''; ?>" alt="" />
								<button type="button" class="button cos-image-picker__remove"><?php esc_html_e( 'Remove Photo', 'cos-core' ); ?></button>
							</div>
							<button type="button" class="button cos-image-picker__select" <?php echo $thumb ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Select Photo', 'cos-core' ); ?></button>
							<input type="hidden" class="cos-image-picker__input" name="cos_guest_author_image" value="<?php echo esc_attr( $image_id ); ?>" />
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['cos_guest_author_nonce'] ) ||
			! wp_verify_nonce( $_POST['cos_guest_author_nonce'], 'cos_guest_author_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST[ self::NAME_META_KEY ] ) ) {
			update_post_meta( $post_id, self::NAME_META_KEY, sanitize_text_field( wp_unslash( $_POST[ self::NAME_META_KEY ] ) ) );
		}
		if ( isset( $_POST[ self::BIO_META_KEY ] ) ) {
			update_post_meta( $post_id, self::BIO_META_KEY, sanitize_textarea_field( wp_unslash( $_POST[ self::BIO_META_KEY ] ) ) );
		}
		if ( isset( $_POST[ self::IMAGE_META_KEY ] ) ) {
			update_post_meta( $post_id, self::IMAGE_META_KEY, absint( $_POST[ self::IMAGE_META_KEY ] ) );
		}
	}
}
