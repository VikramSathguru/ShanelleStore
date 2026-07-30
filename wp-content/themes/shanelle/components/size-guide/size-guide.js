/**
 * Shanelle Size Guide Modal
 *
 * @package Shanelle
 */

const CM_PER_INCH = 2.54;

/**
 * @param {HTMLElement} root
 * @returns {Record<string, unknown>}
 */
function parseGuide( root ) {
	try {
		const raw = root.dataset.sizeGuide || '{}';
		const data = JSON.parse( raw );
		return data && typeof data === 'object' ? data : {};
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {number} value
 * @param {string} unit
 * @returns {string}
 */
function formatMeasure( value, unit ) {
	if ( ! Number.isFinite( value ) ) {
		return '';
	}

	if ( unit === 'in' ) {
		const inches = value / CM_PER_INCH;
		return Number.parseFloat( inches.toFixed( 1 ) ).toString();
	}

	return Number.parseFloat( value.toFixed( 1 ) ).toString();
}

/**
 * @param {unknown} value
 * @returns {string}
 */
function asText( value ) {
	return value == null ? '' : String( value );
}

/**
 * @param {string} value
 * @returns {string}
 */
function escapeHtml( value ) {
	return value
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#39;' );
}

/**
 * @param {HTMLElement} root
 * @param {Record<string, unknown>} guide
 */
function renderMeasureTable( root, guide ) {
	const host = root.querySelector( '[data-shanelle-size-guide-table]' );

	if ( !( host instanceof HTMLElement ) ) {
		return;
	}

	const unit = root.dataset.unit === 'in' ? 'in' : 'cm';
	const type = root.dataset.type || 'tops';
	const chartKey = root.dataset.chart === 'body' ? 'body' : 'product';
	const charts = guide.charts && typeof guide.charts === 'object' ? guide.charts : {};
	const chartGroup = charts[ chartKey ] && typeof charts[ chartKey ] === 'object' ? charts[ chartKey ] : {};
	const chart = chartGroup[ type ] && typeof chartGroup[ type ] === 'object' ? chartGroup[ type ] : null;
	const columnLabels = guide.column_labels && typeof guide.column_labels === 'object' ? guide.column_labels : {};

	if ( ! chart || ! Array.isArray( chart.columns ) || ! Array.isArray( chart.rows ) ) {
		host.innerHTML = '';
		return;
	}

	const columns = chart.columns.map( ( key ) => String( key ) );
	const highlight = chart.highlight ? String( chart.highlight ) : '';
	const thead = columns.map( ( key ) => {
		const label = columnLabels[ key ] ? String( columnLabels[ key ] ) : key;
		const cls = key === highlight ? ' class="is-highlight"' : '';
		return `<th scope="col"${ cls }>${ escapeHtml( label ) }</th>`;
	} ).join( '' );

	const tbody = chart.rows.map( ( row ) => {
		if ( ! row || typeof row !== 'object' ) {
			return '';
		}

		const cells = columns.map( ( key ) => {
			const cls = key === highlight ? ' class="is-highlight"' : '';
			const raw = row[ key ];

			if ( key === 'size' || typeof raw === 'string' ) {
				return `<td${ cls }>${ escapeHtml( asText( raw ) ) }</td>`;
			}

			const numeric = Number( raw );
			return `<td${ cls }>${ escapeHtml( formatMeasure( numeric, unit ) ) }</td>`;
		} ).join( '' );

		return `<tr>${ cells }</tr>`;
	} ).join( '' );

	host.innerHTML = `<table class="size-guide__table"><thead><tr>${ thead }</tr></thead><tbody>${ tbody }</tbody></table>`;
}

/**
 * @param {HTMLElement} root
 * @param {Record<string, unknown>} guide
 */
function updateDisclaimer( root, guide ) {
	const node = root.querySelector( '[data-shanelle-size-guide-disclaimer]' );

	if ( !( node instanceof HTMLElement ) ) {
		return;
	}

	const unit = root.dataset.unit === 'in' ? 'in' : 'cm';
	const base = asText( guide.disclaimer );

	if ( unit === 'in' && base ) {
		node.textContent = base.replace( /\bCM\b/gi, 'IN' ).replace( /1–2/g, '0.4–0.8' );
		return;
	}

	node.textContent = base;
}

/**
 * @param {HTMLElement} root
 */
function refresh( root ) {
	const guide = parseGuide( root );
	renderMeasureTable( root, guide );
	updateDisclaimer( root, guide );
}

/**
 * @param {HTMLElement} root
 * @param {string} type
 */
function setType( root, type ) {
	root.dataset.type = type;

	const select = root.querySelector( '[data-shanelle-size-guide-type]' );

	if ( select instanceof HTMLSelectElement && select.value !== type ) {
		select.value = type;
	}

	root.querySelectorAll( '[data-shanelle-size-guide-type-tab]' ).forEach( ( tab ) => {
		if ( !( tab instanceof HTMLElement ) ) {
			return;
		}

		const active = tab.dataset.shanelleSizeGuideTypeTab === type;
		tab.classList.toggle( 'is-active', active );
		tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
	} );

	refresh( root );
}

/**
 * @param {HTMLElement} root
 * @param {'product'|'body'} chart
 */
function setChart( root, chart ) {
	root.dataset.chart = chart;

	root.querySelectorAll( '[data-shanelle-size-guide-chart]' ).forEach( ( tab ) => {
		if ( !( tab instanceof HTMLElement ) ) {
			return;
		}

		const active = tab.dataset.shanelleSizeGuideChart === chart;
		tab.classList.toggle( 'is-active', active );
		tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
	} );

	refresh( root );
}

/**
 * @param {HTMLElement} root
 * @param {'cm'|'in'} unit
 */
function setUnit( root, unit ) {
	root.dataset.unit = unit;

	root.querySelectorAll( '[data-shanelle-size-guide-unit]' ).forEach( ( button ) => {
		if ( !( button instanceof HTMLElement ) ) {
			return;
		}

		const active = button.dataset.shanelleSizeGuideUnit === unit;
		button.classList.toggle( 'is-active', active );
		button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
	} );

	refresh( root );
}

/**
 * @param {HTMLElement} panel
 * @returns {HTMLElement[]}
 */
function getFocusableElements( panel ) {
	return Array.from(
		panel.querySelectorAll(
			'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
		)
	).filter( ( el ) => el instanceof HTMLElement && ! el.hasAttribute( 'disabled' ) && el.offsetParent !== null );
}

/**
 * @param {ParentNode} [scope]
 */
export function initSizeGuide( scope = document ) {
	const roots = scope.querySelectorAll( '[data-shanelle-size-guide]' );

	roots.forEach( ( root ) => {
		if ( !( root instanceof HTMLElement ) || root.dataset.sizeGuideHydrated === 'true' ) {
			return;
		}

		root.dataset.sizeGuideHydrated = 'true';

		const panel = root.querySelector( '[data-shanelle-size-guide-panel]' );
		const closeBtn = root.querySelector( '[data-shanelle-size-guide-close]' );
		const overlay = root.querySelector( '[data-shanelle-size-guide-overlay]' );
		const modalId = root.id;

		if ( !( panel instanceof HTMLElement ) ) {
			return;
		}

		const modalHome = root.parentElement;
		const placeholder = document.createComment( 'shanelle-size-guide' );

		/** @type {HTMLElement|null} */
		let lastFocused = null;
		let isOpen = false;

		const mount = () => {
			if ( root.parentElement === document.body ) {
				return;
			}

			if ( modalHome instanceof Node ) {
				modalHome.insertBefore( placeholder, root );
			}

			document.body.appendChild( root );
		};

		const unmount = () => {
			if ( placeholder.parentNode ) {
				placeholder.parentNode.insertBefore( root, placeholder );
				placeholder.remove();
				return;
			}

			if ( modalHome instanceof HTMLElement ) {
				modalHome.appendChild( root );
			}
		};

		/**
		 * @param {KeyboardEvent} event
		 */
		const onKeydown = ( event ) => {
			if ( ! isOpen ) {
				return;
			}

			if ( event.key === 'Escape' ) {
				event.preventDefault();
				close();
				return;
			}

			if ( event.key !== 'Tab' ) {
				return;
			}

			const focusable = getFocusableElements( panel );

			if ( focusable.length === 0 ) {
				event.preventDefault();
				panel.focus();
				return;
			}

			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];
			const active = document.activeElement;

			if ( event.shiftKey && active === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && active === last ) {
				event.preventDefault();
				first.focus();
			}
		};

		/**
		 * @param {HTMLElement|null} [trigger]
		 */
		const open = ( trigger = null ) => {
			if ( isOpen ) {
				return;
			}

			lastFocused = trigger instanceof HTMLElement
				? trigger
				: ( document.activeElement instanceof HTMLElement ? document.activeElement : null );

			refresh( root );
			mount();
			root.hidden = false;
			root.classList.add( 'is-open' );
			isOpen = true;
			document.body.style.overflow = 'hidden';
			document.addEventListener( 'keydown', onKeydown );

			requestAnimationFrame( () => {
				const focusTarget = closeBtn instanceof HTMLElement ? closeBtn : panel;
				focusTarget.focus();
			} );
		};

		const close = () => {
			if ( ! isOpen ) {
				return;
			}

			root.hidden = true;
			root.classList.remove( 'is-open' );
			isOpen = false;
			document.body.style.overflow = '';
			document.removeEventListener( 'keydown', onKeydown );
			unmount();

			if ( lastFocused instanceof HTMLElement && document.contains( lastFocused ) ) {
				lastFocused.focus();
			}
		};

		refresh( root );

		document.querySelectorAll( `[data-shanelle-size-guide-open][aria-controls="${ CSS.escape( modalId ) }"]` ).forEach( ( trigger ) => {
			trigger.addEventListener( 'click', () => {
				open( trigger instanceof HTMLElement ? trigger : null );
			} );
		} );

		closeBtn?.addEventListener( 'click', close );
		overlay?.addEventListener( 'click', close );

		root.querySelectorAll( '[data-shanelle-size-guide-unit]' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				if ( !( button instanceof HTMLElement ) ) {
					return;
				}

				setUnit( root, button.dataset.shanelleSizeGuideUnit === 'in' ? 'in' : 'cm' );
			} );
		} );

		root.querySelector( '[data-shanelle-size-guide-type]' )?.addEventListener( 'change', ( event ) => {
			const select = event.target;

			if ( !( select instanceof HTMLSelectElement ) ) {
				return;
			}

			setType( root, select.value );
		} );

		root.querySelectorAll( '[data-shanelle-size-guide-type-tab]' ).forEach( ( tab ) => {
			tab.addEventListener( 'click', () => {
				if ( !( tab instanceof HTMLElement ) ) {
					return;
				}

				setType( root, String( tab.dataset.shanelleSizeGuideTypeTab || 'tops' ) );
			} );
		} );

		root.querySelectorAll( '[data-shanelle-size-guide-chart]' ).forEach( ( tab ) => {
			tab.addEventListener( 'click', () => {
				if ( !( tab instanceof HTMLElement ) ) {
					return;
				}

				setChart( root, tab.dataset.shanelleSizeGuideChart === 'body' ? 'body' : 'product' );
			} );
		} );
	} );
}

initSizeGuide();
