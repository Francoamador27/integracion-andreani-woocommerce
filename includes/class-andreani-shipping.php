<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Clase andreani_Shipping    .
 *
 * @extiende de WC_Shipping_Method
 */ 
class andreani_Shipping extends WC_Shipping_Method {
	private $default_boxes;
	private $found_rates;
  
	public $title;
	private $origen;
	private $pais_origen;
	private $andreani_usuario;
	private $andreani_password;
	private $andreani_nrocuenta ;
	private $ajuste_precio;
	private $tipo_servicio   ;
	private $debug     ;
	private $contratos       ;
	private $redondear;
	private $init_form_fields;


 
	public function init() {

		$this->init_form_fields = include( 'config/config-usuario-andreani.php' );
		$this->init_settings();
		$this->instance_form_fields = include( 'config/config-usuario-andreani.php' );
	 
		$this->title           = $this->get_option( 'titulo', $this->method_title );
		$this->origen          = apply_filters( 'andreani_origin_postal_code', str_replace( ' ', '', strtoupper( $this->get_option( 'origen' ) ) ) );
		$this->pais_origen  = apply_filters( 'andreani_origin_country_code', WC()->countries->get_base_country() );
 		$this->andreani_usuario				 = $this->get_option( 'andreani_usuario' );
		$this->andreani_password		 = $this->get_option( 'andreani_password' );
		$this->andreani_nrocuenta   = $this->get_option( 'andreani_nrocuenta' );
 		$this->ajuste_precio   = $this->get_option( 'ajuste_precio' );
		$this->tipo_servicio   = $this->get_option( 'tipo_servicio', array( ) );
		$this->debug           = ( $bool = $this->get_option( 'debug' ) ) && $bool == 'yes' ? true : false;
  		$this->redondear = ( $bool = $this->get_option( 'redondear' ) ) && $bool == 'yes' ? true : false;
 	
	}

	public function __construct( $instance_id = 0 ) {
		$this->id                   = 'andreani_flexipaas';
	   $this->instance_id 			 		= absint( $instance_id );
	   $this->method_title         = __( 'Andreani Envios', 'grupo-logistico-andreani' );
		$this->method_description   = __( 'Obtiene las tasas de envio de andreani.', 'grupo-logistico-andreani' );
		$this->supports             = array(
		   'shipping-zones',
		   'instance-settings',
	   );
	
	   $this->init();
   
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

	}

