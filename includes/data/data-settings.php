<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Array of settings
 */
return array(
	'enabled'           => array(
		'title'           => __( 'Andreani Envios', 'grupo-logistico-andreani' ),
		'type'            => 'checkbox',
		'label'           => __( 'Activar', 'grupo-logistico-andreani' ),
		'default'         => 'no'
	),

	'title'             => array(
		'title'           => __( 'Título', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Controla el título que el usuario ve durante el pago.', 'grupo-logistico-andreani' ),
		'default'         => __( 'Andreani', 'grupo-logistico-andreani' ),
  
	),
	'origin'            => array(
		'title'           => __( 'Código Postal (*)', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Ingrese el código postal del <strong> remitente </ strong>.', 'grupo-logistico-andreani' ),
		'default'         => '0',
		'desc_tip'        => true
    ),
	
 
 
   'api'              => array(
		'title'           => __( 'Configuración de la API', 'grupo-logistico-andreani' ),
		'type'            => 'title',
     ),
	
	'api_user'         => array(
		'title'           => __( 'Usuario API (*)', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Usuario API Andreani', 'grupo-logistico-andreani' ),
      ),
	
   'api_password'     => array(
		'title'           => __( 'Password API(*)', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Password', 'grupo-logistico-andreani' ),
      ),
	
 
	
   'api_nrocuenta'     => array(
		'title'           => __( 'Código de cliente (*)', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Código de cliente proporcionado por Andreani ', 'grupo-logistico-andreani' ),
      ),	
	
    'cuit'              => array(
		'title'           => __( 'Cuit', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Cuit', 'grupo-logistico-andreani' ),
 
    ),
	
   'ajuste_precio'    => array(
		'title'           => __( 'Ajustar Costos %', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Agregar costo extra al precio. Ingresar valor numérico.', 'grupo-logistico-andreani' ),
		'default'         => __( '0', 'grupo-logistico-andreani' ),
    'placeholder' => __( '1', 'grupo-logistico-andreani' ),		
    ),	

		'mercado_pago'      => array(
				'title'           => __( 'Modo Mercado Pago', 'grupo-logistico-andreani' ),
				'label'           => __( 'No agregar el costo de envio en el Total.', 'grupo-logistico-andreani' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'desc_tip'    => true,
				'description'     => __( 'Activar el modo de Mercado pago para no agregar costo de envio en el Total.', 'grupo-logistico-andreani' )
		),	
	
 		'redondear_total'      => array(
				'title'           => __( 'Ajustar Totales', 'grupo-logistico-andreani' ),
				'label'           => __( 'Mostrar costos totales sin decimales.', 'grupo-logistico-andreani' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'desc_tip'    => true,
				'description'     => __( 'Mostrar costos totales sin decimales. Ej: $56.96 a $57', 'grupo-logistico-andreani' )
		),	

    'packing'           => array(
		'title'           => __( 'Contratos', 'grupo-logistico-andreani' ),
		'type'            => 'title',
		'description'     => __( 'Los siguientes ajustes determinan cómo los artículos se embalan antes de ser enviado a Andreani.', 'grupo-logistico-andreani' ),
    ),

	'packing_method'   => array(
		'title'           => __( 'Método Embalaje', 'grupo-logistico-andreani' ),
		'type'            => 'select',
		'default'         => ' ',
		'class'           => 'packing_method',
		'options'         => array(
			'per_item'       => __( 'Por defecto: artículos individuales', 'grupo-logistico-andreani' ),
		),
	),

 	'services'  => array(
		'type'            => 'service'
	),

);