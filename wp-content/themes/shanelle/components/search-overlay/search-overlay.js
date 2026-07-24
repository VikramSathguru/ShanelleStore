/**
 * Shanelle Search Overlay Component
 *
 * @package Shanelle
 */

const config = window.shanelleSearchOverlay ?? {};
const i18n = config.i18n ?? {};
const settings = config.initialState?.settings ?? {};

/** @type {HTMLElement|null} */
let root = null;

/** @type {HTMLElement|null} */
let dialog = null;

/** @type {HTMLInputElement|null} */
let input = null;

/** @type {HTMLElement|null} */
let results = null;

/** @type {HTMLElement|null} */
let lastFocusedElement = null;

/** @type {boolean} */
let isOpen = false;

/** @type {boolean} */
let isLoading = false;

/** @type {number} */
let debounceTimer = 0;

/** @type {number} */
let activeIndex = -1;

/** @type {AbortController|null} */
let requestController = null;

const RECENT_KEY = settings.recent_storage_key || 'shanelle_recent_searches';
const RECENT_LIMIT = Number( settings.recent_limit || 5 );
const MIN_QUERY_LENGTH = Number( settings.min_query_length || 2 );
const DEBOUNCE_MS = Number( settings.debounce_ms || 300 );

const FOCUSABLE_SELECTOR = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled])',
	'textarea:not([disabled])',
	'select:not([disabled])',
	'[tabindex]:not([tabindex="-1"])',
].join( ', ' );

/**
 * @returns {Record<string, unknown>}
 */
function getOverlayState() {
	if ( ! root ) {
		return config.initialState ?? {};
	}

	try {
		return JSON.parse( root.dataset.searchState || '{}' );
	} catch ( error ) {
		return config.initialState ?? {};
	}
}

/**
 * @param {string} message
 */
