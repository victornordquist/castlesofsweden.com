document.addEventListener( 'DOMContentLoaded', function () {
	var mapEl = document.getElementById( 'cos-map' );
	if ( ! mapEl || typeof L === 'undefined' ) {
		return;
	}

	// The sticky header's height changes when it shrinks on scroll, so the
	// map's available height is tracked via a CSS variable instead of a
	// fixed offset, to avoid ever leaving a stale gap at the bottom.
	var header = document.querySelector( '.site-header' );
	function updateHeaderHeightVar() {
		if ( header ) {
			document.documentElement.style.setProperty( '--cos-header-height', header.getBoundingClientRect().height + 'px' );
		}
	}
	updateHeaderHeightVar();
	window.addEventListener( 'resize', updateHeaderHeightVar );
	window.addEventListener( 'scroll', updateHeaderHeightVar );

	var map = L.map( 'cos-map' ).setView( [ 62.0, 15.5 ], 5 );

	L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 18,
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
	} ).addTo( map );

	function haversineKm( lat1, lng1, lat2, lng2 ) {
		var R = 6371;
		var dLat = ( lat2 - lat1 ) * Math.PI / 180;
		var dLng = ( lng2 - lng1 ) * Math.PI / 180;
		var a = Math.sin( dLat / 2 ) * Math.sin( dLat / 2 ) +
			Math.cos( lat1 * Math.PI / 180 ) * Math.cos( lat2 * Math.PI / 180 ) *
			Math.sin( dLng / 2 ) * Math.sin( dLng / 2 );
		return R * 2 * Math.atan2( Math.sqrt( a ), Math.sqrt( 1 - a ) );
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

	var markerGroup = L.layerGroup();
	map.addLayer( markerGroup );

	// Leaflet only builds a popup's DOM when it opens, so the save button
	// inside it can't be synced to the current saved state until then.
	map.on( 'popupopen', function ( e ) {
		if ( window.cosSyncSaveButtons ) {
			window.cosSyncSaveButtons( e.popup.getElement() );
		}
	} );

	var allBuildings = [];
	var filterKeys = [ 'region', 'type', 'category', 'activity', 'feature', 'style', 'era' ];
	// These render/behave as checkbox groups (multi-select, AND-matched) rather
	// than a single <select> — a building can offer several categories,
	// activities, or features at once.
	var checkboxKeys = [ 'category', 'activity', 'feature' ];

	var filters = {
		search: document.getElementById( 'cos-map-search' ),
		region: document.getElementById( 'cos-map-region' ),
		type: document.getElementById( 'cos-map-type' ),
		category: document.getElementById( 'cos-map-category' ),
		activity: document.getElementById( 'cos-map-activity' ),
		feature: document.getElementById( 'cos-map-feature' ),
		style: document.getElementById( 'cos-map-style' ),
		era: document.getElementById( 'cos-map-era' )
	};

	// Near-me is a coordinate-based filter, not a building-attribute one, so
	// it lives outside `filters`/`filterKeys` (those are built from values
	// seen in the fetched building data, which lat/lng comparisons aren't).
	var nearMeCheckbox     = document.getElementById( 'cos-map-near-me' );
	var nearMeRadiusSelect = document.getElementById( 'cos-map-near-me-radius' );
	var nearMeStatusEl     = document.getElementById( 'cos-map-near-me-status' );
	var nearMeCoords       = null; // { lat, lng } once resolved (from URL or geolocation)

	// The Map's region/category options are built from term *names* seen in
	// the fetched data (see populateFilterOptions), but a link from the
	// homepage hero only knows term *slugs* — cosMapData.regions/categories
	// (slug -> name) bridges the two so an incoming URL can be applied.
	var urlParams            = new URLSearchParams( window.location.search );
	var pendingRegionName    = null;
	var pendingCategoryNames = [];

	if ( urlParams.get( 'region' ) && cosMapData.regions && cosMapData.regions[ urlParams.get( 'region' ) ] ) {
		pendingRegionName = cosMapData.regions[ urlParams.get( 'region' ) ];
	}
	if ( urlParams.get( 'category' ) && cosMapData.categories ) {
		urlParams.get( 'category' ).split( ',' ).forEach( function ( slug ) {
			if ( cosMapData.categories[ slug ] ) {
				pendingCategoryNames.push( cosMapData.categories[ slug ] );
			}
		} );
	}
	if ( urlParams.get( 'near_lat' ) && urlParams.get( 'near_lng' ) ) {
		nearMeCoords = {
			lat: parseFloat( urlParams.get( 'near_lat' ) ),
			lng: parseFloat( urlParams.get( 'near_lng' ) )
		};
		if ( nearMeCheckbox ) {
			nearMeCheckbox.checked = true;
		}
		if ( nearMeRadiusSelect && urlParams.get( 'radius_km' ) ) {
			nearMeRadiusSelect.value = urlParams.get( 'radius_km' );
		}
	}

	function populateFilterOptions() {
		var sets = {};
		filterKeys.forEach( function ( key ) { sets[ key ] = {}; } );

		allBuildings.forEach( function ( building ) {
			filterKeys.forEach( function ( key ) {
				( building[ key ] || [] ).forEach( function ( value ) {
					sets[ key ][ value ] = true;
				} );
			} );
		} );

		// Default string sort isn't Swedish-aware (puts Å/Ä/Ö next to A/O
		// instead of after Z), so an explicit Swedish collator is used.
		var svCollator = new Intl.Collator( 'sv' );

		filterKeys.forEach( function ( key ) {
			var container = filters[ key ];
			if ( ! container ) {
				return;
			}
			var values = Object.keys( sets[ key ] ).sort( svCollator.compare );

			if ( checkboxKeys.indexOf( key ) !== -1 ) {
				values.forEach( function ( value, index ) {
					var id = 'cos-map-' + key + '-' + index;

					var wrapper = document.createElement( 'label' );
					wrapper.className = 'map-filters__checkbox';

					var checkbox = document.createElement( 'input' );
					checkbox.type = 'checkbox';
					checkbox.id = id;
					checkbox.value = value;
					checkbox.addEventListener( 'change', render );

					wrapper.appendChild( checkbox );
					wrapper.appendChild( document.createTextNode( value ) );
					container.appendChild( wrapper );
				} );
				return;
			}

			values.forEach( function ( value ) {
				var option = document.createElement( 'option' );
				option.value = value;
				option.textContent = value;
				container.appendChild( option );
			} );
		} );
	}

	function selectedCheckboxValues( key ) {
		var container = filters[ key ];
		var checked = container ? container.querySelectorAll( 'input:checked' ) : [];
		return Array.prototype.map.call( checked, function ( checkbox ) { return checkbox.value; } );
	}

	function matchesFilters( building ) {
		var searchTerm = filters.search ? filters.search.value.trim().toLowerCase() : '';
		if ( searchTerm && building.name.toLowerCase().indexOf( searchTerm ) === -1 ) {
			return false;
		}

		var singleSelectKeys = [ 'region', 'type', 'style', 'era' ];
		var matchesSingleSelects = singleSelectKeys.every( function ( key ) {
			var select = filters[ key ];
			var value = select ? select.value : '';
			return ! value || ( building[ key ] || [] ).indexOf( value ) !== -1;
		} );

		if ( ! matchesSingleSelects ) {
			return false;
		}

		// AND, not OR, both within and across category/activity/feature: a
		// building must offer every value checked in every group, not just
		// any one of them.
		var matchesCheckboxGroups = checkboxKeys.every( function ( key ) {
			var selected = selectedCheckboxValues( key );
			if ( ! selected.length ) {
				return true;
			}
			return selected.every( function ( value ) {
				return ( building[ key ] || [] ).indexOf( value ) !== -1;
			} );
		} );
		if ( ! matchesCheckboxGroups ) {
			return false;
		}

		if ( nearMeCheckbox && nearMeCheckbox.checked && nearMeCoords ) {
			var radiusKm = nearMeRadiusSelect ? parseFloat( nearMeRadiusSelect.value ) : 25;
			var distanceKm = haversineKm( nearMeCoords.lat, nearMeCoords.lng, building.lat, building.lng );
			if ( distanceKm > radiusKm ) {
				return false;
			}
		}

		return true;
	}

	function render() {
		markerGroup.clearLayers();
		var count = 0;
		var matchedCoords = [];

		allBuildings.forEach( function ( building ) {
			if ( ! matchesFilters( building ) ) {
				return;
			}
			count++;
			matchedCoords.push( [ building.lat, building.lng ] );

			var marker = L.marker( [ building.lat, building.lng ], { icon: photoMarkerIcon( building.thumbnail ) } );
			var popupHtml = '<div class="cos-map-popup">';
			if ( building.thumbnail ) {
				popupHtml += '<img src="' + building.thumbnail + '" alt="" style="width:100%;margin-bottom:6px;">';
			}
			popupHtml += '<strong>' + building.name + '</strong>';
			popupHtml += '<div class="cos-map-popup__actions">';
			popupHtml += '<a href="' + building.permalink + '">' + cosMapData.viewDetailsLabel + '</a>';
			if ( window.cosBuildSaveButtonHtml ) {
				popupHtml += window.cosBuildSaveButtonHtml( building.id, 'save-building-button--popup' );
			}
			popupHtml += '</div></div>';
			marker.bindPopup( popupHtml );
			markerGroup.addLayer( marker );
		} );

		var counter = document.getElementById( 'cos-map-count' );
		if ( counter ) {
			counter.textContent = count + ' ' + cosMapData.buildingsLabel;
		}

		// Keep the view in sync with whatever's actually showing — e.g. picking
		// a region zooms/centers on that region's buildings. maxZoom caps how
		// far a single (or tightly clustered) match zooms in, since fitBounds
		// on one point alone would otherwise go to the map's max zoom level.
		if ( matchedCoords.length ) {
			map.fitBounds( matchedCoords, { padding: [ 40, 40 ], maxZoom: 13 } );
		} else {
			map.setView( [ 62.0, 15.5 ], 5 );
		}
	}

	fetch( cosMapData.endpoint )
		.then( function ( response ) { return response.json(); } )
		.then( function ( data ) {
			allBuildings = data;
			populateFilterOptions();

			if ( pendingRegionName && filters.region ) {
				filters.region.value = pendingRegionName; // no-op if that name never became an <option>
			}
			if ( pendingCategoryNames.length && filters.category ) {
				filters.category.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( cb ) {
					if ( pendingCategoryNames.indexOf( cb.value ) !== -1 ) {
						cb.checked = true;
					}
				} );
			}

			render();
		} );

	Object.keys( filters ).forEach( function ( key ) {
		if ( filters[ key ] && checkboxKeys.indexOf( key ) === -1 ) {
			filters[ key ].addEventListener( 'input', render );
		}
	} );

	if ( nearMeRadiusSelect ) {
		nearMeRadiusSelect.addEventListener( 'change', render );
	}
	if ( nearMeCheckbox ) {
		nearMeCheckbox.addEventListener( 'change', function () {
			if ( ! nearMeCheckbox.checked ) {
				render();
				return;
			}
			if ( nearMeCoords ) { // already have coords (e.g. arrived via URL) — just re-render
				render();
				return;
			}
			if ( ! navigator.geolocation ) {
				nearMeStatusEl.hidden = false;
				nearMeStatusEl.textContent = cosMapData.nearMeLabels.unsupported;
				nearMeCheckbox.checked = false;
				return;
			}
			nearMeStatusEl.hidden = false;
			nearMeStatusEl.textContent = cosMapData.nearMeLabels.locating;
			navigator.geolocation.getCurrentPosition(
				function ( position ) {
					nearMeCoords = { lat: position.coords.latitude, lng: position.coords.longitude };
					nearMeStatusEl.hidden = true;
					render();
				},
				function () {
					nearMeStatusEl.textContent = cosMapData.nearMeLabels.denied;
					nearMeCheckbox.checked = false;
					render();
				}
			);
		} );
	}
} );
