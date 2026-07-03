/**
 * Shanelle My Account Page Script
 *
 * @package Shanelle
 */

const config = window.shanelleMyAccountPage ?? {};
const i18n = config.i18n ?? {};

/** @type {HTMLElement|null} */
let root = null;

/**
 * @returns {Record<string, unknown>}
 */
function getMyAccountPageState() {
	if ( ! root ) {
		return config.initialState ?? {};
	}

	try {
		return JSON.parse( root.dataset.accountState || '{}' );
	} catch ( error ) {
		return config.initialState ?? {};
	}
}

/**
 * @param {string} message
 */
function announce( message ) {
	const status = root?.querySelector( '[data-shanelle-my-account-page-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * Reveal hydrated account content and hide skeleton placeholders.
 */
function revealAccountContent() {
	if ( ! root ) {
		return;
	}

	root.classList.add( 'is-ready' );

	root.querySelectorAll( '[data-shanelle-account-skeleton-host]' ).forEach( ( host ) => {
		host.hidden = true;
	} );

	root.querySelectorAll( '[data-shanelle-account-order-list], [data-shanelle-account-download-list]' ).forEach( ( list ) => {
		list.hidden = false;
	} );

	if ( i18n.contentReady ) {
		announce( i18n.contentReady );
	}
}

/**
 * @param {HTMLElement|null} element
 */
function initMyAccountPage( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-my-account-page]' );

	if ( ! root || root.dataset.accountHydrated === 'true' ) {
		return;
	}

	root.dataset.accountHydrated = 'true';
	bindMobileNavigation();
	revealAccountContent();

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:my-account-page:ready', {
			bubbles: true,
			detail: {
				root,
				state: getMyAccountPageState(),
				api: {
					getMyAccountPageState,
					announce,
					revealAccountContent,
				},
			},
		} )
	);
}

function bindMobileNavigation() {
	const toggle = root?.querySelector( '[data-shanelle-account-nav-toggle]' );
	const panel = root?.querySelector( '[data-shanelle-account-nav-panel]' );

	if ( ! toggle || ! ( panel instanceof HTMLElement ) ) {
		return;
	}

	const openLabel = i18n.navToggle || 'Account menu';
	const closeLabel = i18n.navToggleClose || 'Close account menu';

	toggle.addEventListener( 'click', () => {
		const isOpen = panel.classList.toggle( 'is-open' );
		toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		toggle.textContent = isOpen ? closeLabel : openLabel;

		if ( isOpen ) {
			announce( openLabel );
		} else {
			announce( closeLabel );
		}
	} );
}

document.querySelectorAll( '[data-shanelle-my-account-page]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initMyAccountPage( element );
	}
} );

export {
	initMyAccountPage,
	getMyAccountPageState,
	announce,
	revealAccountContent,
};
