/**
 * Shanelle Product Information Component
 *
 * @package Shanelle
 */

const config = window.shanelleProductInformation ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} root
 * @returns {Record<string, unknown>}
 */
function getInformationData( root ) {
	try {
		return JSON.parse( root.dataset.informationJson || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLButtonElement} trigger
 * @returns {string}
 */
function getTriggerLabel( trigger ) {
	return trigger.querySelector( '.product-information__trigger-label' )?.textContent?.trim()
		|| trigger.textContent?.trim()
		|| '';
}

/**
 * @param {HTMLButtonElement} trigger
 * @param {boolean} expanded
 */
function setTriggerState( trigger, expanded ) {
	trigger.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
	trigger.setAttribute(
		'aria-label',
		`${ getTriggerLabel( trigger ) } — ${ expanded ? i18n.collapseSection || 'Contraer sección' : i18n.expandSection || 'Expandir sección' }`
	);
}

/**
 * @param {HTMLButtonElement} trigger
 */
function toggleSection( trigger ) {
	const panelId = trigger.getAttribute( 'aria-controls' );
	const panel = panelId ? document.getElementById( panelId ) : null;
	const accordion = trigger.closest( '[data-shanelle-information-accordion]' );
	const root = trigger.closest( '[data-shanelle-product-information]' );
	const expanded = trigger.getAttribute( 'aria-expanded' ) === 'true';

	if ( ! ( panel instanceof HTMLElement ) || ! accordion || ! ( root instanceof HTMLElement ) ) {
		return;
	}

	accordion.querySelectorAll( '[data-shanelle-information-trigger]' ).forEach( ( otherTrigger ) => {
		if ( ! ( otherTrigger instanceof HTMLButtonElement ) || otherTrigger === trigger ) {
			return;
		}

		const otherPanelId = otherTrigger.getAttribute( 'aria-controls' );
		const otherPanel = otherPanelId ? document.getElementById( otherPanelId ) : null;

		setTriggerState( otherTrigger, false );

		if ( otherPanel instanceof HTMLElement ) {
			otherPanel.hidden = true;
		}
	} );

	setTriggerState( trigger, ! expanded );
	panel.hidden = expanded;

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-information:toggle', {
			bubbles: true,
			detail: {
				root,
				trigger,
				panel,
				sectionId: trigger.dataset.sectionId || '',
				expanded: ! expanded,
			},
		} )
	);
}

/**
 * @param {HTMLButtonElement} trigger
 * @param {KeyboardEvent} event
 */
function handleTriggerKeydown( trigger, event ) {
	const accordion = trigger.closest( '[data-shanelle-information-accordion]' );

	if ( ! accordion ) {
		return;
	}

	const triggers = Array.from( accordion.querySelectorAll( '[data-shanelle-information-trigger]' ) )
		.filter( ( item ) => item instanceof HTMLButtonElement );
	const index = triggers.indexOf( trigger );

	if ( index < 0 ) {
		return;
	}

	if ( event.key === 'ArrowDown' ) {
		event.preventDefault();
		triggers[ index + 1 ]?.focus();
	}

	if ( event.key === 'ArrowUp' ) {
		event.preventDefault();
		triggers[ index - 1 ]?.focus();
	}

	if ( event.key === 'Home' ) {
		event.preventDefault();
		triggers[ 0 ]?.focus();
	}

	if ( event.key === 'End' ) {
		event.preventDefault();
		triggers[ triggers.length - 1 ]?.focus();
	}

	if ( event.key === 'Enter' || event.key === ' ' ) {
		event.preventDefault();
		toggleSection( trigger );
	}
}

/**
 * @param {HTMLElement} root
 */
function initAccordion( root ) {
	root.querySelectorAll( '[data-shanelle-information-trigger]' ).forEach( ( trigger ) => {
		if ( ! ( trigger instanceof HTMLButtonElement ) ) {
			return;
		}

		setTriggerState( trigger, trigger.getAttribute( 'aria-expanded' ) === 'true' );

		trigger.addEventListener( 'click', () => {
			toggleSection( trigger );
		} );

		trigger.addEventListener( 'keydown', ( event ) => {
			if ( event instanceof KeyboardEvent ) {
				handleTriggerKeydown( trigger, event );
			}
		} );
	} );
}

/**
 * @param {HTMLElement} root
 */
function initProductInformation( root ) {
	if ( root.dataset.informationHydrated === 'true' ) {
		return;
	}

	root.dataset.informationHydrated = 'true';

	initAccordion( root );

	const data = getInformationData( root );
	const sections = Array.from( root.querySelectorAll( '[data-shanelle-information-section]' ) );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-information:ready', {
			bubbles: true,
			detail: {
				root,
				data,
				sections,
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-information]' ).forEach( initProductInformation );

export {
	initProductInformation,
	getInformationData,
	toggleSection,
	setTriggerState,
};
