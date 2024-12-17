( function( $ ) {

	'use strict';

	class JetWCProductTableVariationHandler {

		constructor( $el ) {

			this.$container     = $el;
			this.$controls      = this.$container.find( '.jet-wc-product-table-variation-select' );
			this.$price         = this.$container.find( '.jet-wc-product-table-variation-price' );
			this.$qty           = this.$container.find( '.jet-wc-product-qty' );
			this.$button        = this.$container.find( '.add_to_cart_button' );
			this.$forwardButton = this.$container.find( '.wc-forward' );
			this.selectedAttrs  = {};

			this.$controls.each( ( index, el ) => {
				const $el = $( el )
				this.selectedAttrs[ $el.attr( 'name' ) ] = $el.val();
			} );

			this.$button.on( 'click', ( event ) => {

				event.preventDefault();

				const data = this.$button.data();

				data['add-to-cart'] = data.add_to_cart;

				this.$button.removeClass( 'added' );
				this.$button.addClass( 'loading' );

				$.ajax( {
					url: this.$button.attr( 'href' ),
					type: 'POST',
					dataType: 'json',
					data: data,
				}).always( () => {
					this.$button.removeClass( 'loading' );
				}).done( ( response ) => {

					if ( response.success ) {
						this.$button.addClass( 'added' );
						this.$forwardButton.show();
					} else {
						this.$forwardButton.hide();
						window.JetWCProductTableSnackbar.addNotice( response.data );
					}

				}).fail( () => {
					this.$forwardButton.hide();
				});

			});

			$( document ).on( 'change', '.jet-wc-product-table-variation-select', ( event ) => {

				const $select = $( event.currentTarget );
				this.selectedAttrs[ $select.attr( 'name' ) ] = $select.val();
				this.$button.data( $select.attr( 'name' ), $select.val() );
				this.handleChange();

			} );
		}

		handleChange() {

			const matchingVariation = this.findMatchingVariation();

			if ( ! matchingVariation || ! this.allSelected() ) {
				this.$price.html( ' &nbsp;' );
				this.$button.addClass( 'jet-wc-product-table-button-disabled' ).data( 'variation_id', 0 );
				return;
			}

			this.$price.html( matchingVariation.price_html );
			this.$button.removeClass( 'jet-wc-product-table-button-disabled' ).data( 'variation_id', matchingVariation.variation_id );

		}

		allSelected() {

			for ( let attr in this.selectedAttrs ) {
				if ( '' == this.selectedAttrs[ attr ] ) {
					return false;
				}
			}

			return true;
		}

		findMatchingVariation() {

			const variations = this.$container.data( 'product_variations' );
			const matching   = [];

			for ( var i = 0; i < variations.length; i++ ) {

				const variation = variations[ i ];

				if ( this.isMatch( variation.attributes, this.selectedAttrs ) ) {
					matching.push( variation );
				}
			}

			if ( ! matching.length ) {
				return false;
			}

			return matching.shift();

		}

		isMatch( variation_attributes, attributes ) {

			var match = true;

			for ( var attr_name in variation_attributes ) {
				if ( variation_attributes.hasOwnProperty( attr_name ) ) {

					var val1 = variation_attributes[ attr_name ];
					var val2 = attributes[ attr_name ];

					if ( val1 !== undefined && val2 !== undefined && val1.length !== 0 && val2.length !== 0 && val1 !== val2 ) {
						match = false;
					}
				}
			}

			return match;
		}

	}

	const $document = $( document );

	$document.on( 'change', '.jet-wc-product-qty', function( event ) {
		const $this = $( this );
		const $btn = $this.closest( '.jet-wc-product-table-col' ).find( '.button' ).data( 'quantity', $this.val() ).attr( 'data-quantity', $this.val() );
	});

	const JetWCProductTableVariationInit = function() {
		$( '.jet-wc-product-table-variation' ).each( ( index, el ) => {
			new JetWCProductTableVariationHandler( $( el ) );
		} );
	}

	JetWCProductTableVariationInit();

	$( window ).load( function() {
		if ( window.JetWCProductFilters ) {
			window.JetWCProductFilters.events.subscribe( 'jet-wc-product-filter/filter-updated', ( data ) => {
				JetWCProductTableVariationInit();
			} );
		}
	} );

}( jQuery ) );
