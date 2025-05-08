<?php
if (!defined('ABSPATH'))
	exit; // Exit if accessed directly 

if (isset($_COOKIE['andreani_notice'])) {
	$_SESSION['andreani_notice'] = sanitize_text_field(wp_unslash($_COOKIE['andreani_notice']));
	add_action('admin_notices', 'andreani_notificacion_admin');
}
add_action('wp_footer', 'andreani_validar_cp');
function andreani_validar_cp()
{
	if (is_checkout()) {
		$response = wp_register_script('funciones', ANDREANI_PLUGIN_URL . '/includes/js/funciones.js', array('jquery'), ANDREANI_VERSION, true);
		$response2 = wp_enqueue_script('funciones');
	}
}
function andreani_enviar_orden($order_id)
{
	global $wp_session;

	$headers = [
		'Content-Type'  => 'application/json',
		'Authorization' => $wp_session["cliente_andreani"]
	];

	$woocommerce_email_from_address = get_option('woocommerce_email_from_address', array());
	$order_raw = wc_get_order($order_id);
	$metodo_envio = get_post_meta($order_id, "_chosen_shipping", true);

	$contrato = isset($wp_session['contratos_por_modalidad'][$metodo_envio])
		? strval($wp_session['contratos_por_modalidad'][$metodo_envio])
		: '100006924';

	$total_ancho = $total_alto = $total_largo = $total_peso = $total_volumen = 0;

	foreach ($order_raw->get_items() as $item) {
		$product = wc_get_product($item->get_product_id());
		$qty = $item->get_quantity();

		$total_ancho  += $qty * (int)$product->get_width();
		$total_alto   += $qty * (int)$product->get_height();
		$total_largo  += $qty * (int)$product->get_length();
		$total_peso   += $qty * (int)$product->get_weight();
		$total_volumen += $qty * (
			(int)$product->get_width() *
			(int)$product->get_height() *
			(int)$product->get_length()
		);
	}

	$bultos = [[
		'anchoCm' => $total_ancho,
		'altoCm' => $total_alto,
		'largoCm' => $total_largo,
		'kilos' => $total_peso,
		'volumenCm' => $total_volumen,
		'valorDeclaradoSinImpuestos' => floatval($order_raw->get_shipping_total()),
		'valorDeclaradoConImpuestos' => floatval($order_raw->get_total()),
		'referencias' => [
			['meta' => 'producto'],
			['meta' => 'idCliente', 'contenido' => strval($order_raw->get_customer_id())],
			['meta' => 'observaciones', 'contenido' => '']
		]
	]];

	$data = [
		'contrato' => $contrato,
		'idPedido' => "'" . $order_raw->get_id() . "'",
		'origen' => [
			'postal' => [
				'codigoPostal' => $order_raw->get_billing_postcode(),
				'calle' => $order_raw->get_billing_address_1(),
				'numero' => '3138',
				'localidad' => $order_raw->get_billing_city(),
				'region' => 'AR-B',
				'pais' => $order_raw->get_billing_country(),
				'componentesDeDireccion' => [
					['meta' => 'entreCalle', 'contenido' => '']
				]
			]
		],
		'destino' => [
			'postal' => [
				'codigoPostal' => $order_raw->get_shipping_postcode(),
				'calle' => $order_raw->get_shipping_address_1(),
				'numero' => '0',
				'localidad' => $order_raw->get_shipping_city(),
				'region' => '',
				'pais' => $order_raw->get_shipping_country(),
				'componentesDeDireccion' => [
					['meta' => 'piso', 'contenido' => '0'],
					['meta' => 'departamento', 'contenido' => '0']
				]
			]
		],
		'remitente' => [
			'nombreCompleto' => $order_raw->get_billing_first_name() . " " . $order_raw->get_billing_last_name(),
			'email' => $woocommerce_email_from_address,
			'documentoTipo' => 'DNI',
			'documentoNumero' => '',
			'telefonos' => [['tipo' => 1, 'numero' => $order_raw->get_billing_phone()]]
		],
		'destinatario' => [[
			'nombreCompleto' => $order_raw->get_shipping_first_name() . " " . $order_raw->get_shipping_last_name(),
			'email' => $order_raw->get_billing_email(),
			'documentoTipo' => 'DNI',
			'documentoNumero' => '00000000',
			'telefonos' => [['tipo' => 2, 'numero' => $order_raw->get_billing_phone()]]
		]],
		'remito' => [
			'numeroRemito' => $order_raw->get_order_key()
		],
		'bultos' => $bultos
	];

	if ($metodo_envio === "pasp" || $metodo_envio === "papp") {
		$andreani_response = wp_remote_post($wp_session["url_andreani_orden"], [
			'headers' => $headers,
			'timeout' => 40,
			'body' => wp_json_encode($data),
			'method' => 'POST'
		]);
	}
	
	if (isset($andreani_response["body"])) {
		$body_response = explode(" ", $andreani_response["body"]);
	
		// Evitar errores si no existen los índices esperados
		$nro_envio = isset($body_response[9]) ? sanitize_text_field($body_response[9]) : '';
		$url_etiqueta = isset($body_response[27]) ? str_replace("<br>", "", $body_response[27]) : '';
	
		if ($nro_envio) update_post_meta($order_id, 'nro_envio', $nro_envio);
		if ($url_etiqueta) update_post_meta($order_id, 'url_etiqueta', $url_etiqueta);
	
		ob_start(); ?>
		<div class='woocommerce-column woocommerce-column--3 woocommerce-column--shipping-address col-3'>
			<h2 class='woocommerce-column__title'>Datos de envío Andreani</h2>
			<p>Número de envío Andreani: <?= esc_html($nro_envio); ?></p>
			<p>Tracking para seguimiento: 
				<a href="https://www.andreani.com/envio/<?= esc_html($nro_envio); ?>" target="_blank">
					https://www.andreani.com/envio/<?= esc_html($nro_envio); ?>
				</a>
			</p>
			<p><strong>El tracking del envío estará activo una vez que el vendedor despache su compra en la Sucursal Andreani. Le aconsejamos revisar el seguimiento en 24hs.</strong></p>
		</div>
		<?php
		$html = ob_get_clean();
	
		if (defined('DOING_AJAX') && DOING_AJAX) {
			return [
				'success' => true,
				'html' => $html,
				'nro_envio' => $nro_envio,
				'etiqueta' => $url_etiqueta
			];
		}
	
		echo $html;
	}
	
	
}



