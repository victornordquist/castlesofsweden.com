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

		$rows = COS_Building_Import_Helpers::read_csv_file( $file );
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
			COS_Building_Import_Helpers::write_csv_file( $file, $output_rows );
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

		$existing_id = COS_Building_Import_Helpers::find_existing( $slug, $data['WP Post ID'] ?? '' );

		if ( $dry_run ) {
			return array(
				'post_id'  => $existing_id ?: 0,
				'action'   => $existing_id ? 'updated' : 'inserted',
				'warnings' => COS_Building_Import_Helpers::count_warnings( $data ),
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

		COS_Building_Import_Helpers::set_meta( $post_id, $data );
		$warnings = COS_Building_Import_Helpers::set_taxonomies( $post_id, $data );

		if ( $warnings ) {
			WP_CLI::warning( "Row '{$data['ID']}': skipped corrupted Architectural style value (looks like a URL, not a style name)." );
		}

		return array(
			'post_id'  => $post_id,
			'action'   => $existing_id ? 'updated' : 'inserted',
			'warnings' => $warnings,
		);
	}
}
