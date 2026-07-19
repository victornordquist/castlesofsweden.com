<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports data/database.csv into cos_building posts.
 *
 * ## OPTIONS
 *
 * <file>
 * : Path to database.csv.
 *
 * [--dry-run]
 * : Preview the import without writing anything.
 *
 * ## EXAMPLES
 *
 *     wp cos import-buildings data/database.csv
 *     wp cos import-buildings data/database.csv --dry-run
 */
class COS_CLI_Import_Buildings {

	public function __invoke( $args, $assoc_args ) {
		list( $file ) = $args;
		$dry_run = WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( "File not found: $file" );
		}

		$rows = self::read_csv( $file );
		if ( empty( $rows ) ) {
			WP_CLI::error( 'CSV file is empty.' );
		}

		$header = array_shift( $rows );
		$stats  = array(
			'inserted' => 0,
			'updated'  => 0,
			'errors'   => 0,
			'warnings' => 0,
		);
		$output_rows = array( $header );

		$progress = WP_CLI\Utils\make_progress_bar( 'Importing buildings', count( $rows ) );

		foreach ( $rows as $row ) {
			$data = array_combine( $header, $row );

			if ( false === $data ) {
				WP_CLI::warning( 'Skipped a row with a column-count mismatch: ' . implode( ',', $row ) );
				$stats['errors']++;
				$output_rows[] = $row;
				$progress->tick();
				continue;
			}

			$result = self::import_row( $data, $dry_run );

			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( "Row '{$data['ID']}': " . $result->get_error_message() );
				$stats['errors']++;
				$output_rows[] = array_values( $data );
				$progress->tick();
				continue;
			}

			$data['WP Post ID'] = $result['post_id'];
			$output_rows[]      = array_values( $data );
			$stats[ $result['action'] ]++;
			$stats['warnings'] += $result['warnings'];

			$progress->tick();
		}

		$progress->finish();

		if ( ! $dry_run ) {
			self::write_csv( $file, $output_rows );
		}

		WP_CLI::success(
			sprintf(
				'Inserted: %d, Updated: %d, Errors: %d, Warnings: %d%s',
				$stats['inserted'],
				$stats['updated'],
				$stats['errors'],
				$stats['warnings'],
				$dry_run ? ' (dry run — no changes were made)' : ''
			)
		);
	}

	private static function import_row( array $data, bool $dry_run ) {
		$slug  = sanitize_title( trim( $data['ID'] ) );
		$title = trim( $data['Name'] ) !== '' ? trim( $data['Name'] ) : trim( $data['English name'] );

		if ( '' === $slug || '' === $title ) {
			return new WP_Error( 'cos_invalid_row', 'Missing ID or name.' );
		}

		$existing_id = self::find_existing( $slug, $data['WP Post ID'] ?? '' );

		if ( $dry_run ) {
			return array(
				'post_id'  => $existing_id ?: 0,
				'action'   => $existing_id ? 'updated' : 'inserted',
				'warnings' => self::count_warnings( $data ),
			);
		}

		$postarr = array(
			'post_type'   => 'cos_building',
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_status' => 'publish',
		);

		if ( $existing_id ) {
			$postarr['ID'] = $existing_id;
		}

		$post_id = wp_insert_post( $postarr, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		self::set_meta( $post_id, $data );
		$warnings = self::set_taxonomies( $post_id, $data );

		return array(
			'post_id'  => $post_id,
			'action'   => $existing_id ? 'updated' : 'inserted',
			'warnings' => $warnings,
		);
	}

	private static function find_existing( string $slug, $wp_post_id_field ): int {
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

	private static function set_meta( int $post_id, array $data ) {
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

	private static function to_int( $value ) {
		$value = trim( (string) $value );
		return ( '' !== $value && is_numeric( $value ) ) ? (int) $value : '';
	}

	private static function set_taxonomies( int $post_id, array $data ): int {
		$warnings = 0;

		self::set_single_term( $post_id, 'cos_region', $data['Region'] );
		self::set_single_term( $post_id, 'cos_building_type', $data['Building type'] );
		self::set_single_term( $post_id, 'cos_era', $data['Century'] );
		self::set_multi_terms( $post_id, 'cos_category', $data['Categories'] );
		self::set_multi_terms( $post_id, 'cos_activity', $data['Activities'] );
		self::set_multi_terms( $post_id, 'cos_feature', $data['Features'] );

		$style = trim( $data['Architectural style'] );
		if ( '' !== $style && preg_match( '#^https?://#i', $style ) ) {
			WP_CLI::warning( "Row '{$data['ID']}': skipped corrupted Architectural style value (looks like a URL, not a style name)." );
			$warnings++;
		} else {
			self::set_single_term( $post_id, 'cos_architectural_style', $style );
		}

		return $warnings;
	}

	private static function count_warnings( array $data ): int {
		$style = trim( $data['Architectural style'] );
		return ( '' !== $style && preg_match( '#^https?://#i', $style ) ) ? 1 : 0;
	}

	private static function set_single_term( int $post_id, string $taxonomy, $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return;
		}
		wp_set_object_terms( $post_id, $value, $taxonomy, false );
	}

	private static function set_multi_terms( int $post_id, string $taxonomy, $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return;
		}
		$terms = array_filter( array_map( 'trim', explode( '|', $value ) ) );
		if ( $terms ) {
			wp_set_object_terms( $post_id, array_values( $terms ), $taxonomy, false );
		}
	}

	private static function read_csv( string $file ): array {
		$rows   = array();
		$handle = fopen( $file, 'r' );
		while ( false !== ( $row = fgetcsv( $handle ) ) ) {
			$rows[] = $row;
		}
		fclose( $handle );
		return $rows;
	}

	private static function write_csv( string $file, array $rows ) {
		$handle = fopen( $file, 'w' );
		foreach ( $rows as $row ) {
			fputcsv( $handle, $row );
		}
		fclose( $handle );
	}
}
