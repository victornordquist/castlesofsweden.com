<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates a static 1200×630 JPEG share image per building (photo +
 * building name + region, dark gradient overlay for legibility) and writes
 * it to wp-content/uploads/cos-share-images/{post_id}.jpg. Regenerated
 * unconditionally on every save; deleted on trash/delete. Never fatals —
 * any failure just leaves no file, and cos_social_share_meta() falls back
 * to the existing thumbnail/generic-image logic untouched.
 */
class COS_Share_Image_Generator {

	const WIDTH  = 1200;
	const HEIGHT = 630;
	const SUBDIR = 'cos-share-images';

	public static function init() {
		add_action( 'save_post_cos_building', array( __CLASS__, 'maybe_generate' ) );
		add_action( 'trashed_post', array( __CLASS__, 'maybe_delete' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'maybe_generate_by_id' ) );
		add_action( 'deleted_post', array( __CLASS__, 'maybe_delete' ) );
	}

	public static function maybe_generate( $post_id ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		self::generate( $post_id );
	}

	public static function maybe_generate_by_id( $post_id ) {
		if ( 'cos_building' === get_post_type( $post_id ) ) {
			self::generate( $post_id );
		}
	}

	public static function maybe_delete( $post_id ) {
		if ( 'cos_building' === get_post_type( $post_id ) ) {
			self::delete_file( $post_id );
		}
	}

	public static function get_path( $post_id ) {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . self::SUBDIR . '/' . (int) $post_id . '.jpg';
	}

