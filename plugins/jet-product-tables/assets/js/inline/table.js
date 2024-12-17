( function( $ ) {

	'use strict';

	const JetWCProductTable = class {

		constructor() {

			$( document ).on( 'click', '.jet-wc-product-table-col--collpased-title', function( event ) {
				$( this ).toggleClass( 'is-active' );
			} );

			this.initStickyBg();

			if ( window.JetWCProductStickyTableHeader ) {

				$( '.jet-wc-product-table--sticky-header' ).each( ( index, el ) => {

					const $el = $( el );

					const tableClone = document.createElement( 'table' );
					const tableCloneContainer = document.createElement( 'div' );

					tableClone.classList.add( 'jet-wc-product-table-clone' );
					tableCloneContainer.classList.add( 'jet-wc-product-table-clone-container' );
					tableCloneContainer.style.display = 'none';
					tableCloneContainer.appendChild( tableClone );

					$el.closest( '.jet-wc-product-table-wrapper' ).append( tableCloneContainer )

					const stickyTable = new window.JetWCProductStickyTableHeader( el, tableClone );

					if ( window.JetWCProductFilters ) {
						window.JetWCProductFilters.events.subscribe(
							'jet-wc-product-filter/filter-updated',
							( data ) => {
								if ( data.response.is_more ) {
									stickyTable.refreshTable();
								}
							}
						);
						window.JetWCProductFilters.events.subscribe( 'jet-wc-product-filter/filter-apply', () => {
							stickyTable.refreshTable();
						} );
					}
				} );
			}

			$( '.jet-wc-product-table--mobile-collapsed.jet-wc-product-table--dir-vertical' ).each( ( index, el ) => {
				this.processLayoutSwitch( el, true );
			} );

			$( '.jet-wc-product-table--mobile-transform.jet-wc-product-table--dir-vertical' ).each( ( index, el ) => {
				this.processLayoutSwitch( el, true );
			} );

		}

		processLayoutSwitch( table, withHeadings ) {

			withHeadings = withHeadings || false;

			let tbody = false;

			for ( const child of table.children ) {
				if ( 'TBODY' === child.tagName ) {
					tbody = child;
					break;
				}
			}

			const newBody     = document.createElement( 'tbody' );
			const newHead     = document.createElement( 'thead' );
			const newRows     = [];

			let headingClass = 'jet-wc-product-table-col--collpased-title';
			let bodyClass = 'jet-wc-product-table-col--collpased-title';

			if ( tbody ) {
				for ( var i = 0; i < tbody.children.length; i++ ) {

					const tr = tbody.children[ i ];
					let colName = '';
					const trClasses = tr.classList;

					for ( var j = 0; j < tr.children.length; j++ ) {
						
						const td = tr.children[ j ];

						if ( 0 === j ) {

							colName = td.innerText;
							const newTh = document.createElement( 'th' );

							const thClasses = td.classList;

							thClasses.forEach( className => {
								newTh.classList.add( className );
							});

							newTh.classList.add( headingClass );
							headingClass = 'jet-wc-product-table-col--collpased-body';

							newTh.innerText = colName;
							newHead.appendChild( newTh );
							continue;

						}

						const newTd = document.createElement( 'td' );

						if ( ! newRows[ j - 1 ] ) {
							
							newRows[ j - 1 ] = document.createElement( 'tr' );

							trClasses.forEach( className => {
								newRows[ j - 1 ].classList.add( className );
							});

						}

						newTd.innerHTML = td.innerHTML;
						newTd.setAttribute( 'data-column-name', colName );

						const tdClasses = td.classList;

						tdClasses.forEach( className => {
							if ( 'jet-wc-product-table-col--collpased-body' !== className 
								&& 'jet-wc-product-table-col--collpased-title' !== className
							) {
								newTd.classList.add( className );
							}
						});

						newTd.classList.add( bodyClass );

						newRows[ j - 1 ].appendChild( newTd );

					}

					bodyClass = 'jet-wc-product-table-col--collpased-body';

				}

				for ( var i = 0; i < newRows.length; i++) {
					newBody.appendChild( newRows[ i ] );
				}

				if ( window.innerWidth <= 767 ) {

					table.innerHTML = '';

					if ( withHeadings ) {
						table.appendChild( newHead );
					}

					table.appendChild( newBody );

				}

				window.addEventListener( 'resize', this.debounce( () => {
					if ( window.innerWidth <= 767 ) {

						table.innerHTML = '';

						if ( withHeadings ) {
							table.appendChild( newHead );
						}

						table.appendChild( newBody );

					} else {
						table.innerHTML = '';
						table.appendChild( tbody );
					}
				}, 100 ) );

			}
		}

		debounce( func, wait ) {
			let timeout;
			return ( ...args ) => {
				clearTimeout( timeout );
				timeout = setTimeout( () => func.apply( this, args ), wait );
			};
		}

		initStickyBg() {
			const bodyBg = $( 'body' ).css( 'background-color' );
			$( '.jet-wc-product-table.jet-wc-product-table--sticky-header' ).find( 'thead' ).css( 'background-color', bodyBg );
		}

	}

	$( () => {
		new JetWCProductTable();
	} );
}( jQuery ) );
