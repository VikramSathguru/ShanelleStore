/**
 * Shanelle Shop Archive Component
 *
 * @package Shanelle
 */

const config = window.shanelleShopArchive ?? {};
const i18n = config.i18n ?? {};

const FOCUSABLE = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'[tabindex]:not([tabindex="-1"])',
].join( ', ' );

/**
 * @param {HTMLElement} container
 * @returns {(event: KeyboardEvent) => void}
 */
function createFocusTrap( container ) {
	const getFocusable = () => Array.from( container.querySelectorAll( FOCUSABLE ) );

	return ( event ) => {
		if ( event.key !== 'Tab' ) {
			return;
		}

		const focusable = getFocusable();

		if ( ! focusable.length ) {
			return;
		}

		const first = focusable[0];
		const last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	};
}

/**
 * Keep only the active filter surface interactive (desktop sidebar vs mobile sheet).
 *
 * @param {HTMLElement} archive
 */
function syncFilterSurfaces( archive ) {
	const desktop = archive.querySelector( '.shop-archive__sidebar' );
	const mobileRoot = archive.querySelector( '[data-shanelle-filters]' );
	const isDesktop = window.matchMedia( '(min-width: 64rem)' ).matches;

	if ( desktop instanceof HTMLElement ) {
		if ( isDesktop ) {
			desktop.removeAttribute( 'inert' );
			desktop.removeAttribute( 'aria-hidden' );
		} else {
			desktop.setAttribute( 'inert', '' );
			desktop.setAttribute( 'aria-hidden', 'true' );
		}
	}

	if ( mobileRoot instanceof HTMLElement && isDesktop && ! mobileRoot.classList.contains( 'is-open' ) ) {
		mobileRoot.setAttribute( 'inert', '' );
	} else if ( mobileRoot instanceof HTMLElement && ! isDesktop ) {
		mobileRoot.removeAttribute( 'inert' );
	}
}

/**
 * @param {HTMLElement} archive
 */
function initFilters( archive ) {
	const root = archive.querySelector( '[data-shanelle-filters]' );
	const panel = archive.querySelector( '[data-shanelle-filters-panel]' );
	const openBtn = archive.querySelector( '[data-shanelle-filter-open]' );
	const closeBtn = archive.querySelector( '[data-shanelle-filters-close]' );
	const overlay = archive.querySelector( '[data-shanelle-filters-overlay]' );
	const applyBtn = archive.querySelector( '[data-shanelle-filters-apply]' );

	if ( ! root || ! panel || ! openBtn ) {
		return;
	}

	let trapHandler = null;
	let lastFocused = null;

	const open = () => {
		lastFocused = document.activeElement;
		root.hidden = false;
		root.classList.add( 'is-open' );
		root.removeAttribute( 'inert' );
		openBtn.setAttribute( 'aria-expanded', 'true' );
		document.body.classList.add( 'shop-archive-filters-open' );

		trapHandler = createFocusTrap( panel );
		panel.addEventListener( 'keydown', trapHandler );
		closeBtn?.focus();
	};

	const close = ( { restoreFocus = true } = {} ) => {
		root.classList.remove( 'is-open' );
		openBtn.setAttribute( 'aria-expanded', 'false' );
		document.body.classList.remove( 'shop-archive-filters-open' );
		root.hidden = true;

		if ( trapHandler ) {
			panel.removeEventListener( 'keydown', trapHandler );
			trapHandler = null;
		}

		syncFilterSurfaces( archive );

		if ( restoreFocus && lastFocused instanceof HTMLElement ) {
			lastFocused.focus();
		}
	};

	openBtn.addEventListener( 'click', open );
	closeBtn?.addEventListener( 'click', () => {
		close( { restoreFocus: true } );
	} );
	overlay?.addEventListener( 'click', () => {
		close( { restoreFocus: true } );
	} );

	applyBtn?.addEventListener( 'click', () => {
		const mobileForm = archive.querySelector( '#catalog-filters-mobile' );

		setFilteringState( archive, true );

		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:shop-archive:apply-filters', {
				bubbles: true,
				detail: { archive },
			} )
		);

		// Keep the sheet open until navigation completes (avoids page flash/focus jump).
		if ( mobileForm instanceof HTMLFormElement ) {
			if ( typeof mobileForm.requestSubmit === 'function' ) {
				mobileForm.requestSubmit();
			} else {
				mobileForm.submit();
			}
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' && root.classList.contains( 'is-open' ) ) {
			close( { restoreFocus: true } );
		}
	} );

	syncFilterSurfaces( archive );
	window.addEventListener( 'resize', () => {
		syncFilterSurfaces( archive );
	}, { passive: true } );
}

/**
 * Height-stable loading indication (progress bar + live region).
 *
 * @param {HTMLElement} archive
 * @param {boolean} loading
 */
function setFilteringState( archive, loading ) {
	archive.classList.toggle( 'is-filtering', loading );
	archive.toggleAttribute( 'aria-busy', loading );

	const placeholder = archive.querySelector( '[data-shanelle-archive-loading]' );

	if ( placeholder ) {
		placeholder.hidden = ! loading;
		placeholder.setAttribute( 'aria-hidden', loading ? 'false' : 'true' );
	}

	const status = archive.querySelector( '[data-shanelle-archive-status]' );

	if ( status && loading ) {
		status.textContent = i18n.loading || 'Actualizando productos…';
	} else if ( status ) {
		status.textContent = '';
	}
}

/**
 * @param {HTMLElement} archive
 */
function initOrdering( archive ) {
	const orderSelect = archive.querySelector( '#shop-archive-orderby, .shop-archive__ordering select.orderby' );

	if ( ! ( orderSelect instanceof HTMLSelectElement ) ) {
		return;
	}

	orderSelect.id = 'shop-archive-orderby';

	orderSelect.addEventListener( 'change', () => {
		const form = orderSelect.closest( 'form' );

		if ( form instanceof HTMLFormElement ) {
			setFilteringState( archive, true );
			form.submit();
		}
	} );
}

/**
 * Show loading chrome on catalog filter form submit (full-page reload).
 *
 * @param {HTMLElement} archive
 */
function initFilterSubmitLoading( archive ) {
	archive.querySelectorAll( 'form' ).forEach( ( form ) => {
		if ( ! ( form instanceof HTMLFormElement ) ) {
			return;
		}

		form.addEventListener( 'submit', () => {
			setFilteringState( archive, true );
		} );
	} );
}

/**
 * @param {HTMLElement} archive
 */
function initArchive( archive ) {
	initFilters( archive );
	initOrdering( archive );
	initFilterSubmitLoading( archive );

	document.body.addEventListener( 'shanelle:shop-archive:filter-start', () => {
		setFilteringState( archive, true );
	} );

	document.body.addEventListener( 'shanelle:shop-archive:filter-complete', () => {
		setFilteringState( archive, false );
	} );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:shop-archive:ready', {
			bubbles: true,
			detail: { archive },
		} )
	);
}

document.querySelectorAll( '[data-shanelle-shop-archive]' ).forEach( initArchive );

export { initArchive, setFilteringState };
