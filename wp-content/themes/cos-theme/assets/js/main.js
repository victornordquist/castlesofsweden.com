document.addEventListener( 'DOMContentLoaded', function () {
	var toggle = document.querySelector( '.menu-toggle' );
	var nav = document.querySelector( '.primary-nav' );

	if ( ! toggle || ! nav ) {
		return;
	}

	toggle.addEventListener( 'click', function () {
		var isOpen = nav.classList.toggle( 'is-open' );
		toggle.classList.toggle( 'is-active', isOpen );
		toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
	} );
} );

// Shrink the sticky header's padding once the page has scrolled past it.
document.addEventListener( 'DOMContentLoaded', function () {
	var header = document.querySelector( '.site-header' );
	if ( ! header ) {
		return;
	}

	var ticking = false;

	function updateHeader() {
		header.classList.toggle( 'site-header--scrolled', window.scrollY > 40 );
		ticking = false;
	}

	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			window.requestAnimationFrame( updateHeader );
			ticking = true;
		}
	} );

	updateHeader();
} );

// Drop the ?cos_subscribe=... status param from the URL bar once its message
// has rendered, so refreshing or bookmarking the page doesn't repeat it.
document.addEventListener( 'DOMContentLoaded', function () {
	if ( window.location.search.indexOf( 'cos_subscribe=' ) !== -1 && window.history.replaceState ) {
		var url = new URL( window.location.href );
		url.searchParams.delete( 'cos_subscribe' );
		window.history.replaceState( {}, '', url );
	}
} );
