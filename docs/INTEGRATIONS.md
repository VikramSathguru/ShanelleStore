# Integrations

Thin theme adapters under `wp-content/themes/shanelle/inc/integrations/`.

**Status:** Skeleton only — no vendor SDKs or secrets. Prefer mature plugins first ([PLUGIN_FIRST_ARCHITECTURE.md](./PLUGIN_FIRST_ARCHITECTURE.md), [FUTURE_INTEGRATIONS.md](./FUTURE_INTEGRATIONS.md)).

---

## Ownership

| Layer | Owns |
|-------|------|
| Plugins (PayPal, Pagadito, Fygaro, Meta, TikTok, Site Kit, …) | Business capability, credentials, webhooks |
| Theme composers / components | Presentation only |
| `inc/integrations/{Adapter}.php` | Optional glue: map WC/`shanelle_*` hooks to a plugin when styling or event bridging is required |

Never put publishable keys, Pixel IDs, or gateway SDKs in templates.

---

## Boot

`functions.php` loads `Integrations::boot()` after core components.

1. Declared adapters in `Integrations::ADAPTERS` are required if the PHP file exists.  
2. Each adapter must implement `should_boot(): bool` and `boot(): void`.  
3. `should_boot()` should return false when the related plugin is inactive or custom glue is unnecessary.  
4. Action `shanelle_integrations_boot` fires afterward for external plugins.

---

## Adding an adapter

1. Confirm a plugin cannot solve it ([PLUGIN_FIRST_ARCHITECTURE.md](./PLUGIN_FIRST_ARCHITECTURE.md)).  
2. Create `inc/integrations/YourAdapter.php` in namespace `Shanelle\Integrations`.  
3. Implement:

```php
final class YourAdapter {
	public static function should_boot(): bool {
		return class_exists( 'Some_Plugin_Class' );
	}

	public static function boot(): void {
		add_filter( 'shanelle_…', array( self::class, '…' ) );
	}
}
```

4. Append `'YourAdapter'` to `Integrations::ADAPTERS`.  
5. Document the adapter in this file and update [PLUGIN_DEPENDENCIES.md](./PLUGIN_DEPENDENCIES.md) if a new plugin is required.

---

## Planned (not implemented)

| Adapter | Prefer plugin first | Notes |
|---------|---------------------|-------|
| Meta / TikTok pixels | facebook-for-woocommerce, tiktok-for-business | Custom adapter only if event bridging to `shanelle:added_to_cart` is required |
| Stripe / PixelPay / BAC | Official WC gateways | Theme stays CSS/hooks only |
| Cargo Mobil | Custom shipping plugin preferred | Theme already exposes estimate filters |

---

## Related

- [FUTURE_INTEGRATIONS.md](./FUTURE_INTEGRATIONS.md)  
- [CUSTOM_HOOKS.md](./CUSTOM_HOOKS.md)  
- [EVENTS.md](./EVENTS.md)  
- [DEPLOYMENT.md](./DEPLOYMENT.md)  
