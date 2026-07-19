<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field-mapping logic shared by the WP-CLI importer (local, create-or-update)
 * and the wp-admin CSV uploader (live site, create-only). Keeping this in one
 * place means the CSV column layout only has to be understood once.
 */
class COS_Building_Import_Helpers {

	const REQUIRED_COLUMNS = array(
		'ID', 'Name', 'English name', 'Architect(s)', 'Builder', 'Year built',
		'Rebuilt year', 'Wikipedia link', 'Website', 'Instagram', 'Map link',
		'Latitude', 'Longitude', 'Region', 'Building type', 'Century',
		'Categories', 'Activities', 'Features', 'Architectural style',
	);

	public static function find_existing( string $slug, $wp_post_id_field ): int {
		$wp_post_id_field = trim( (string) $wp_post_id_field );

		if ( '' !== $wp_post_id_field && is_numeric( $wp_post_id_field ) ) {
			$post = get_post( (int) $wp_post_id_field );
			if ( $post && 'cos_building' === $post->post_type ) {
				return $post->ID;
			}
		}

		$existing = get_posts(
			array(
				'post_type'      => 'cos_building',
				'name'           => $slug,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		return $existing ? (int) $existing[0] : 0;
	}

	public static function set_meta( int $post_id, array $data ) {
		update_post_meta( $post_id, 'cos_english_name', trim( $data['English name'] ) );
		update_post_meta( $post_id, 'cos_architects', trim( $data['Architect(s)'] ) );
		update_post_meta( $post_id, 'cos_builder', trim( $data['Builder'] ) );
		update_post_meta( $post_id, 'cos_year_built', self::to_int( $data['Year built'] ) );
		update_post_meta( $post_id, 'cos_rebuilt_year', self::to_int( $data['Rebuilt year'] ) );
		update_post_meta( $post_id, 'cos_wikipedia_url', esc_url_raw( trim( $data['Wikipedia link'] ) ) );
		update_post_meta( $post_id, 'cos_website_url', esc_url_raw( trim( $data['Website'] ) ) );
		update_post_meta( $post_id, 'cos_instagram_url', esc_url_raw( trim( $data['Instagram'] ) ) );
		update_post_meta( $post_id, 'cos_map_link', esc_url_raw( trim( $data['Map link'] ) ) );

		if ( is_numeric( trim( $data['Latitude'] ) ) ) {
			update_post_meta( $post_id, 'cos_lat', (float) $data['Latitude'] );
		}
		if ( is_numeric( trim( $data['Longitude'] ) ) ) {
			update_post_meta( $post_id, 'cos_lng', (float) $data['Longitude'] );
		}
	}

	public static function to_int( $value ) {
		$value = trim( (string) $value );
		return ( '' !== $value && is_numeric( $value ) ) ? (int) $value : '';
	}

	public static function set_taxonomies( int $post_id, array $data ): int {
		$warnings = 0;

		self::set_single_term( $post_id, 'cos_region', $data['Region'] );
		self::set_single_term( $post_id, 'cos_building_type', $data['Building type'] );
		self::set_single_term( $post_id, 'cos_era', $data['Century'] );
		self::set_multi_terms( $post_id, 'cos_category', $data['Categories'] );
		self::set_multi_terms( $post_id, 'cos_activity', $data['Activities'] );
		self::set_multi_terms( $post_id, 'cos_feature', $data['Features'] );

		$style = trim( $data['Architectural style'] );
		if ( '' !== $style && preg_match( '#^https?://#i', $style ) ) {
			$warnings++;
		} else {
			self::set_single_term( $post_id, 'cos_architectural_style', $style );
		}

		return $warnings;
	}

	public static function count_warnings( array $data ): int {
		$style = trim( $data['Architectural style'] );
		return ( '' !== $style && preg_match( '#^https?://#i', $style ) ) ? 1 : 0;
	}

	public static function set_single_term( int $post_id, string $taxonomy, $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return;
		}
		wp_set_object_terms( $post_id, $value, $taxonomy, false );
	}

	public static function set_multi_terms( int $post_id, string $taxonomy, $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return;
		}
		$terms = array_filter( array_map( 'trim', explode( '|', $value ) ) );
		if ( $terms ) {
			wp_set_object_terms( $post_id, array_values( $terms ), $taxonomy, false );
		}
	}

	public static function read_csv_file( string $file ): array {
		$rows   = array();
		$handle = fopen( $file, 'r' );
		while ( false !== ( $row = fgetcsv( $handle ) ) ) {
			$rows[] = $row;
		}
		fclose( $handle );
		return $rows;
	}

	public static function write_csv_file( string $file, array $rows ) {
		$handle = fopen( $file, 'w' );
		foreach ( $rows as $row ) {
			fputcsv( $handle, $row );
		}
		fclose( $handle );
	}

	/**
	 * Parses CSV rows already split into arrays (e.g. via read_csv_file) into
	 * header-keyed associative rows. Returns WP_Error if the header is missing
	 * any column the importer relies on.
	 */
	public static function to_assoc_rows( array $rows ) {
		if ( empty( $rows ) ) {
			return new WP_Error( 'cos_empty_csv', 'CSV file is empty.' );
		}

		$header  = array_shift( $rows );
		$missing = array_diff( self::REQUIRED_COLUMNS, $header );
		if ( $missing ) {
			return new WP_Error(
				'cos_missing_columns',
				'CSV is missing required column(s): ' . implode( ', ', $missing )
			);
		}

		$assoc_rows = array();
		foreach ( $rows as $row ) {
			$data = array_combine( $header, $row );
			if ( false === $data ) {
				continue; // column-count mismatch, silently skip
			}
			$assoc_rows[] = $data;
		}

		return $assoc_rows;
	}
}
