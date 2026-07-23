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

// Journal subnav dropdowns are also openable via :hover/:focus-within in CSS
// for mouse/keyboard users, but touch devices have no real hover state — a
// tap on the parent link would otherwise just navigate away before the
// dropdown ever gets a chance to show. This gives touch users an explicit
// toggle button to open/close it instead, without changing anything for the
// mouse-hover experience.
document.addEventListener( 'DOMContentLoaded', function () {
	var toggles = document.querySelectorAll( '.journal-subnav__toggle' );

	toggles.forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var li = button.closest( 'li' );
			if ( ! li ) {
				return;
			}
			var wasOpen = li.classList.contains( 'is-open' );

			toggles.forEach( function ( otherButton ) {
				var otherLi = otherButton.closest( 'li' );
				if ( otherLi && otherLi !== li ) {
					otherLi.classList.remove( 'is-open' );
					otherButton.setAttribute( 'aria-expanded', 'false' );
				}
			} );

			li.classList.toggle( 'is-open', ! wasOpen );
			button.setAttribute( 'aria-expanded', ! wasOpen ? 'true' : 'false' );
		} );
	} );

	if ( toggles.length ) {
		document.addEventListener( 'click', function ( event ) {
			if ( event.target.closest && event.target.closest( '.journal-subnav li.has-children' ) ) {
				return;
			}
			document.querySelectorAll( '.journal-subnav li.has-children.is-open' ).forEach( function ( li ) {
				li.classList.remove( 'is-open' );
				var button = li.querySelector( '.journal-subnav__toggle' );
				if ( button ) {
					button.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		} );
	}
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
