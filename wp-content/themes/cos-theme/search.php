<?php
$cos_query   = get_search_query();
$cos_results = COS_Search::run( $cos_query, array( 'limit' => 30 ) );

get_header();
?>

<div class="page-title-bar">
	<div class="container">
		<h1>
			<?php
			/* translators: %s: search query */
			printf( esc_html__( 'Search results for "%s"', 'cos-theme' ), esc_html( $cos_query ) );
			?>
		</h1>
	</div>
</div>

<div class="container section">
	<?php if ( empty( $cos_results ) ) : ?>
		<p><?php esc_html_e( 'No results found. Try a different search term.', 'cos-theme' ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $cos_results['destinations'] ) ) : ?>
		<div class="search-group">
			<h2><?php esc_html_e( 'Destinations', 'cos-theme' ); ?></h2>
			<div class="card-grid">
				<?php foreach ( $cos_results['destinations'] as $destination ) : ?>
					<?php cos_building_card( $destination['id'] ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $cos_results['terms'] ) ) : ?>
		<div class="search-group">
			<h2><?php esc_html_e( 'Categories & Regions', 'cos-theme' ); ?></h2>
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
		'articles' => __( 'Articles', 'cos-theme' ),
		'listings' => __( 'For Sale', 'cos-theme' ),
		'products' => __( 'Shop', 'cos-theme' ),
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
