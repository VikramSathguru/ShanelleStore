<?php
/**
 * Seeds starter content for informational / policy WordPress pages.
 *
 * Content is written into WP posts so merchants can edit it in the editor.
 * This class does not render copy on the storefront.
 *
 * @package Shanelle\Setup
 */

declare(strict_types=1);

namespace Shanelle\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * One-time / CLI seeder for Shanelle informational pages.
 */
final class InfoPageSeeder {

	public const OPTION_KEY = 'shanelle_info_pages_seeded_v1';

	/**
	 * Page definitions keyed by stable lookup (template or slug).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		return array(
			'shipping'         => array(
				'title'    => 'Política de envíos',
				'slug'     => 'shipping-policy',
				'template' => 'page-templates/shipping.php',
				'excerpt'  => 'Enviamos a todo Nicaragua. Los pedidos se preparan en 1–2 días hábiles; la entrega estándar suele tomar 3–5 días hábiles según tu zona.',
				'content'  => self::content_shipping(),
			),
			'returns'          => array(
				'title'    => 'Devoluciones y reembolsos',
				'slug'     => 'refund_returns',
				'template' => 'page-templates/returns.php',
				'excerpt'  => 'Tienes 30 días para devolver artículos sin usar y con etiquetas. Los reembolsos se procesan en 5–7 días hábiles tras recibir la devolución.',
				'content'  => self::content_returns(),
			),
			'privacy'          => array(
				'title'    => 'Política de privacidad',
				'slug'     => 'privacy-policy',
				'template' => 'page-templates/privacy.php',
				'excerpt'  => 'Explicamos qué datos personales recopilamos, para qué los usamos y cómo puedes ejercer tus derechos al comprar en Shanelle Store.',
				'content'  => self::content_privacy(),
			),
			'terms'            => array(
				'title'    => 'Términos y condiciones',
				'slug'     => 'terms-conditions',
				'template' => 'page-templates/terms.php',
				'excerpt'  => 'Condiciones de uso de Shanelle Store: pedidos, precios, propiedad intelectual y responsabilidades al comprar en nuestra tienda.',
				'content'  => self::content_terms(),
			),
			'cookies'          => array(
				'title'    => 'Política de cookies',
				'slug'     => 'cookie-policy',
				'template' => 'page-templates/cookies.php',
				'excerpt'  => 'Usamos cookies esenciales y, con tu permiso, cookies de medición para mejorar la experiencia de compra.',
				'content'  => self::content_cookies(),
			),
			'faq'              => array(
				'title'    => 'Preguntas frecuentes',
				'slug'     => 'preguntas-frecuentes',
				'template' => 'page-templates/faq.php',
				'excerpt'  => 'Respuestas rápidas sobre pedidos, envíos, pagos, tallas y devoluciones en Shanelle Store.',
				'content'  => self::content_faq(),
			),
			'contact'          => array(
				'title'    => 'Contacto',
				'slug'     => 'contact-us',
				'template' => 'page-templates/contact.php',
				'excerpt'  => 'Estamos para ayudarte con pedidos, productos, envíos y devoluciones.',
				'content'  => self::content_contact(),
			),
			'size_guide'       => array(
				'title'    => 'Guía de tallas',
				'slug'     => 'guia-de-tallas',
				'template' => 'page-templates/info-content.php',
				'excerpt'  => 'Cómo medir tu cuerpo y elegir la talla ideal en prendas Shanelle.',
				'content'  => self::content_size_guide(),
			),
			'order_tracking'   => array(
				'title'    => 'Seguimiento de pedidos',
				'slug'     => 'seguimiento-de-pedidos',
				'template' => 'page-templates/info-content.php',
				'excerpt'  => 'Consulta el estado de tu pedido desde tu cuenta o con el número de pedido y correo.',
				'content'  => self::content_order_tracking(),
			),
			'payment_methods'  => array(
				'title'    => 'Métodos de pago',
				'slug'     => 'metodos-de-pago',
				'template' => 'page-templates/info-content.php',
				'excerpt'  => 'Formas de pago aceptadas y consejos de seguridad al finalizar tu compra.',
				'content'  => self::content_payment_methods(),
			),
		);
	}

	/**
	 * Upsert all defined informational pages and sync footer policy menu labels.
	 *
	 * @param bool $force Overwrite existing post content even if already seeded.
	 * @return array<string, int> Map of definition key => post ID.
	 */
	public static function seed( bool $force = false ): array {
		$results = array();

		foreach ( self::get_definitions() as $key => $definition ) {
			$results[ $key ] = self::upsert_page( $definition, $force );
		}

		self::sync_menu_labels();
		update_option( self::OPTION_KEY, gmdate( 'c' ), false );

		return $results;
	}

