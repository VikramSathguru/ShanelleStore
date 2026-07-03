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
 * @param {HTMLElement|null} element
 */
function initMyAccountPage( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-my-account-page]' );

	if ( ! root || root.dataset.accountHydrated === 'true' ) {
		return;
	}

	root.dataset.accountHydrated = 'true';
	bindMobileNavigation();

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:my-account-page:ready', {
			bubbles: true,
			detail: {
				root,
				state: getMyAccountPageState(),
				api: {
					getMyAccountPageState,
					announce,
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
};