function announce( message ) {
	const status = root?.querySelector( '[data-shanelle-search-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @returns {string[]}
 */
function getRecentSearches() {
	try {
		const stored = localStorage.getItem( RECENT_KEY );
		const parsed = stored ? JSON.parse( stored ) : [];

		return Array.isArray( parsed )
			? parsed.filter( ( item ) => typeof item === 'string' && item.trim() !== '' ).slice( 0, RECENT_LIMIT )
			: [];
	} catch ( error ) {
		return [];
	}
}

/**
 * @param {string[]} items
 */
function setRecentSearches( items ) {
	try {
		localStorage.setItem( RECENT_KEY, JSON.stringify( items.slice( 0, RECENT_LIMIT ) ) );
	} catch ( error ) {
		// Ignore storage failures in private mode.
	}
}

/**
 * @param {string} query
 */
function rememberSearch( query ) {
	const normalized = query.trim();

	if ( normalized.length < MIN_QUERY_LENGTH ) {
		return;
	}

	const next = [ normalized, ...getRecentSearches().filter( ( item ) => item.toLowerCase() !== normalized.toLowerCase() ) ];
	setRecentSearches( next );
	renderRecentSearches();
}

/**
 * Render recent search chips from localStorage.
 */
function renderRecentSearches() {
	const section = root?.querySelector( '[data-shanelle-search-recent]' );
	const list = root?.querySelector( '[data-shanelle-search-recent-list]' );
	const clearButton = root?.querySelector( '[data-shanelle-search-clear-recent]' );
	const recent = getRecentSearches();

	if ( ! section || ! list ) {
		return;
	}

	list.innerHTML = '';

	if ( recent.length === 0 ) {
		section.hidden = true;

		if ( clearButton ) {
			clearButton.hidden = true;
		}

		return;
	}

	section.hidden = false;

	if ( clearButton ) {
		clearButton.hidden = false;
	}

	recent.forEach( ( term ) => {
		const item = document.createElement( 'li' );
		const button = document.createElement( 'button' );

		button.type = 'button';
		button.className = 'chip search-results__chip';
		button.dataset.shanelleSearchSuggestion = '';
		button.dataset.searchQuery = term;
		button.textContent = term;
		item.appendChild( button );
		list.appendChild( item );
	} );
}

/**
 * @returns {HTMLElement[]}
 */
function getOptions() {
	if ( ! results ) {
		return [];
	}

	return Array.from( results.querySelectorAll( '[data-shanelle-search-option]' ) ).filter( ( element ) => {
		return element instanceof HTMLElement;
	} );
}

/**
 * @param {number} index
 */
function setActiveOption( index ) {
	const options = getOptions();

	options.forEach( ( option, optionIndex ) => {
		option.classList.toggle( 'is-active', optionIndex === index );
		option.setAttribute( 'aria-selected', optionIndex === index ? 'true' : 'false' );
	} );

	activeIndex = index;

	if ( input ) {
		input.setAttribute( 'aria-activedescendant', index >= 0 && options[ index ] ? options[ index ].id || '' : '' );
	}
}

/** @type {string} */
let idleHtml = '';

/**
 * Show idle suggestions panel.
 */
function showIdlePanel() {
	if ( ! results ) {
		return;
	}

	if ( idleHtml ) {
		results.innerHTML = idleHtml;
	}

	renderRecentSearches();
	setActiveOption( -1 );

	if ( input ) {
		input.setAttribute( 'aria-expanded', 'true' );
	}
}

/**
 * @param {string} html
 * @param {string} status
 */
function renderResultsHtml( html, status ) {
	if ( ! results ) {
		return;
	}

	results.innerHTML = html;

	const idle = results.querySelector( '[data-shanelle-search-idle]' );

	if ( idle instanceof HTMLElement && status !== 'idle' ) {
		idle.hidden = true;
	}

	if ( status === 'idle' ) {
		renderRecentSearches();
	}

	const options = getOptions();

	options.forEach( ( option, index ) => {
		if ( ! option.id ) {
			option.id = `shanelle-search-option-${ index }`;
		}
	} );

	setActiveOption( -1 );

	if ( input ) {
		input.setAttribute( 'aria-expanded', options.length > 0 || status === 'idle' ? 'true' : 'false' );
	}
}

/**
 * Show loading skeleton lazily.
 */
function showSkeleton() {
	if ( ! results ) {
		return;
	}

	results.innerHTML = `
		<div class="search-results search-results--loading" data-shanelle-search-skeleton aria-hidden="true">
			<ul class="search-results__list" role="presentation">
				${ Array.from( { length: 4 } ).map( () => `
					<li class="search-results__skeleton-item" aria-hidden="true">
						<span class="search-results__skeleton-media"></span>
						<span class="search-results__skeleton-copy">
							<span class="search-results__skeleton-line search-results__skeleton-line--title"></span>
							<span class="search-results__skeleton-line search-results__skeleton-line--meta"></span>
						</span>
					</li>
				`).join( '' ) }
			</ul>
		</div>
	`;

	if ( input ) {
		input.setAttribute( 'aria-expanded', 'true' );
	}
}

/**
 * @param {string} query
 */
async function fetchSuggestions( query ) {
	const trimmed = query.trim();

	if ( trimmed.length < MIN_QUERY_LENGTH ) {
		showIdlePanel();
		return;
	}

	if ( requestController ) {
		requestController.abort();
	}

	requestController = new AbortController();
	isLoading = true;
	showSkeleton();
	announce( i18n.loading || 'Buscando…' );

	const params = new URLSearchParams( {
		action: config.action || 'shanelle_search_suggest',
		nonce: config.nonce || '',
		query: trimmed,
	} );

	try {
		const response = await fetch( `${ config.ajaxUrl || '/wp-admin/admin-ajax.php' }?${ params.toString() }`, {
			method: 'GET',
			signal: requestController.signal,
			headers: {
				Accept: 'application/json',
			},
		} );

		if ( ! response.ok ) {
			throw new Error( 'Error en la búsqueda.' );
		}

		const payload = await response.json();

		if ( ! payload?.success || ! payload?.data ) {
			throw new Error( 'Respuesta de búsqueda no válida.' );
		}

		renderResultsHtml( payload.data.html || '', payload.data.status || 'results' );
		announce(
			payload.data.status === 'empty'
				? ( i18n.noResults || 'No se encontraron resultados' )
				: ( i18n.resultsUpdated || 'Sugerencias de búsqueda actualizadas' )
		);
	} catch ( error ) {
		if ( error instanceof DOMException && error.name === 'AbortError' ) {
			return;
		}

		renderResultsHtml(
			`<div class="search-results search-results--empty"><p class="search-results__empty-message text-muted">${ i18n.noResults || 'No se encontraron resultados' }</p></div>`,
			'empty'
		);
	} finally {
		isLoading = false;
		requestController = null;
	}
}

/**
 * @param {string} query
 */
function scheduleSearch( query ) {
	window.clearTimeout( debounceTimer );
	debounceTimer = window.setTimeout( () => {
		fetchSuggestions( query );
	}, DEBOUNCE_MS );
}

/**
 * @param {string} query
 */
function navigateToResults( query ) {
	const trimmed = query.trim();

	if ( trimmed.length < MIN_QUERY_LENGTH ) {
		return;
	}

	rememberSearch( trimmed );

	const url = new URL( config.initialState?.urls?.results || window.location.origin, window.location.origin );
	url.searchParams.set( 's', trimmed );
	url.searchParams.set( 'post_type', 'product' );
	window.location.href = url.toString();
}

/**
 * @param {HTMLElement} option
 */
function navigateToOption( option ) {
	const url = option.dataset.searchUrl || option.querySelector( 'a[href]' )?.getAttribute( 'href' ) || '';

	if ( ! url ) {
		return;
	}

	if ( input?.value ) {
		rememberSearch( input.value );
	}

	window.location.href = url;
}

/**
 * @param {HTMLElement} container
 * @returns {HTMLElement[]}
 */
function getFocusableElements( container ) {
	return Array.from( container.querySelectorAll( FOCUSABLE_SELECTOR ) ).filter( ( element ) => {
		return element instanceof HTMLElement && ! element.hasAttribute( 'disabled' ) && element.offsetParent !== null;
	} );
}

/**
 * @param {KeyboardEvent} event
 */
function handleFocusTrap( event ) {
	if ( ! isOpen || ! dialog || event.key !== 'Tab' ) {
		return;
	}

	const focusables = getFocusableElements( dialog );

	if ( focusables.length === 0 ) {
		event.preventDefault();
		dialog.focus();
		return;
	}

	const first = focusables[ 0 ];
	const last = focusables[ focusables.length - 1 ];
	const active = document.activeElement;

	if ( event.shiftKey && active === first ) {
		event.preventDefault();
		last.focus();
	} else if ( ! event.shiftKey && active === last ) {
		event.preventDefault();
		first.focus();
	}
}

/**
 * @param {KeyboardEvent} event
 */
function handleInputKeydown( event ) {
	const options = getOptions();

	if ( event.key === 'ArrowDown' ) {
		event.preventDefault();

		if ( options.length === 0 ) {
			return;
		}

		const nextIndex = activeIndex >= options.length - 1 ? 0 : activeIndex + 1;
		setActiveOption( nextIndex );
		options[ nextIndex ]?.scrollIntoView( { block: 'nearest' } );
		return;
	}

	if ( event.key === 'ArrowUp' ) {
		event.preventDefault();

		if ( options.length === 0 ) {
			return;
		}

		const nextIndex = activeIndex <= 0 ? options.length - 1 : activeIndex - 1;
		setActiveOption( nextIndex );
		options[ nextIndex ]?.scrollIntoView( { block: 'nearest' } );
		return;
	}

	if ( event.key === 'Enter' && activeIndex >= 0 && options[ activeIndex ] ) {
		event.preventDefault();
		navigateToOption( options[ activeIndex ] );
		return;
	}

	if ( event.key === 'Escape' ) {
		event.preventDefault();
		closeSearchOverlay();
	}
}

/**
 * Open search overlay.
 *
 * @param {Record<string, unknown>} [options]
 */
function openSearchOverlay( options = {} ) {
	if ( ! root || ! dialog || isOpen ) {
		return;
	}

	lastFocusedElement = document.activeElement instanceof HTMLElement
		? document.activeElement
		: null;

	isOpen = true;
	root.hidden = false;
	root.classList.add( 'is-open' );
	document.body.classList.add( 'is-search-overlay-open' );

	const presetQuery = typeof options.query === 'string' ? options.query : '';

	if ( input ) {
		if ( presetQuery ) {
			input.value = presetQuery;
		}

		input.setAttribute( 'aria-expanded', 'true' );
	}

	requestAnimationFrame( () => {
		input?.focus();
		input?.select();

		if ( presetQuery && presetQuery.trim().length >= MIN_QUERY_LENGTH ) {
			scheduleSearch( presetQuery );
		} else {
			showIdlePanel();
		}
	} );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:search-overlay:opened', {
			bubbles: true,
			detail: {
				root,
				state: getOverlayState(),
			},
		} )
	);
}

