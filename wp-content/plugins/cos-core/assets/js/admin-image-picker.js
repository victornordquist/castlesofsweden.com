( function ( $ ) {
	'use strict';

	function initPicker( $wrap ) {
		var $preview = $wrap.find( '.cos-image-picker__preview' );
		var $img     = $preview.find( 'img' );
		var $select  = $wrap.find( '.cos-image-picker__select' );
		var $input   = $wrap.find( '.cos-image-picker__input' );
		var title    = $wrap.data( 'title' ) || 'Select image';
		var button   = $wrap.data( 'button-text' ) || 'Use this image';
		var frame;

		$select.on( 'click', function ( e ) {
			e.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: title,
				button: { text: button },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var thumbUrl   = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;

				$input.val( attachment.id );
				$img.attr( 'src', thumbUrl );
				$preview.show();
				$select.hide();
			} );

			frame.open();
		} );

		$wrap.on( 'click', '.cos-image-picker__remove', function ( e ) {
			e.preventDefault();
			$input.val( '' );
			$img.attr( 'src', '' );
			$preview.hide();
			$select.show();
		} );
	}

	$( function () {
		$( '.cos-image-picker' ).each( function () {
			initPicker( $( this ) );
		} );
	} );
} )( jQuery );
