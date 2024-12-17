( function( $ ) {

	'use strict';

	class JetWCProductTableSnackbar {

		constructor() {
			this.createContainer();
		}

		createContainer() {

			const css = `
			.jet-wc-product-table-snackbar {
				position: fixed;
				left: 20px;
				bottom: 20px;
				max-width: 550px;
				z-index: 9999;
			}
			.jet-wc-product-table-snackbar__notice {
				display: flex;
				flex-direction: column;
				gap: 20px;
				position: relative;
			}
			.jet-wc-product-table-snackbar button.jet-wc-product-table-snackbar__notice-dismiss {
				position: absolute;
				display: inline-flex;
				padding: 0 0 2px;
				font-size: 28px;
				line-height: 28px;
				left: 100%;
				top: 0;
				width: 36px;
				height: 36px;
				margin: 0 0 0 10px;
				align-items: center;
				justify-content: center;
				box-sizing: border-box;
				border-radius; 3px;
				background: #eee;
				color: #777;
				cursor: pointer;
			}
			.jet-wc-product-table-snackbar button.jet-wc-product-table-snackbar__notice-dismiss:hover {
				color: #111;
			}
			.jet-wc-product-table-snackbar .jet-wc-product-table-snackbar__notice .woocommerce-error,
			.jet-wc-product-table-snackbar .jet-wc-product-table-snackbar__notice .woocommerce-message {
				margin: 0;
			}
			`;
			
			const head = document.head || document.getElementsByTagName('head')[0];
			const style = document.createElement( 'style' );

			head.appendChild(style);

			style.type = 'text/css';
			if ( style.styleSheet ) {
				style.styleSheet.cssText = css;
			} else {
				style.appendChild( document.createTextNode( css ) );
			}

			this.$container = document.createElement( 'div' );
			this.$container.classList.add( 'jet-wc-product-table-snackbar' );

			document.body.appendChild( this.$container );
		}

		addNotice( html, lifespan ) {
			
			const noticeDismiss = document.createElement( 'button' );
			const notice = document.createElement( 'div' );

			noticeDismiss.classList = 'jet-wc-product-table-snackbar__notice-dismiss';
			noticeDismiss.innerHTML = '&times;';
			noticeDismiss.type = 'button';
			
			notice.classList.add( 'jet-wc-product-table-snackbar__notice' );
			notice.innerHTML = html;

			notice.appendChild( noticeDismiss );

			lifespan = lifespan || 10000;

			this.$container.append( notice );

			noticeDismiss.addEventListener( 'click', () => {
				noticeDismiss.remove();
				notice.remove();
			} );

			setTimeout( () => {
				noticeDismiss.remove();
				notice.remove();
			}, lifespan );
		}

	}

	window.JetWCProductTableSnackbar = new JetWCProductTableSnackbar();

} ( jQuery ) );