add_action('woocommerce_thankyou', "andreani_enviar_orden");
/**
 * Update the order meta with field value
 */
add_action('woocommerce_checkout_update_order_meta', 'andreani_actualizar_metodo_envio');
function andreani_actualizar_metodo_envio($order_id)
{
	session_start();
	//$chosen_shipping = json_encode($_SESSION['chosen_shipping'] );
	$params_andreani = "";
	if (isset($_SESSION['params_andreani']))
		$params_andreani = wp_json_encode(sanitize_text_field(wp_unslash($_SESSION['params_andreani'])));
	$chosen_shipping = WC()->session->get('chosen_shipping_methods');

	update_post_meta($order_id, '_params_andreani', $params_andreani);
	update_post_meta($order_id, '_chosen_shipping', $chosen_shipping[0]);

	if (!empty($_POST['sucursales_andreani'])) {
		update_post_meta($order_id, 'sucursal_andreani', sanitize_text_field(wp_unslash($_POST['sucursales_andreani'])));
	}
}


function andreani_notificacion_admin()
{
	if (isset($_SESSION['andreani_notice'])) { ?>
		<div class="notice error my-acf-notice is-dismissible">
			<p><?php print (esc_html(sanitize_text_field($_SESSION['andreani_notice']))); ?></p>
		</div>

	<?php }
}


