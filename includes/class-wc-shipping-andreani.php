<?php
error_reporting(0);
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
	private $default_boxes;
	private $found_rates;

	/**
	 * Constructor
	 */
	public function __construct($instance_id = 0)
	{

		$this->id = 'andreani_wanderlust';
		$this->instance_id = absint($instance_id);
		$this->method_title = __('Andreani Envios', 'woocommerce-shipping-andreani');
		$this->method_description = __('Obtain shipping rates dynamically via the Andreani API for your orders.', 'woocommerce');
		$this->default_boxes = include('data/data-box-sizes.php');
		$this->supports = array(
			'shipping-zones',
			'instance-settings',
		);

		$this->init();

		add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));

	}

	/**
	 * init function.
	 */
	public function init()
	{
		// Load the settings.
		$this->init_form_fields = include('data/data-settings.php');
		$this->init_settings();
		$this->instance_form_fields = include('data/data-settings.php');

		// Define user set variables
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

	/**
	 * Output a message
	 */
	public function debug($message, $type = 'notice')
	{
		if ($this->debug) {
			wc_add_notice($message, $type);
		}
	}

	/**
	 * environment_check function.
	 */
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

	/**
	 * admin_options function.
	 */
	public function admin_options()
	{
		// Check users environment supports this method
		$this->environment_check();

		// Show settings
		parent::admin_options();
	}


	/**
	 * generate_box_packing_html function.
	 */
	public function generate_service_html()
	{
		ob_start();
		include('data/services.php');
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
		$service_sucursal = isset($_POST['woocommerce_andreani_wanderlust_modalidad']) ? $_POST['woocommerce_andreani_wanderlust_modalidad'] : array();
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
						'woocommerce_andreani_wanderlust_modalidad' => $service_sucursal[$i],
						'enabled' => isset($service_enabled[$i]) ? true : false
					);
				}
			}

		}

		return $services;
	}

	/**
	 * Get packages - divide the WC package into packages/parcels suitable for a OCA quote
	 */
	public function get_andreani_packages($package)
	{
		switch ($this->packing_method) {
			case 'box_packing':
				return $this->box_shipping($package);
				break;
			case 'per_item':
			default:
				return $this->per_item_shipping($package);
				break;
		}
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

	private function get_member_discount_from_session() {
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

	/*
	 * calculate_shipping function.
	 *
	 * @param mixed $package
	 */
public function calculate_shipping($package = array())
{
	global $woocommerce;

	$session_data = $woocommerce->session->get_session_data();
	$amount = maybe_unserialize($session_data['cart_totals']);
	$subtotal = floatval($amount['subtotal']);
	$descuento = floatval($amount['discount_total']);
	$member_discount_data = $this->get_member_discount_from_session();
	$monto_neto = $subtotal - $member_discount_data['discount_amount'] - $descuento;

	$this->debug(__('Andreani modo de depuración está activado - para ocultar estos mensajes, desactive el modo de depuración en los ajustes.', 'woocommerce-shipping-andreani'));

	$andreani_packages = $this->get_andreani_packages($package);
	$dimension_unit = esc_attr(get_option('woocommerce_dimension_unit'));
	$weight_unit = esc_attr(get_option('woocommerce_weight_unit'));

	$dimension_multi = ($dimension_unit === 'm') ? 1 : (($dimension_unit === 'cm') ? 100 : 1000);
	$weight_multi = ($weight_unit === 'g') ? 0.001 : 1;

	$andreani_amount = 0;
	$andreani_weightb = 0;
	$andreani_volumesy = 0;
	$andreani_packageb = 1;

	foreach ($andreani_packages as $key) {
		$andreani_package = $key['GroupPackageCount'];
		$andreani_weight = $key['Weight']['Value'] * $weight_multi;
		$andreani_lenth = $key['Dimensions']['Length'] / $dimension_multi;
		$andreani_width = $key['Dimensions']['Width'] / $dimension_multi;
		$andreani_height = $key['Dimensions']['Height'] / $dimension_multi;

		if ($andreani_lenth == 0 || $andreani_width == 0 || $andreani_height == 0) {
			continue;
		}

		$andreani_amount += $key['InsuredValue']['Amount'];
		if ($andreani_amount == 0) {
			continue;
		}

		$andreani_weightb += $andreani_weight * $andreani_package;
		$andreani_volume = $andreani_lenth * $andreani_width * $andreani_height;
		$andreani_volumesy += $andreani_volume * $andreani_package;
		$andreani_packageb = 1;
	}
	$andreani_volumesy = number_format($andreani_volumesy, 2, '.', '');
	$seguro = $amount['total'];

	foreach ($this->services as $services) {
		if (!$services['enabled']) {
			continue;
		}

		$params_hash = md5(serialize([
			$this->api_nrocuenta,
			$services['operativa'],
			$andreani_weightb,
			$andreani_volumesy,
			$this->origin,
			$package['destination']['postcode'],
		]));

		$precio = get_transient("andreani_cart_$params_hash");

		if (!$precio) {
			$precio = $this->consultar_api_flexipaas(
				$andreani_weightb,
				$andreani_volumesy,
				$this->origin,
				$package['destination']['postcode'],
				$services['operativa']
			);

			if (!$precio || $precio <= 0) {
				continue;
			}

			set_transient("andreani_cart_$params_hash", $precio, HOUR_IN_SECONDS);
		}

		$ajuste = ($this->ajuste_precio === '0' || $this->ajuste_precio === '0%') ? 1 : floatval($this->ajuste_precio);
		$precio += ($precio * $ajuste / 100);

		if ($this->redondear_total) {
			$precio = round($precio, 0, PHP_ROUND_HALF_UP);
		}

		if ($monto_neto >= $this->ajuste_gratis) {
			$precio = 0;
			$titulo = $services['service_name'] . ' GRATIS';
		} else {
			$titulo = $services['service_name'] ;
		}

		$rate = array(
			'id'        => sprintf("%s-%s", sanitize_title($titulo), $services['service_name'] . '-' . $services['woocommerce_andreani_wanderlust_modalidad'] . 'api_nrocuenta' . $this->api_nrocuenta . 'operativa' . $services['operativa'] . 'instance_id' . $this->instance_id),
			'label'     => $titulo,
			'calc_tax'  => 'per_item',
			'cost'      => $precio
		);

		$this->add_rate($rate);
	}
}

	/**
	 * sort_rates function.
	 **/
	public function sort_rates($a, $b)
	{
		if ($a['sort'] == $b['sort'])
			return 0;
		return ($a['sort'] < $b['sort']) ? -1 : 1;
	}
	private function consultar_api_flexipaas($peso_total, $volumen_total, $cp_origen, $cp_destino, $operativa) {
	$url = 'https://external-services.api.flexipaas.com/woo/seguridad/cotizaciones/';

	$username = $this->api_user;
	$password = $this->api_password;

	$body = array(
		'api_nrocuenta' => $this->api_nrocuenta,
		'operativa'     => $operativa,
		'peso_total'    => number_format($peso_total, 2, '.', ''),
		'volumen_total' => number_format($volumen_total, 2, '.', ''),
		'cp_origen'     => $cp_origen,
		'cp_destino'    => $cp_destino,
	);

	$response = wp_remote_post($url, array(
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
			'Content-Type'  => 'application/json',
		),
		'body' => json_encode($body),
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
}

}