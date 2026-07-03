/**
 * Shanelle Product Variation Selector Component
 *
 * @package Shanelle
 */

const config = window.shanelleProductVariations ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} root
 * @returns {HTMLFormElement|null}
 */
function findVariationsForm( root ) {
	const form = root.closest( 'form.variations_form' );

	if ( form instanceof HTMLFormElement ) {
		return form;
	}

	return document.querySelector( 'form.variations_form' );
}

/**
 * @param {HTMLElement} root
 * @param {string} attribute
 * @returns {HTMLSelectElement|null}
 */
function getNativeSelect( root, attribute ) {
	const native = root.querySelector( '[data-shanelle-variation-native]' );

	if ( ! native ) {
		return null;
	}

	const item = native.querySelector( `[data-shanelle-variation-native-item][data-attribute-name="${ CSS.escape( attribute ) }"]` );

	if ( item ) {
		const select = item.querySelector( 'select' );

		if ( select instanceof HTMLSelectElement ) {
			return select;
		}
	}

	return null;
}

/**
 * @param {string} attribute
 * @returns {string}
 */
function sanitizeAttributeKey( attribute ) {
	return attribute.replace( /^attribute_/i, '' ).toLowerCase().replace( /[^a-z0-9_-]+/g, '-' );
}

/**
 * @param {HTMLElement} root
 * @param {string} message
 */