add_filter('woocommerce_form_field', 'andreani_style_select_field', 10, 4);
function andreani_style_select_field($field, $key, $args, $value)
{
	if ($key === 'sucursales_andreani' && is_array($args['class'])) {
		$args['class'][] = 'custom-select-field-style';
		$field = str_replace('select', 'select ' . esc_attr(join(' ', $args['class'])), $field);
	}
	return $field;
}
function andreani_obtener_api_sucursales()
{

	global $wp_session;
	$headers = array(
		'Content-Type' => 'application/json',
		'Authorization' => $wp_session["cliente_andreani"]

	);
	//if(!isset($sucursales)  || is_empty($sucursales) ){
	$response = wp_remote_post($wp_session["url_andreani_sedes"], array(
		'timeout' => 155, // Set the timeout in seconds

		'headers' => $headers,
		'body' => '{}',
		'method' => 'POST'


	));



	$body = wp_remote_retrieve_body($response);
	$options = json_decode($body, true);
	if (isset($options["message"])) {

		return array();

	}
	//}

	return $options;
}
add_action('woocommerce_after_shipping_rate', 'andreani_obtener_sucursales', 10, 2);
add_action('wp_ajax_andreani_reenviar_orden', function () {
    if (
        !current_user_can('manage_woocommerce') ||
        !check_ajax_referer('andreani_reenviar', '_ajax_nonce', false)
    ) {
        wp_send_json_error('No autorizado');
    }

    $order_id = absint($_POST['order_id'] ?? 0);
    if (!$order_id) wp_send_json_error('ID inválido');

    $response = andreani_enviar_orden($order_id);

    if (!empty($response['success'])) {
        wp_send_json_success($response); // ⬅️ Enviamos todo al JS
    } else {
        wp_send_json_error($response);
    }
});


function andreani_obtener_sucursales($method, $index)
{
	// Mostrar solo si el método tiene el ID exacto 'pasp'
	if ($method->get_id() === 'pasp') {
		global $woocommerce;

		wp_register_style('sucursales_css', ANDREANI_PLUGIN_URL . '/includes/css/sucursales.css', ANDREANI_VERSION, true);
		wp_enqueue_style('sucursales_css');

		$options = andreani_obtener_api_sucursales();
		$array_combo = array("0" => "Seleccione una sucursal");
		$codigo_postal_x_sucursal = array("0" => "");

		foreach ($options as $op) {
			$array_combo[$op["numero"] . "#" . $op["direccion"]["codigoPostal"]] = $op["direccion"]["provincia"] . "-" . $op["descripcion"] . "-" . $op["direccion"]["calle"] . " " . $op["direccion"]["numero"] . "-" . $op["direccion"]["localidad"];
			$codigo_postal_x_sucursal[$op["codigo"]] = $op["direccion"]["codigoPostal"];
		}

		asort($array_combo);

		WC()->session->set('codigo_postal_x_sucursal', $codigo_postal_x_sucursal);
		WC()->session->set('array_combo', $array_combo);

		woocommerce_form_field('sucursales_andreani', array(
			'type' => 'select',
			'options' => $array_combo,
			'label' => __('Sucursales Andreani', 'grupo-logistico-andreani'),
			'class' => array('custom-select-field-style'),
			'placeholder' => __('Seleccione una sucursal', 'grupo-logistico-andreani')
		), $woocommerce->checkout->get_value('sucursales_andreani'));

		echo "<br/>";
	}
}



add_action('woocommerce_after_shipping_calculator', 'andreani_grabar_codigo_sucursal');

function andreani_grabar_codigo_sucursal()
{
	if (isset($_POST['sucursales_andreani'])) {
		WC()->session->set('codigo_sucursal', sanitize_text_field(wp_unslash($_POST['sucursales_andreani'])));
	}
}

function andreani_modificar_contenido_orden($order)
{
	$order_raw = wc_get_order($order);
	$nro_envio = get_post_meta($order, "nro_envio", true);

	echo "<br><div><h2>Datos de envio Andreani ii</h2>
			<p>Número de envio Andreani: " . esc_html($nro_envio) . " </p>
			<p>Tracking para seguimiento: <a href=https://www.andreani.com/envio/" . esc_html($nro_envio) . ">https://www.andreani.com/envio/" . esc_html($nro_envio) . "</a> </p>
			</div>
			<p><strong>El tracking del envío estará activo una vez que el vendedor despache su compra en la Sucursal Andreani. Le aconsejamos revisar el seguimiento en 24hs.</strong></p>";
}

