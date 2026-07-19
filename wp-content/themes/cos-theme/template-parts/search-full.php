<div class="cos-search cos-search--full">
	<form class="cos-search__form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
		<input type="search" name="s" class="cos-search__input" placeholder="<?php esc_attr_e( 'Search destinations, categories, and more…', 'cos-theme' ); ?>" autocomplete="off">
		<button type="submit" class="button"><?php esc_html_e( 'Search', 'cos-theme' ); ?></button>
	</form>

	<div class="cos-search__results" hidden></div>

	<div class="cos-search__browse">
		<h2><?php esc_html_e( 'Browse by Category', 'cos-theme' ); ?></h2>
		<div class="term-grid">
			<?php
			$categories = get_terms( array( 'taxonomy' => 'cos_category', 'hide_empty' => true ) );
			foreach ( $categories as $term ) :
				?>
				<a class="term-tile" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
					<?php echo esc_html( $term->name ); ?>
					<div class="card__meta"><?php echo esc_html( $term->count ); ?> <?php esc_html_e( 'buildings', 'cos-theme' ); ?></div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</div>
