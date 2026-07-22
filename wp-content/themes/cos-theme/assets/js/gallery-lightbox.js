( function () {
	var gallery = document.querySelector( '.photo-gallery__grid' );
	if ( ! gallery ) {
		return;
	}

	var links = Array.prototype.slice.call( gallery.querySelectorAll( '.photo-gallery__item' ) );
	var index = 0;

	var overlay = document.createElement( 'div' );
	overlay.className = 'gallery-lightbox';
	overlay.innerHTML =
		'<button type="button" class="gallery-lightbox__close" aria-label="Close">&times;</button>' +
		'<button type="button" class="gallery-lightbox__prev" aria-label="Previous">&#8249;</button>' +
		'<img class="gallery-lightbox__image" src="" alt="" />' +
		'<button type="button" class="gallery-lightbox__next" aria-label="Next">&#8250;</button>';
	document.body.appendChild( overlay );

	var img = overlay.querySelector( '.gallery-lightbox__image' );

	function show( i ) {
		index = ( i + links.length ) % links.length;
		img.src = links[ index ].getAttribute( 'href' );
		img.alt = links[ index ].querySelector( 'img' ).alt;
	}

	function open( i ) {
		show( i );
		overlay.classList.add( 'is-open' );
		document.body.style.overflow = 'hidden';
	}

	function close() {
		overlay.classList.remove( 'is-open' );
		document.body.style.overflow = '';
	}

	links.forEach( function ( link, i ) {
		link.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			open( i );
		} );
	} );

	overlay.querySelector( '.gallery-lightbox__close' ).addEventListener( 'click', close );
	overlay.querySelector( '.gallery-lightbox__prev' ).addEventListener( 'click', function () {
		show( index - 1 );
	} );
	overlay.querySelector( '.gallery-lightbox__next' ).addEventListener( 'click', function () {
		show( index + 1 );
	} );
	overlay.addEventListener( 'click', function ( e ) {
		if ( e.target === overlay ) {
			close();
		}
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( ! overlay.classList.contains( 'is-open' ) ) {
			return;
		}
		if ( 'Escape' === e.key ) {
			close();
		} else if ( 'ArrowLeft' === e.key ) {
			show( index - 1 );
		} else if ( 'ArrowRight' === e.key ) {
			show( index + 1 );
		}
	} );
} )();
