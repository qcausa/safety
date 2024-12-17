(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 
	$(document).ready( function() {
		
		$( document  ).on('click', '#aw-whats-new-reset, #aw-whats-new-submit', function(){						
			bp.Nouveau.setStorage( 'bp-activity','filter','');
			setTimeout(function(){				
				bp.Nouveau.setStorage( 'bp-activity','filter',$('#activity_filters_objects').val() );
			}, 3000);			
		});

		// load more for activity
		$( '#buddypress [data-bp-list="activity"]' ).off( 'click', 'li.load-newest, li.load-more' );//Override buddypress load more click function
		$(document).on('click', '#load-more-activity', function (e) {
			e.preventDefault();
			var self = $(this);
			var page = self.data('page');
			var limit = self.data('limit');
			$.ajax({
				type: "post",
				url: buddypress_shortcode.ajax_url,
				data: {
					'action': 'bpsp_activities_load',
					'page': page, // Increment the page for the request
					'limit': limit,
					'nonce': buddypress_shortcode.ajax_nonce,
				},
				success: function (res) {
					$('ul.activity-list.item-list.bp-list').append(res.data);
					var newPage = self.data('page') + 1;
					self.data('page', newPage);
				},
			});
		});
		// load more for activity

	});

})( jQuery );
