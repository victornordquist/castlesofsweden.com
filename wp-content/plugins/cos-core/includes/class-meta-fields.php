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
		'cos_tagline'         => array( 'Tagline', 'string', 'sanitize_text_field' ),
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
		'cos_why_visit'         => array( 'Why Visit (short description)', 'string', 'sanitize_textarea_field', 'textarea' ),
		'cos_opening_hours'     => array( 'Opening Hours', 'string', 'sanitize_textarea_field', 'textarea' ),
		'cos_admission'         => array( 'Admission', 'string', 'sanitize_text_field' ),
		'cos_parking'           => array( 'Parking', 'string', 'sanitize_text_field' ),
		'cos_accessibility'     => array( 'Accessibility', 'string', 'sanitize_text_field' ),
		'cos_guided_tours'      => array( 'Guided Tours', 'string', 'sanitize_text_field' ),
	);

	const GALLERY_META_KEY = 'cos_building_gallery';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_cos_building', array( __CLASS__, 'save_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	public static function sanitize_float( $value ) {
		return is_numeric( $value ) ? (float) $value : '';
	}

	public static function sanitize_gallery( $value ) {
		if ( is_string( $value ) ) {
			$value = array_filter( explode( ',', $value ) );
		}
		return array_values( array_unique( array_map( 'absint', (array) $value ) ) );
	}

	public static function register_meta() {
		foreach ( self::BUILDING_FIELDS as $key => $field ) {
			list( $label, $type, $sanitize ) = $field;
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

		register_post_meta(
			'cos_building',
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

	public static function add_meta_box() {
		add_meta_box(
			'cos_building_details',
			__( 'Building Details', 'cos-core' ),
			array( __CLASS__, 'render_meta_box' ),
			'cos_building',
			'normal',
			'high'
		);

		add_meta_box(
			'cos_building_gallery',
			__( 'Photo Gallery', 'cos-core' ),
			array( __CLASS__, 'render_gallery_meta_box' ),
			'cos_building',
			'normal',
			'default'
		);
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'cos_building' !== get_post_type() ) {
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
			<input type="hidden" class="cos-gallery-picker__input" name="cos_building_gallery" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
			<button type="button" class="button cos-gallery-picker__add"><?php esc_html_e( 'Add Images', 'cos-core' ); ?></button>
		</div>
		<?php
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'cos_building_details_save', 'cos_building_details_nonce' );
		echo '<table class="form-table"><tbody>';
		foreach ( self::BUILDING_FIELDS as $key => $field ) {
			list( $label, $type, $sanitize, $input_type ) = array_pad( $field, 4, null );
			$value = get_post_meta( $post->ID, $key, true );
			if ( 'textarea' === $input_type ) {
				printf(
					'<tr><th><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="%1$s" rows="3" class="large-text">%3$s</textarea></td></tr>',
					esc_attr( $key ),
					esc_html( $label ),
					esc_textarea( $value )
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
		if ( ! isset( $_POST['cos_building_details_nonce'] ) ||
			! wp_verify_nonce( $_POST['cos_building_details_nonce'], 'cos_building_details_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( self::BUILDING_FIELDS as $key => $field ) {
			list( $label, $type, $sanitize ) = $field;
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
