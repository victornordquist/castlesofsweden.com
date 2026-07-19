<?php $is_sv = 'sv' === COS_Language_Routing::current_lang(); ?>
<div class="cos-search cos-search--full">
	<form class="cos-search__form" action="<?php echo esc_url( home_url( $is_sv ? '/sv/' : '/' ) ); ?>" method="get" role="search">
		<input type="search" name="s" class="cos-search__input" placeholder="<?php echo esc_attr( $is_sv ? 'Sök destinationer, kategorier med mera...' : 'Search destinations, categories, and more…' ); ?>" autocomplete="off">
		<button type="submit" class="button"><?php echo esc_html( $is_sv ? 'Sök' : 'Search' ); ?></button>
	</form>

	<div class="cos-search__results" hidden></div>

	<div class="cos-search__browse">
		<h2><?php echo esc_html( $is_sv ? 'Bläddra efter kategori' : 'Browse by Category' ); ?></h2>
		<div class="term-grid">
			<?php
			$categories = get_terms( array( 'taxonomy' => 'cos_category', 'hide_empty' => true ) );
			foreach ( $categories as $term ) :
				?>
				<a class="term-tile" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
					<?php echo esc_html( $term->name ); ?>
					<div class="card__meta"><?php echo esc_html( $term->count ); ?> <?php echo esc_html( $is_sv ? 'byggnader' : 'buildings' ); ?></div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</div>
