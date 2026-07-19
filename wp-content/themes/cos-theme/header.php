<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="container">
		<div class="site-logo">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<button class="menu-toggle" aria-expanded="false" aria-label="<?php esc_attr_e( 'Menu', 'cos-theme' ); ?>">
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

		<nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'cos-theme' ); ?>">
			<ul>
				<?php foreach ( cos_primary_nav_links() as $label => $url ) : ?>
					<li>
						<a href="<?php echo esc_url( $url ); ?>" class="<?php echo ( 'Support us' === $label ) ? 'nav-cta' : ''; ?>"><?php echo esc_html( $label ); ?></a>
					</li>
				<?php endforeach; ?>
				<li>
					<a class="nav-search-icon" href="<?php echo esc_url( home_url( '/search/' ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'cos-theme' ); ?>" aria-haspopup="dialog" aria-expanded="false" data-search-trigger>
						<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
							<circle cx="8" cy="8" r="6.25" stroke="currentColor" stroke-width="1.5"/>
							<line x1="12.7" y1="12.7" x2="17" y2="17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
						</svg>
					</a>
				</li>
			</ul>
		</nav>
	</div>
</header>

<?php if ( ! is_page( 'search' ) ) : ?>
	<div class="search-overlay" id="search-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'cos-theme' ); ?>" hidden>
		<div class="search-overlay__panel">
			<button type="button" class="search-overlay__close" aria-label="<?php esc_attr_e( 'Close search', 'cos-theme' ); ?>" data-search-close>&times;</button>
			<div class="search-overlay__content">
				<div class="cos-search cos-search--full">
					<form class="cos-search__form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
						<input type="search" name="s" class="cos-search__input" placeholder="<?php esc_attr_e( 'Search the site…', 'cos-theme' ); ?>" autocomplete="off">
						<button type="submit" class="button"><?php esc_html_e( 'Search', 'cos-theme' ); ?></button>
					</form>
					<div class="cos-search__results" hidden></div>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<main id="main" class="site-main">