/**
 * Close search overlay.
 */
function closeSearchOverlay() {
	if ( ! root || ! dialog || ! isOpen ) {
		return;
	}

	if ( requestController ) {
		requestController.abort();
		requestController = null;
	}

	isOpen = false;
	isLoading = false;
	activeIndex = -1;
	root.classList.remove( 'is-open' );
	document.body.classList.remove( 'is-search-overlay-open' );

	window.setTimeout( () => {
		if ( ! isOpen ) {
			root.hidden = true;
		}
	}, 200 );

	if ( input ) {
		input.value = '';
		input.setAttribute( 'aria-expanded', 'false' );
	}

	showIdlePanel();

	if ( lastFocusedElement ) {
		lastFocusedElement.focus();
	}

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:search-overlay:closed', {
			bubbles: true,
			detail: {
				root,
			},
		} )
	);
}

/**
 * @param {Event} event
 */
function handleDocumentClick( event ) {
	const target = event.target;

	if ( ! ( target instanceof Element ) ) {
		return;
	}

	const openTrigger = target.closest( '[data-shanelle-search-open]' );

	if ( openTrigger ) {
		event.preventDefault();
		openSearchOverlay();
		return;
	}

	const closeTrigger = target.closest( '[data-shanelle-search-close]' );

	if ( closeTrigger && isOpen ) {
		event.preventDefault();
		closeSearchOverlay();
		return;
	}

	if ( isOpen && dialog && ! dialog.contains( target ) ) {
		closeSearchOverlay();
	}

	const suggestion = target.closest( '[data-shanelle-search-suggestion]' );

	if ( suggestion instanceof HTMLElement ) {
		event.preventDefault();
		const query = suggestion.dataset.searchQuery || suggestion.textContent || '';

		if ( input ) {
			input.value = query;
		}

		scheduleSearch( query );
		return;
	}

	const option = target.closest( '[data-shanelle-search-option]' );

	if ( option instanceof HTMLElement && ! target.closest( 'a[href]' ) ) {
		event.preventDefault();
		navigateToOption( option );
	}

	const clearRecent = target.closest( '[data-shanelle-search-clear-recent]' );

	if ( clearRecent ) {
		event.preventDefault();
		setRecentSearches( [] );
		renderRecentSearches();
		announce( i18n.clearRecent || 'Borrar búsquedas recientes' );
	}
}

