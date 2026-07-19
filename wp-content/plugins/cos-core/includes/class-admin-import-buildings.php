<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * wp-admin CSV importer for buildings — the live-site counterpart to
 * `wp cos import-buildings`. Deliberately create-only: existing buildings
 * are always skipped, never overwritten, since (unlike the CLI tool, which
 * only ever ran against local dev before a full database export) this runs
 * directly against live content. Upload → preview → confirm, so nothing is
 * written until the admin has seen exactly what will be created.
 */
class COS_Admin_Import_Buildings {

	const TRANSIENT_PREFIX = 'cos_import_';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
	}

	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=cos_building',
			__( 'Import Buildings', 'cos-core' ),
			__( 'Import CSV', 'cos-core' ),
			'manage_options',
			'cos-import-buildings',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cos-core' ) );
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Import Buildings', 'cos-core' ) . '</h1>';

		$step = isset( $_POST['cos_import_step'] ) ? sanitize_key( wp_unslash( $_POST['cos_import_step'] ) ) : '';

		if ( 'confirm' === $step && check_admin_referer( 'cos_import_confirm' ) ) {
			self::handle_confirm();
		} elseif ( 'preview' === $step && check_admin_referer( 'cos_import_preview' ) ) {
			self::handle_preview();
		} else {
			self::render_upload_form();
		}

		echo '</div>';
	}

	private static function render_upload_form( $error = '' ) {
		if ( $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}
		?>
		<p><?php esc_html_e( 'Upload a CSV of buildings to add. Only new buildings are created — anything already on the site is left untouched. You\'ll see a preview before anything is saved.', 'cos-core' ); ?></p>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'cos_import_preview' ); ?>
			<input type="hidden" name="cos_import_step" value="preview" />
			<input type="file" name="cos_import_csv" accept=".csv" required />
			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Preview Import', 'cos-core' ); ?></button>
			</p>
		</form>
		<?php
	}

	private static function handle_preview() {
		if ( empty( $_FILES['cos_import_csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['cos_import_csv']['tmp_name'] ) ) {
			self::render_upload_form( __( 'Please choose a CSV file to upload.', 'cos-core' ) );
			return;
		}

		$rows = COS_Building_Import_Helpers::read_csv_file( $_FILES['cos_import_csv']['tmp_name'] );
		$assoc_rows = COS_Building_Import_Helpers::to_assoc_rows( $rows );

		if ( is_wp_error( $assoc_rows ) ) {
			self::render_upload_form( $assoc_rows->get_error_message() );
			return;
		}

		$new_rows      = array();
		$skipped_rows  = array();
		$invalid_count = 0;

		foreach ( $assoc_rows as $data ) {
			$slug  = sanitize_title( trim( $data['ID'] ?? '' ) );
			$title = trim( $data['Name'] ?? '' ) !== '' ? trim( $data['Name'] ) : trim( $data['English name'] ?? '' );

			if ( '' === $slug || '' === $title ) {
				$invalid_count++;
				continue;
			}

			$existing_id = COS_Building_Import_Helpers::find_existing( $slug, $data['WP Post ID'] ?? '' );

			if ( $existing_id ) {
				$skipped_rows[] = $title;
			} else {
				$new_rows[] = $data;
			}
		}

		if ( empty( $new_rows ) ) {
			self::render_upload_form( __( 'Nothing to import — every row in that file already matches an existing building.', 'cos-core' ) );
			return;
		}

		$token = wp_generate_password( 20, false );
		set_transient( self::TRANSIENT_PREFIX . $token, $new_rows, 15 * MINUTE_IN_SECONDS );

		?>
		<p>
			<?php
			printf(
				/* translators: 1: number of new buildings, 2: number of skipped (already existing) rows */
				esc_html__( '%1$d new building(s) will be created. %2$d row(s) were skipped because a matching building already exists.', 'cos-core' ),
				count( $new_rows ),
				count( $skipped_rows )
			);
			?>
			<?php if ( $invalid_count ) : ?>
				<br /><?php printf( esc_html__( '%d row(s) were ignored (missing an ID or name).', 'cos-core' ), (int) $invalid_count ); ?>
			<?php endif; ?>
		</p>

		<h2><?php esc_html_e( 'New buildings to create', 'cos-core' ); ?></h2>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Name', 'cos-core' ); ?></th><th><?php esc_html_e( 'Region', 'cos-core' ); ?></th><th><?php esc_html_e( 'Building type', 'cos-core' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $new_rows as $data ) : ?>
					<tr>
						<td><?php echo esc_html( trim( $data['Name'] ) !== '' ? trim( $data['Name'] ) : trim( $data['English name'] ) ); ?></td>
						<td><?php echo esc_html( $data['Region'] ); ?></td>
						<td><?php echo esc_html( $data['Building type'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $skipped_rows ) : ?>
			<h2><?php esc_html_e( 'Skipped (already exist)', 'cos-core' ); ?></h2>
			<p><?php echo esc_html( implode( ', ', $skipped_rows ) ); ?></p>
		<?php endif; ?>

		<form method="post" style="margin-top:20px;">
			<?php wp_nonce_field( 'cos_import_confirm' ); ?>
			<input type="hidden" name="cos_import_step" value="confirm" />
			<input type="hidden" name="cos_import_token" value="<?php echo esc_attr( $token ); ?>" />
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Confirm & Create', 'cos-core' ); ?></button>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cos_building&page=cos-import-buildings' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'cos-core' ); ?></a>
		</form>
		<?php
	}

	private static function handle_confirm() {
		$token = isset( $_POST['cos_import_token'] ) ? sanitize_text_field( wp_unslash( $_POST['cos_import_token'] ) ) : '';
		$new_rows = $token ? get_transient( self::TRANSIENT_PREFIX . $token ) : false;

		if ( false === $new_rows ) {
			self::render_upload_form( __( 'This import session has expired. Please upload the file again.', 'cos-core' ) );
			return;
		}

		delete_transient( self::TRANSIENT_PREFIX . $token );

		$created = array();
		$skipped = 0;

		foreach ( $new_rows as $data ) {
			$slug  = sanitize_title( trim( $data['ID'] ?? '' ) );
			$title = trim( $data['Name'] ?? '' ) !== '' ? trim( $data['Name'] ) : trim( $data['English name'] ?? '' );

			// Re-check in case a matching building was created between preview and confirm.
			if ( COS_Building_Import_Helpers::find_existing( $slug, $data['WP Post ID'] ?? '' ) ) {
				$skipped++;
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => 'cos_building',
					'post_title'  => $title,
					'post_name'   => $slug,
					'post_status' => 'publish',
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			COS_Building_Import_Helpers::set_meta( $post_id, $data );
			COS_Building_Import_Helpers::set_taxonomies( $post_id, $data );

			$created[] = $post_id;
		}

		?>
		<div class="notice notice-success">
			<p>
				<?php
				printf(
					/* translators: %d: number of buildings created */
					esc_html__( 'Created %d new building(s).', 'cos-core' ),
					count( $created )
				);
				?>
				<?php if ( $skipped ) : ?>
					<?php printf( esc_html__( ' %d row(s) were skipped — a matching building had already been added since the preview.', 'cos-core' ), (int) $skipped ); ?>
				<?php endif; ?>
			</p>
		</div>
		<?php if ( $created ) : ?>
			<ul>
				<?php foreach ( $created as $post_id ) : ?>
					<li><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cos_building&page=cos-import-buildings' ) ); ?>" class="button"><?php esc_html_e( 'Import Another File', 'cos-core' ); ?></a></p>
		<?php
	}
}
