<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The English/Swedish data model: a `cos_lang` flag ('en'|'sv', unset = 'en')
 * plus a `cos_translation_id` pointer to the paired post/term in the other
 * language, on every translatable post type and taxonomy. Also provides the
 * "Duplicate as translation" admin tooling that pre-fills a linked draft
 * counterpart (content + taxonomy assignments remapped to their paired
 * terms) so translating is the only work left for a human.
 */
class COS_Language_Fields {

	const LANG_META_KEY   = 'cos_lang';
	const PAIR_META_KEY   = 'cos_translation_id';
	const DEFAULT_LANG    = 'en';
	const LANGUAGES       = array(
		'en' => 'English',
		'sv' => 'Swedish',
	);

	const POST_TYPES = array( 'cos_building', 'cos_listing', 'post', 'page', 'product' );

	public static function taxonomies() {
		return array_merge(
			array_keys( COS_Taxonomies::BUILDING_TAXONOMIES ),
			array( 'product_cat', 'category' )
		);
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_meta_fields' ) );
		add_action( 'init', array( __CLASS__, 'register_term_meta_fields' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ) );
		add_action( 'admin_action_cos_duplicate_translation', array( __CLASS__, 'handle_duplicate_post' ) );

		foreach ( self::POST_TYPES as $post_type ) {
			add_filter( "bulk_actions-edit-{$post_type}", array( __CLASS__, 'add_bulk_duplicate_action' ) );
			add_filter( "handle_bulk_actions-edit-{$post_type}", array( __CLASS__, 'handle_bulk_duplicate_action' ), 10, 3 );
			add_filter( "manage_{$post_type}_posts_columns", array( __CLASS__, 'add_language_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( __CLASS__, 'render_language_column' ), 10, 2 );
		}
		add_action( 'admin_notices', array( __CLASS__, 'render_bulk_duplicate_notice' ) );
		add_filter( 'get_terms_args', array( __CLASS__, 'force_show_empty_terms_in_admin' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_language_filter_dropdown' ) );
		add_filter( 'pre_get_posts', array( __CLASS__, 'apply_admin_language_filter' ) );

		foreach ( self::taxonomies() as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", array( __CLASS__, 'render_term_fields_add' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( __CLASS__, 'render_term_fields_edit' ) );
			add_filter( "manage_edit-{$taxonomy}_columns", array( __CLASS__, 'add_term_language_column' ) );
			add_filter( "manage_{$taxonomy}_custom_column", array( __CLASS__, 'render_term_language_column' ), 10, 3 );
		}
		add_action( 'created_term', array( __CLASS__, 'save_term_fields' ), 10, 3 );
		add_action( 'edited_term', array( __CLASS__, 'save_term_fields' ), 10, 3 );
		add_action( 'admin_action_cos_duplicate_term_translation', array( __CLASS__, 'handle_duplicate_term' ) );
	}

	public static function current_lang_label( $lang ) {
		return isset( self::LANGUAGES[ $lang ] ) ? self::LANGUAGES[ $lang ] : self::LANGUAGES[ self::DEFAULT_LANG ];
	}

	public static function other_lang( $lang ) {
		return 'sv' === $lang ? 'en' : 'sv';
	}

	/* ---------------------------------------------------------------------
	 * Post meta: registration, meta box, save, pairing, duplication.
	 * ------------------------------------------------------------------- */

	public static function register_post_meta_fields() {
		foreach ( self::POST_TYPES as $post_type ) {
			register_post_meta(
				$post_type,
				self::LANG_META_KEY,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
					'default'           => self::DEFAULT_LANG,
					'sanitize_callback' => array( __CLASS__, 'sanitize_lang' ),
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
			register_post_meta(
				$post_type,
				self::PAIR_META_KEY,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	public static function sanitize_lang( $value ) {
		return isset( self::LANGUAGES[ $value ] ) ? $value : self::DEFAULT_LANG;
	}

	public static function add_meta_box() {
		add_meta_box(
			'cos_language',
			__( 'Language', 'cos-core' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPES,
			'side',
			'default'
		);
	}

	public static function render_meta_box( $post ) {
		if ( ! in_array( $post->post_type, self::POST_TYPES, true ) ) {
			return;
		}

		wp_nonce_field( 'cos_language_save', 'cos_language_nonce' );

		$lang       = get_post_meta( $post->ID, self::LANG_META_KEY, true ) ?: self::DEFAULT_LANG;
		$partner_id = (int) get_post_meta( $post->ID, self::PAIR_META_KEY, true );
		$other_lang = self::other_lang( $lang );

		?>
		<p>
			<label for="cos_lang"><strong><?php esc_html_e( 'Language', 'cos-core' ); ?></strong></label><br />
			<select name="cos_lang" id="cos_lang">
				<?php foreach ( self::LANGUAGES as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $lang, $code ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>

		<?php if ( $partner_id && get_post( $partner_id ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'Translation', 'cos-core' ); ?></strong><br />
				<a href="<?php echo esc_url( get_edit_post_link( $partner_id ) ); ?>"><?php echo esc_html( get_the_title( $partner_id ) ); ?></a>
				(<?php echo esc_html( self::current_lang_label( get_post_meta( $partner_id, self::LANG_META_KEY, true ) ?: self::DEFAULT_LANG ) ); ?>)
			</p>
			<p>
				<label>
					<input type="checkbox" name="cos_unlink_translation" value="1" />
					<?php esc_html_e( 'Unlink translation', 'cos-core' ); ?>
				</label>
			</p>
		<?php else : ?>
			<?php
			$candidates = get_posts( array(
				'post_type'      => $post->post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'exclude'        => array( $post->ID ),
				'meta_query'     => array(
					array( 'key' => self::LANG_META_KEY, 'value' => $other_lang ),
				),
			) );
			?>
			<p>
				<label for="cos_translation_id"><strong><?php esc_html_e( 'Link existing translation', 'cos-core' ); ?></strong></label><br />
				<select name="cos_translation_id" id="cos_translation_id">
					<option value=""><?php esc_html_e( '— None —', 'cos-core' ); ?></option>
					<?php foreach ( $candidates as $candidate ) : ?>
						<option value="<?php echo esc_attr( $candidate->ID ); ?>"><?php echo esc_html( $candidate->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php if ( $post->ID ) : ?>
				<p>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?action=cos_duplicate_translation&post=' . $post->ID ), 'cos_duplicate_translation_' . $post->ID ) ); ?>">
						<?php
						printf(
							/* translators: %s: the other language's name */
							esc_html__( 'Duplicate as %s translation', 'cos-core' ),
							esc_html( self::current_lang_label( $other_lang ) )
						);
						?>
					</a>
				</p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['cos_language_nonce'] ) || ! wp_verify_nonce( $_POST['cos_language_nonce'], 'cos_language_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, self::POST_TYPES, true ) ) {
			return;
		}

		if ( isset( $_POST['cos_lang'] ) ) {
			update_post_meta( $post_id, self::LANG_META_KEY, self::sanitize_lang( wp_unslash( $_POST['cos_lang'] ) ) );
		}

		if ( ! empty( $_POST['cos_unlink_translation'] ) ) {
			self::set_post_translation_pair( $post_id, 0 );
		} elseif ( isset( $_POST['cos_translation_id'] ) && '' !== $_POST['cos_translation_id'] ) {
			self::set_post_translation_pair( $post_id, absint( $_POST['cos_translation_id'] ) );
		}
	}

	/**
	 * Keeps the pairing bidirectional and self-healing: replacing a pairing
	 * unlinks whatever either side used to point to, so nothing is left
	 * pointing at a post that no longer points back.
	 */
	public static function set_post_translation_pair( $post_id, $new_partner_id ) {
		$post_id        = (int) $post_id;
		$new_partner_id = (int) $new_partner_id;
		$old_partner_id = (int) get_post_meta( $post_id, self::PAIR_META_KEY, true );

		if ( $old_partner_id && $old_partner_id !== $new_partner_id ) {
			if ( (int) get_post_meta( $old_partner_id, self::PAIR_META_KEY, true ) === $post_id ) {
				delete_post_meta( $old_partner_id, self::PAIR_META_KEY );
			}
		}

		if ( ! $new_partner_id ) {
			delete_post_meta( $post_id, self::PAIR_META_KEY );
			return;
		}

		$new_partner_old = (int) get_post_meta( $new_partner_id, self::PAIR_META_KEY, true );
		if ( $new_partner_old && $new_partner_old !== $post_id ) {
			delete_post_meta( $new_partner_old, self::PAIR_META_KEY );
		}

		update_post_meta( $post_id, self::PAIR_META_KEY, $new_partner_id );
		update_post_meta( $new_partner_id, self::PAIR_META_KEY, $post_id );
	}

	public static function handle_duplicate_post() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'cos_duplicate_translation_' . $post_id );

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'cos-core' ) );
		}

		$new_id = self::duplicate_post_as_translation( $post_id );

		wp_safe_redirect( get_edit_post_link( $new_id, 'raw' ) );
		exit;
	}

	public static function add_bulk_duplicate_action( $actions ) {
		$actions['cos_bulk_duplicate_translation'] = __( 'Duplicate as translation', 'cos-core' );
		return $actions;
	}

	/**
	 * Bulk version of the per-post "Duplicate as translation" button.
	 * Posts that already have a paired translation are silently skipped
	 * (matching the single-post button, which hides itself in that case)
	 * rather than creating a second, orphaned duplicate.
	 */
	public static function handle_bulk_duplicate_action( $redirect_to, $action, $post_ids ) {
		if ( 'cos_bulk_duplicate_translation' !== $action ) {
			return $redirect_to;
		}

		$duplicated = 0;
		$skipped    = 0;

		foreach ( $post_ids as $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			if ( get_post_meta( $post_id, self::PAIR_META_KEY, true ) ) {
				$skipped++;
				continue;
			}
			self::duplicate_post_as_translation( $post_id );
			$duplicated++;
		}

		return add_query_arg( array(
			'cos_bulk_duplicated' => $duplicated,
			'cos_bulk_skipped'    => $skipped,
		), $redirect_to );
	}

	public static function render_bulk_duplicate_notice() {
		if ( ! isset( $_GET['cos_bulk_duplicated'] ) ) {
			return;
		}
		$duplicated = absint( $_GET['cos_bulk_duplicated'] );
		$skipped    = isset( $_GET['cos_bulk_skipped'] ) ? absint( $_GET['cos_bulk_skipped'] ) : 0;
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: 1: number duplicated, 2: number skipped (already had a translation) */
					esc_html__( 'Duplicated %1$d item(s) as translations. %2$d already had a linked translation and were skipped.', 'cos-core' ),
					(int) $duplicated,
					(int) $skipped
				);
				?>
			</p>
		</div>
		<?php
	}

	public static function add_language_column( $columns ) {
		$columns['cos_language'] = __( 'Language', 'cos-core' );
		return $columns;
	}

	/**
	 * Shows an EN/SV badge, plus either a link to the paired translation or
	 * a one-click "+ Duplicate" link (reusing the same handler as the
	 * per-post button) when nothing is linked yet — so the list screen
	 * doubles as an at-a-glance translation-coverage view.
	 */
	public static function render_language_column( $column, $post_id ) {
		if ( 'cos_language' !== $column ) {
			return;
		}

		$lang       = get_post_meta( $post_id, self::LANG_META_KEY, true ) ?: self::DEFAULT_LANG;
		$partner_id = (int) get_post_meta( $post_id, self::PAIR_META_KEY, true );
		$partner    = $partner_id ? get_post( $partner_id ) : null;

		$badge_color = 'sv' === $lang ? '#2271b1' : '#646970';
		printf(
			'<span style="display:inline-block;padding:2px 8px;border-radius:3px;background:%s;color:#fff;font-size:11px;font-weight:600;letter-spacing:0.5px;">%s</span>',
			esc_attr( $badge_color ),
			esc_html( strtoupper( $lang ) )
		);

		echo '<br />';

		if ( $partner ) {
			printf(
				'<a href="%s" style="font-size:12px;">↔ %s</a>',
				esc_url( get_edit_post_link( $partner_id ) ),
				esc_html( self::current_lang_label( get_post_meta( $partner_id, self::LANG_META_KEY, true ) ?: self::DEFAULT_LANG ) )
			);
		} else {
			printf(
				'<a href="%s" style="font-size:12px;">%s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin.php?action=cos_duplicate_translation&post=' . $post_id ), 'cos_duplicate_translation_' . $post_id ) ),
				esc_html__( '+ Duplicate', 'cos-core' )
			);
		}
	}

	public static function render_language_filter_dropdown( $post_type ) {
		if ( ! in_array( $post_type, self::POST_TYPES, true ) ) {
			return;
		}
		$current = isset( $_GET['cos_lang_filter'] ) ? sanitize_key( $_GET['cos_lang_filter'] ) : '';
		?>
		<select name="cos_lang_filter">
			<option value=""><?php esc_html_e( 'All languages', 'cos-core' ); ?></option>
			<?php foreach ( self::LANGUAGES as $code => $label ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current, $code ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Handles the language filter dropdown on admin list screens (front-end
	 * language filtering is handled separately by COS_Language_Routing).
	 * Deliberately not gated on is_main_query(): the admin list table
	 * (WP_Posts_List_Table) runs its own separate WP_Query instance rather
	 * than the global main query, so that check would always exclude it.
	 */
	public static function apply_admin_language_filter( $query ) {
		if ( ! is_admin() || empty( $_GET['cos_lang_filter'] ) ) {
			return;
		}
		$post_type = $query->get( 'post_type' );
		if ( ! in_array( $post_type, self::POST_TYPES, true ) ) {
			return;
		}

		$lang = sanitize_key( $_GET['cos_lang_filter'] );
		if ( ! array_key_exists( $lang, self::LANGUAGES ) ) {
			return;
		}

		$meta_query = (array) $query->get( 'meta_query' );
		if ( 'en' === $lang ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array( 'key' => self::LANG_META_KEY, 'compare' => 'NOT EXISTS' ),
				array( 'key' => self::LANG_META_KEY, 'value' => 'en' ),
			);
		} else {
			$meta_query[] = array( 'key' => self::LANG_META_KEY, 'value' => $lang );
		}
		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Deliberately does NOT label term names ANYWHERE admin-side — not in
	 * get_terms(), get_the_terms(), Quick Edit's tag field, or REST. Every
	 * one of those was tried at some point (see git history) and every one
	 * turned out to be a real data-corruption vector, not just a cosmetic
	 * risk: the moment a language label becomes part of a term's *name* in
	 * any UI a human can read from and then type/paste into ANY other
	 * name-matching input (a tag field, Quick Edit's own pre-filled tag
	 * list, a "most used" link), wp_set_object_terms()/wp_insert_term()
	 * silently create a new orphan term instead of matching the real one —
	 * confirmed in production for both the classic term-list label and the
	 * REST label, across three separate incidents. Quick Edit was the
	 * worst of these: it pre-fills the tag input with the labeled string,
	 * so simply opening Quick Edit and clicking "Update" without touching
	 * that field would silently replace a correct assignment with a fresh
	 * orphan.
	 *
	 * Disambiguation (needed for taxonomies like cos_region where an EN/SV
	 * pair can be spelled identically, e.g. "Skåne") is handled instead by
	 * add_term_language_column()/render_term_language_column() below — a
	 * genuinely separate table cell in the classic term-list screen that
	 * can never be selected/copied as part of the term's name.
	 */

	/**
	 * Most admin UI that lists terms (tag-suggest autocomplete, the "most
	 * used" cloud, Quick Edit) queries with hide_empty=true by default —
	 * which hides every Swedish term entirely until at least one Swedish
	 * post using it has been published, making it impossible to even
	 * assign that term to a new draft in the meantime. Forces every term
	 * to stay visible in the admin regardless of its published-post count.
	 */
	public static function force_show_empty_terms_in_admin( $args, $taxonomies ) {
		if ( ! is_admin() ) {
			return $args;
		}
		if ( ! array_intersect( (array) $taxonomies, self::taxonomies() ) ) {
			return $args;
		}
		$args['hide_empty'] = false;
		return $args;
	}

	public static function add_term_language_column( $columns ) {
		$columns['cos_language'] = __( 'Language', 'cos-core' );
		return $columns;
	}

	public static function render_term_language_column( $content, $column, $term_id ) {
		if ( 'cos_language' !== $column ) {
			return $content;
		}
		$lang        = get_term_meta( $term_id, self::LANG_META_KEY, true ) ?: self::DEFAULT_LANG;
		$badge_color = 'sv' === $lang ? '#2271b1' : '#646970';
		return sprintf(
			'<span style="display:inline-block;padding:2px 8px;border-radius:3px;background:%s;color:#fff;font-size:11px;font-weight:600;letter-spacing:0.5px;">%s</span>',
			esc_attr( $badge_color ),
			esc_html( strtoupper( $lang ) )
		);
	}

	public static function duplicate_post_as_translation( $post_id ) {
		$source = get_post( $post_id );
		$lang   = get_post_meta( $post_id, self::LANG_META_KEY, true ) ?: self::DEFAULT_LANG;
		$target_lang = self::other_lang( $lang );

		$new_id = wp_insert_post( array(
			'post_type'    => $source->post_type,
			'post_title'   => $source->post_title,
			'post_content' => $source->post_content,
			'post_excerpt' => $source->post_excerpt,
			'post_status'  => 'draft',
			'post_author'  => $source->post_author,
		), true );

		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}

		// Copy every existing meta field except the language pairing itself
		// (coordinates, years, URLs etc. are language-independent — the
		// translator only needs to change title/content/proper nouns).
		foreach ( get_post_meta( $post_id ) as $key => $values ) {
			if ( in_array( $key, array( self::LANG_META_KEY, self::PAIR_META_KEY ), true ) ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
			}
		}
		update_post_meta( $new_id, self::LANG_META_KEY, $target_lang );

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $new_id, $thumbnail_id );
		}

		// Re-tag every taxonomy the post type supports, remapping each term
		// to its paired translation where one exists, falling back to the
		// original term so nothing is silently dropped.
		foreach ( get_object_taxonomies( $source->post_type ) as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $terms ) || ! $terms ) {
				continue;
			}
			$remapped = array();
			foreach ( $terms as $term_id ) {
				$paired = (int) get_term_meta( $term_id, self::PAIR_META_KEY, true );
				$remapped[] = ( $paired && get_term( $paired, $taxonomy ) ) ? $paired : $term_id;
			}
			wp_set_object_terms( $new_id, $remapped, $taxonomy, false );
		}

