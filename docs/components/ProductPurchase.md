# ProductPurchase

## Purpose

Renders purchase controls on the single product page: quantity selector, add to bag, stock notices, trust badges, and placeholders for buy now, wishlist, and shipping/delivery estimates.

## Responsibilities

- Render accessible quantity stepper (increment, decrement, manual input)
- Add to cart via WooCommerce AJAX endpoint
- Sync quantity and `variation_id` with parent cart form
- Display stock warnings, backorder notices, and out-of-stock state
- Listen to ProductVariations events for variation-aware purchase state
- Apply WooCommerce cart fragments after successful add
- Enqueue purchase panel CSS/JS

## Public API

### PHP class

`Shanelle\Components\ProductPurchase`

| Method | Description |
|--------|-------------|
| `boot()` | Registers asset enqueue hook |
| `render( WC_Product $product, array $args = [] )` | Renders purchase panel |
| `render_quantity()` | Quantity stepper |
| `render_actions()` | Add to bag and placeholder buttons |
| `render_notices()` | Stock/backorder/out-of-stock notices |
| `render_estimates()` | Shipping/delivery placeholders |
| `render_trust()` | Trust badges and secure checkout message |
| `get_state_json()` | Purchase state JSON for hydration |

### Theme helper

```php
shanelle_product_purchase( WC_Product $product, array $args = [] );
```

### Purchase state JSON fields

- `productId`, `productType`, `minQuantity`, `maxQuantity`, `defaultQuantity`
- `canPurchase`, `requiresVariation`, `variationId`
- `isInStock`, `isOnBackorder`, `isLowStock`, `stockStatus`, `stockLabel`, `stockQuantity`

### JavaScript exports

From `components/product-purchase/product-purchase.js`:

- `initPurchase( panel )`
- `getPurchaseState( panel )`
- `setQuantity( panel, quantity )`
- `stepQuantity( panel, delta )`
- `addToCart( panel )`
- `updateStockState( panel, stock )`
- `applyVariationState( panel, variation )`
- `syncVariationId( panel, variationId )`
- `findPurchaseForm( panel )`

## Dependencies

- Design System (buttons, form patterns)
- WooCommerce cart/AJAX APIs
- Parent `form.cart` or `form.variations_form` (from ProductDetail)
- ProductVariations events for variable products
- Optional `[data-shanelle-summary-stock]` sync target

## Events

### Dispatched

| Event | Source | Payload |
|-------|--------|---------|
| `shanelle:product-purchase:ready` | Init complete | `{ panel, state }` |
| `shanelle:product-purchase:quantity-change` | Quantity updated | `{ panel, quantity, productId, variationId }` |
| `shanelle:product-purchase:added` | AJAX add success | `{ panel, productId, quantity, variationId, response }` |
| `shanelle:product-purchase:error` | Add failed | `{ panel, message }` |
| `shanelle:added_to_cart` | Also fired on success | `{ productId, quantity, variationId, data }` |

### Listened

| Event | Behavior |
|-------|----------|
| `shanelle:product-variations:change` | Updates variation ID and purchase eligibility |
| `shanelle:product-variations:stock-change` | Updates stock notices and controls |

## Extension points

- Replace buy now, wishlist, shipping, and delivery placeholders with real integrations
- Hook `shanelle:product-purchase:added` for mini-cart drawer or analytics
- Use exported JS methods in PWA checkout flows
- Customize add-to-cart text via WooCommerce `single_add_to_cart_text` filter

## Filters

This component does not register custom filters. Uses WooCommerce product APIs for min/max quantity and stock.

## Actions

This component does not register custom WordPress actions.

## Example usage

Inside the product detail cart form:

```php
global $product;

if ( $product instanceof WC_Product ) {
	shanelle_product_purchase( $product );
}
```

Listen for successful add:

```javascript
document.body.addEventListener( 'shanelle:product-purchase:added', ( event ) => {
	const { productId, quantity, variationId } = event.detail;
	// Open mini cart, track conversion, etc.
} );
```

## Known limitations

- Buy now, wishlist, shipping estimate, and delivery estimate are placeholders (disabled or static)
- Variable products require a selected variation before add to cart is enabled
- AJAX add to cart depends on WooCommerce AJAX endpoint availability
- Does not render grouped or external product forms
- Stock sync for summary depends on `[data-shanelle-summary-stock]` being present in the DOM
- jQuery is not required directly, but variation sync depends on ProductVariations + WC scripts
