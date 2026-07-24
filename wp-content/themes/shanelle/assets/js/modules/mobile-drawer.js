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
	const panel = header.querySelector( '[data-drawer-panel]' );

	if ( ! toggle || ! drawer || ! panel ) {
		return;
	}

	/** @type {HTMLElement | null} */
	let lastFocused = null;

	const getFocusable = () => {
		const nodes = panel.querySelectorAll(
			'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
		);

		return Array.from( nodes ).filter(
			( el ) => el instanceof HTMLElement && ! el.hasAttribute( 'disabled' ) && el.offsetParent !== null
		);
	};

	const trapFocus = ( event ) => {
		if ( event.key !== 'Tab' || ! drawer.classList.contains( 'is-open' ) ) {
			return;
		}

		const focusable = getFocusable();

		if ( focusable.length === 0 ) {
			event.preventDefault();
			panel.focus();
			return;
		}

		const first = focusable[ 0 ];
		const last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	};

	const open = () => {
		lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : toggle;
		drawer.hidden = false;
		drawer.classList.add( 'is-open' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		document.body.style.overflow = 'hidden';

		if ( ! panel.hasAttribute( 'tabindex' ) ) {
			panel.setAttribute( 'tabindex', '-1' );
		}

		const focusable = getFocusable();
		( closeBtn instanceof HTMLElement ? closeBtn : focusable[ 0 ] || panel ).focus();
	};

	const close = () => {
		drawer.classList.remove( 'is-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		document.body.style.overflow = '';
		drawer.hidden = true;

		const restore = lastFocused instanceof HTMLElement && document.contains( lastFocused )
			? lastFocused
			: toggle;
		restore.focus();
		lastFocused = null;
	};

	toggle.addEventListener( 'click', open );
	closeBtn?.addEventListener( 'click', close );
	overlay?.addEventListener( 'click', close );

	document.addEventListener( 'keydown', ( event ) => {
		if ( ! drawer.classList.contains( 'is-open' ) ) {
			return;
		}

		if ( event.key === 'Escape' ) {
			close();
			return;
		}

		trapFocus( event );
	} );
}
