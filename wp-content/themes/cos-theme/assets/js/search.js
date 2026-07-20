document.addEventListener( 'DOMContentLoaded', function () {
	var blocks = document.querySelectorAll( '.cos-search' );
	if ( ! blocks.length || typeof cosSearchData === 'undefined' ) {
		return;
	}

	blocks.forEach( initBlock );
	initSearchOverlay();

	function initSearchOverlay() {
		var trigger = document.querySelector( '[data-search-trigger]' );
		var overlay = document.getElementById( 'search-overlay' );

		if ( ! trigger || ! overlay ) {
			return;
		}

		trigger.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			openOverlay();
		} );

		overlay.querySelectorAll( '[data-search-close]' ).forEach( function ( el ) {
			el.addEventListener( 'click', closeOverlay );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( overlay.hidden ) {
				return;
			}
			if ( 'Escape' === e.key ) {
				closeOverlay();
			} else if ( 'Tab' === e.key ) {
				trapFocus( e );
			}
		} );

		function openOverlay() {
			overlay.hidden = false;
			document.body.classList.add( 'search-overlay-open' );
			trigger.setAttribute( 'aria-expanded', 'true' );
			var input = overlay.querySelector( '.cos-search__input' );
			if ( input ) {
				input.focus();
			}
		}

		function closeOverlay() {
			overlay.hidden = true;
			document.body.classList.remove( 'search-overlay-open' );
			trigger.setAttribute( 'aria-expanded', 'false' );
			trigger.focus();
		}

		function trapFocus( e ) {
			var focusable = overlay.querySelectorAll( 'a[href], button, input' );
			if ( ! focusable.length ) {
				return;
			}
			var first = focusable[ 0 ];
			var last = focusable[ focusable.length - 1 ];

			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}
	}

	function escapeHtml( value ) {
		var div = document.createElement( 'div' );
		div.textContent = value == null ? '' : String( value );
		return div.innerHTML;
	}

	function initBlock( block ) {
		var input = block.querySelector( '.cos-search__input' );
		var resultsEl = block.querySelector( '.cos-search__results' );
		var browseEl = block.querySelector( '.cos-search__browse' );
		var destinationsOnly = block.classList.contains( 'cos-search--destinations' );

		if ( ! input || ! resultsEl ) {
			return;
		}

		var debounceTimer;

		input.addEventListener( 'input', function () {
			clearTimeout( debounceTimer );
			var query = input.value.trim();

			if ( ! query ) {
				resultsEl.hidden = true;
				resultsEl.innerHTML = '';
				if ( browseEl ) {
					browseEl.hidden = false;
				}
				return;
			}

			debounceTimer = setTimeout( function () {
				fetchResults( query );
			}, 250 );
		} );

		function fetchResults( query ) {
			var url = cosSearchData.endpoint + '&q=' + encodeURIComponent( query );
			if ( destinationsOnly ) {
				url += '&types=destinations';
			}

			fetch( url )
				.then( function ( response ) { return response.json(); } )
				.then( function ( data ) { render( data, query ); } );
		}

		function render( data, query ) {
			if ( browseEl ) {
				browseEl.hidden = true;
			}
			resultsEl.hidden = false;

			var groups = [
				[ 'destinations', cosSearchData.labels.destinations ],
				[ 'terms', cosSearchData.labels.terms ],
				[ 'articles', cosSearchData.labels.articles ],
				[ 'listings', cosSearchData.labels.listings ],
				[ 'products', cosSearchData.labels.products ]
			];

			var html = '';

			groups.forEach( function ( group ) {
				var key = group[ 0 ];
				var label = group[ 1 ];
				var items = data[ key ];
				if ( ! items || ! items.length ) {
					return;
				}
				html += '<div class="cos-search__group"><h3>' + escapeHtml( label ) + '</h3><ul class="cos-search__list">';
				items.forEach( function ( item ) { html += renderItem( key, item ); } );
				html += '</ul></div>';
			} );

			if ( ! html ) {
				resultsEl.innerHTML = '<p class="cos-search__empty">' + escapeHtml( cosSearchData.labels.noResults ) + '</p>';
				return;
			}

			html += '<p class="cos-search__view-all"><a href="' + cosSearchData.searchPageUrl + encodeURIComponent( query ) + '">' +
				escapeHtml( cosSearchData.labels.viewAll.replace( '%s', query ) ) + '</a></p>';

			resultsEl.innerHTML = html;
		}

		function renderItem( groupKey, item ) {
			var thumb = item.thumbnail ? '<img src="' + escapeHtml( item.thumbnail ) + '" alt="" class="cos-search__thumb">' : '';
			var permalink = escapeHtml( item.permalink );

			if ( 'terms' === groupKey ) {
				return '<li><a href="' + permalink + '">' + escapeHtml( item.name ) +
					' <span class="cos-search__meta">' + escapeHtml( item.taxonomy ) + '</span></a></li>';
			}

			if ( 'destinations' === groupKey ) {
				var meta = item.region ? escapeHtml( item.region ) : '';
				return '<li><a href="' + permalink + '">' + thumb + '<span>' + escapeHtml( item.title ) +
					'<span class="cos-search__meta">' + meta + '</span></span></a></li>';
			}

			return '<li><a href="' + permalink + '">' + thumb + '<span>' + escapeHtml( item.title ) + '</span></a></li>';
		}
	}
} );
