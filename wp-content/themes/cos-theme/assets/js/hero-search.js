document.addEventListener( 'DOMContentLoaded', function () {
	var root = document.getElementById( 'hero-search' );
	if ( ! root || typeof cosHeroSearchData === 'undefined' ) {
		return;
	}

	var nearMeBtn    = document.getElementById( 'hero-near-me' );
	var regionSelect = document.getElementById( 'hero-region' );
	var statusEl     = document.getElementById( 'hero-near-me-status' );
	var submitBtn    = document.getElementById( 'hero-search-submit' );

	var activeChoice = null; // 'near-me' | 'region' | null
	var coords = null;       // { lat, lng } once geolocation resolves
	var pendingSubmit = false;

	function setStatus( text, isError ) {
		if ( ! statusEl ) {
			return;
		}
		statusEl.hidden = ! text;
		statusEl.textContent = text || '';
		statusEl.classList.toggle( 'hero-search__status--error', !! isError );
	}

	function setActiveChoice( choice ) {
		activeChoice = choice;
		nearMeBtn.classList.toggle( 'is-active', 'near-me' === choice );
		nearMeBtn.setAttribute( 'aria-pressed', 'near-me' === choice ? 'true' : 'false' );
		regionSelect.classList.toggle( 'is-active', 'region' === choice );
	}

	nearMeBtn.addEventListener( 'click', function () {
		regionSelect.value = '';
		setActiveChoice( 'near-me' );
		coords = null;
		setStatus( '' );

		if ( ! navigator.geolocation ) {
			setStatus( cosHeroSearchData.labels.unsupported, true );
			setActiveChoice( null );
			return;
		}

		nearMeBtn.setAttribute( 'data-state', 'locating' );
		setStatus( cosHeroSearchData.labels.locating );

		navigator.geolocation.getCurrentPosition(
			function ( position ) {
				coords = { lat: position.coords.latitude, lng: position.coords.longitude };
				nearMeBtn.setAttribute( 'data-state', 'idle' );
				setStatus( '' );
				if ( pendingSubmit ) {
					navigate();
				}
			},
			function () {
				nearMeBtn.setAttribute( 'data-state', 'idle' );
				setStatus( cosHeroSearchData.labels.denied, true );
				setActiveChoice( null );
				pendingSubmit = false;
			}
		);
	} );

	regionSelect.addEventListener( 'change', function () {
		if ( regionSelect.value ) {
			setActiveChoice( 'region' );
			setStatus( '' );
		} else if ( 'region' === activeChoice ) {
			setActiveChoice( null );
		}
	} );

	function selectedCategories() {
		var checked = root.querySelectorAll( 'input[name="category[]"]:checked' );
		return Array.prototype.map.call( checked, function ( cb ) { return cb.value; } );
	}

	root.querySelectorAll( 'input[name="category[]"]' ).forEach( function ( cb ) {
		cb.addEventListener( 'change', function () {
			cb.closest( '.hero-search__checkbox' ).classList.toggle( 'is-active', cb.checked );
		} );
	} );

	function navigate() {
		pendingSubmit = false;
		var params = new URLSearchParams();

		if ( 'region' === activeChoice && regionSelect.value ) {
			params.set( 'region', regionSelect.value );
		} else if ( 'near-me' === activeChoice && coords ) {
			params.set( 'near_lat', coords.lat );
			params.set( 'near_lng', coords.lng );
			params.set( 'radius_km', '50' );
		}

		var categories = selectedCategories();
		if ( categories.length ) {
			params.set( 'category', categories.join( ',' ) );
		}

		var qs = params.toString();
		window.location.href = cosHeroSearchData.mapUrl + ( qs ? '?' + qs : '' );
	}

	submitBtn.addEventListener( 'click', function () {
		// Near Me was clicked but geolocation hasn't resolved (or errored) yet —
		// wait for it instead of racing off with no coordinates.
		if ( 'near-me' === activeChoice && ! coords && 'locating' === nearMeBtn.getAttribute( 'data-state' ) ) {
			pendingSubmit = true;
			setStatus( cosHeroSearchData.labels.locating );
			return;
		}
		navigate();
	} );
} );