/**
 * @param {KeyboardEvent} event
 */
function handleGlobalKeydown( event ) {
	if ( event.key === 'Escape' && isOpen ) {
		event.preventDefault();
		closeSearchOverlay();
	}

	handleFocusTrap( event );
}

/**
 * @param {HTMLElement|null} element
 */
function initSearchOverlay( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-search-overlay]' );
	dialog = root?.querySelector( '[data-shanelle-search-dialog]' ) ?? null;
	input = root?.querySelector( '[data-shanelle-search-input]' ) ?? null;
	results = root?.querySelector( '[data-shanelle-search-results]' ) ?? null;

	if ( ! root || root.dataset.searchHydrated === 'true' ) {
		return;
	}

	root.dataset.searchHydrated = 'true';

	if ( results ) {
		idleHtml = results.innerHTML;
	}

	renderRecentSearches();

	const form = root.querySelector( '[data-shanelle-search-form]' );

	form?.addEventListener( 'submit', ( event ) => {
		event.preventDefault();

		if ( activeIndex >= 0 ) {
			const options = getOptions();

			if ( options[ activeIndex ] ) {
				navigateToOption( options[ activeIndex ] );
				return;
			}
		}

		navigateToResults( input?.value || '' );
	} );

	input?.addEventListener( 'input', () => {
		scheduleSearch( input.value );
	} );

	input?.addEventListener( 'keydown', handleInputKeydown );

	document.addEventListener( 'click', handleDocumentClick );
	document.addEventListener( 'keydown', handleGlobalKeydown );
	document.addEventListener( 'focusin', handleHeaderSearchFocus );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:search-overlay:ready', {
			bubbles: true,
			detail: {
				root,
				state: getOverlayState(),
				api: {
					openSearchOverlay,
					closeSearchOverlay,
					getOverlayState,
					rememberSearch,
					announce,
				},
			},
		} )
	);
}

/**
 * Open the overlay when the desktop header search field is focused.
 *
 * @param {FocusEvent} event
 */
function handleHeaderSearchFocus( event ) {
	const target = event.target;

	if ( ! ( target instanceof HTMLInputElement ) || ! target.matches( '[data-shanelle-header-search]' ) ) {
		return;
	}

	const seed = target.value.trim();

	openSearchOverlay( { query: seed } );
	target.blur();
}

document.querySelectorAll( '[data-shanelle-search-overlay]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initSearchOverlay( element );
	}
} );

export {
	initSearchOverlay,
	openSearchOverlay,
	closeSearchOverlay,
	getOverlayState,
	announce,
};