	public static function get_url( $post_id ) {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['baseurl'] ) . self::SUBDIR . '/' . (int) $post_id . '.jpg';
	}

	public static function exists( $post_id ) {
		return (bool) file_exists( self::get_path( $post_id ) );
	}

	private static function delete_file( $post_id ) {
		$path = self::get_path( $post_id );
		if ( file_exists( $path ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors
			@unlink( $path );
		}
	}

	public static function generate( $post_id ) {
		if ( ! extension_loaded( 'gd' ) ) {
			return false;
		}

		if ( ! has_post_thumbnail( $post_id ) ) {
			// No photo → no share image; remove any stale one from a
			// previously-set-then-removed featured image.
			self::delete_file( $post_id );
			return false;
		}

		$thumb_id = get_post_thumbnail_id( $post_id );
		$src_path = get_attached_file( $thumb_id );
		if ( ! $src_path || ! file_exists( $src_path ) ) {
			return false;
		}

		$playfair   = COS_THEME_DIR . '/assets/fonts/PlayfairDisplay-Bold.ttf';
		$montserrat = COS_THEME_DIR . '/assets/fonts/Montserrat-SemiBold.ttf';
		if ( ! file_exists( $playfair ) || ! file_exists( $montserrat ) ) {
			return false;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors
		$info = @getimagesize( $src_path );
		if ( ! $info ) {
			return false;
		}

		switch ( $info['mime'] ) {
			case 'image/jpeg':
				// phpcs:ignore WordPress.PHP.NoSilencedErrors
				$src = @imagecreatefromjpeg( $src_path );
				break;
			case 'image/png':
				// phpcs:ignore WordPress.PHP.NoSilencedErrors
				$src = @imagecreatefrompng( $src_path );
				break;
			default:
				// No WebP anywhere in this codebase currently.
				return false;
		}
		if ( ! $src ) {
			return false;
		}

		$canvas = self::cover_resize( $src, self::WIDTH, self::HEIGHT );
		imagedestroy( $src );
		if ( ! $canvas ) {
			return false;
		}

		self::draw_gradient_overlay( $canvas );

		$title        = get_the_title( $post_id );
		$region_terms = get_the_terms( $post_id, 'cos_region' );
		$region       = ( ! is_wp_error( $region_terms ) && ! empty( $region_terms ) )
			? $region_terms[0]->name
			: '';

		self::draw_text( $canvas, $title, $region, $playfair, $montserrat );

		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['basedir'] ) . self::SUBDIR;
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			$index_file = trailingslashit( $dir ) . 'index.php';
			if ( ! file_exists( $index_file ) ) {
				file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
			}
		}

		$ok = imagejpeg( $canvas, self::get_path( $post_id ), 85 );
		imagedestroy( $canvas );

		return (bool) $ok;
	}

	/**
	 * "Cover" resize: scale + crop so the source fills exactly $target_w ×
	 * $target_h with no distortion, cropping overflow from the longer axis,
	 * centered. Single imagecopyresampled() call using a source crop
	 * rectangle computed in original-image coordinates.
	 */
	private static function cover_resize( $src, $target_w, $target_h ) {
		$src_w = imagesx( $src );
		$src_h = imagesy( $src );
		if ( $src_w < 1 || $src_h < 1 ) {
			return false;
		}

		$src_ratio    = $src_w / $src_h;
		$target_ratio = $target_w / $target_h;

		if ( $src_ratio > $target_ratio ) {
			// Source is relatively wider than target → crop left/right.
			$crop_h = $src_h;
			$crop_w = (int) round( $src_h * $target_ratio );
			$crop_x = (int) round( ( $src_w - $crop_w ) / 2 );
			$crop_y = 0;
		} else {
			// Source is relatively taller than target → crop top/bottom.
			$crop_w = $src_w;
			$crop_h = (int) round( $src_w / $target_ratio );
			$crop_x = 0;
			$crop_y = (int) round( ( $src_h - $crop_h ) / 2 );
		}

		$canvas = imagecreatetruecolor( $target_w, $target_h );
		$ok     = imagecopyresampled(
			$canvas,
			$src,
			0,
			0,
			$crop_x,
			$crop_y,
			$target_w,
			$target_h,
			$crop_w,
			$crop_h
		);

		if ( ! $ok ) {
			imagedestroy( $canvas );
			return false;
		}

		return $canvas;
	}

	/**
	 * Manual per-row alpha-blended gradient, since GD has no native gradient
	 * fill. Bottom of the card fades to rgb(30,26,20) (same dark warm tone
	 * as the on-site hero overlay) for text legibility; top of the card is
	 * untouched.
	 */
	private static function draw_gradient_overlay( $canvas ) {
		$width                   = imagesx( $canvas );
		$height                  = imagesy( $canvas );
		$gradient_start_fraction = 0.35; // top 35% of the card is untouched.
		$max_opacity             = 0.78; // opacity at the very bottom row.

		imagealphablending( $canvas, true );

		$start_y = (int) round( $height * $gradient_start_fraction );

		for ( $y = $start_y; $y < $height; $y++ ) {
			$f       = ( $y - $start_y ) / max( 1, ( $height - $start_y - 1 ) ); // 0..1
			$opacity = $max_opacity * $f;

			// GD alpha: 0 = fully opaque, 127 = fully transparent.
			$alpha = (int) round( ( 1 - $opacity ) * 127 );
			$alpha = max( 0, min( 127, $alpha ) );

			$color = imagecolorallocatealpha( $canvas, 30, 26, 20, $alpha );
			imagefilledrectangle( $canvas, 0, $y, $width - 1, $y, $color );
		}
	}

	/**
	 * Draws the centered building name (Playfair Display Bold) and, below
	 * it, the uppercase region name (Montserrat SemiBold) in the gradient
	 * zone near the bottom of the card.
	 */
	private static function draw_text( $canvas, $title, $region, $font_bold, $font_body ) {
		$width     = imagesx( $canvas );
		$padding_x = 70;

		$white = imagecolorallocate( $canvas, 255, 255, 255 );
		$gold  = imagecolorallocate( $canvas, 194, 148, 82 ); // --color-accent

		// Building name: start large, shrink to fit if the title is long,
		// down to a sane minimum, so nothing overflows the card edges.
		$max_text_width = $width - ( $padding_x * 2 );
		$name_size      = 54;
		$min_name_size  = 30;
		do {
			$name_width = self::text_width( $name_size, $font_bold, $title );
			if ( $name_width <= $max_text_width || $name_size <= $min_name_size ) {
				break;
			}
			$name_size -= 2;
		} while ( true );

		$name_baseline_y = self::HEIGHT - 110;
		self::draw_centered_line( $canvas, $title, $name_size, $font_bold, $white, $width, $name_baseline_y );

		if ( $region ) {
			$region_text       = mb_strtoupper( $region, 'UTF-8' );
			$region_size       = 22;
			$region_baseline_y = self::HEIGHT - 65;
			self::draw_centered_line( $canvas, $region_text, $region_size, $font_body, $gold, $width, $region_baseline_y );
		}
	}

	private static function text_width( $size, $font, $text ) {
		$bbox = imagettfbbox( $size, 0, $font, $text );
		return abs( $bbox[2] - $bbox[0] );
	}

	private static function draw_centered_line( $canvas, $text, $size, $font, $color, $canvas_width, $baseline_y ) {
		$bbox       = imagettfbbox( $size, 0, $font, $text );
		$text_width = abs( $bbox[2] - $bbox[0] );
		$x          = (int) round( ( $canvas_width - $text_width ) / 2 ) - $bbox[0];

		imagettftext( $canvas, $size, 0, $x, $baseline_y, $color, $font, $text );
	}
}
