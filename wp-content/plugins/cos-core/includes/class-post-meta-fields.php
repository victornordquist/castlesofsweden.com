<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Feature this article" flag for regular posts — used by the Journal
 * overview to pick the pinned hero article instead of always showing the
 * most recent one. Also holds the featured image's photo credit, shown on
 * the article the same way it already is on Buildings and Listings.
 */
class COS_Post_Meta_Fields {

	const FEATURED_META_KEY      = 'cos_featured';
	const IMAGE_CREDIT_META_KEY  = 'cos_image_credit';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_post', array( __CLASS__, 'save_meta_box' ) );
	}

	public static function register_meta() {
		register_post_meta(
			'post',
			self::FEATURED_META_KEY,
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_post_meta(
			'post',
			self::IMAGE_CREDIT_META_KEY,
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
	}

	public static function add_meta_box() {
		add_meta_box(
			'cos_featured_article',
			__( 'Journal', 'cos-core' ),
			array( __CLASS__, 'render_meta_box' ),
			'post',
			'side',
			'default'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'cos_featured_save', 'cos_featured_nonce' );
		$checked       = get_post_meta( $post->ID, self::FEATURED_META_KEY, true );
		$image_credit  = get_post_meta( $post->ID, self::IMAGE_CREDIT_META_KEY, true );
		?>
		<label>
			<input type="checkbox" name="cos_featured" value="1" <?php checked( $checked, true ); ?> />
			<?php esc_html_e( 'Feature this article on the Journal overview', 'cos-core' ); ?>
		</label>
		<p>
			<label for="cos_image_credit"><?php esc_html_e( 'Image Credit', 'cos-core' ); ?></label><br />
			<input type="text" id="cos_image_credit" name="cos_image_credit" value="<?php echo esc_attr( $image_credit ); ?>" class="widefat" />
		</p>
		<?php
	}

	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['cos_featured_nonce'] ) ||
			! wp_verify_nonce( $_POST['cos_featured_nonce'], 'cos_featured_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, self::FEATURED_META_KEY, ! empty( $_POST['cos_featured'] ) );
		if ( isset( $_POST['cos_image_credit'] ) ) {
			update_post_meta( $post_id, self::IMAGE_CREDIT_META_KEY, sanitize_text_field( wp_unslash( $_POST['cos_image_credit'] ) ) );
		}
	}
}