		self::set_post_translation_pair( $post_id, $new_id );

		return $new_id;
	}

	/* ---------------------------------------------------------------------
	 * Term meta: registration, fields, save, pairing, duplication.
	 * ------------------------------------------------------------------- */

	public static function register_term_meta_fields() {
		foreach ( self::taxonomies() as $taxonomy ) {
			register_term_meta( $taxonomy, self::LANG_META_KEY, array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'default'           => self::DEFAULT_LANG,
				'sanitize_callback' => array( __CLASS__, 'sanitize_lang' ),
			) );
			register_term_meta( $taxonomy, self::PAIR_META_KEY, array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			) );
		}
	}

	public static function render_term_fields_add( $taxonomy ) {
		wp_nonce_field( 'cos_language_term_save', 'cos_language_term_nonce' );
		?>
		<div class="form-field">
			<label for="cos_lang"><?php esc_html_e( 'Language', 'cos-core' ); ?></label>
			<select name="cos_lang" id="cos_lang">
				<?php foreach ( self::LANGUAGES as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( self::DEFAULT_LANG, $code ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	public static function render_term_fields_edit( $term ) {
		wp_nonce_field( 'cos_language_term_save', 'cos_language_term_nonce' );

		$lang       = get_term_meta( $term->term_id, self::LANG_META_KEY, true ) ?: self::DEFAULT_LANG;
		$partner_id = (int) get_term_meta( $term->term_id, self::PAIR_META_KEY, true );
		$other_lang = self::other_lang( $lang );
		$taxonomy   = $term->taxonomy;

		?>
		<tr class="form-field">
			<th scope="row"><label for="cos_lang"><?php esc_html_e( 'Language', 'cos-core' ); ?></label></th>
			<td>
				<select name="cos_lang" id="cos_lang">
					<?php foreach ( self::LANGUAGES as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $lang, $code ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Translation', 'cos-core' ); ?></th>
			<td>
				<?php if ( $partner_id && get_term( $partner_id, $taxonomy ) ) : ?>
					<p>
						<a href="<?php echo esc_url( get_edit_term_link( $partner_id, $taxonomy ) ); ?>"><?php echo esc_html( get_term( $partner_id, $taxonomy )->name ); ?></a>
						(<?php echo esc_html( self::current_lang_label( get_term_meta( $partner_id, self::LANG_META_KEY, true ) ?: self::DEFAULT_LANG ) ); ?>)
					</p>
					<label>
						<input type="checkbox" name="cos_unlink_translation" value="1" />
						<?php esc_html_e( 'Unlink translation', 'cos-core' ); ?>
					</label>
				<?php else : ?>
					<?php
					$candidates = get_terms( array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
						'exclude'    => array( $term->term_id ),
						'meta_query' => array(
							array( 'key' => self::LANG_META_KEY, 'value' => $other_lang ),
						),
					) );
					?>
					<select name="cos_translation_id" id="cos_translation_id">
						<option value=""><?php esc_html_e( '— None —', 'cos-core' ); ?></option>
						<?php foreach ( $candidates as $candidate ) : ?>
							<option value="<?php echo esc_attr( $candidate->term_id ); ?>"><?php echo esc_html( $candidate->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<p>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?action=cos_duplicate_term_translation&term=' . $term->term_id . '&taxonomy=' . $taxonomy ), 'cos_duplicate_term_translation_' . $term->term_id ) ); ?>">
							<?php
							printf(
								/* translators: %s: the other language's name */
								esc_html__( 'Duplicate as %s translation', 'cos-core' ),
								esc_html( self::current_lang_label( $other_lang ) )
							);
							?>
						</a>
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	public static function save_term_fields( $term_id, $tt_id, $taxonomy ) {
		if ( ! in_array( $taxonomy, self::taxonomies(), true ) ) {
			return;
		}
		if ( ! isset( $_POST['cos_language_term_nonce'] ) || ! wp_verify_nonce( $_POST['cos_language_term_nonce'], 'cos_language_term_save' ) ) {
			return;
		}

		if ( isset( $_POST['cos_lang'] ) ) {
			update_term_meta( $term_id, self::LANG_META_KEY, self::sanitize_lang( wp_unslash( $_POST['cos_lang'] ) ) );
		}

		if ( ! empty( $_POST['cos_unlink_translation'] ) ) {
			self::set_term_translation_pair( $term_id, 0, $taxonomy );
		} elseif ( isset( $_POST['cos_translation_id'] ) && '' !== $_POST['cos_translation_id'] ) {
			self::set_term_translation_pair( $term_id, absint( $_POST['cos_translation_id'] ), $taxonomy );
		}
	}

	public static function set_term_translation_pair( $term_id, $new_partner_id, $taxonomy ) {
		$term_id        = (int) $term_id;
		$new_partner_id = (int) $new_partner_id;
		$old_partner_id = (int) get_term_meta( $term_id, self::PAIR_META_KEY, true );

		if ( $old_partner_id && $old_partner_id !== $new_partner_id ) {
			if ( (int) get_term_meta( $old_partner_id, self::PAIR_META_KEY, true ) === $term_id ) {
				delete_term_meta( $old_partner_id, self::PAIR_META_KEY );
			}
		}

		if ( ! $new_partner_id ) {
			delete_term_meta( $term_id, self::PAIR_META_KEY );
			return;
		}

		$new_partner_old = (int) get_term_meta( $new_partner_id, self::PAIR_META_KEY, true );
		if ( $new_partner_old && $new_partner_old !== $term_id ) {
			delete_term_meta( $new_partner_old, self::PAIR_META_KEY );
		}

		update_term_meta( $term_id, self::PAIR_META_KEY, $new_partner_id );
		update_term_meta( $new_partner_id, self::PAIR_META_KEY, $term_id );
	}

	public static function handle_duplicate_term() {
		$term_id  = isset( $_GET['term'] ) ? absint( $_GET['term'] ) : 0;
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		check_admin_referer( 'cos_duplicate_term_translation_' . $term_id );

		if ( ! $term_id || ! in_array( $taxonomy, self::taxonomies(), true ) || ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'cos-core' ) );
		}

		$new_term_id = self::duplicate_term_as_translation( $term_id, $taxonomy );

		wp_safe_redirect( get_edit_term_link( $new_term_id, $taxonomy ) );
		exit;
	}

	public static function duplicate_term_as_translation( $term_id, $taxonomy ) {
		$source = get_term( $term_id, $taxonomy );
		$lang   = get_term_meta( $term_id, self::LANG_META_KEY, true ) ?: self::DEFAULT_LANG;
		$target_lang = self::other_lang( $lang );

		// wp_insert_term() rejects a name that already exists in the
		// taxonomy — which is always true here, since we're duplicating.
		// The suffix is a placeholder for the translator to overwrite.
		$placeholder_name = sprintf( '%s (%s)', $source->name, strtoupper( $target_lang ) );

		// Preserve hierarchy: if the source term has a parent that's already
		// been paired with a translation, link the new term to that paired
		// parent. If the parent hasn't been duplicated yet, the new term is
		// created as top-level — duplicating parents before children avoids this.
		$new_parent = 0;
		if ( $source->parent ) {
			$paired_parent_id = (int) get_term_meta( $source->parent, self::PAIR_META_KEY, true );
			if ( $paired_parent_id && term_exists( $paired_parent_id, $taxonomy ) ) {
				$new_parent = $paired_parent_id;
			}
		}

		$result = wp_insert_term( $placeholder_name, $taxonomy, array(
			'description' => $source->description,
			'parent'      => $new_parent,
		) );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		$new_term_id = $result['term_id'];
		update_term_meta( $new_term_id, self::LANG_META_KEY, $target_lang );
		self::set_term_translation_pair( $term_id, $new_term_id, $taxonomy );

		return $new_term_id;
	}
}
