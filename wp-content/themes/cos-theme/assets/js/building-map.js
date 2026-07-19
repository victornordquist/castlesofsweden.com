document.addEventListener( 'DOMContentLoaded', function () {
	var mapEl = document.getElementById( 'cos-building-map' );
	if ( ! mapEl || typeof L === 'undefined' ) {
		return;
	}

	var lat = parseFloat( mapEl.dataset.lat );
	var lng = parseFloat( mapEl.dataset.lng );

	if ( isNaN( lat ) || isNaN( lng ) ) {
		return;
	}

	var map = L.map( 'cos-building-map', { scrollWheelZoom: false } ).setView( [ lat, lng ], 14 );

	L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 18,
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
	} ).addTo( map );

	var thumbnailUrl = mapEl.dataset.thumbnail || '';
	var style = thumbnailUrl ? "background-image:url('" + thumbnailUrl.replace( /'/g, '%27' ) + "');" : '';
	var icon = L.divIcon( {
		className: 'cos-map-marker',
		html: '<span class="cos-map-marker__inner" style="' + style + '"></span>',
		iconSize: [ 40, 40 ],
		iconAnchor: [ 20, 20 ],
		popupAnchor: [ 0, -20 ]
	} );

	L.marker( [ lat, lng ], { icon: icon } ).addTo( map );
} );
