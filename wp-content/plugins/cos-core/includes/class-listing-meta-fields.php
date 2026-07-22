<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class COS_Listing_Meta_Fields {

	/**
	 * meta key => [ label, type, sanitize_callback ]
	 */
	const LISTING_FIELDS = array(
		'cos_listing_sold'          => array( 'Sold', 'boolean', 'rest_sanitize_boolean' ),
		'cos_listing_price_sek'     => array( 'Price (SEK)', 'integer', 'absint' ),
		'cos_listing_location'      => array( 'Location', 'string', 'sanitize_text_field' ),
		'cos_listing_building_size' => array( 'Building Size (m²)', 'number', array( __CLASS__, 'sanitize_float' ) ),
		'cos_listing_land_size'     => array( 'Land Size (hectares)', 'number', array( __CLASS__, 'sanitize_float' ) ),
		'cos_listing_lat'           => array( 'Latitude', 'number', array( __CLASS__, 'sanitize_float' ) ),
		'cos_listing_lng'           => array( 'Longitude', 'number', array( __CLASS__, 'sanitize_float' ) ),
		'cos_listing_image_credit'  => array( 'Image Credit', 'string', 'sanitize_text_field' ),
		'cos_listing_broker_name'   => array( 'Broker Name', 'string', 'sanitize_text_field' ),
		'cos_listing_broker_url'    => array( 'Broker Listing URL', 'string', 'esc_url_raw' ),
	);

	const GALLERY_META_KEY = 'cos_listing_gallery';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_cos_listing', array( __CLASS__, 'save_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	public static function sanitize_float( $value ) {
		return is_numeric( $value ) ? (float) $value : '';
	}

	public static function register_meta() {
		foreach ( self::LISTING_FIELDS as $key => list( $label, $type, $sanitize ) ) {
			register_post_meta(
				'cos_listing',
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

		register_post_meta(
			'cos_listing',
			self::GALLERY_META_KEY,
			array(
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'single'            => true,
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_gallery' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	public static function sanitize_gallery( $value ) {
		if ( is_string( $value ) ) {
			$value = array_filter( explode( ',', $value ) );
		}
		return array_values( array_unique( array_map( 'absint', (array) $value ) ) );
	}

	public static function add_meta_box() {
		add_meta_box(
			'cos_listing_details',
			__( 'Listing Details', 'cos-core' ),
			array( __CLASS__, 'render_meta_box' ),
			'cos_listing',
			'normal',
			'high'
		);

		add_meta_box(
			'cos_listing_gallery',
			__( 'Photo Gallery', 'cos-core' ),
			array( __CLASS__, 'render_gallery_meta_box' ),
			'cos_listing',
			'normal',
			'default'
		);
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'cos_listing' !== get_post_type() ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script(
			'cos-gallery-picker-admin',
			COS_CORE_URL . 'assets/js/admin-gallery-picker.js',
			array( 'jquery' ),
			filemtime( COS_CORE_DIR . 'assets/js/admin-gallery-picker.js' ),
			true
		);
		wp_enqueue_style(
			'cos-gallery-picker-admin',
			COS_CORE_URL . 'assets/css/admin-gallery-picker.css',
			array(),
			filemtime( COS_CORE_DIR . 'assets/css/admin-gallery-picker.css' )
		);
	}

	public static function render_gallery_meta_box( $post ) {
		$ids = (array) get_post_meta( $post->ID, self::GALLERY_META_KEY, true );
		?>
		<div class="cos-gallery-picker" data-title="<?php esc_attr_e( 'Select gallery images', 'cos-core' ); ?>" data-button-text="<?php esc_attr_e( 'Add to gallery', 'cos-core' ); ?>">
			<ul class="cos-gallery-picker__list">
				<?php foreach ( $ids as $id ) : ?>
					<?php $thumb = wp_get_attachment_image_src( $id, 'thumbnail' ); ?>
					<?php if ( $thumb ) : ?>
						<li class="cos-gallery-picker__item" data-id="<?php echo esc_attr( $id ); ?>">
							<img src="<?php echo esc_url( $thumb[0] ); ?>" alt="" />
							<button type="button" class="cos-gallery-picker__remove" aria-label="<?php esc_attr_e( 'Remove image', 'cos-core' ); ?>">&times;</button>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" class="cos-gallery-picker__input" name="cos_listing_gallery" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
			<button type="button" class="button cos-gallery-picker__add"><?php esc_html_e( 'Add Images', 'cos-core' ); ?></button>
		</div>
		<?php
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'cos_listing_details_save', 'cos_listing_details_nonce' );
		echo '<table class="form-table"><tbody>';
		foreach ( self::LISTING_FIELDS as $key => list( $label, $type ) ) {
			$value = get_post_meta( $post->ID, $key, true );
			if ( 'boolean' === $type ) {
				printf(
					'<tr><th><label for="%1$s">%2$s</label></th><td><input type="checkbox" id="%1$s" name="%1$s" value="1" %3$s /></td></tr>',
					esc_attr( $key ),
					esc_html( $label ),
					checked( $value, true, false )
				);
				continue;
			}
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
		if ( ! isset( $_POST['cos_listing_details_nonce'] ) ||
			! wp_verify_nonce( $_POST['cos_listing_details_nonce'], 'cos_listing_details_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( self::LISTING_FIELDS as $key => list( $label, $type, $sanitize ) ) {
			if ( 'boolean' === $type ) {
				// Unchecked checkboxes submit no field at all, unlike every
				// other input type here — isset() alone would never be able
				// to record "turned off", so this branch treats a missing
				// key as an explicit false rather than skipping the save.
				update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? 1 : 0 );
				continue;
			}
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$value = call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) );
			update_post_meta( $post_id, $key, $value );
		}

		if ( isset( $_POST[ self::GALLERY_META_KEY ] ) ) {
			update_post_meta( $post_id, self::GALLERY_META_KEY, self::sanitize_gallery( wp_unslash( $_POST[ self::GALLERY_META_KEY ] ) ) );
		}
	}
}
