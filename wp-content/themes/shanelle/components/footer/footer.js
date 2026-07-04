/**
 * Shanelle Footer Component
 *
 * @package Shanelle
 */

const config = window.shanelleFooter ?? {};
const i18n = config.i18n ?? {};

/** @type {HTMLElement|null} */
let root = null;

const DESKTOP_QUERY = window.matchMedia( '(min-width: 48rem)' );

/**
 * @param {string} message
 */
function announce( message ) {
	const status = root?.querySelector( '[data-shanelle-footer-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @param {HTMLElement} menu
 * @param {boolean} expanded
 */
function setMenuExpanded( menu, expanded ) {
	const toggle = menu.querySelector( '[data-shanelle-footer-menu-toggle]' );
	const panel = menu.querySelector( '[data-shanelle-footer-menu-panel]' );

	menu.classList.toggle( 'is-open', expanded );

	if ( toggle instanceof HTMLButtonElement ) {
		toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
		toggle.setAttribute(
			'aria-label',
			expanded
				? ( i18n.menuCollapse || 'Collapse menu' )
				: ( i18n.menuExpand || 'Expand menu' )
		);
	}

	if ( panel instanceof HTMLElement ) {
		panel.hidden = ! expanded;
	}
}

/**
 * Sync accordion panels for the current viewport.
 */
function syncMenuPanels() {
	if ( ! root ) {
		return;
	}

	root.querySelectorAll( '[data-shanelle-footer-menu]' ).forEach( ( menu ) => {
		if ( ! ( menu instanceof HTMLElement ) ) {
			return;
		}

		if ( DESKTOP_QUERY.matches ) {
			setMenuExpanded( menu, true );
			return;
		}

		setMenuExpanded( menu, menu.classList.contains( 'is-open' ) );
	} );
}

/**
 * @param {HTMLFormElement} form
 */
function handleNewsletterSubmit( form ) {
	const input = form.querySelector( 'input[type="email"]' );
	const message = form.querySelector( '[data-shanelle-footer-newsletter-message]' );

	if ( ! ( input instanceof HTMLInputElement ) || ! ( message instanceof HTMLElement ) ) {
		return;
	}

	const email = input.value.trim();
	const isValid = input.checkValidity();

	message.hidden = false;
	message.classList.remove( 'is-success', 'is-error' );

	if ( ! isValid ) {
		message.textContent = i18n.newsletterInvalid || 'Please enter a valid email address.';
		message.classList.add( 'is-error' );
		announce( message.textContent );
		input.focus();
		return;
	}

	message.textContent = i18n.newsletterSuccess || 'Thanks for subscribing. We will be in touch soon.';
	message.classList.add( 'is-success' );
	announce( message.textContent );
	form.classList.add( 'is-submitted' );
	input.value = '';
}

/**
 * @param {Event} event
 */
function handleDocumentClick( event ) {
	const target = event.target;

	if ( ! ( target instanceof Element ) || ! root ) {
		return;
	}

	const toggle = target.closest( '[data-shanelle-footer-menu-toggle]' );

	if ( toggle instanceof HTMLButtonElement ) {
		const menu = toggle.closest( '[data-shanelle-footer-menu]' );

		if ( menu instanceof HTMLElement && ! DESKTOP_QUERY.matches ) {
			event.preventDefault();
			setMenuExpanded( menu, ! menu.classList.contains( 'is-open' ) );
		}
	}
}

/**
 * @param {HTMLElement|null} element
 */
function initFooter( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-footer]' );

	if ( ! root || root.dataset.footerHydrated === 'true' ) {
		return;
	}

	root.dataset.footerHydrated = 'true';

	const newsletterForm = root.querySelector( '[data-shanelle-footer-newsletter]' );

	if ( newsletterForm instanceof HTMLFormElement ) {
		newsletterForm.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			handleNewsletterSubmit( newsletterForm );
		} );
	}

	syncMenuPanels();
	DESKTOP_QUERY.addEventListener( 'change', syncMenuPanels );
	document.addEventListener( 'click', handleDocumentClick );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:footer:ready', {
			bubbles: true,
			detail: {
				root,
				state: config.initialState ?? {},
				api: {
					announce,
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-footer]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initFooter( element );
	}
} );

export {
	initFooter,
	announce,
};
