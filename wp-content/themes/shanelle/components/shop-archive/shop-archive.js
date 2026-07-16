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
		openBtn.setAttribute( 'aria-expanded', 'true' );
		document.body.classList.add( 'shop-archive-filters-open' );

		trapHandler = createFocusTrap( panel );
		panel.addEventListener( 'keydown', trapHandler );
		closeBtn?.focus();
	};

	const close = () => {
		root.classList.remove( 'is-open' );
		openBtn.setAttribute( 'aria-expanded', 'false' );
		document.body.classList.remove( 'shop-archive-filters-open' );
		root.hidden = true;

		if ( trapHandler ) {
			panel.removeEventListener( 'keydown', trapHandler );
			trapHandler = null;
		}

		if ( lastFocused instanceof HTMLElement ) {
			lastFocused.focus();
		}
	};

	openBtn.addEventListener( 'click', open );
	closeBtn?.addEventListener( 'click', close );
	overlay?.addEventListener( 'click', close );

	applyBtn?.addEventListener( 'click', () => {
		const mobileForm = archive.querySelector( '#catalog-filters-mobile' );

		if ( mobileForm instanceof HTMLFormElement ) {
			if ( typeof mobileForm.requestSubmit === 'function' ) {
				mobileForm.requestSubmit();
			} else {
				mobileForm.submit();
			}
		}

		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:shop-archive:apply-filters', {
				bubbles: true,
				detail: { archive },
			} )
		);
		close();
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' && root.classList.contains( 'is-open' ) ) {
			close();
		}
	} );
}

/**
 * Placeholder loading state for future AJAX filtering.
 *
 * @param {HTMLElement} archive
 * @param {boolean} loading
 */
function setFilteringState( archive, loading ) {
	archive.classList.toggle( 'is-filtering', loading );

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
