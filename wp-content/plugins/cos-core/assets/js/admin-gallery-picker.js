( function ( $ ) {
	'use strict';

	function initPicker( $wrap ) {
		var $list  = $wrap.find( '.cos-gallery-picker__list' );
		var $input = $wrap.find( '.cos-gallery-picker__input' );
		var $add   = $wrap.find( '.cos-gallery-picker__add' );
		var title  = $wrap.data( 'title' ) || 'Select gallery images';
		var button = $wrap.data( 'button-text' ) || 'Add to gallery';
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

			var $item = $( '<li class="cos-gallery-picker__item"></li>' ).attr( 'data-id', attachment.id );
			$item.append( $( '<img />' ).attr( 'src', thumbUrl ) );
			$item.append( $( '<button type="button" class="cos-gallery-picker__remove" aria-label="Remove image">&times;</button>' ) );
			$list.append( $item );
		}

		$wrap.on( 'click', '.cos-gallery-picker__remove', function () {
			var $item = $( this ).closest( '.cos-gallery-picker__item' );
			var id    = String( $item.data( 'id' ) );
			setIds( currentIds().filter( function ( existingId ) {
				return existingId !== id;
			} ) );
			$item.remove();
		} );

		$add.on( 'click', function ( e ) {
			e.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: title,
				button: { text: button },
				multiple: true,
			} );

			frame.on( 'select', function () {
				frame.state().get( 'selection' ).each( function ( attachment ) {
					addItem( attachment.toJSON() );
				} );
			} );

			frame.open();
		} );
	}

	$( function () {
		$( '.cos-gallery-picker' ).each( function () {
			initPicker( $( this ) );
		} );
	} );
} )( jQuery );
