document.addEventListener( 'DOMContentLoaded', function () {
	var mapEl = document.getElementById( 'cos-term-map' );
	if ( ! mapEl || typeof L === 'undefined' ) {
		return;
	}

	var map = L.map( 'cos-term-map', { scrollWheelZoom: false } ).setView( [ 62.0, 15.5 ], 5 );

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

	var url = cosTermMapData.endpoint +
		'&taxonomy=' + encodeURIComponent( mapEl.dataset.taxonomy ) +
		'&term=' + encodeURIComponent( mapEl.dataset.term );

	fetch( url )
		.then( function ( response ) { return response.json(); } )
		.then( function ( buildings ) {
			var bounds = [];

			buildings.forEach( function ( building ) {
				var marker = L.marker( [ building.lat, building.lng ], { icon: photoMarkerIcon( building.thumbnail ) } );
				var popupHtml = '';
				if ( building.thumbnail ) {
					popupHtml += '<img src="' + building.thumbnail + '" alt="" style="width:100%;margin-bottom:6px;">';
				}
				popupHtml += '<strong>' + building.name + '</strong><br>';
				popupHtml += '<a href="' + building.permalink + '">' + cosTermMapData.viewDetailsLabel + '</a>';
				marker.bindPopup( popupHtml );
				markerGroup.addLayer( marker );
				bounds.push( [ building.lat, building.lng ] );
			} );

			if ( bounds.length ) {
				map.fitBounds( bounds, { padding: [ 30, 30 ], maxZoom: 12 } );
			}
		} );
} );
