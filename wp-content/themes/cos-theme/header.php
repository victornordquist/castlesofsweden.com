<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php $is_sv = 'sv' === COS_Language_Routing::current_lang(); ?>

<header class="site-header">
	<div class="container">
		<div class="site-logo">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( $is_sv ? '/sv/' : '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<button class="menu-toggle" aria-expanded="false" aria-label="<?php echo esc_attr( $is_sv ? 'Meny' : 'Menu' ); ?>">
			<svg class="menu-toggle__icon-open" width="22" height="16" viewBox="0 0 22 16" fill="none" aria-hidden="true">
				<line x1="0" y1="1" x2="22" y2="1" stroke="currentColor" stroke-width="2"/>
				<line x1="0" y1="8" x2="22" y2="8" stroke="currentColor" stroke-width="2"/>
				<line x1="0" y1="15" x2="22" y2="15" stroke="currentColor" stroke-width="2"/>
			</svg>
			<svg class="menu-toggle__icon-close" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
				<line x1="1" y1="1" x2="17" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				<line x1="17" y1="1" x2="1" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>

		<nav class="primary-nav" aria-label="<?php echo esc_attr( $is_sv ? 'Huvudmeny' : 'Primary' ); ?>">
			<ul>
				<?php foreach ( cos_primary_nav_links() as $item ) : ?>
					<li>
						<a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo ! empty( $item['cta'] ) ? 'nav-cta' : ''; ?>"><?php echo esc_html( $item['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
				<li><?php cos_render_language_switcher(); ?></li>
			</ul>
		</nav>

		<div class="site-header__actions">
			<a class="nav-search-icon" href="<?php echo esc_url( home_url( $is_sv ? '/sv/sok/' : '/search/' ) ); ?>" aria-label="<?php echo esc_attr( $is_sv ? 'Sök' : 'Search' ); ?>" aria-haspopup="dialog" aria-expanded="false" data-search-trigger>
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
					<circle cx="8" cy="8" r="6.25" stroke="currentColor" stroke-width="1.5"/>
					<line x1="12.7" y1="12.7" x2="17" y2="17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				</svg>
			</a>
			<a class="nav-saved-icon" href="<?php echo esc_url( home_url( $is_sv ? '/sv/sparade-platser/' : '/saved-places/' ) ); ?>" aria-label="<?php echo esc_attr( $is_sv ? 'Sparade platser' : 'Saved places' ); ?>">
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
					<path d="M9 15.5 2.6 9.2C0.9 7.5 0.9 4.8 2.6 3.1c1.7-1.7 4.4-1.7 6.1 0L9 3.4l0.3-0.3c1.7-1.7 4.4-1.7 6.1 0 1.7 1.7 1.7 4.4 0 6.1L9 15.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
				</svg>
				<span class="nav-saved-icon__badge" hidden>0</span>
			</a>
		</div>
	</div>
</header>

<?php if ( ! cos_is_page_any_lang( 'search' ) ) : ?>
	<div class="search-overlay" id="search-overlay" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $is_sv ? 'Sök' : 'Search' ); ?>" hidden>
		<div class="search-overlay__panel">
			<button type="button" class="search-overlay__close" aria-label="<?php echo esc_attr( $is_sv ? 'Stäng sök' : 'Close search' ); ?>" data-search-close>&times;</button>
			<div class="search-overlay__content">
				<div class="cos-search cos-search--full">
					<form class="cos-search__form" action="<?php echo esc_url( home_url( $is_sv ? '/sv/' : '/' ) ); ?>" method="get" role="search">
						<input type="search" name="s" class="cos-search__input" placeholder="<?php echo esc_attr( $is_sv ? 'Sök hela webbplatsen...' : 'Search the site…' ); ?>" autocomplete="off">
						<button type="submit" class="button"><?php echo esc_html( $is_sv ? 'Sök' : 'Search' ); ?></button>
					</form>
					<div class="cos-search__results" hidden></div>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<main id="main" class="site-main">
