<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configuracion de usuario de andreani
 */
return array(
	'activo'           => array(
		'title'           => __( 'Andreani Envios', 'grupo-logistico-andreani' ),
		'type'            => 'checkbox',
		'label'           => __( 'Activar', 'grupo-logistico-andreani' ),
		'default'         => 'no'
	),

	'titulo'             => array(
		'title'           => __( 'Título', 'grupo-logistico-andreani' ),
 		'type'            => 'text',
		'description'     => __( 'Controla el título que el usuario ve durante el pago.', 'grupo-logistico-andreani' ),
		'default'         => __( 'Andreani', 'grupo-logistico-andreani' ),
  
	),
	'origen'            => array(
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
	
	'andreani_usuario'         => array(
		'title'           => __( 'Usuario API (*)', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Usuario API Andreani', 'grupo-logistico-andreani' ),
      ),
	
   'andreani_password'     => array(
		'title'           => __( 'Password andreani(*)', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Password', 'grupo-logistico-andreani' ),
      ),	
 
	
   'andreani_nrocuenta'     => array(
		'title'           => __( 'Código de cliente (*)', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Código de cliente proporcionado por Andreani ', 'grupo-logistico-andreani' ),
      ),	
	
    'cuit'              => array(
		'title'           => __( 'Cuit', 'grupo-logistico-andreani' ),
		'type'            => 'text',
		'description'     => __( 'Cuit', 'grupo-logistico-andreani' ),
 
    ),
 	'envio_gratis_desde' => array(
    'title'       => __( 'Envío gratis desde ($)', 'grupo-logistico-andreani' ),
    'type'        => 'text',
    'description' => __( 'Si se ingresa un valor, se ofrecerá envío gratis a partir de ese monto de compra.', 'grupo-logistico-andreani' ),
    'default'     => '',
    'desc_tip'    => true,
),

 	'redondear'      => array(
				'title'           => __( 'Ajustar Totales', 'grupo-logistico-andreani' ),
				'label'           => __( 'Mostrar costos totales sin decimales.', 'grupo-logistico-andreani' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'desc_tip'    => true,
				'description'     => __( 'Mostrar costos totales sin decimales. Ej: $56.96 a $57', 'grupo-logistico-andreani' )
	),	
 	'tipo_servicio'  => array(
		'type'            => 'service'
	),

);