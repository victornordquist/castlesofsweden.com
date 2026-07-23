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

				Promise.all( lookups ).then( function ( results ) {
					var names = {
						region: results[ 0 ],
						buildingType: results[ 1 ],
						style: results[ 2 ],
						era: results[ 3 ]
					};
					renderSavedPlacesUI( container, matching, names, data.labels );
				} );
			} )
			.catch( function () { container.textContent = data.labels.empty; } );
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

		container.textContent = '';
		container.appendChild( toolbar );
		container.appendChild( grid );
		container.appendChild( bar );
		container.appendChild( section );

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
