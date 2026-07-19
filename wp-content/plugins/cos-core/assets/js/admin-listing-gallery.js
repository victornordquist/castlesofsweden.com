( function ( $ ) {
	'use strict';

	$( function () {
		var $wrap  = $( '#cos-listing-gallery' );
		var $list  = $wrap.find( '.cos-listing-gallery__list' );
		var $input = $wrap.find( '#cos_listing_gallery' );
		var frame;

		function currentIds() {
			var value = $input.val();
			return value ? value.split( ',' ).filter( Boolean ) : [];
		}

		function setIds( ids ) {
			$input.val( ids.join( ',' ) );
		}

		function addItem( attachment ) {
			var ids = currentIds();
			if ( ids.indexOf( String( attachment.id ) ) !== -1 ) {
				return;
			}
			ids.push( attachment.id );
			setIds( ids );

			var thumbUrl = attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;

			var $item = $( '<li class="cos-listing-gallery__item"></li>' ).attr( 'data-id', attachment.id );
			$item.append( $( '<img />' ).attr( 'src', thumbUrl ) );
			$item.append( $( '<button type="button" class="cos-listing-gallery__remove" aria-label="Remove image">&times;</button>' ) );
			$list.append( $item );
		}

		$wrap.on( 'click', '.cos-listing-gallery__remove', function () {
			var $item = $( this ).closest( '.cos-listing-gallery__item' );
			var id    = String( $item.data( 'id' ) );
			setIds( currentIds().filter( function ( existingId ) {
				return existingId !== id;
			} ) );
			$item.remove();
		} );

		$( '#cos-listing-gallery-add' ).on( 'click', function ( e ) {
			e.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: 'Select gallery images',
				button: { text: 'Add to gallery' },
				multiple: true,
			} );

			frame.on( 'select', function () {
				frame.state().get( 'selection' ).each( function ( attachment ) {
					addItem( attachment.toJSON() );
				} );
			} );

			frame.open();
		} );
	} );
} )( jQuery );