	/**
	 * Create or update a single page from a definition.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 * @param bool                 $force      Overwrite content.
	 */
	private static function upsert_page( array $definition, bool $force ): int {
		$title    = (string) $definition['title'];
		$slug     = (string) $definition['slug'];
		$template = (string) $definition['template'];
		$excerpt  = (string) $definition['excerpt'];
		$content  = (string) $definition['content'];

		$page = get_page_by_path( $slug );

		if ( ! $page instanceof \WP_Post ) {
			$pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => array( 'publish', 'draft', 'private' ),
					'meta_key'       => '_wp_page_template',
					'meta_value'     => $template,
					'posts_per_page' => 1,
					'no_found_rows'  => true,
				)
			);

			if ( ! empty( $pages[0] ) && $pages[0] instanceof \WP_Post ) {
				$page = $pages[0];
			}
		}

		$payload = array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_excerpt' => $excerpt,
			'post_content' => $content,
		);

		if ( $page instanceof \WP_Post ) {
			$payload['ID'] = $page->ID;

			if ( ! $force && '' !== trim( (string) $page->post_content ) && get_option( self::OPTION_KEY ) ) {
				unset( $payload['post_content'], $payload['post_excerpt'] );
			}

			$post_id = wp_update_post( $payload, true );
		} else {
			$post_id = wp_insert_post( $payload, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		$post_id = (int) $post_id;
		update_post_meta( $post_id, '_wp_page_template', $template );

		return $post_id;
	}

	/**
	 * Align footer policy menu item titles with Spanish page titles.
	 */
	private static function sync_menu_labels(): void {
		$locations = get_nav_menu_locations();
		$menu_ids  = array_filter(
			array(
				isset( $locations['footer_policies'] ) ? (int) $locations['footer_policies'] : 0,
				isset( $locations['footer_customer_service'] ) ? (int) $locations['footer_customer_service'] : 0,
			)
		);

		$title_by_slug = array();
		foreach ( self::get_definitions() as $definition ) {
			$title_by_slug[ (string) $definition['slug'] ] = (string) $definition['title'];
		}

		foreach ( $menu_ids as $menu_id ) {
			$items = wp_get_nav_menu_items( $menu_id );
			if ( ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				if ( 'post_type' !== $item->type || 'page' !== $item->object ) {
					continue;
				}

				$page = get_post( (int) $item->object_id );
				if ( ! $page instanceof \WP_Post ) {
					continue;
				}

				$desired = $title_by_slug[ $page->post_name ] ?? $page->post_title;
				if ( $desired === $item->title ) {
					continue;
				}

				wp_update_nav_menu_item(
					$menu_id,
					(int) $item->ID,
					array(
						'menu-item-title'     => $desired,
						'menu-item-object'    => 'page',
						'menu-item-object-id' => (int) $page->ID,
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-position'  => (int) $item->menu_order,
						'menu-item-parent-id' => (int) $item->menu_item_parent,
					)
				);
			}
		}
	}

	private static function content_shipping(): string {
		return <<<'HTML'
<h2>Envíos en Nicaragua</h2>
<p>En Shanelle Store preparamos cada pedido con cuidado para que tus prendas lleguen en perfectas condiciones. Enviamos a todo el territorio de Nicaragua.</p>

<h3>Tiempos de preparación</h3>
<ul>
<li>Los pedidos se confirman al completar el pago.</li>
<li>Preparación y empaque: <strong>1–2 días hábiles</strong> (lunes a viernes, excepto feriados).</li>
<li>Recibirás un correo cuando tu pedido esté listo para despacho.</li>
</ul>

<h3>Tiempos de entrega estimados</h3>
<ul>
<li><strong>Entrega estándar:</strong> 3–5 días hábiles después del despacho, según tu departamento y zona.</li>
<li><strong>Entrega express:</strong> disponible en checkout cuando el método esté habilitado; suele reducir el tránsito a 1–2 días hábiles en zonas cubiertas.</li>
</ul>
<p>Los plazos son estimaciones orientativas. Condiciones de tránsito, clima o acceso a tu zona pueden modificar la fecha final.</p>

<h3>Costo de envío</h3>
<ul>
<li>El costo se calcula en el carrito o al pagar, según tu dirección y el método elegido.</li>
<li>Pueden aplicarse promociones de <strong>envío gratis</strong> en pedidos que cumplan el monto mínimo vigente.</li>
<li>Revisa siempre el resumen del pedido antes de confirmar.</li>
</ul>

<h3>Dirección de entrega</h3>
<p>Asegúrate de ingresar una dirección completa y un teléfono de contacto. No nos hacemos responsables por retrasos o devoluciones al remitente causadas por datos incompletos o incorrectos.</p>

<h3>Seguimiento</h3>
<p>Cuando tu pedido se despache, podrás consultar el estado desde <a href="/my-account/">Mi cuenta</a> o en la página de <a href="/seguimiento-de-pedidos/">Seguimiento de pedidos</a>. Si no ves movimiento después del plazo estimado, escríbenos desde <a href="/contact-us/">Contacto</a>.</p>

<h3>Pedidos incompletos o dañados</h3>
<p>Si el paquete llega incompleto o con daño evidente, contáctanos dentro de las <strong>48 horas</strong> posteriores a la recepción con fotos del empaque y del producto. Te ayudaremos a resolverlo lo antes posible.</p>
HTML;
	}

	private static function content_returns(): string {
		return <<<'HTML'
<h2>Devoluciones y reembolsos</h2>
<p>Queremos que ames cada prenda. Si algo no es lo ideal, puedes solicitar una devolución dentro del plazo indicado, siempre que el artículo cumpla las condiciones de esta política.</p>

<h3>Plazo</h3>
<ul>
<li>Tienes <strong>30 días</strong> desde la fecha de entrega para iniciar una devolución.</li>
<li>Las solicitudes fuera de plazo no podrán aceptarse, salvo excepción legal aplicable.</li>
</ul>

<h3>Condiciones del artículo</h3>
<ul>
<li>Sin usar, sin lavar y sin alteraciones.</li>
<li>Con etiquetas originales y en su empaque cuando sea posible.</li>
<li>Sin perfume, desodorante, maquillaje ni olores ajenos al producto.</li>
<li>Accesorios, ropa interior y artículos en oferta final de temporada pueden tener restricciones; se indicará en la ficha del producto cuando aplique.</li>
</ul>

<h3>Cómo iniciar una devolución</h3>
<ol>
<li>Inicia sesión en <a href="/my-account/">Mi cuenta</a> y localiza el pedido, o escríbenos desde <a href="/contact-us/">Contacto</a> con tu número de pedido.</li>
<li>Indica el o los artículos a devolver y el motivo.</li>
<li>Te confirmaremos si aplica devolución o cambio y las instrucciones de envío.</li>
<li>Empaca el artículo de forma segura y envíalo según las indicaciones recibidas.</li>
</ol>

<h3>Cambios de talla o color</h3>
<p>Los cambios están sujetos a disponibilidad de inventario. Si la nueva variante no está disponible, podemos ofrecerte un reembolso del artículo devuelto o una nota de crédito según lo acordado contigo.</p>

<h3>Reembolsos</h3>
<ul>
<li>Una vez recibido e inspeccionado el artículo, procesamos el reembolso en <strong>5–7 días hábiles</strong>.</li>
<li>El reembolso se realiza al mismo método de pago utilizado en la compra, cuando el proveedor lo permita.</li>
<li>Los costos de envío originales suelen no ser reembolsables, salvo error nuestro o producto defectuoso.</li>
<li>El costo de reenvío de la devolución corre por cuenta de la clienta, excepto en casos de defecto o envío incorrecto.</li>
</ul>

<h3>Artículos defectuosos</h3>
<p>Si recibes un producto con defecto de fabricación, contáctanos de inmediato con fotos. Gestionaremos reemplazo o reembolso sin costo adicional de envío razonable asociado al caso.</p>
HTML;
	}

	private static function content_privacy(): string {
		return <<<'HTML'
<h2>Política de privacidad</h2>
<p>En Shanelle Store respetamos tu privacidad. Esta política describe qué datos personales tratamos cuando visitas o compras en nuestra tienda en línea, con sede de operación orientada al mercado de Nicaragua.</p>

<h3>Responsable</h3>
<p>El responsable del tratamiento es Shanelle Store. Para consultas de privacidad, utiliza la página de <a href="/contact-us/">Contacto</a> o el correo publicado en el pie de página del sitio.</p>

<h3>Datos que recopilamos</h3>
<ul>
<li><strong>Datos de cuenta y pedido:</strong> nombre, correo, teléfono, dirección de envío y facturación, historial de pedidos.</li>
<li><strong>Datos de pago:</strong> los procesan pasarelas de pago de terceros; no almacenamos números completos de tarjeta en nuestros servidores.</li>
<li><strong>Datos de navegación:</strong> dirección IP, tipo de dispositivo, páginas visitadas y cookies técnicas o de medición (ver <a href="/cookie-policy/">Política de cookies</a>).</li>
<li><strong>Comunicaciones:</strong> mensajes que nos envías por formulario, correo o WhatsApp.</li>
</ul>

<h3>Para qué usamos tus datos</h3>
<ul>
<li>Procesar y entregar pedidos.</li>
<li>Gestionar tu cuenta, devoluciones y soporte.</li>
<li>Enviar confirmaciones y actualizaciones del pedido.</li>
<li>Mejorar el sitio, la seguridad y la prevención de fraude.</li>
<li>Enviar novedades o promociones solo si te suscribes o la ley lo permite; puedes darte de baja en cualquier momento.</li>
</ul>

<h3>Base y conservación</h3>
<p>Tratamos datos para cumplir el contrato de compraventa, obligaciones legales (por ejemplo, facturación) y, cuando corresponde, con tu consentimiento o interés legítimo en mejorar el servicio. Conservamos la información el tiempo necesario para estos fines y los plazos contables o legales aplicables.</p>

<h3>Compartición con terceros</h3>
<p>Podemos compartir datos con proveedores que nos ayudan a operar la tienda, siempre bajo instrucciones adecuadas:</p>
<ul>
<li>WooCommerce / WordPress (plataforma de la tienda).</li>
<li>Pasarelas de pago y procesadores financieros.</li>
<li>Empresas de logística y mensajería.</li>
<li>Herramientas de correo, formularios, analítica o publicidad, cuando estén activas.</li>
</ul>
<p>No vendemos tu información personal.</p>

<h3>Transferencias y seguridad</h3>
<p>Algunos proveedores pueden estar fuera de Nicaragua. Aplicamos medidas técnicas y organizativas razonables para proteger tus datos. Ningún sistema es 100&nbsp;% seguro; te pedimos también cuidar tus credenciales de acceso.</p>

<h3>Tus derechos</h3>
<p>Según la normativa aplicable, puedes solicitar acceso, corrección, actualización, eliminación u oposición al tratamiento de tus datos, y retirar consentimientos de marketing. Para ejercerlos, escríbenos desde <a href="/contact-us/">Contacto</a>. También puedes actualizar datos de cuenta en <a href="/my-account/">Mi cuenta</a>.</p>

<h3>Menores</h3>
<p>La tienda está dirigida a personas adultas. No recopilamos a sabiendas datos de menores de 18 años.</p>

<h3>Cambios</h3>
<p>Podemos actualizar esta política. La versión vigente se publica en esta página con la fecha de la última revisión en el sitio.</p>
HTML;
	}

	private static function content_terms(): string {
		return <<<'HTML'
<h2>Términos y condiciones</h2>
<p>Al acceder o comprar en Shanelle Store, aceptas estos términos. Si no estás de acuerdo, te pedimos no utilizar el sitio.</p>

<h3>El servicio</h3>
<p>Shanelle Store es una tienda en línea de moda femenina. Ofrecemos productos sujetos a disponibilidad de inventario. Nos reservamos el derecho de corregir errores de precio, descripción o stock y de cancelar pedidos cuando exista un error manifiesto o riesgo de fraude.</p>

<h3>Cuenta</h3>
<ul>
<li>Eres responsable de la exactitud de los datos de tu cuenta y de la confidencialidad de tu contraseña.</li>
<li>Debes tener capacidad legal para celebrar contratos de compraventa.</li>
</ul>

<h3>Pedidos y precios</h3>
<ul>
<li>Un pedido constituye una oferta de compra. La aceptación se confirma cuando recibes la confirmación de pedido / pago.</li>
<li>Los precios se muestran en la moneda configurada en la tienda e incluyen o excluyen impuestos según lo indicado en checkout.</li>
<li>Promociones y cupones tienen condiciones propias y pueden modificarse o retirarse.</li>
</ul>

<h3>Pago</h3>
<p>El pago se procesa mediante los métodos habilitados en checkout. La autorización del pago es condición para preparar el envío. Consulta también <a href="/metodos-de-pago/">Métodos de pago</a>.</p>

<h3>Envíos y devoluciones</h3>
<p>Las reglas de entrega, plazos y costos se detallan en la <a href="/shipping-policy/">Política de envíos</a>. Las devoluciones y reembolsos se rigen por la política de <a href="/refund_returns/">Devoluciones y reembolsos</a>.</p>

<h3>Propiedad intelectual</h3>
<p>Marcas, fotografías, textos, diseño y código del sitio son propiedad de Shanelle Store o de sus licenciantes. No está permitido copiarlos, redistribuirlos o usarlos con fines comerciales sin autorización.</p>

<h3>Uso aceptable</h3>
<p>No debes usar el sitio para actividades ilícitas, scrapear de forma abusiva, interferir con la seguridad o suplantar a otras personas.</p>

<h3>Limitación de responsabilidad</h3>
<p>En la medida permitida por la ley, Shanelle Store no responde por daños indirectos, lucro cesante o demoras causadas por terceros (pasarelas, mensajería, conectividad). Nuestra responsabilidad total relacionada con un pedido se limita, en general, al monto pagado por ese pedido.</p>

<h3>Ley aplicable</h3>
<p>Estos términos se interpretan conforme a las leyes aplicables en Nicaragua, sin perjuicio de derechos imperativos del consumidor. Para controversias, priorizaremos una resolución amistosa a través de <a href="/contact-us/">Contacto</a>.</p>

<h3>Cambios</h3>
<p>Podemos actualizar estos términos publicando la versión vigente en esta página. El uso continuado del sitio después de un cambio implica aceptación de la versión actualizada, cuando la ley lo permita.</p>
HTML;
	}

	private static function content_cookies(): string {
		return <<<'HTML'
<h2>Política de cookies</h2>
<p>Esta política explica cómo Shanelle Store utiliza cookies y tecnologías similares en el sitio.</p>

<h3>¿Qué son las cookies?</h3>
<p>Son pequeños archivos que el navegador guarda en tu dispositivo. Permiten recordar preferencias, mantener la sesión del carrito y, en algunos casos, medir el rendimiento del sitio.</p>

<h3>Tipos que podemos usar</h3>
<ul>
<li><strong>Esenciales:</strong> necesarias para el carrito, checkout, inicio de sesión y seguridad. No se pueden desactivar si quieres comprar en línea.</li>
<li><strong>Preferencias:</strong> recuerdan elecciones como región o consentimientos.</li>
<li><strong>Analítica:</strong> nos ayudan a entender visitas y mejorar la experiencia (solo cuando estén activadas y, si aplica, con tu consentimiento).</li>
<li><strong>Marketing:</strong> pueden usarse para medir campañas o remarketing cuando integremos píxeles de terceros.</li>
</ul>

<h3>Gestión</h3>
<ul>
<li>Puedes borrar o bloquear cookies desde la configuración de tu navegador.</li>
<li>Si bloqueas cookies esenciales, partes de la tienda (carrito o cuenta) pueden dejar de funcionar.</li>
</ul>

<h3>Más información</h3>
<p>El tratamiento de datos personales asociado a cookies se describe en la <a href="/privacy-policy/">Política de privacidad</a>. Para dudas, visita <a href="/contact-us/">Contacto</a>.</p>
HTML;
	}

	private static function content_faq(): string {
		return <<<'HTML'
<h2>Preguntas frecuentes</h2>
<p>Respuestas rápidas a las dudas más comunes. Si no encuentras lo que buscas, escríbenos desde <a href="/contact-us/">Contacto</a>.</p>

<h3>¿Cuánto tarda mi pedido?</h3>
<p>Preparación en 1–2 días hábiles y entrega estándar estimada de 3–5 días hábiles en Nicaragua, según la zona. Detalles en la <a href="/shipping-policy/">Política de envíos</a>.</p>

<h3>¿Puedo cambiar la talla?</h3>
<p>Sí, sujeto a disponibilidad y a la política de <a href="/refund_returns/">Devoluciones y reembolsos</a>. Revisa también la <a href="/guia-de-tallas/">Guía de tallas</a> antes de comprar.</p>

<h3>¿Cómo sé si hay envío gratis?</h3>
<p>Las promociones vigentes se muestran en el carrito o en banners del sitio. El costo final aparece antes de pagar.</p>

<h3>¿Qué métodos de pago aceptan?</h3>
<p>Los métodos habilitados aparecen en el checkout. Consulta <a href="/metodos-de-pago/">Métodos de pago</a> para una visión general.</p>

<h3>¿Cómo sigo mi pedido?</h3>
<p>Desde <a href="/my-account/">Mi cuenta</a> o la página de <a href="/seguimiento-de-pedidos/">Seguimiento de pedidos</a>.</p>

<h3>¿Puedo cancelar un pedido?</h3>
<p>Si aún no se ha despachado, contáctanos lo antes posible. Una vez en tránsito, aplica el flujo de devolución habitual.</p>

<h3>¿Los colores se ven iguales que en las fotos?</h3>
<p>Cuidamos la fotografía, pero la luz de pantalla puede variar el tono. Si hay una diferencia importante de calidad, te ayudamos según nuestra política de devoluciones.</p>
HTML;
	}

	private static function content_contact(): string {
		return <<<'HTML'
<p>Cuéntanos cómo podemos ayudarte. Responderemos a consultas sobre pedidos, productos, envíos y devoluciones en el menor tiempo posible.</p>
<p>También puedes usar los datos de contacto del panel (correo, teléfono o WhatsApp) o dejar tu mensaje en el formulario a continuación cuando esté configurado.</p>
HTML;
	}

	private static function content_size_guide(): string {
		return <<<'HTML'
<h2>Guía de tallas</h2>
<p>Elegir bien la talla hace que cada prenda se sienta hecha para ti. Usa una cinta métrica y mide sobre la ropa interior o una prenda ajustada.</p>

<h3>Cómo medirte</h3>
<ul>
<li><strong>Busto:</strong> contorno en la parte más plena, manteniendo la cinta horizontal.</li>
<li><strong>Cintura:</strong> contorno en la parte más estrecha del torso.</li>
<li><strong>Cadera:</strong> contorno en la parte más amplia de caderas y glúteos.</li>
<li><strong>Largo:</strong> según la prenda (tiro, entrepierna o largo total) indicado en la ficha del producto.</li>
</ul>

<h3>Consejos</h3>
<ul>
<li>Si estás entre dos tallas, revisa la descripción (ajuste oversized, regular o fitted).</li>
<li>Las tablas específicas por estilo pueden aparecer en la ficha del producto.</li>
<li>Ante la duda, contáctanos con tus medidas desde <a href="/contact-us/">Contacto</a>.</li>
</ul>

<h3>Referencia general (cm)</h3>
<ul>
<li><strong>XS:</strong> busto 78–82 · cintura 60–64 · cadera 86–90</li>
<li><strong>S:</strong> busto 82–86 · cintura 64–68 · cadera 90–94</li>
<li><strong>M:</strong> busto 86–90 · cintura 68–72 · cadera 94–98</li>
<li><strong>L:</strong> busto 90–96 · cintura 72–78 · cadera 98–104</li>
<li><strong>XL:</strong> busto 96–102 · cintura 78–84 · cadera 104–110</li>
</ul>
<p>Esta tabla es orientativa. Prioriza siempre las medidas publicadas en cada producto.</p>
HTML;
	}

	private static function content_order_tracking(): string {
		return <<<'HTML'
<h2>Seguimiento de pedidos</h2>
<p>Puedes consultar el estado de tu compra de estas formas:</p>

<h3>Desde tu cuenta</h3>
<ol>
<li>Inicia sesión en <a href="/my-account/">Mi cuenta</a>.</li>
<li>Abre <strong>Pedidos</strong> y selecciona el pedido.</li>
<li>Verás el estado actual (pendiente de pago, procesando, completado, etc.).</li>
</ol>

<h3>Con el correo de confirmación</h3>
<p>Después del pago recibirás un correo con el número de pedido. Cuando despachemos, te enviaremos la información de seguimiento disponible según el transportista.</p>

<h3>¿El estado no cambia?</h3>
<ul>
<li>Los tiempos estimados están en la <a href="/shipping-policy/">Política de envíos</a>.</li>
<li>Si el plazo ya pasó, escríbenos desde <a href="/contact-us/">Contacto</a> con tu número de pedido.</li>
</ul>
HTML;
	}

	private static function content_payment_methods(): string {
		return <<<'HTML'
<h2>Métodos de pago</h2>
<p>En el checkout verás únicamente los métodos activos en la tienda. Shanelle Store utiliza pasarelas compatibles con WooCommerce; no procesamos pagos fuera de esos flujos.</p>

<h3>Qué puedes esperar</h3>
<ul>
<li>Pago con tarjeta u otros métodos digitales cuando estén habilitados.</li>
<li>Confirmación inmediata o pendiente según el proveedor.</li>
<li>Comprobante por correo al confirmarse el pedido.</li>
</ul>

<h3>Seguridad</h3>
<ul>
<li>Los datos de tarjeta se manejan en la pasarela de pago; no los guardamos en texto completo en la tienda.</li>
<li>Usa siempre la URL oficial de Shanelle Store y no compartas contraseñas ni códigos OTP.</li>
</ul>

<h3>Problemas al pagar</h3>
<p>Si un pago es rechazado, verifica saldo, datos de tarjeta y que tu banco autorice compras en línea. Si el monto fue retenido pero el pedido no aparece, contáctanos con el comprobante desde <a href="/contact-us/">Contacto</a>.</p>
HTML;
	}
}