	public function admin_options() {
 		$this->environment_check();

 		parent::admin_options();
	}


	 

 
	private function environment_check() {
		$errors = array();
	
	
	
		// Verificar si el usuario de la API está configurado
		if (!$this->andreani_usuario && $this->enabled == 'yes'  ) {
			$errors[] = 'Andreani está activo, pero el campo Usuario API (*) está vacío.';
		}
  		// Verificar si el código postal está configurado
		if ( !$this->origen && $this->enabled == 'yes' ) {
			$errors[] = 'Andreani está activo, pero no hay Código Postal.';
		}
		
			// Verificar si el país de origen es Argentina
		if ( ! in_array( WC()->countries->get_base_country(), array( 'AR' ) ) ) {
				$errors[] = 'Argentina tiene que ser el país de Origen.';
		}
		
		// Mostrar errores si existen
		if ( ! empty( $errors ) ) {
			echo '<div class="error"><ul>';
			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}
			echo '</ul></div>';
		}
	}


	public function generate_service_html() {
		ob_start();
		include( 'servicios.php' );
		return ob_get_clean();
	}
	 
	public function validate_service_field( $key ) {
	
	$contrato_modalidad    = isset( $_POST['woocommerce_andreani_flexipaas_modalidad'] ) ?  array_map('sanitize_text_field',wp_unslash( $_POST['woocommerce_andreani_flexipaas_modalidad'] )) : array();
	$modalidad_activa    = isset( $_POST['modalidad_activa'] ) ?  array_map('sanitize_text_field',wp_unslash( $_POST['modalidad_activa'] )) : array();
	$contrato_numero     = isset( $_POST['contrato_numero'] ) ? array_map('sanitize_text_field',wp_unslash( $_POST['contrato_numero'] )) : array();
	$contrato_modalidad_desc     = isset( $_POST['contrato_modalidad_desc'] ) ?   array_map('sanitize_text_field',wp_unslash( $_POST['contrato_modalidad_desc'] )): array();

	$contratos = array();
	  if ( ! empty( $contrato_numero ) && sizeof( $contrato_numero ) > 0 ) {
		for ( $i = 0; $i <= max( array_keys( $contrato_numero ) ); $i ++ ) {

			if ( ! isset( $contrato_numero[ $i ] ) )
				continue;
	
			if ( $contrato_numero[ $i ] ) {
				  $contratos[] = array(
					'woocommerce_andreani_flexipaas_modalidad' =>  $contrato_modalidad[ $i ] ,  
					'contrato_modalidad_desc'     =>  $contrato_modalidad_desc[ $i ],
					'contrato_numero'     => floatval( $contrato_numero[ $i ] ),

					'modalidad_activa'    => isset( $modalidad_activa[ $i ] ) ? true : false
				);
				 
			}
		}

	}
		
	return $contratos;
}
 
	public function obtener_paquetes_andreani( $package ) {
		return $this->envio_por_item( $package );
	}

	 
	private function envio_por_item( $package ) {
		$enviar = array();
		$id_grupo = 1;
	
		foreach ( $package['contents'] as $item_id => $values ) {
			// Verificar si el producto necesita envío
			if ( ! $values['data']->needs_shipping() ) {
				$this->debug( sprintf( __( 'El producto no necesita envío.', 'grupo-logistico-andreani' ), $item_id ), 'error' );
				continue;
			}
	
			// Verificar si el peso del producto está definido
			$peso = $values['data']->get_weight();
			if ( ! $peso ) {
				$this->debug( sprintf( __( 'El peso del producto es obligatorio.', 'grupo-logistico-andreani' ), $item_id ), 'error' );
				return;
			}
	
			// Crear el grupo de envío
			$grupo = array(
				'GroupNumber'       => $id_grupo,
				'GroupPackageCount' => $values['quantity'],
				'Weight' => array(
					'Value' => $peso,
					'Units' => 'KG'
				),
				'packed_products' => array( $values['data'] )
			);
	
			// Agregar dimensiones si están disponibles
			$this->agregar_dimensiones( $grupo, $values['data'] );
	
			// Agregar valor asegurado
			$grupo['InsuredValue'] = array(
				'Amount'   => round( $values['data']->get_price() ),
				'Currency' => get_woocommerce_currency()
			);
	
			$enviar[] = $grupo;
			$id_grupo++;
		}
	
		return $enviar;
	}
	
	private function agregar_dimensiones( &$grupo, $producto ) {
		if ( $producto->get_length() && $producto->get_height() && $producto->get_width() ) {
			$dimensions = array( $producto->get_length(), $producto->get_width(), $producto->get_height() );
			sort( $dimensions );
	
			$grupo['Dimensions'] = array(
				'Length' => $producto->get_length(),
				'Width'  => $producto->get_width(),
				'Height' => $producto->get_height(),
				'Units'  => 'CM'
			);
		}
	}
	public function sort_rates( &$rates ) {
		usort( $rates, function( $a, $b ) {
			// Comparar por el valor de 'sort'
			if ( $a['sort'] === $b['sort'] ) {
				return 0; // Son iguales
			}
			return ( $a['sort'] < $b['sort'] ) ? -1 : 1; // Ordenar de menor a mayor
		});
	}
 
	public function calculate_shipping($package = array()) {
		global $wp_session;
	
		if ($codigo_sucursal = WC()->session->get('codigo_sucursal')) {
			$package['codigo_sucursal'] = $custom_field;
		}
	
		$this->debug(__('Andreani modo de depuración está activado - para ocultar estos mensajes, desactive el modo de depuración en los ajustes.', 'grupo-logistico-andreani'));
	
		$paquetes_andreani = $this->obtener_paquetes_andreani($package);
		$paquete_andreani = $paquetes_andreani[0]['GroupPackageCount'];
	
		$unidad_dimension = esc_attr(get_option('woocommerce_dimension_unit'));
		$unidad_peso = esc_attr(get_option('woocommerce_weight_unit'));
	
		$dimension_multiplicadores = ['m' => 1, 'cm' => 100, 'mm' => 1000];
		$peso_multiplicadores = ['kg' => 1, 'g' => 0.001];
	
		$dimension_multiplicador = $dimension_multiplicadores[$unidad_dimension] ?? 0;
		$peso_multi = $peso_multiplicadores[$unidad_peso] ?? 0;
	
		$peso_total = 0;
		$volumen_total = 0;
	
		foreach ($paquetes_andreani as $pq) {
			$paquete_andreani = $pq['GroupPackageCount'];
			$ancho = $pq['Dimensions']['Width'] / $dimension_multiplicador;
			$alto = $pq['Dimensions']['Height'] / $dimension_multiplicador;
			$profundidad = $pq['Dimensions']['Length'] / $dimension_multiplicador;
			$peso = $pq['Weight']['Value'] * $peso_multi;
	
			$peso_total += $peso * $paquete_andreani;
			$volumen = $ancho * $alto * $profundidad;
			$volumen_total += $volumen * $paquete_andreani;
			$volumen_total = number_format($volumen_total, 10);
		}
	
		foreach ($this->tipo_servicio as $servicio) {
			if ($servicio['modalidad_activa'] == 1) {
				$params = array(
					'api_nrocuenta'  => $this->andreani_nrocuenta,
					'operativa'      => strval($servicio['contrato_numero']),
					'peso_total'     => strval($peso_total),
					'volumen_total'  => strval($volumen_total),
					'cp_origen'      => $this->origen,
					'cp_destino'     => $package['destination']['postcode'],
				);
	
				$headers = array(
					'Content-Type'  => 'application/json',
					'Authorization' => base64_encode($this->andreani_usuario . ":" . $this->andreani_password)
				);
	
				$respuesta_api_andreani = wp_remote_post($wp_session['url_andreani_cotizador'], array(
					'headers' => $headers,
					'body'    => wp_json_encode($params),
					'method'  => 'POST'
				));
	
				if (!is_wp_error($respuesta_api_andreani)) {
					$respuesta_api_andreani = json_decode($respuesta_api_andreani['body']);
	
					if (!empty($respuesta_api_andreani->error)) {
						echo '<ul class="woocommerce-error"><li>' . esc_html($respuesta_api_andreani->error) . '</li></ul>';
						return;
					}
	
					if (!empty($respuesta_api_andreani->notice)) {
						if (!isset($_COOKIE['andreani_notice'])) {
							@setcookie('andreani_notice', $respuesta_api_andreani->notice, time() + 3600, "/");
						}
					} else {
						@setcookie("andreani_notice", "", time() - 3600, "/");
					}
	
					$redondear = $this->redondear;
					$precio_envio = 0;
	
					if (!is_null($respuesta_api_andreani->tarifaConIva->total)) {
						$precio_envio = $respuesta_api_andreani->tarifaConIva->total;
	
						// ✅ APLICAR ENVÍO GRATIS SI CORRESPONDE
						$envio_gratis_desde = floatval($this->get_option('envio_gratis_desde'));
						$subtotal_carrito = $package['contents_cost'];
	
						if ($envio_gratis_desde > 0 && $subtotal_carrito >= $envio_gratis_desde) {
							$precio_envio = 0;
						}
	
						// Redondear si está activado
						if ($redondear == '1') {
							$precio_envio = round($precio_envio, 0, PHP_ROUND_HALF_UP);
						}
	
						$titulo = $servicio['contrato_modalidad_desc'];
						$wp_session["codigo_contrato_andreani"] = $servicio['contrato_numero'];
	
						// Agregar mensaje de envío gratis al título si corresponde
						if ($precio_envio == 0 && $envio_gratis_desde > 0) {
							$titulo .= ' – Envío gratis';
						}
	
						$rate = array(
							'value'    => $servicio['woocommerce_andreani_flexipaas_modalidad'],
							'id'       => $servicio['woocommerce_andreani_flexipaas_modalidad'],
							'label'    => sprintf("%s", $titulo),
							'cost'     => $precio_envio,
							'calc_tax' => 'per_item'
						);
	
						$this->add_rate($rate);
					}
				}
			}
		}
	}
	

	  
	public function debug( $message, $type = 'notice' ) {
		if ( $this->debug ) {
			wc_add_notice( $message, $type );
		}
	}

}