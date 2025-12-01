<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Array of settings
 */
return array(
	'entorno_api' => array(
		'title' => __('Entorno API', 'woocommerce-shipping-andreani'),
		'type' => 'select',
		'description' => __('Seleccionar si utilizar entorno de desarrollo o producción para las llamadas a la API.', 'woocommerce-shipping-andreani'),
		'default' => 'produccion',
		'desc_tip' => true,
		'options' => array(
			'produccion' => 'Producción',
			'desarrollo' => 'Desarrollo',
		)
	),
	'enabled' => array(
		'title' => __('Activar Andreani', 'woocommerce-shipping-andreani'),
		'type' => 'checkbox',
		'label' => __('Activar este método de envió', 'woocommerce-shipping-andreani'),
		'default' => 'no'
	),

	'debug' => array(
		'title' => __('Modo Depuración', 'woocommerce-shipping-andreani'),
		'label' => __('Activar modo depuración', 'woocommerce-shipping-andreani'),
		'type' => 'checkbox',
		'default' => 'no',
		'desc_tip' => true,
		'description' => __('Activar el modo de depuración para mostrar información de depuración en la compra/pago y envío.', 'woocommerce-shipping-andreani')
	),

	'title' => array(
		'title' => __('Título', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('Controla el título que el usuario ve durante el pago.', 'woocommerce-shipping-andreani'),
		'default' => __('Andreani', 'woocommerce-shipping-andreani'),
		'desc_tip' => true
	),

	'origen' => array(
		'title' => __('Detalles de Envío', 'woocommerce-shipping-andreani'),
		'type' => 'title',
		'description' => __('Dirección de envío / sucursal origen', 'woocommerce-shipping-andreani'),
	),

	'origin_contacto' => array(
		'title' => __('Nombre y Apellido', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('Datos de Contacto', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'origin_email' => array(
		'title' => __('Email', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'origin_calle' => array(
		'title' => __('Calle de Origen', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'origin_numero' => array(
		'title' => __('Número de Calle', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'origin_piso' => array(
		'title' => __('Piso', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'origin_depto' => array(
		'title' => __('Depto', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'origin' => array(
		'title' => __('Código Postal', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('Ingrese el código postal de <strong> origen </ strong>.', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'sucursal_origin' => array(
		'title' => __('Sucursal de Origen', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('Ingrese la Sucursal de <strong> origen </ strong>.', 'woocommerce-shipping-andreani'),
		'default' => '',
		'class' => 'sucursal_origins',
		'desc_tip' => true
	),

	'origin_landreanilidad' => array(
		'title' => __('Localidad', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'origin_provincia' => array(
		'title' => __('Provincia', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'origin_observaciones' => array(
		'title' => __('Observaciones', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('', 'woocommerce-shipping-andreani'),
		'default' => '',
		'desc_tip' => true
	),

	'api' => array(
		'title' => __('Configuración de la API', 'woocommerce-shipping-andreani'),
		'type' => 'title',
		'description' => __('', 'woocommerce-shipping-andreani'),
	),


	'api_user' => array(
		'title' => __('User', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('Username', 'woocommerce-shipping-andreani'),
		'default' => __('', 'woocommerce-shipping-andreani'),
		'placeholder' => __('', 'meta-box'),
	),

	'api_password' => array(
		'title' => __('Password', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('Password', 'woocommerce-shipping-andreani'),
		'default' => __('', 'woocommerce-shipping-andreani'),
		'placeholder' => __('', 'meta-box'),
	),

	'api_nrocuenta' => array(
		'title' => __('Código de cliente', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('CL0003750', 'woocommerce-shipping-andreani'),
		'default' => __('', 'woocommerce-shipping-andreani'),
		'placeholder' => __('', 'meta-box'),
	),
	'dni_meta_key' => array(
		'title' => __('Meta key del DNI del cliente', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __(
			'Nombre del meta de la orden donde se guarda el DNI del cliente. Ej: _billing_dni, _billing_cpf, etc.',
			'woocommerce-shipping-andreani'
		),
		'default' => '_billing_dni',
		'desc_tip' => true,
	),

	'ajuste_precio' => array(
		'title' => __('Ajustar Costos %', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('Agregar costo extra al precio. Ingresar valor numérico.', 'woocommerce-shipping-andreani'),
		'default' => __('', 'woocommerce-shipping-andreani'),
		'placeholder' => __('1', 'meta-box'),
	),

	'ajuste_gratis' => array(
		'title' => __('Envio gratis para montos superiores a ', 'woocommerce-shipping-andreani'),
		'type' => 'text',
		'description' => __('Ingresar valor numérico.', 'woocommerce-shipping-andreani'),
		'default' => __('', 'woocommerce-shipping-andreani'),
		'placeholder' => __('1', 'meta-box'),
	),
	'mercado_pago' => array(
		'title' => __('Modo Mercado Pago', 'woocommerce-shipping-andreani'),
		'label' => __('No agregar el costo de envio en el Total.', 'woocommerce-shipping-andreani'),
		'type' => 'checkbox',
		'default' => 'no',
		'desc_tip' => true,
		'description' => __('Activar el modo de Mercado pago para no agregar costo de envio en el Total.', 'woocommerce-shipping-andreani')
	),

	'redondear_total' => array(
		'title' => __('Ajustar Totales', 'woocommerce-shipping-andreani'),
		'label' => __('Mostrar costos totales sin decimales.', 'woocommerce-shipping-andreani'),
		'type' => 'checkbox',
		'default' => 'no',
		'desc_tip' => true,
		'description' => __('Mostrar costos totales sin decimales. Ej: $56.96 a $57', 'woocommerce-shipping-andreani')
	),

	'packing' => array(
		'title' => __('Contratos', 'woocommerce-shipping-andreani'),
		'type' => 'title',
		'description' => __('Los siguientes ajustes determinan cómo los artículos se embalan antes de ser enviado a Andreani.', 'woocommerce-shipping-andreani'),
	),

	'packing_method' => array(
		'title' => __('Método Embalaje', 'woocommerce-shipping-andreani'),
		'type' => 'select',
		'default' => '',
		'class' => 'packing_method',
		'options' => array(
			'per_item' => __('Por defecto: artículos individuales', 'woocommerce-shipping-andreani'),
		),
	),

	'services' => array(
		'type' => 'service'
	),

);