function announce( root, message ) {
	const status = root.querySelector( '[data-shanelle-variation-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @param {HTMLElement} root
 * @param {string} attribute
 * @param {string} label
 */
function updateSelectedLabel( root, attribute, label ) {
	const slug = sanitizeAttributeKey( attribute );
	const target = root.querySelector( `[data-shanelle-variation-selected-label="${ slug }"]` );

	if ( target ) {
		target.textContent = label ? `: ${ label }` : '';
	}
}

/**
 * @param {HTMLElement} root
 */
function syncSelectedState( root ) {
	root.querySelectorAll( '[data-shanelle-variation-group]' ).forEach( ( group ) => {
		if ( ! ( group instanceof HTMLElement ) ) {
			return;
		}

		const attribute = group.dataset.attributeName || '';
		const select = getNativeSelect( root, attribute );
		const selectedValue = select?.value || '';

		group.querySelectorAll( '[data-shanelle-variation-option]' ).forEach( ( option ) => {
			if ( ! ( option instanceof HTMLButtonElement ) ) {
				return;
			}

			const isSelected = option.dataset.value === selectedValue && '' !== selectedValue;
			option.classList.toggle( 'is-selected', isSelected );
			option.setAttribute( 'aria-checked', isSelected ? 'true' : 'false' );
			option.tabIndex = isSelected ? 0 : -1;

			if ( isSelected ) {
				updateSelectedLabel( root, attribute, option.dataset.label || option.textContent?.trim() || '' );
			}
		} );

		if ( '' === selectedValue ) {
			updateSelectedLabel( root, attribute, '' );
		}
	} );

	const hasSelection = Array.from( root.querySelectorAll( '[data-shanelle-variation-native] select' ) )
		.some( ( select ) => select instanceof HTMLSelectElement && '' !== select.value );

	root.classList.toggle( 'is-resettable', hasSelection );
}

/**
 * @param {HTMLElement} root
 */
function syncDisabledState( root ) {
	root.querySelectorAll( '[data-shanelle-variation-group]' ).forEach( ( group ) => {
		if ( ! ( group instanceof HTMLElement ) ) {
			return;
		}

		const attribute = group.dataset.attributeName || '';
		const select = getNativeSelect( root, attribute );

		if ( ! select ) {
			return;
		}

		const disabledValues = new Set();

		select.querySelectorAll( 'option' ).forEach( ( option ) => {
			if ( option.disabled && option.value ) {
				disabledValues.add( option.value );
			}
		} );

		group.querySelectorAll( '[data-shanelle-variation-option]' ).forEach( ( option ) => {
			if ( ! ( option instanceof HTMLButtonElement ) ) {
				return;
			}

			const value = option.dataset.value || '';
			const isDisabled = disabledValues.has( value );
			option.disabled = isDisabled;
			option.classList.toggle( 'is-disabled', isDisabled );
			option.setAttribute( 'aria-disabled', isDisabled ? 'true' : 'false' );
		} );
	} );
}

/**
 * @param {HTMLElement} root
 * @param {string} attribute
 * @param {string} value
 * @param {string} label
 */
function selectOption( root, attribute, value, label ) {
	const select = getNativeSelect( root, attribute );

	if ( ! select ) {
		return;
	}

	select.value = value;
	select.dispatchEvent( new Event( 'change', { bubbles: true } ) );

	updateSelectedLabel( root, attribute, label );
	syncSelectedState( root );

	const template = i18n.optionSelected || '%1$s selected: %2$s';
	announce( root, template.replace( '%1$s', attribute ).replace( '%2$s', label ) );
}

/**
 * @param {HTMLElement} root
 */
function clearSelections( root ) {
	const form = findVariationsForm( root );

	if ( form ) {
		const resetLink = form.querySelector( '.reset_variations' );

		if ( resetLink instanceof HTMLElement ) {
			resetLink.click();
			return;
		}
	}

	root.querySelectorAll( '[data-shanelle-variation-native] select' ).forEach( ( select ) => {
		if ( select instanceof HTMLSelectElement ) {
			select.selectedIndex = 0;
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
	} );

	syncSelectedState( root );
	syncDisabledState( root );
	resetAvailability( root );
	announce( root, i18n.variationReset || 'Variation cleared' );
}

/**
 * @param {HTMLElement} root
 */
function resetAvailability( root ) {
	const availability = root.querySelector( '[data-shanelle-variation-availability]' );
	const label = root.querySelector( '[data-shanelle-variation-availability-label]' );

	if ( availability instanceof HTMLElement ) {
		availability.hidden = true;
		availability.className = 'product-variations__availability';
	}

	if ( label ) {
		label.textContent = '';
	}
}

/**
 * @param {HTMLElement} root
 * @param {Record<string, unknown>} variation
 */
function applyAvailability( root, variation ) {
	const availability = root.querySelector( '[data-shanelle-variation-availability]' );
	const label = root.querySelector( '[data-shanelle-variation-availability-label]' );

	if ( ! ( availability instanceof HTMLElement ) || ! ( label instanceof HTMLElement ) ) {
		return;
	}

	const status = String( variation.shanelle_stock_status || 'instock' );
	const text = String( variation.shanelle_stock_label || variation.availability_html || i18n.inStock || 'In stock' );

	availability.hidden = false;
	availability.className = `product-variations__availability product-variations__availability--${ status }`;
	label.textContent = text.replace( /<[^>]+>/g, '' );
}

/**
 * @param {HTMLElement|null} summaryStock
 * @param {Record<string, unknown>} variation
 */
function syncSummaryStock( summaryStock, variation ) {
	if ( ! ( summaryStock instanceof HTMLElement ) ) {
		return;
	}

	const status = String( variation.shanelle_stock_status || 'instock' );
	const label = String( variation.shanelle_stock_label || '' ).replace( /<[^>]+>/g, '' );

	summaryStock.dataset.stockStatus = status;
	summaryStock.className = `product-summary__stock product-summary__stock--${ status }`;

	const stockLabel = summaryStock.querySelector( '.product-summary__stock-label' );

	if ( stockLabel ) {
		stockLabel.textContent = label;
	}
}

/**
 * @param {HTMLElement} root
 * @param {Record<string, unknown>|null} variation
 */
function dispatchVariationEvents( root, variation ) {
	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-variations:change', {
			bubbles: true,
			detail: {
				root,
				variation,
				form: findVariationsForm( root ),
			},
		} )
	);

	if ( ! variation ) {
		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:product-variations:stock-change', {
				bubbles: true,
				detail: { root, variation: null },
			} )
		);
		return;
	}

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-variations:stock-change', {
			bubbles: true,
			detail: {
				root,
				variation,
				stockStatus: variation.shanelle_stock_status,
				stockLabel: variation.shanelle_stock_label,
			},
		} )
	);

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-variations:gallery-change', {
			bubbles: true,
			detail: {
				root,
				variation,
				imageId: variation.shanelle_gallery_image_id || variation.image_id || 0,
			},
		} )
	);

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-variations:price-change', {
			bubbles: true,
			detail: {
				root,
				variation,
				price: {
					hasPrice: Boolean( variation.display_price ),
					isOnSale: Boolean( variation.shanelle_is_on_sale ),
					isRange: false,
					currentHtml: String( variation.shanelle_current_html || variation.price_html || '' ),
					regularHtml: String( variation.shanelle_regular_html || '' ),
					savingsHtml: String( variation.shanelle_savings_html || '' ),
				},
			},
		} )
	);
}