add_action('woocommerce_view_order', 'andreani_modificar_contenido_orden');

add_action('add_meta_boxes', 'andreani_agregar_metabox_envio');

function andreani_agregar_metabox_envio() {
    global $post;

    // Asegurarse de que sea un pedido válido
    if ( ! $post || $post->post_type !== 'shop_order' ) {
        return;
    }

    $order = wc_get_order( $post->ID );

    if ( ! $order ) return;

    // Verificar si el método de envío del pedido es Andreani
    $tiene_envio_andreani = false;

    foreach ( $order->get_shipping_methods() as $shipping_method ) {
        if ( $shipping_method->get_method_id() === 'andreani_flexipaas' ) {
            $tiene_envio_andreani = true;
            break;
        }
    }

    // Solo agregar el metabox si es Andreani
    if ( $tiene_envio_andreani ) {
        add_meta_box(
            'andreani_datos_envio',                    // ID
            'Datos de Envío Andreani',                 // Título
            'andreani_contenido_metabox_envio',        // Callback
            'shop_order',                              // Post type
            'side',                                    // Ubicación ('normal', 'side')
            'high'                                  // Prioridad
        );
    }
}
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen->post_type !== 'shop_order') return;
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('#reenviar-andreani').on('click', function(e) {
                e.preventDefault();
                const button = $(this);
                const orderId = button.data('order-id');
                const nonce = button.data('nonce');
                const respuesta = $('#respuesta-andreani');

                button.prop('disabled', true).text('Enviando...');
                respuesta.text('Procesando...');

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'andreani_reenviar_orden',
                        order_id: orderId,
                        _ajax_nonce: nonce
                    },
                    success: function(res) {
                        console.log('✅ Respuesta completa:', res.data);
                        if (res.data.html) {
                            respuesta.html(res.data.html);
                        } else {
                            respuesta.text('Orden enviada correctamente.');
                        }
                        button.prop('disabled', false).text('Reenviar a Andreani');
                    },
                    error: function(err) {
                        console.error('❌ Error:', err);
                        respuesta.text('Error al reenviar la orden.');
                        button.prop('disabled', false).text('Reenviar a Andreani');
                    }
                });
            });
        });
    </script>
    <?php
});


function andreani_contenido_metabox_envio($post) {
    $order = wc_get_order($post->ID);
    if (!$order) return;

    $nro_envio    = get_post_meta($order->get_id(), 'nro_envio', true);
    $url_etiqueta = get_post_meta($order->get_id(), 'url_etiqueta', true);

    echo "<p><strong>Nro. de Envío:</strong><br>" . esc_html($nro_envio) . "</p>";

    if ($nro_envio) {
        echo "<p><strong>Seguimiento:</strong><br><a href='https://www.andreani.com/envio/" . esc_html($nro_envio) . "' target='_blank'>Ver estado</a></p>";
    }

    if ($url_etiqueta) {
        echo "<p><strong>Etiqueta:</strong><br><a href='" . esc_html($url_etiqueta) . "' target='_blank'>Descargar</a></p>";
    }

    echo "<p style='font-size: 12px; color: #555;'>El tracking estará activo luego del despacho en sucursal.</p>";

    // Botón AJAX con nonce
    $nonce = wp_create_nonce('andreani_reenviar');

    echo '<p>
        <button 
            type="button" 
            class="button button-primary" 
            id="reenviar-andreani" 
            data-order-id="' . esc_attr($order->get_id()) . '" 
            data-nonce="' . esc_attr($nonce) . '">
            Reenviar a Andreani
        </button>
    </p>';

    echo '<div id="respuesta-andreani" style="margin-top: 10px;"></div>';
}





?>