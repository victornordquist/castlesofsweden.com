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

	var allBuildings = [];
	var filterKeys = [ 'region', 'type', 'category', 'style', 'era' ];

	var filters = {
		search: document.getElementById( 'cos-map-search' ),
		region: document.getElementById( 'cos-map-region' ),
		type: document.getElementById( 'cos-map-type' ),
		category: document.getElementById( 'cos-map-category' ),
		style: document.getElementById( 'cos-map-style' ),
		era: document.getElementById( 'cos-map-era' )
	};

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

			if ( 'category' === key ) {
				values.forEach( function ( value, index ) {
					var id = 'cos-map-category-' + index;

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

	function selectedCategories() {
		var checked = filters.category ? filters.category.querySelectorAll( 'input:checked' ) : [];
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

		var categories = selectedCategories();
		if ( ! categories.length ) {
			return true;
		}
		// AND, not OR: a building must offer every selected category, not just any one of them.
		return categories.every( function ( category ) {
			return ( building.category || [] ).indexOf( category ) !== -1;
		} );
	}

	function render() {
		markerGroup.clearLayers();
		var count = 0;

		allBuildings.forEach( function ( building ) {
			if ( ! matchesFilters( building ) ) {
				return;
			}
			count++;

			var marker = L.marker( [ building.lat, building.lng ], { icon: photoMarkerIcon( building.thumbnail ) } );
			var popupHtml = '';
			if ( building.thumbnail ) {
				popupHtml += '<img src="' + building.thumbnail + '" alt="" style="width:100%;margin-bottom:6px;">';
			}
			popupHtml += '<strong>' + building.name + '</strong><br>';
			popupHtml += '<a href="' + building.permalink + '">' + cosMapData.viewDetailsLabel + '</a>';
			marker.bindPopup( popupHtml );
			markerGroup.addLayer( marker );
		} );

		var counter = document.getElementById( 'cos-map-count' );
		if ( counter ) {
			counter.textContent = count + ' ' + cosMapData.buildingsLabel;
		}
	}

	fetch( cosMapData.endpoint )
		.then( function ( response ) { return response.json(); } )
		.then( function ( data ) {
			allBuildings = data;
			populateFilterOptions();
			render();
		} );

	Object.keys( filters ).forEach( function ( key ) {
		if ( filters[ key ] && 'category' !== key ) {
			filters[ key ].addEventListener( 'input', render );
		}
	} );
} );
