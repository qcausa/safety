/* global wpforms_builder */

'use strict';

( function( $ ) {

	var WPFormsPostSubmissions = {

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			WPFormsPostSubmissions.bindUIActions();

			$( WPFormsPostSubmissions.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function() {

			WPFormsPostSubmissions.conditionals();
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		bindUIActions: function() {

			$( document )
				.on( 'wpformsBuilderSetupReady', WPFormsPostSubmissions.togglePostSubmissions )
				.on( 'input', '#wpforms-panel-field-settings-post_submissions', WPFormsPostSubmissions.togglePostSubmissions )
				.on( 'input', '#wpforms-panel-field-settings-post_submissions_featured', WPFormsPostSubmissions.changeFeaturedImage )
				.on( 'click', '.wpforms-post-submissions-disabled-option', WPFormsPostSubmissions.disabledMediaAlert )
				.on( 'wpformsFieldDuplicated', WPFormsPostSubmissions.togglePostSubmissions );
		},

		/**
		 * Show settings only if they are enabled.
		 *
		 * @since 1.4.0
		 */
		conditionals: function() {

			if ( typeof $.fn.conditions === 'undefined' ) {
				return;
			}

			$( '#wpforms-panel-field-settings-post_submissions' ).conditions( {
				conditions: {
					element: '#wpforms-panel-field-settings-post_submissions',
					type: 'checked',
					operator: 'is',
				},
				actions: {
					if: {
						element: '#wpforms-post-submissions-content-block',
						action: 'show',
					},
					else: {
						element: '#wpforms-post-submissions-content-block',
						action: 'hide',
					},
				},
				effect: 'appear',
			} );
		},

		/**
		 * Adjust featured image settings on builder load.
		 *
		 * @since 1.5.0
		 */
		togglePostSubmissions: function() {

			const disabledClass = 'wpforms-post-submissions-disabled-option';

			$( '.' + disabledClass ).removeClass( disabledClass );

			if ( ! $( '#wpforms-panel-field-settings-post_submissions' ).is( ':checked' ) ) {
				return;
			}

			const fieldId = $( '#wpforms-panel-field-settings-post_submissions_featured' ).val();
			const $options = $( '#wpforms-field-option-' + fieldId );

			$options
				.find( '.wpforms-field-option-row-extensions' ).addClass( disabledClass )
				.find( 'input' ).val(  wpforms_builder.post_submissions.image_extensions );
			$options
				.find( '.wpforms-field-option-row-max_file_number' ).addClass( disabledClass )
				.find( 'input' ).val( 1 );
			$options
				.find( '.wpforms-field-option-row-media_library' ).addClass( disabledClass )
				.find( 'input' ).prop( 'checked', 'checked' ).trigger( 'input' );
		},

		/**
		 * Adjust featured image settings on featured image field change.
		 *
		 * @since 1.5.0
		 */
		changeFeaturedImage: function() {

			const fieldId = $( this ).find( 'option:selected' ).val();

			WPFormsPostSubmissions.togglePostSubmissions();

			if ( ! fieldId ) {
				return;
			}

			$.confirm( {
				title: wpforms_builder.heads_up,
				content: wpforms_builder.post_submissions.disabling_options,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},

		/**
		 * If user clicks on disabled media field, show alert.
		 *
		 * @since 1.5.0
		 */
		disabledMediaAlert: function() {

			$.alert( {
				title: wpforms_builder.heads_up,
				content: wpforms_builder.post_submissions.disabled_option,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},
	};

	WPFormsPostSubmissions.init();
}( jQuery ) );
