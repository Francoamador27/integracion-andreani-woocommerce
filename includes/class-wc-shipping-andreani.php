<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * WC_Shipping_Andreani class.
 *
 * @extends WC_Shipping_Method
 */
class WC_Shipping_Andreani extends WC_Shipping_Method
{
	private $found_rates;


	public function __construct($instance_id = 0)
	{

		$this->id = 'andreani_eon';
		$this->instance_id = absint($instance_id);
		$this->method_title = __('Andreani Envios', 'woocommerce-shipping-andreani');
		$this->method_description = __('Obtain shipping rates dynamically via the Andreani API for your orders.', 'woocommerce');
		$this->supports = array(
			'shipping-zones',
			'instance-settings',
		);

		$this->init();

		add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));

	}

	public function init()
	{
		$settings_path = __DIR__ . '/settings/settings.php';

		$settings = include $settings_path;

		$this->init_form_fields = $settings;
		$this->init_settings();
		$this->instance_form_fields = $settings;

		$this->title = $this->get_option('title', $this->method_title);
		$this->origin = apply_filters('woocommerce_andreani_origin_postal_code', str_replace(' ', '', strtoupper($this->get_option('origin'))));
		$this->origin_country = apply_filters('woocommerce_andreani_origin_country_code', WC()->countries->get_base_country());
		$this->api_key = $this->get_option('api_key');
		$this->origin_contacto = $this->get_option('origin_contacto');
		$this->origin_email = $this->get_option('origin_email');
		$this->origin_calle = $this->get_option('origin_calle');
		$this->origin_numero = $this->get_option('origin_numero');
		$this->origin_piso = $this->get_option('origin_piso');
		$this->origin_depto = $this->get_option('origin_depto');
		$this->origin_landreanilidad = $this->get_option('origin_landreanilidad');
		$this->origin_provincia = $this->get_option('origin_provincia');
		$this->origin_observaciones = $this->get_option('origin_observaciones');
		$this->api_user = $this->get_option('api_user');
		$this->api_password = $this->get_option('api_password');
		$this->api_nrocuenta = $this->get_option('api_nrocuenta');
		$this->api_confirmarretiro = $this->get_option('api_confirmarretiro');
		$this->ajuste_precio = $this->get_option('ajuste_precio');
		$this->ajuste_gratis = $this->get_option('ajuste_gratis');
		$this->tipo_servicio = $this->get_option('tipo_servicio');
		$this->debug = ($bool = $this->get_option('debug')) && $bool == 'yes' ? true : false;
		$this->services = $this->get_option('services', array());
		$this->mercado_pago = ($bool = $this->get_option('mercado_pago')) && $bool == 'yes' ? true : false;
		$this->redondear_total = ($bool = $this->get_option('redondear_total')) && $bool == 'yes' ? true : false;
	}


	public function debug($message, $type = 'notice')
	{
		if ($this->debug) {
			wc_add_notice($message, $type);
		}
	}


	private function environment_check()
	{
		if (!in_array(WC()->countries->get_base_country(), array('AR'))) {
			echo '<div class="error">
				<p>' . __('Argentina tiene que ser el pais de Origen.', 'woocommerce-shipping-andreani') . '</p>
			</div>';
		} elseif (!$this->origin && $this->enabled == 'yes') {
			echo '<div class="error">
				<p>' . __('Andreani esta activo, pero no hay Codigo Postal.', 'woocommerce-shipping-andreani') . '</p>
			</div>';
		}
	}


	public function admin_options()
	{
		$this->environment_check();

		parent::admin_options();
	}

	public function generate_service_html()
	{
		ob_start();
		include __DIR__ . '/settings/services.php';;
		return ob_get_clean();
	}


	/**
	 * validate_box_packing_field function.
	 *
	 * @param mixed $key
	 */
	public function validate_service_field($key)
	{

		$service_name = isset($_POST['service_name']) ? $_POST['service_name'] : array();
		$service_operativa = isset($_POST['service_operativa']) ? $_POST['service_operativa'] : array();
		$service_sucursal = isset($_POST['woocommerce_andreani_eon_modalidad']) ? $_POST['woocommerce_andreani_eon_modalidad'] : array();
		$service_enabled = isset($_POST['service_enabled']) ? $_POST['service_enabled'] : array();

		$services = array();

		if (!empty($service_operativa) && sizeof($service_operativa) > 0) {
			for ($i = 0; $i <= max(array_keys($service_operativa)); $i++) {

				if (!isset($service_operativa[$i]))
					continue;

				if ($service_operativa[$i]) {
					$services[] = array(
						'service_name' => $service_name[$i],
						'operativa' => floatval($service_operativa[$i]),
						'woocommerce_andreani_eon_modalidad' => $service_sucursal[$i],
						'enabled' => isset($service_enabled[$i]) ? true : false
					);
				}
			}

		}

		return $services;
	}


	/**
	 * per_item_shipping function.
	 *
	 * @access private
	 * @param mixed $package
	 * @return array
	 */
	private function per_item_shipping($package)
	{
		$to_ship = array();
		$group_id = 1;

		// Get weight of order
		foreach ($package['contents'] as $item_id => $values) {

			if ($values['stamp']) {
				$group = array();

				foreach ($values['stamp'] as $prodcutos) {

					$_product = wc_get_product($prodcutos['product_id']);

					$group = array(
						'GroupNumber' => $group_id,
						'GroupPackageCount' => $prodcutos['quantity'],
						'Weight' => array(
							'Value' => $_product->get_weight(),
							'Units' => 'KG'
						),
						'packed_products' => array($prodcutos['product_id'])
					);

					if ($_product->get_length() && $_product->get_height() && $_product->get_width()) {

						$dimensions = array($_product->get_length(), $_product->get_width(), $_product->get_height());

						sort($dimensions);

						$group['Dimensions'] = array(
							'Length' => $_product->get_length(),
							'Width' => $_product->get_width(),
							'Height' => $_product->get_height(),
							'Units' => 'CM'
						);
					}

					$group['InsuredValue'] = array(
						'Amount' => round($_product->get_price()),
						'Currency' => get_woocommerce_currency()
					);

					$to_ship[] = $group;

					$group_id++;

				}


				return $to_ship;

			} else {

				if (!$values['data']->needs_shipping()) {
					$this->debug(sprintf(__('Product # is virtual. Skipping.', 'woocommerce-shipping-andreani'), $item_id), 'error');
					continue;
				}

				if (!$values['data']->get_weight()) {
					$this->debug(sprintf(__('Product # is missing weight. Aborting.', 'woocommerce-shipping-andreani'), $item_id), 'error');
					return;
				}

				$group = array();

				$group = array(
					'GroupNumber' => $group_id,
					'GroupPackageCount' => $values['quantity'],
					'Weight' => array(
						'Value' => $values['data']->get_weight(),
						'Units' => 'KG'
					),
					'packed_products' => array($values['data'])
				);

				if ($values['data']->get_length() && $values['data']->get_height() && $values['data']->get_width()) {

					$dimensions = array($values['data']->get_length(), $values['data']->get_width(), $values['data']->get_height());

					sort($dimensions);

					$group['Dimensions'] = array(
						'Length' => $values['data']->get_length(),
						'Width' => $values['data']->get_width(),
						'Height' => $values['data']->get_height(),
						'Units' => 'CM'
					);
				}

				$group['InsuredValue'] = array(
					'Amount' => round($values['data']->get_price()),
					'Currency' => get_woocommerce_currency()
				);

				$to_ship[] = $group;

				$group_id++;
			}




		}

		return $to_ship;
	}

	private function get_member_discount_from_session()
	{
		try {
			if (WC()->session && method_exists(WC()->session, 'get')) {
				return WC()->session->get('member_discount_applied');
			}
			return null;
		} catch (Exception $e) {
			$logger = new WC_Logger();
			$logger->add('andreani_shipping', 'Error al obtener datos de sesión: ' . $e->getMessage());
			return null;
		}
	}


	public function calculate_shipping($package = array())
	{
		global $woocommerce;

		// Totales de carrito (defensivo ante índices faltantes)
		$session_data = is_object($woocommerce->session) ? $woocommerce->session->get_session_data() : [];
		$amount = isset($session_data['cart_totals']) ? maybe_unserialize($session_data['cart_totals']) : [];
		$subtotal = isset($amount['subtotal']) ? (float) $amount['subtotal'] : 0.0;
		$descuento = isset($amount['discount_total']) ? (float) $amount['discount_total'] : 0.0;

		$member_discount_data = $this->get_member_discount_from_session();
		$member_discount = (is_array($member_discount_data) && isset($member_discount_data['discount_amount']))
			? (float) $member_discount_data['discount_amount'] : 0.0;

		$monto_neto = max(0.0, $subtotal - $member_discount - $descuento);

		$this->debug(__('Andreani modo de depuración está activado - para ocultar estos mensajes, desactive el modo de depuración en los ajustes.', 'woocommerce-shipping-andreani'));

		// Paquetes preparados por tu método
		$andreani_packages = $this->per_item_shipping($package);

		// Acumuladores corregidos
		$andreani_amount = 0.0;   // asegurado total (suma de ítems válidos)
		$total_weight_kg = 0.0;   // peso TOTAL en kg
		$max_volume_cm3 = 0;     // volumen MÁXIMO (no suma) en cm³
		$andreani_packageb = 1;

		foreach ($andreani_packages as $key) {
			// Cantidad de bultos de este item/grupo
			$pkg_qty = isset($key['GroupPackageCount']) ? (int) $key['GroupPackageCount'] : 1;

			// Peso normalizado a KG
			$w_val = isset($key['Weight']['Value']) ? (float) $key['Weight']['Value'] : 0;
			$weight_kg = (float) wc_get_weight($w_val, 'kg');

			// Dimensiones normalizadas a CM
			$dim = isset($key['Dimensions']) ? $key['Dimensions'] : ['Length' => 0, 'Width' => 0, 'Height' => 0];
			$len_cm = (float) wc_get_dimension($dim['Length'] ?? 0, 'cm');
			$wid_cm = (float) wc_get_dimension($dim['Width'] ?? 0, 'cm');
			$hei_cm = (float) wc_get_dimension($dim['Height'] ?? 0, 'cm');

			// Si faltan dimensiones válidas, saltamos este grupo
			if ($len_cm <= 0 || $wid_cm <= 0 || $hei_cm <= 0) {
				continue;
			}

			// Asegurado del ítem (no del acumulado)
			$item_insured = isset($key['InsuredValue']['Amount']) ? (float) $key['InsuredValue']['Amount'] : 0;
			if ($item_insured <= 0) {
				continue;
			}

			// Volumen por bulto en cm³
			$vol_cm3_per_pkg = $len_cm * $wid_cm * $hei_cm;

			// Actualiza el mayor volumen encontrado (independiente de la cantidad)
			if ($vol_cm3_per_pkg > $max_volume_cm3) {
				$max_volume_cm3 = $vol_cm3_per_pkg;
			}

			// Peso total: suma peso*bultos
			$total_weight_kg += ($weight_kg * $pkg_qty);

			// Suma asegurado
			$andreani_amount += $item_insured;

			$andreani_packageb = 1;
		}

		// Normalizaciones finales para la consulta
		$andreani_volumesy = (int) max(1, round($max_volume_cm3));           // cm³ entero, nunca 0
		$andreani_weightb = (float) max(0.001, round($total_weight_kg, 3));  // kg con 3 decimales

		// (Si usabas $amount['total'] como "seguro", mantenelo por si lo necesitás)
		$seguro = isset($amount['total']) ? (float) $amount['total'] : 0.0;

		// Tarificación por cada servicio habilitado
		foreach ($this->services as $services) {
			if (empty($services['enabled'])) {
				continue;
			}

			$params_hash = md5(serialize([
				$this->api_nrocuenta,
				$services['operativa'] ?? '',
				$andreani_weightb,
				$andreani_volumesy,
				$this->origin,
				$package['destination']['postcode'] ?? '',
			]));

			$precio = get_transient("andreani_cart_$params_hash");

			if (!$precio) {
				$precio = $this->consultar_api_andreani(
					$andreani_weightb,            // kg total
					$andreani_volumesy,           // cm³ (máximo)
					$this->origin,
					$package['destination']['postcode'] ?? '',
					$services['operativa'] ?? ''
				);

				if (!$precio || $precio <= 0) {
					continue;
				}

				set_transient("andreani_cart_$params_hash", $precio, HOUR_IN_SECONDS);
			}

			$ajuste = (is_numeric($this->ajuste_precio)) ? (float) $this->ajuste_precio : 0;
			$precio += ($precio * $ajuste / 100);

			if (!empty($this->redondear_total)) {
				$precio = round($precio, 0, PHP_ROUND_HALF_UP);
			}

			if (is_numeric($this->ajuste_gratis) && $monto_neto >= (float) $this->ajuste_gratis) {
				$precio = 0;
				$titulo = $services['service_name'] . ' GRATIS';
			} else {
				$titulo = $services['service_name'];
			}

			$rate = array(
				'id' => sprintf("%s-%s", sanitize_title($titulo), $services['service_name'] . '-' . ($services['woocommerce_andreani_eon_modalidad'] ?? '') . 'api_nrocuenta' . $this->api_nrocuenta . 'operativa' . ($services['operativa'] ?? '') . 'instance_id' . $this->instance_id),
				'label' => $titulo,
				'calc_tax' => 'per_item',
				'cost' => $precio
			);

			$this->add_rate($rate);
		}
	}


	public function sort_rates($a, $b)
	{
		if ($a['sort'] == $b['sort'])
			return 0;
		return ($a['sort'] < $b['sort']) ? -1 : 1;
	}
	private function consultar_api_andreani($peso_total, $volumen_total, $cp_origen, $cp_destino, $operativa)
	{
		try {
			// Base URL de la nueva API de Andreani
			$base_url = 'https://apis.andreani.com/v1/tarifas';

			// Construir parámetros de la URL
			$params = array(
				'cpDestino' => $cp_destino,
				'contrato' => $operativa, // Debes definir esta propiedad
				'cliente' => $this->api_nrocuenta,   // Debes definir esta propiedad
				'bultos[0][volumen]' => number_format($volumen_total, 0, '', ''), // Sin decimales según el ejemplo
				'bultos[0][peso]' => number_format($peso_total, 2, '.', ''), // Con decimales para el peso
			);

			// Agregar parámetros adicionales si son necesarios
			if (!empty($cp_origen)) {
				$params['cpOrigen'] = $cp_origen;
			}

			// Construir la URL completa con parámetros
			$url = $base_url . '?' . http_build_query($params);

			// Obtener el token Bearer (debes implementar esta función)
			$bearer_token = $this->login_andreani();

			if (!$bearer_token) {
				return null; // No se pudo obtener el token
			}

			$response = wp_remote_get($url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $bearer_token,
					'Content-Type' => 'application/json',
				),
				'timeout' => 15,
			));

			if (is_wp_error($response)) {
				return null;
			}

			$data = json_decode(wp_remote_retrieve_body($response), true);

			if (isset($data['tarifaConIva']['total'])) {
				return floatval($data['tarifaConIva']['total']);
			}

			return null;
		} catch (Exception $e) {
			return null;
		}

	}
	private function login_andreani()
	{
		try {
			$transient_key = 'token_andreani_login';
			$token = get_transient($transient_key);

			if ($token) {
				return $token;
			}

			$url = $this->get_andreani_api_url() . '/login';

			$credenciales = $this->get_user_password();

			$body = [
				"userName" => $credenciales['user'],
				"password" => $credenciales['password'],
			];

			$args = [
				'method' => 'POST',
				'headers' => [
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
				],
				'body' => json_encode($body),
				'timeout' => 15,
			];

			$response = wp_remote_post($url, $args);

			if (is_wp_error($response)) {
				return null;
			}

			$json = wp_remote_retrieve_body($response);
			$data = json_decode($json, true);

			if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
				return null;
			}

			$token = $data['token'] ?? null;

			if ($token) {
				// Guardar el token durante 23 horas
				set_transient($transient_key, $token, 23 * HOUR_IN_SECONDS);
			}

			return $token;

		} catch (Throwable $e) {
			return null;
		}
	}
	private function get_andreani_api_url($fallback = 'https://apis.andreani.com')
	{
		$delivery_zones = WC_Shipping_Zones::get_zones();

		foreach ($delivery_zones as $zone) {
			foreach ($zone['shipping_methods'] as $method) {
				if ($method->id === 'andreani_eon' && $method->enabled === 'yes') {
					$environment = $method->instance_settings['entorno_api'] ?? 'produccion';
					return $environment === 'desarrollo'
						? 'https://apisqa.andreani.com'
						: 'https://apis.andreani.com';
				}
			}
		}

		return $fallback;
	}
	private function get_user_password()
	{
		$delivery_zones = WC_Shipping_Zones::get_zones();

		foreach ($delivery_zones as $zone) {
			foreach ($zone["shipping_methods"] as $method) {
				if ($method->id === "andreani_eon" && $method->enabled === "yes") {
					return [
						'user' => $method->instance_settings['api_user'] ?? '',
						'password' => $method->instance_settings['api_password'] ?? ''
					];
				}
			}
		}

		// Si no se encontró nada
		return ['user' => '', 'password' => ''];
	}
	private function log_to_file($data)
	{
		$log_file = plugin_dir_path(__FILE__) . 'andreani_log.txt';
		$log_data = "Log entry at " . date("Y-m-d H:i:s") . "\n";
		$log_data .= print_r($data, true) . "\n";
		file_put_contents($log_file, $log_data, FILE_APPEND);
	}

}