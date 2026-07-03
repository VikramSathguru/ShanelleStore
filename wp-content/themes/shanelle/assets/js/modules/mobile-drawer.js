/**
 * Mobile drawer navigation module.
 *
 * @package Shanelle
 */

/**
 * @param {HTMLElement} header
 */
export function initMobileDrawer( header ) {
	const toggle = header.querySelector( '[data-menu-toggle]' );
	const drawer = header.querySelector( '[data-mobile-drawer]' );
	const overlay = header.querySelector( '[data-drawer-overlay]' );
	const closeBtn = header.querySelector( '[data-drawer-close]' );

	if ( ! toggle || ! drawer ) {
		return;
	}

	const open = () => {
		drawer.hidden = false;
		drawer.classList.add( 'is-open' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		document.body.style.overflow = 'hidden';
		closeBtn?.focus();
	};

	const close = () => {
		drawer.classList.remove( 'is-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		document.body.style.overflow = '';
		drawer.hidden = true;
		toggle.focus();
	};

	toggle.addEventListener( 'click', open );
	closeBtn?.addEventListener( 'click', close );
	overlay?.addEventListener( 'click', close );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' && drawer.classList.contains( 'is-open' ) ) {
			close();
		}
	} );
}