/**
 * @param {HTMLElement} root
 * @param {Record<string, unknown>} variation
 */
function handleFoundVariation( root, variation ) {
	applyAvailability( root, variation );
	syncSummaryStock( document.querySelector( '[data-shanelle-summary-stock]' ), variation );
	dispatchVariationEvents( root, variation );
	announce( root, i18n.variationReady || 'Variation selected' );
}

/**
 * @param {HTMLElement} root
 */
function handleResetData( root ) {
	syncSelectedState( root );
	syncDisabledState( root );
	resetAvailability( root );
	dispatchVariationEvents( root, null );
	announce( root, i18n.variationReset || 'Variation cleared' );
}

/**
 * @param {HTMLElement} group
 * @param {KeyboardEvent} event
 */
function handleRadioGroupKeydown( group, event ) {
	const options = Array.from( group.querySelectorAll( '[data-shanelle-variation-option]:not([disabled])' ) )
		.filter( ( option ) => option instanceof HTMLButtonElement );

	if ( ! options.length ) {
		return;
	}

	const currentIndex = options.findIndex( ( option ) => option.classList.contains( 'is-selected' ) );
	let nextIndex = currentIndex;

	if ( event.key === 'ArrowRight' || event.key === 'ArrowDown' ) {
		event.preventDefault();
		nextIndex = currentIndex + 1 >= options.length ? 0 : currentIndex + 1;
	}

	if ( event.key === 'ArrowLeft' || event.key === 'ArrowUp' ) {
		event.preventDefault();
		nextIndex = currentIndex - 1 < 0 ? options.length - 1 : currentIndex - 1;
	}

	if ( event.key === ' ' || event.key === 'Enter' ) {
		event.preventDefault();
		const focused = document.activeElement;

		if ( focused instanceof HTMLButtonElement && options.includes( focused ) ) {
			focused.click();
		}

		return;
	}

	if ( nextIndex !== currentIndex && options[ nextIndex ] ) {
		options[ nextIndex ].focus();
		options[ nextIndex ].click();
	}
}

/**
 * @param {HTMLElement} root
 */
function bindOptionControls( root ) {
	root.querySelectorAll( '[data-shanelle-variation-option]' ).forEach( ( option ) => {
		if ( ! ( option instanceof HTMLButtonElement ) ) {
			return;
		}

		option.addEventListener( 'click', () => {
			if ( option.disabled ) {
				return;
			}

			selectOption(
				root,
				option.dataset.attribute || '',
				option.dataset.value || '',
				option.dataset.label || option.textContent?.trim() || ''
			);
		} );
	} );

	root.querySelectorAll( '[data-shanelle-variation-group]' ).forEach( ( group ) => {
		if ( ! ( group instanceof HTMLElement ) ) {
			return;
		}

		group.addEventListener( 'keydown', ( event ) => {
			if ( event instanceof KeyboardEvent ) {
				handleRadioGroupKeydown( group, event );
			}
		} );
	} );

	root.querySelector( '[data-shanelle-variation-reset]' )?.addEventListener( 'click', () => {
		clearSelections( root );
	} );
}

/**
 * @param {HTMLElement} root
 */
function bindWooCommerceForm( root ) {
	const form = findVariationsForm( root );

	if ( ! form || typeof window.jQuery === 'undefined' ) {
		return;
	}

	window.jQuery( form )
		.on( 'woocommerce_update_variation_values', () => {
			syncDisabledState( root );
			syncSelectedState( root );
		} )
		.on( 'found_variation', ( event, variation ) => {
			handleFoundVariation( root, variation );
		} )
		.on( 'reset_data', () => {
			handleResetData( root );
		} );
}

/**
 * @param {HTMLElement} root
 */
function initVariations( root ) {
	if ( root.dataset.variationsHydrated === 'true' ) {
		return;
	}

	root.dataset.variationsHydrated = 'true';

	bindOptionControls( root );
	bindWooCommerceForm( root );
	syncSelectedState( root );
	syncDisabledState( root );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-variations:ready', {
			bubbles: true,
			detail: { root, form: findVariationsForm( root ) },
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-variations]' ).forEach( initVariations );

export {
	initVariations,
	selectOption,
	clearSelections,
	syncSelectedState,
	syncDisabledState,
	applyAvailability,
	dispatchVariationEvents,
	findVariationsForm,
};
