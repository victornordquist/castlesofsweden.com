( function () {
	'use strict';

	var STORAGE_KEY = 'cosSavedBuildings';

	function getSavedIds() {
		try {
			var raw = JSON.parse( localStorage.getItem( STORAGE_KEY ) );
			return Array.isArray( raw ) ? raw.filter( function ( id ) { return Number.isInteger( id ); } ) : [];
		} catch ( e ) {
			return [];
		}
	}

	function setSavedIds( ids ) {
		localStorage.setItem( STORAGE_KEY, JSON.stringify( ids ) );
	}

	function isSaved( id ) {
		return getSavedIds().indexOf( id ) !== -1;
	}

	function toggleSaved( id ) {
		var ids   = getSavedIds();
		var index = ids.indexOf( id );
		if ( index === -1 ) {
			ids.push( id );
		} else {
			ids.splice( index, 1 );
		}
		setSavedIds( ids );
		return index === -1;
	}

	function decodeHtml( html ) {
		var textarea = document.createElement( 'textarea' );
		textarea.innerHTML = html;
		return textarea.value;
	}

	function updateNavBadge() {
		var badge = document.querySelector( '.nav-saved-icon__badge' );
		if ( ! badge ) {
			return;
		}
		var count = getSavedIds().length;
		badge.textContent = String( count );
		badge.hidden = count === 0;
	}

	updateNavBadge();

	document.addEventListener( 'DOMContentLoaded', function () {
		var button = document.querySelector( '.save-building-button' );
		if ( button ) {
			var id = parseInt( button.getAttribute( 'data-save-building-id' ), 10 );

			var render = function () {
				var saved = isSaved( id );
				button.textContent = saved ? button.getAttribute( 'data-label-saved' ) : button.getAttribute( 'data-label-save' );
				button.classList.toggle( 'is-saved', saved );
			};
			render();

			button.addEventListener( 'click', function () {
				toggleSaved( id );
				render();
				updateNavBadge();
			} );
		}

		var container = document.getElementById( 'cos-saved-places' );
		if ( container && window.cosSavedBuildingsData ) {
			renderSavedPlaces( container, window.cosSavedBuildingsData );
		}
	} );

	function renderSavedPlaces( container, data ) {
		var ids = getSavedIds();
		if ( ! ids.length ) {
			container.textContent = data.labels.empty;
			return;
		}

		// Deliberately no `_fields` restriction: WP's `_fields` filter is
		// applied after `_embed` resolves related data, so trimming fields
		// also strips `_links`/`_embedded` unless they're explicitly kept —
		// simplest to just take the full (small) response for this handful
		// of saved posts rather than juggle that interaction.
		var url = data.buildingsEndpoint + '?include=' + ids.join( ',' ) +
			'&orderby=include&per_page=100&_embed=wp:featuredmedia';

		fetch( url )
			.then( function ( response ) { return response.ok ? response.json() : []; } )
			.then( function ( buildings ) {
				var matching = buildings.filter( function ( building ) {
					return ( ( building.meta && building.meta.cos_lang ) || 'en' ) === data.lang;
				} );

				if ( ! matching.length ) {
					container.textContent = data.labels.empty;
					return;
				}

				var regionIds = [];
				matching.forEach( function ( building ) {
					( building.cos_region || [] ).forEach( function ( regionId ) {
						if ( regionIds.indexOf( regionId ) === -1 ) {
							regionIds.push( regionId );
						}
					} );
				} );

				if ( ! regionIds.length ) {
					renderCards( container, matching, {} );
					return;
				}

				fetch( data.regionsEndpoint + '?include=' + regionIds.join( ',' ) + '&_fields=id,name' )
					.then( function ( response ) { return response.ok ? response.json() : []; } )
					.then( function ( regions ) {
						var regionNames = {};
						regions.forEach( function ( region ) { regionNames[ region.id ] = decodeHtml( region.name ); } );
						renderCards( container, matching, regionNames );
					} )
					.catch( function () { renderCards( container, matching, {} ); } );
			} )
			.catch( function () { container.textContent = data.labels.empty; } );
	}

	function renderCards( container, buildings, regionNames ) {
		var grid = document.createElement( 'div' );
		grid.className = 'card-grid';

		buildings.forEach( function ( building ) {
			var media     = building._embedded && building._embedded[ 'wp:featuredmedia' ] && building._embedded[ 'wp:featuredmedia' ][ 0 ];
			var sizes     = media && media.media_details && media.media_details.sizes;
			var imageUrl  = ( sizes && sizes.medium && sizes.medium.source_url ) || ( media && media.source_url ) || '';
			var regionText = ( building.cos_region || [] )
				.map( function ( id ) { return regionNames[ id ]; } )
				.filter( Boolean )
				.join( ', ' );

			var card = document.createElement( 'a' );
			card.className = 'card';
			card.href = building.link;

			var imageDiv = document.createElement( 'div' );
			imageDiv.className = 'card__image';
			var titleText = decodeHtml( building.title.rendered );

			if ( imageUrl ) {
				var img = document.createElement( 'img' );
				img.src = imageUrl;
				img.alt = titleText;
				imageDiv.appendChild( img );
			}

			var body = document.createElement( 'div' );
			body.className = 'card__body';

			var title = document.createElement( 'h3' );
			title.className = 'card__title';
			title.textContent = titleText;
			body.appendChild( title );

			if ( regionText ) {
				var meta = document.createElement( 'p' );
				meta.className = 'card__meta';
				meta.textContent = regionText;
				body.appendChild( meta );
			}

			card.appendChild( imageDiv );
			card.appendChild( body );
			grid.appendChild( card );
		} );

		container.textContent = '';
		container.appendChild( grid );
	}
} )();
