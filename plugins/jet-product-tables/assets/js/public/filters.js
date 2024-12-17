( function( $ ) {

	'use strict';

	class JetWCProductFiltersUISelect {

		constructor( $el ) {
			this.$el = $el;
		}

		getValue() {
			return this.$el.val();
		}

		reset() {
			this.$el.val( '' );
		}

	}

	class JetWCProductFiltersUISearch {

		constructor( $el ) {

			this.$el     = $el;
			this.$button = this.$el.closest( '.jet-wc-product-filter-block' ).find( '.jet-wc-search-button' )
			this.options = {};

			this.options.searchOnTyping        = this.$el.data( 'search-on-typing' );
			this.options.minCharsToStartSearch = parseInt( this.$el.data( 'min-chars-to-start-search' ), 10 ) || 0;

			if ( this.options.searchOnTyping && this.options.minCharsToStartSearch ) {

				this.$el.on( 'input', () => {
					this.triggerSearch();
				} );

			}

			this.$el.on( 'search', () => {
				this.triggerSearch();
			} );

			if ( this.$button.length ) {
				this.$button.on( 'click', () => {
					this.triggerSearch();
				} );
			}
		}

		getValue() {
			return this.$el.val();
		}

		reset() {
			this.$el.val( '' );
		}

		eventSupported( event ) {

			if ( 'change' === event.type ) {
				return false;
			}

			return true;
		}

		triggerSearch( reset ) {

			reset = reset || false;

			if ( reset || this.getValue().length === 0 ) {
				this.$el.trigger( 'applyFilters', { reset: true } );
			} else {

				if ( this.options.searchOnTyping && this.options.minCharsToStartSearch ) {

					const value = this.getValue();

					if ( value && value.length >= this.options.minCharsToStartSearch ) {
						this.$el.trigger( 'applyFilters' );
					}
				} else {
					this.$el.trigger( 'applyFilters' );
				}

			}
		}

	}

	class JetWCProductFiltersEvents {

		constructor() {
			this.observers = {};
		}

		subscribe( namespace, func ) {

			if ( ! this.observers[ namespace ] ) {
				this.observers[ namespace ] = [];
			}

			this.observers[ namespace ].push( func );

		}

		unsubscribe( namespace, func ) {

			if ( ! func ) {
				this.observers[ namespace ] = {};
			}

			this.observers[ namespace ] = this.observers[ namespace ].filter( ( observer ) => observer !== func );
		}

		dispatch( namespace, data ) {

			if ( this.observers[ namespace ] ) {
				this.observers[ namespace ].forEach( ( observer ) => observer( data ) );
			}

		}

	}

	class JetWCProductFilters {

		constructor() {

			this.entries = [];

			this.selectors = {
				tableWrapper: '.jet-wc-product-table-container',
				table: '.jet-wc-product-table',
				filters: '.jet-wc-product-filters',
				filter: '.jet-wc-product-filter',
				activeTags: '.jet-wc-product-active-tags',
				removeTag: '.jet-wc-product-active-tag__remove',
				resetFilters: '.jet-wc-product-filters-reset',
				sortBlock: '.jet-wc-product-table-sort',
				sortButton: '.jet-wc-product-table-sort__button',
				searchButton: '.jet-wc-product-filter-search-button',
				pagerButton: '.jet-wc-product-pager__item',
				pagerBlock: '.jet-wc-product-pager-block',
				moreButton: '.jet-wc-product-table-more',
				moreButtonBlock: '.jet-wc-product-more-block',
			}

			this.currentRequest = {};

			this.events = new JetWCProductFiltersEvents();
			this.events.subscribe( 'jet-wc-product-filter/filter-apply', ( data ) => this.applyFilters( data ) );

			this.initFilterEntries();
			this.initFiltersReset();
			this.initSorting();
			this.initLazyLoad();
			this.initPager();

		}

		initLazyLoad() {
			for ( var i = 0; i < this.entries.length; i++ ) {
				if ( this.entries[ i ].$table && this.entries[ i ].$table.hasClass( 'jet-wc-product-table--lazy' ) ) {

					this.events.dispatch( 'jet-wc-product-filter/filter-apply', {
						entry: this.entries[ i ],
						$el: this.entries[ i ].$table,
						value: false,
					} );

				}
			}
		}

		initPager() {

			// Pager
			jQuery( document ).on( 'click', this.selectors.pagerButton, ( event ) => {

				event.preventDefault();

				const $el = $( event.currentTarget );

				if ( ! $el.attr( 'href' ) ) {
					return;
				}

				const entry = this.getEntryForEl( $el );

				if ( ! entry ) {
					return;
				}

				entry.page = $el.attr( 'href' ).replace( '#', '' );

				this.events.dispatch( 'jet-wc-product-filter/filter-apply', {
					entry: entry,
					$el: $el,
					value: false,
				} );
			} );

			// Load more
			jQuery( document ).on( 'click', this.selectors.moreButton, ( event ) => {

				event.preventDefault();

				const $el   = $( event.currentTarget );
				const entry = this.getEntryForEl( $el );

				entry.page = $el.data( 'page' );
				entry.isMore = true;

				this.events.dispatch( 'jet-wc-product-filter/filter-apply', {
					entry: entry,
					$el: $el,
					value: false,
				} );
			} );

		}

		initSorting() {

			jQuery( document ).on( 'click', this.selectors.sortButton, ( event ) => {

				event.preventDefault();

				const $el        = $( event.currentTarget );
				const $sortblock = $el.closest( this.selectors.sortBlock );
				const entry      = this.getEntryForEl( $sortblock );
				const isActive   = $el.hasClass( 'jet-wc-product-table-sort__button-active' );

				entry.$container.find( '.jet-wc-product-table-sort__button-active' ).removeClass( 'jet-wc-product-table-sort__button-active' );

				if ( ! isActive ) {

					entry.sort = {
						order_by: $sortblock.data( 'column' ),
						order: $el.data( 'order' ),
					};

					$el.addClass( 'jet-wc-product-table-sort__button-active' );

				} else {
					entry.sort = {};
				}


				this.events.dispatch( 'jet-wc-product-filter/filter-apply', {
					entry: entry,
					$el: $el,
					value: false,
				} );
			} );
		}

		getFiltersEl( $el ) {
			return this.getContainerEl( $el ).find( this.selectors.filters );
		}

		getContainerEl( $el ) {
			return $el.closest( this.selectors.tableWrapper );
		}

		getEntryForEl( $el ) {

			let $container = this.getContainerEl( $el );
			let entryID    = $container.data( 'uid' );

			return this.getEntryByID( entryID, 'uid' );
		}

		initFiltersReset() {

			jQuery( document ).on( 'click', this.selectors.removeTag, ( event ) => {

				event.preventDefault();

				let $el   = $( event.target );
				let entry = this.getEntryForEl( $el );

				if ( entry ) {

					let filterVar   = $el.data( 'filter' );
					let filterValue = $el.data( 'value' );
					let filter      = this.getFilterByVar( filterVar, entry.filters );

					// reset page to first on reseting any filter
					entry.page = 1;

					if ( filter ) {

						this.removeEntryQueryVar( filterVar, filterValue, entry );

						filter.ui.reset();

						this.events.dispatch( 'jet-wc-product-filter/filter-apply', {
							entry: entry,
							$el: filter.$el,
							value: null,
						} );

					} else if ( ! filterVar && ! filterValue ) {

						// reset all
						entry.query = {};
						entry.filters.forEach( ( filter ) => {
							filter.ui.reset();
						} );

						this.events.dispatch( 'jet-wc-product-filter/filter-apply', {
							entry: entry,
							$el: entry.$filters,
							value: null,
						} );
					}

				}

			} );

		}

		removeEntryQueryVar( filterVar, filterValue, entry ) {

			if ( ! entry.query || ! entry.query[ filterVar ] ) {
				return
			}

			if ( entry.query[ filterVar ] == filterValue ) {
				delete entry.query[ filterVar ];
			}

			if ( entry.query[ filterVar ] && entry.query[ filterVar ].length ) {
				entry.query[ filterVar ] = entry.query[ filterVar ].filter( ( item ) => {
					return item != filterValue;
				} );
			}

		}

		getFilterByVar( filterVar, filters ) {

			filters = filters || [];

			for ( var i = 0; i < filters.length; i++ ) {
				if ( filterVar === filters[ i ].filterVar ) {
					return filters[ i ];
				}
			}

			return false;

		}

		getEntryByID( entryID, IDprop ) {

			IDprop = IDprop || 'entryID'

			for ( var i = 0; i < this.entries.length; i++ ) {
				if ( entryID === this.entries[ i ][ IDprop ] ) {
					return this.entries[ i ];
				}
			}

			return false;
		}

		initFilterEntries() {

			this.entries = [];

			$( this.selectors.tableWrapper ).each( ( index, el ) => {

				let $container  = $( el );
				let $filters    = $container.find( this.selectors.filters );
				let $table      = $container.find( this.selectors.table );
				let $activeTags = $container.find( this.selectors.activeTags );
				let $pager      = $container.find( this.selectors.pagerBlock );
				let $more       = $container.find( this.selectors.moreButtonBlock );

				const entry = {
					entryID: $container.data( 'entry-id' ),
					uid: $container.data( 'uid' ),
					$container: $container,
					$filters: $filters,
					$table: $table,
					$activeTags: $activeTags,
					$pager: $pager,
					$more: $more,
					tableData: $container.data( 'entry' ),
					signature: $container.data( 'signature' ),
					filters: [],
					query: {},
					sort: {},
				}

				if ( $filters.length ) {
					$filters.find( this.selectors.filter ).each( ( filterFndex, filterEl ) => {

						const $filterEl = $( filterEl );
						const uiType    = filterEl.dataset.ui;

						entry.filters.push( {
							filterVar: $filterEl.attr( 'name' ),
							$el: $filterEl,
							ui: this.getUI( filterEl.dataset.ui, $filterEl )
						} );
					} );
				}

				this.entries.push( entry );

			} );

			for ( var i = 0; i < this.entries.length; i++ ) {
				if ( this.entries[ i ].filters.length ) {
					for ( var j = 0; j < this.entries[ i ].filters.length; j++ ) {

						let entry  = this.entries[ i ];
						let filter = this.entries[ i ].filters[ j ];

						filter.$el.on( 'change applyFilters', ( event ) => {

							if ( ! this.isValidEvent( event, filter.ui ) ) {
								return;
							}

							event.preventDefault();

							let value = filter.ui.getValue();
							let name  = filter.$el.attr( 'name' );

							entry.query[ name ] = value;

							// reset page to first on reseting any filter
							entry.page = 1;

							this.events.dispatch( 'jet-wc-product-filter/filter-apply', {
								entry: entry,
								$el: filter.$el,
								value: filter.ui.getValue()
							} );

						} );

					}
				}
			}

		}

		isValidEvent( event, ui ) {

			if ( 'function' !== typeof ui.eventSupported ) {
				return true;
			}

			return ui.eventSupported( event );
		}

		getUI( uiType, $el ) {
			switch ( uiType ) {
				case 'select':
					return new JetWCProductFiltersUISelect( $el );
				case 'search':
					return new JetWCProductFiltersUISearch( $el );
			}
		}

		lockEntry( entry ) {
			entry.$container.css( {
				opacity: 0.5,
				pointerEvents: 'none',
			} );
		}

		unlockEntry( entry ) {
			entry.$container.css( {
				opacity: 1,
				pointerEvents: 'auto',
			} );
		}

		applyFilters( data ) {

			this.lockEntry( data.entry );

			if ( this.currentRequest[ data.entry.uid ] ) {
				this.currentRequest[ data.entry.uid ].abort();
			}

			this.currentRequest[ data.entry.uid ] = $.ajax( {
				url: window.JetWCProductFiltersData.apiURL,
				type: 'POST',
				dataType: 'json',
				data: {
					query: data.entry.query,
					table: data.entry.tableData,
					signature: data.entry.signature,
					sort: data.entry.sort,
					page: data.entry.page,
					is_more: data.entry.isMore
				},
			} ).always( () => {
				this.unlockEntry( data.entry );
				data.entry.isMore = false;
				delete this.currentRequest[ data.entry.uid ];
			} ).done( ( response ) => {
				try {

					const $body = data.entry.$table.find( 'tbody' );

					if ( response.is_more ) {
						$body.append( response.body );
					} else {
						$body.html( response.body );
					}

					data.entry.$activeTags.html( response.active_tags );

					if ( data.entry.$pager.length ) {
						data.entry.$pager.html( response.pager );
					}

					if ( data.entry.$more.length ) {
						data.entry.$more.html( response.more );
					}

					this.events.dispatch( 'jet-wc-product-filter/filter-updated', {
						input: data,
						response: response
					} );

				} catch ( error ) {
					window.JetWCProductTableSnackbar.addNotice( error );
				}
			} ).fail( ( response, errorCode, errorText ) => {
				if ( 'abort' === errorCode ) {
					return;
				}

				if ( response.responseJSON && response.responseJSON.data ) {
					errorText = response.responseJSON.data;
				}

				window.JetWCProductTableSnackbar.addNotice( '<div class="woocommerce-error">' + errorText + '</div>' );
			} );
		}
	}

	window.JetWCProductFilters = new JetWCProductFilters();

}( jQuery ) );
