( function( $ ) {
	$(window).load( function() {
		var headerHeight = $( '.site-header' ).outerHeight();

		if ( $(window).width() > 768 ) {
			$( '.site' ).css( 'padding-top', headerHeight );
		}

		$( document.body ).on( 'checkout_error', function() {
			$( 'html, body' ).animate({
				scrollTop: ( $( '#primary' ).offset().top - headerHeight )
			}, 1000 );
		});
	});
} )( jQuery );