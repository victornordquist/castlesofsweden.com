( function () {
	'use strict';

	var STORAGE_KEY  = 'cosSavedBuildings';
	var MAX_COMPARE  = 3;

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
			var id    = parseInt( button.getAttribute( 'data-save-building-id' ), 10 );
			var label = button.querySelector( '.save-building-button__label' );

			var render = function () {
				var saved = isSaved( id );
				var text  = saved ? button.getAttribute( 'data-label-saved' ) : button.getAttribute( 'data-label-save' );
				if ( label ) {
					label.textContent = text;
				} else {
					button.textContent = text;
				}
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
			var tripParam = new URLSearchParams( window.location.search ).get( 'trip' );
			var sharedIds = tripParam
				? tripParam.split( ',' ).map( function ( s ) { return parseInt( s, 10 ); } ).filter( function ( id ) { return Number.isInteger( id ) && id > 0; } )
				: [];

			if ( sharedIds.length >= 2 ) {
				renderSharedTrip( container, sharedIds, window.cosSavedBuildingsData );
			} else {
				renderSavedPlaces( container, window.cosSavedBuildingsData );
			}
		}
	} );

	function fetchTermNames( matching, data ) {
		var taxonomyConfigs = [
			{ field: 'cos_region', endpoint: data.regionsEndpoint, key: 'region' },
			{ field: 'cos_building_type', endpoint: data.buildingTypesEndpoint, key: 'buildingType' },
			{ field: 'cos_architectural_style', endpoint: data.stylesEndpoint, key: 'style' },
			{ field: 'cos_era', endpoint: data.erasEndpoint, key: 'era' }
		];

		var lookups = taxonomyConfigs.map( function ( config ) {
			var termIds = [];
			matching.forEach( function ( building ) {
				( building[ config.field ] || [] ).forEach( function ( termId ) {
					if ( termIds.indexOf( termId ) === -1 ) {
						termIds.push( termId );
					}
				} );
			} );

			if ( ! termIds.length || ! config.endpoint ) {
				return Promise.resolve( {} );
			}

			return fetch( config.endpoint + '?include=' + termIds.join( ',' ) + '&_fields=id,name' )
				.then( function ( response ) { return response.ok ? response.json() : []; } )
				.then( function ( terms ) {
					var names = {};
					terms.forEach( function ( term ) { names[ term.id ] = decodeHtml( term.name ); } );
					return names;
				} )
				.catch( function () { return {}; } );
		} );

		return Promise.all( lookups ).then( function ( results ) {
			return {
				region: results[ 0 ],
				buildingType: results[ 1 ],
				style: results[ 2 ],
				era: results[ 3 ]
			};
		} );
	}

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
		// of saved posts rather than juggle that interaction. This also
		// means every registered building meta field (admission, opening
		// hours, etc.) is already present under `meta.*` for the compare view.
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

				fetchTermNames( matching, data ).then( function ( names ) {
					renderSavedPlacesUI( container, matching, names, data.labels );
				} );
			} )
			.catch( function () { container.textContent = data.labels.empty; } );
	}

	function renderSharedTrip( container, ids, data ) {
		var url = data.buildingsEndpoint + '?include=' + ids.join( ',' ) +
			'&orderby=include&per_page=100&_embed=wp:featuredmedia';

		fetch( url )
			.then( function ( response ) { return response.ok ? response.json() : []; } )
			.then( function ( buildings ) {
				var matching = buildings.filter( function ( building ) {
					return ( ( building.meta && building.meta.cos_lang ) || 'en' ) === data.lang;
				} );

				if ( matching.length < 2 ) {
					container.textContent = data.labels.tripSharedEmpty;
					return;
				}

				fetchTermNames( matching, data ).then( function ( names ) {
					var buildingsById = {};
					matching.forEach( function ( building ) {
						buildingsById[ building.id ] = buildingCardData( building, names );
					} );

					var orderedIds = ids.filter( function ( id ) { return buildingsById[ id ]; } );

					container.textContent = '';

					var tripSection = document.createElement( 'div' );
					tripSection.className = 'trip-planner';
					container.appendChild( tripSection );

					renderTripPlanner( tripSection, orderedIds, buildingsById, data.labels, {
						onReorder: null,
						readOnly: true,
						showSaveAll: true
					} );
				} );
			} )
			.catch( function () { container.textContent = data.labels.tripSharedEmpty; } );
	}

	function termNames( ids, names ) {
		return ( ids || [] )
			.map( function ( id ) { return names[ id ]; } )
			.filter( Boolean )
			.join( ', ' );
	}

	function buildingCardData( building, names ) {
		var media    = building._embedded && building._embedded[ 'wp:featuredmedia' ] && building._embedded[ 'wp:featuredmedia' ][ 0 ];
		var sizes    = media && media.media_details && media.media_details.sizes;
		var imageUrl = ( sizes && sizes.medium && sizes.medium.source_url ) || ( media && media.source_url ) || '';
		var postMeta = building.meta || {};

		return {
			title: decodeHtml( building.title.rendered ),
			link: building.link,
			image: imageUrl,
			// A registered `number`-type meta field with no value set comes
			// back from the REST API as 0, not '' or null — and (0,0) is
			// never a real Swedish location, so treating it as "missing" is
			// correct for this dataset, unlike a bare null/empty-string check.
			lat: postMeta.cos_lat ? parseFloat( postMeta.cos_lat ) : null,
			lng: postMeta.cos_lng ? parseFloat( postMeta.cos_lng ) : null,
			region: termNames( building.cos_region, names.region ),
			buildingType: termNames( building.cos_building_type, names.buildingType ),
			style: termNames( building.cos_architectural_style, names.style ),
			era: termNames( building.cos_era, names.era ),
			yearBuilt: postMeta.cos_year_built || '',
			admission: postMeta.cos_admission || '',
			openingHours: postMeta.cos_opening_hours || '',
			parking: postMeta.cos_parking || '',
			accessibility: postMeta.cos_accessibility || '',
			guidedTours: postMeta.cos_guided_tours || ''
		};
	}

	function buildSaveCard( buildingId, cardData, labels ) {
		var wrapper = document.createElement( 'div' );
		wrapper.className = 'save-card';
		wrapper.setAttribute( 'data-save-card-id', buildingId );

		var checkboxLabel = document.createElement( 'label' );
		checkboxLabel.className = 'save-card__checkbox';

		var checkbox = document.createElement( 'input' );
		checkbox.type = 'checkbox';
		checkbox.setAttribute( 'data-compare-id', buildingId );
		checkbox.setAttribute( 'aria-label', cardData.title );

		checkboxLabel.appendChild( checkbox );
		checkboxLabel.appendChild( document.createElement( 'span' ) );

		var removeButton = document.createElement( 'button' );
		removeButton.type = 'button';
		removeButton.className = 'save-card__remove';
		removeButton.setAttribute( 'data-remove-id', buildingId );
		removeButton.setAttribute( 'aria-label', labels.removeItem );
		removeButton.innerHTML = '&times;';

		var card = document.createElement( 'a' );
		card.className = 'card';
		card.href = cardData.link;

		var imageDiv = document.createElement( 'div' );
		imageDiv.className = 'card__image';

		if ( cardData.image ) {
			var img = document.createElement( 'img' );
			img.src = cardData.image;
			img.alt = cardData.title;
			imageDiv.appendChild( img );
		}

		var body = document.createElement( 'div' );
		body.className = 'card__body';

		var title = document.createElement( 'h3' );
		title.className = 'card__title';
		title.textContent = cardData.title;
		body.appendChild( title );

		if ( cardData.region ) {
			var meta = document.createElement( 'p' );
			meta.className = 'card__meta';
			meta.textContent = cardData.region;
			body.appendChild( meta );
		}

		card.appendChild( imageDiv );
		card.appendChild( body );

		wrapper.appendChild( checkboxLabel );
		wrapper.appendChild( removeButton );
		wrapper.appendChild( card );

		return wrapper;
	}

	function renderSavedPlacesUI( container, buildings, names, labels ) {
		var MAX_COMPARE   = 3;
		var selection     = [];
		var buildingsById = {};

		var toolbar = document.createElement( 'div' );
		toolbar.className = 'saved-places__toolbar';

		var countLabel = document.createElement( 'span' );
		countLabel.className = 'saved-places__count';

		var clearAllButton = document.createElement( 'button' );
		clearAllButton.type = 'button';
		clearAllButton.className = 'saved-places__clear-all';
		clearAllButton.textContent = labels.clearAll;

		toolbar.appendChild( countLabel );
		toolbar.appendChild( clearAllButton );

		var grid = document.createElement( 'div' );
		grid.className = 'card-grid';

		buildings.forEach( function ( building ) {
			var cardData = buildingCardData( building, names );
			buildingsById[ building.id ] = cardData;
			grid.appendChild( buildSaveCard( building.id, cardData, labels ) );
		} );

		var bar = document.createElement( 'div' );
		bar.className = 'compare-bar';
		bar.hidden = true;

		var status = document.createElement( 'span' );
		status.className = 'compare-bar__status';

		var barActions = document.createElement( 'div' );
		barActions.className = 'compare-bar__actions';

		var clearCompareButton = document.createElement( 'button' );
		clearCompareButton.type = 'button';
		clearCompareButton.className = 'compare-bar__clear';
		clearCompareButton.textContent = labels.clearSelection;

		var compareButton = document.createElement( 'button' );
		compareButton.type = 'button';
		compareButton.className = 'button compare-bar__button';
		compareButton.textContent = labels.compareButton;
		compareButton.disabled = true;

		barActions.appendChild( clearCompareButton );
		barActions.appendChild( compareButton );
		bar.appendChild( status );
		bar.appendChild( barActions );

		var section = document.createElement( 'div' );
		section.className = 'compare-section';
		section.hidden = true;

		var tripSection = document.createElement( 'div' );
		tripSection.className = 'trip-planner';

		container.textContent = '';
		container.appendChild( toolbar );
		container.appendChild( grid );
		container.appendChild( bar );
		container.appendChild( section );
		container.appendChild( tripSection );

		function handleReorder( newIds ) {
			setSavedIds( newIds );
			renderTripPlanner( tripSection, newIds, buildingsById, labels, { onReorder: handleReorder } );
		}

		function updateToolbar() {
			countLabel.textContent = labels.savedCount.replace( '%d', grid.querySelectorAll( '.save-card' ).length );
		}

		function updateCompareBar() {
			if ( ! selection.length ) {
				bar.hidden = true;
			} else {
				bar.hidden = false;
				status.textContent = selection.length < 2 ? labels.compareHint : labels.selectedCount.replace( '%d', selection.length );
				compareButton.disabled = selection.length < 2;
			}

			grid.querySelectorAll( '.save-card__checkbox input' ).forEach( function ( input ) {
				var id = parseInt( input.getAttribute( 'data-compare-id' ), 10 );
				if ( selection.indexOf( id ) === -1 ) {
					input.disabled = selection.length >= MAX_COMPARE;
				}
			} );
		}

		function removeCard( id ) {
			toggleSaved( id );
			updateNavBadge();

			var index = selection.indexOf( id );
			if ( index !== -1 ) {
				selection.splice( index, 1 );
			}
			delete buildingsById[ id ];

			var cardEl = grid.querySelector( '[data-save-card-id="' + id + '"]' );
			if ( cardEl ) {
				cardEl.remove();
			}

			if ( ! grid.querySelectorAll( '.save-card' ).length ) {
				container.textContent = labels.empty;
				return;
			}

			updateToolbar();
			updateCompareBar();
			renderTripPlanner( tripSection, getSavedIds(), buildingsById, labels, { onReorder: handleReorder } );
		}

		container.addEventListener( 'click', function ( event ) {
			var removeTarget = event.target.closest && event.target.closest( '.save-card__remove' );
			if ( removeTarget ) {
				removeCard( parseInt( removeTarget.getAttribute( 'data-remove-id' ), 10 ) );
				return;
			}

			if ( event.target === clearAllButton ) {
				if ( window.confirm( labels.clearAllConfirm ) ) {
					setSavedIds( [] );
					updateNavBadge();
					container.textContent = labels.empty;
				}
				return;
			}

			if ( event.target === clearCompareButton ) {
				selection = [];
				grid.querySelectorAll( '.save-card__checkbox input' ).forEach( function ( input ) {
					input.checked = false;
					input.disabled = false;
				} );
				section.hidden = true;
				section.textContent = '';
				updateCompareBar();
				return;
			}

			if ( event.target === compareButton ) {
				renderComparisonTable( section, selection, buildingsById, labels );
				section.hidden = false;
				section.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			}
		} );

		container.addEventListener( 'change', function ( event ) {
			var input = event.target;
			if ( ! input.matches || ! input.matches( '.save-card__checkbox input[data-compare-id]' ) ) {
				return;
			}

			var id = parseInt( input.getAttribute( 'data-compare-id' ), 10 );

			if ( input.checked ) {
				if ( selection.length >= MAX_COMPARE ) {
					input.checked = false;
					bar.hidden = false;
					status.textContent = labels.compareMax;
					return;
				}
				selection.push( id );
			} else {
				var index = selection.indexOf( id );
				if ( index !== -1 ) {
					selection.splice( index, 1 );
				}
			}

			updateCompareBar();
		} );

		updateToolbar();
		renderTripPlanner( tripSection, getSavedIds(), buildingsById, labels, { onReorder: handleReorder } );
	}

	function haversineKm( lat1, lng1, lat2, lng2 ) {
		var R    = 6371;
		var dLat = ( lat2 - lat1 ) * Math.PI / 180;
		var dLng = ( lng2 - lng1 ) * Math.PI / 180;
		var a    = Math.sin( dLat / 2 ) * Math.sin( dLat / 2 ) +
			Math.cos( lat1 * Math.PI / 180 ) * Math.cos( lat2 * Math.PI / 180 ) *
			Math.sin( dLng / 2 ) * Math.sin( dLng / 2 );
		return R * 2 * Math.atan2( Math.sqrt( a ), Math.sqrt( 1 - a ) );
	}

	function formatKm( km ) {
		return Math.round( km ) + ' km';
	}

	function photoMarkerIcon( thumbnailUrl ) {
		var style = thumbnailUrl ? "background-image:url('" + thumbnailUrl.replace( /'/g, '%27' ) + "');" : '';
		return L.divIcon( {
			className: 'cos-map-marker',
			html: '<span class="cos-map-marker__inner" style="' + style + '"></span>',
			iconSize: [ 40, 40 ],
			iconAnchor: [ 20, 20 ],
			popupAnchor: [ 0, -20 ]
		} );
	}

	function renderTripMap( mapDiv, stops ) {
		if ( typeof L === 'undefined' ) {
			mapDiv.hidden = true;
			return null;
		}

		var validStops = stops.filter( function ( stop ) { return stop.data.lat != null && stop.data.lng != null; } );
		if ( validStops.length < 2 ) {
			mapDiv.hidden = true;
			return null;
		}
		mapDiv.hidden = false;

		var map = L.map( mapDiv, { scrollWheelZoom: false } ).setView( [ validStops[ 0 ].data.lat, validStops[ 0 ].data.lng ], 6 );

		L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 18,
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
		} ).addTo( map );

		var latlngs = validStops.map( function ( stop ) { return [ stop.data.lat, stop.data.lng ]; } );

		validStops.forEach( function ( stop, index ) {
			L.marker( latlngs[ index ], { icon: photoMarkerIcon( stop.data.image ) } )
				.bindPopup( '<strong>' + ( index + 1 ) + '. ' + stop.data.title + '</strong>' )
				.addTo( map );
		} );

		// Hardcoded --color-accent-dark: Leaflet passes this straight into an
		// SVG stroke attribute, not a stylesheet, so a CSS custom property
		// isn't guaranteed to resolve the same way — a literal value is safer.
		// Dashed = straight-line estimate, shown immediately; swapped for a
		// solid line once the real driving route resolves (updateTripMapRoute).
		var line = L.polyline( latlngs, { color: '#a87e42', weight: 3, dashArray: '6,8' } ).addTo( map );

		map.fitBounds( latlngs, { padding: [ 30, 30 ], maxZoom: 12 } );

		return { map: map, line: line };
	}

	function updateTripMapRoute( mapState, routeLatLngs ) {
		if ( ! mapState ) {
			return;
		}
		mapState.line.remove();
		mapState.line = L.polyline( routeLatLngs, { color: '#a87e42', weight: 4 } ).addTo( mapState.map );
		mapState.map.fitBounds( routeLatLngs, { padding: [ 30, 30 ], maxZoom: 12 } );
	}

	/**
	 * Real driving distance/route via OSRM's public demo API — no key
	 * needed, but it's explicitly a demo server (not an SLA'd production
	 * service), so any failure here just means the straight-line estimate
	 * already on screen stays as-is rather than being replaced.
	 */
	function fetchDrivingRoute( stops ) {
		var coords = stops.map( function ( stop ) { return stop.data.lng + ',' + stop.data.lat; } ).join( ';' );
		var url = 'https://router.project-osrm.org/route/v1/driving/' + coords + '?overview=full&geometries=geojson';

		return fetch( url )
			.then( function ( response ) { return response.ok ? response.json() : null; } )
			.then( function ( data ) {
				if ( ! data || 'Ok' !== data.code || ! data.routes || ! data.routes.length ) {
					return null;
				}
				var route = data.routes[ 0 ];
				return {
					legKm: route.legs.map( function ( leg ) { return leg.distance / 1000; } ),
					totalKm: route.distance / 1000,
					// OSRM returns [lng,lat] pairs; Leaflet expects [lat,lng].
					latlngs: route.geometry.coordinates.map( function ( pair ) { return [ pair[ 1 ], pair[ 0 ] ]; } )
				};
			} )
			.catch( function () { return null; } );
	}

	function renderTripPlanner( container, ids, buildingsById, labels, options ) {
		options = options || {};
		container.textContent = '';

		var stops = ids
			.map( function ( id ) { return { id: id, data: buildingsById[ id ] }; } )
			.filter( function ( stop ) { return stop.data; } );

		if ( stops.length < 2 ) {
			container.hidden = true;
			return;
		}
		container.hidden = false;

		var heading = document.createElement( 'h2' );
		heading.className = 'trip-planner__heading';
		heading.textContent = labels.tripHeading;
		container.appendChild( heading );

		if ( options.readOnly ) {
			var notice = document.createElement( 'p' );
			notice.className = 'trip-planner__shared-notice';
			notice.textContent = labels.tripSharedNotice;
			container.appendChild( notice );
		}

		var mapDiv = document.createElement( 'div' );
		mapDiv.id = 'cos-trip-map';
		mapDiv.className = 'trip-planner__map';
		container.appendChild( mapDiv );

		var list = document.createElement( 'ol' );
		list.className = 'trip-planner__list';
		var legElements = [];

		stops.forEach( function ( stop, index ) {
			var li = document.createElement( 'li' );
			li.className = 'trip-planner__stop';
			li.setAttribute( 'data-trip-stop-id', stop.id );

			var titleLink = document.createElement( 'a' );
			titleLink.className = 'trip-planner__stop-title';
			titleLink.href = stop.data.link;
			titleLink.textContent = ( index + 1 ) + '. ' + stop.data.title;
			li.appendChild( titleLink );

			if ( ! options.readOnly ) {
				var upBtn = document.createElement( 'button' );
				upBtn.type = 'button';
				upBtn.className = 'trip-planner__move trip-planner__move--up';
				upBtn.setAttribute( 'data-move', 'up' );
				upBtn.setAttribute( 'data-move-id', stop.id );
				upBtn.setAttribute( 'aria-label', labels.tripMoveUp );
				upBtn.disabled = ( index === 0 );
				upBtn.textContent = '↑';

				var downBtn = document.createElement( 'button' );
				downBtn.type = 'button';
				downBtn.className = 'trip-planner__move trip-planner__move--down';
				downBtn.setAttribute( 'data-move', 'down' );
				downBtn.setAttribute( 'data-move-id', stop.id );
				downBtn.setAttribute( 'aria-label', labels.tripMoveDown );
				downBtn.disabled = ( index === stops.length - 1 );
				downBtn.textContent = '↓';

				li.appendChild( upBtn );
				li.appendChild( downBtn );
			}

			list.appendChild( li );

			if ( index < stops.length - 1 ) {
				var next  = stops[ index + 1 ];
				var legLi = document.createElement( 'li' );
				legLi.className = 'trip-planner__leg';
				legLi.setAttribute( 'aria-hidden', 'true' );

				if ( stop.data.lat != null && stop.data.lng != null && next.data.lat != null && next.data.lng != null ) {
					var legKm = haversineKm( stop.data.lat, stop.data.lng, next.data.lat, next.data.lng );
					legLi.textContent = formatKm( legKm ) + ' ' + labels.tripToNext;
				} else {
					legLi.textContent = '—';
				}

				list.appendChild( legLi );
				legElements.push( legLi );
			}
		} );

		container.appendChild( list );

		var totalKm = 0;
		for ( var i = 0; i < stops.length - 1; i++ ) {
			var a = stops[ i ].data;
			var b = stops[ i + 1 ].data;
			if ( a.lat != null && a.lng != null && b.lat != null && b.lng != null ) {
				totalKm += haversineKm( a.lat, a.lng, b.lat, b.lng );
			}
		}

		var totalEl = document.createElement( 'p' );
		totalEl.className = 'trip-planner__total';
		totalEl.textContent = labels.tripTotal.replace( '%s', formatKm( totalKm ) );
		container.appendChild( totalEl );

		var actions = document.createElement( 'div' );
		actions.className = 'trip-planner__actions';

		var copyLinkBtn = document.createElement( 'button' );
		copyLinkBtn.type = 'button';
		copyLinkBtn.className = 'trip-planner__copy-link';
		var copyLinkDefaultText = labels.tripCopyLink;
		copyLinkBtn.textContent = copyLinkDefaultText;
		copyLinkBtn.addEventListener( 'click', function () {
			var shareUrl = location.origin + location.pathname + '?trip=' + stops.map( function ( stop ) { return stop.id; } ).join( ',' );

			function showCopied() {
				copyLinkBtn.textContent = labels.tripLinkCopied;
				copyLinkBtn.disabled = true;
				setTimeout( function () {
					copyLinkBtn.textContent = copyLinkDefaultText;
					copyLinkBtn.disabled = false;
				}, 2000 );
			}

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( shareUrl ).then( showCopied, function () {
					window.prompt( labels.tripCopyLinkFallback, shareUrl );
				} );
			} else {
				window.prompt( labels.tripCopyLinkFallback, shareUrl );
			}
		} );
		actions.appendChild( copyLinkBtn );

		if ( options.showSaveAll ) {
			var saveAllBtn = document.createElement( 'button' );
			saveAllBtn.type = 'button';
			saveAllBtn.className = 'button trip-planner__save-all';
			saveAllBtn.textContent = labels.tripSaveAll;
			saveAllBtn.addEventListener( 'click', function () {
				var currentIds = getSavedIds();
				stops.forEach( function ( stop ) {
					if ( currentIds.indexOf( stop.id ) === -1 ) {
						currentIds.push( stop.id );
					}
				} );
				setSavedIds( currentIds );
				updateNavBadge();
				saveAllBtn.textContent = labels.tripSaveAllDone;
				saveAllBtn.disabled = true;
			} );
			actions.appendChild( saveAllBtn );
		}

		container.appendChild( actions );

		if ( ! options.readOnly ) {
			list.addEventListener( 'click', function ( event ) {
				var btn = event.target.closest && event.target.closest( '[data-move]' );
				if ( ! btn || btn.disabled ) {
					return;
				}
				var id         = parseInt( btn.getAttribute( 'data-move-id' ), 10 );
				var dir        = btn.getAttribute( 'data-move' );
				var currentIds = stops.map( function ( stop ) { return stop.id; } );
				var pos        = currentIds.indexOf( id );
				var swapWith   = dir === 'up' ? pos - 1 : pos + 1;
				if ( swapWith < 0 || swapWith >= currentIds.length ) {
					return;
				}
				var tmp = currentIds[ pos ];
				currentIds[ pos ] = currentIds[ swapWith ];
				currentIds[ swapWith ] = tmp;

				if ( options.onReorder ) {
					options.onReorder( currentIds );
				}
			} );
		}

		var mapState = renderTripMap( mapDiv, stops );

		// Straight-line numbers/route above are already on screen and fully
		// correct as an estimate — this just upgrades them in place once a
		// real driving route comes back, and quietly leaves them as-is if
		// the request fails or any stop lacks coordinates.
		var allStopsHaveCoords = stops.every( function ( stop ) { return stop.data.lat != null && stop.data.lng != null; } );
		if ( allStopsHaveCoords && stops.length >= 2 ) {
			fetchDrivingRoute( stops ).then( function ( route ) {
				if ( ! route ) {
					return;
				}
				legElements.forEach( function ( legEl, index ) {
					if ( route.legKm[ index ] != null ) {
						legEl.textContent = formatKm( route.legKm[ index ] ) + ' ' + labels.tripToNext;
					}
				} );
				totalEl.textContent = labels.tripTotal.replace( '%s', formatKm( route.totalKm ) );
				updateTripMapRoute( mapState, route.latlngs );
			} );
		}
	}

	function renderComparisonTable( section, ids, buildingsById, labels ) {
		var fields = [
			[ 'region', labels.compareFields.region ],
			[ 'buildingType', labels.compareFields.buildingType ],
			[ 'style', labels.compareFields.style ],
			[ 'era', labels.compareFields.era ],
			[ 'yearBuilt', labels.compareFields.yearBuilt ],
			[ 'admission', labels.compareFields.admission ],
			[ 'openingHours', labels.compareFields.openingHours ],
			[ 'parking', labels.compareFields.parking ],
			[ 'accessibility', labels.compareFields.accessibility ],
			[ 'guidedTours', labels.compareFields.guidedTours ]
		];

		var buildings = ids.map( function ( id ) { return buildingsById[ id ]; } ).filter( Boolean );

		var wrap = document.createElement( 'div' );
		wrap.className = 'table-wrap';

		var table = document.createElement( 'table' );
		table.className = 'compare-table';

		var thead = document.createElement( 'thead' );
		var headRow = document.createElement( 'tr' );
		headRow.appendChild( document.createElement( 'th' ) );

		buildings.forEach( function ( building ) {
			var th = document.createElement( 'th' );

			if ( building.image ) {
				var img = document.createElement( 'img' );
				img.src = building.image;
				img.alt = '';
				th.appendChild( img );
			}

			var link = document.createElement( 'a' );
			link.href = building.link;
			link.textContent = building.title;
			th.appendChild( link );

			headRow.appendChild( th );
		} );

		thead.appendChild( headRow );
		table.appendChild( thead );

		var tbody = document.createElement( 'tbody' );

		fields.forEach( function ( field ) {
			var key   = field[ 0 ];
			var label = field[ 1 ];
			var row   = document.createElement( 'tr' );

			var rowHeader = document.createElement( 'th' );
			rowHeader.scope = 'row';
			rowHeader.textContent = label;
			row.appendChild( rowHeader );

			buildings.forEach( function ( building ) {
				var td    = document.createElement( 'td' );
				var value = building[ key ];

				if ( ! value ) {
					td.textContent = '—';
				} else if ( 'openingHours' === key ) {
					String( value ).split( '\n' ).forEach( function ( line, index ) {
						if ( index > 0 ) {
							td.appendChild( document.createElement( 'br' ) );
						}
						td.appendChild( document.createTextNode( line ) );
					} );
				} else {
					td.textContent = value;
				}

				row.appendChild( td );
			} );

			tbody.appendChild( row );
		} );

		table.appendChild( tbody );
		wrap.appendChild( table );

		section.textContent = '';
		section.appendChild( wrap );
	}
} )();
