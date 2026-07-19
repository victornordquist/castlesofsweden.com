<?php
$cos_query   = get_search_query();
$is_sv       = 'sv' === COS_Language_Routing::current_lang();
$cos_results = COS_Search::run( $cos_query, array( 'limit' => 30 ) );

get_header();
?>

<div class="page-title-bar">
	<div class="container">
		<h1>
			<?php
			if ( $is_sv ) {
				/* translators: %s: search query */
				printf( esc_html__( 'Sökresultat för "%s"', 'cos-theme' ), esc_html( $cos_query ) );
			} else {
				/* translators: %s: search query */
				printf( esc_html__( 'Search results for "%s"', 'cos-theme' ), esc_html( $cos_query ) );
			}
			?>
		</h1>
	</div>
</div>

<div class="container section">
	<?php if ( empty( $cos_results ) ) : ?>
		<p>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Inga resultat hittades. Försök med ett annat sökord.', 'cos-theme' );
			} else {
				esc_html_e( 'No results found. Try a different search term.', 'cos-theme' );
			}
			?>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $cos_results['destinations'] ) ) : ?>
		<div class="search-group">
			<h2><?php echo esc_html( $is_sv ? 'Destinationer' : 'Destinations' ); ?></h2>
			<div class="card-grid">
				<?php foreach ( $cos_results['destinations'] as $destination ) : ?>
					<?php cos_building_card( $destination['id'] ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $cos_results['terms'] ) ) : ?>
		<div class="search-group">
			<h2><?php echo esc_html( $is_sv ? 'Kategorier & Landskap' : 'Categories & Regions' ); ?></h2>
			<div class="term-grid">
				<?php foreach ( $cos_results['terms'] as $term ) : ?>
					<a class="term-tile" href="<?php echo esc_url( $term['permalink'] ); ?>">
						<?php echo esc_html( $term['name'] ); ?>
						<div class="card__meta"><?php echo esc_html( $term['taxonomy'] ); ?></div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php foreach ( array(
		'articles' => $is_sv ? __( 'Artiklar', 'cos-theme' ) : __( 'Articles', 'cos-theme' ),
		'listings' => $is_sv ? __( 'Till salu', 'cos-theme' ) : __( 'For Sale', 'cos-theme' ),
		'products' => $is_sv ? __( 'Butik', 'cos-theme' ) : __( 'Shop', 'cos-theme' ),
	) as $cos_group_key => $cos_group_label ) : ?>
		<?php if ( ! empty( $cos_results[ $cos_group_key ] ) ) : ?>
			<div class="search-group">
				<h2><?php echo esc_html( $cos_group_label ); ?></h2>
				<div class="card-grid">
					<?php foreach ( $cos_results[ $cos_group_key ] as $item ) : ?>
						<?php cos_card( $item['permalink'], $item['title'], $item['thumbnail'] ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
</div>

<?php get_footer(); ?>
