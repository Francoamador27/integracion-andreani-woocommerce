<tr valign="top" id="packing_options">
	<th scope="row" class="titledesc"><?php esc_attr_e( 'Contrato para envíos', 'grupo-logistico-andreani' ); ?></th>
	<td class="forminp">
	<?php 
            if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

   				wp_register_style( 'services_css',ANDREANI_PLUGIN_URL. '/includes/css/services.css'  , ANDREANI_VERSION,  
				   true);
				wp_enqueue_style( 'services_css' );
		 
		 ?>
	
		<table class="flexipaas_boxes widefat">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox" /></th>
					<th><?php esc_attr_e( 'Modalidad', 'grupo-logistico-andreani' ); ?></th>

					<th><?php esc_attr_e( 'N. Contrato Andreani', 'grupo-logistico-andreani' ); ?></th>
					<th><?php esc_attr_e( 'Servicio', 'grupo-logistico-andreani' ); ?></th>

					<th><?php esc_attr_e( 'Activo', 'grupo-logistico-andreani' ); ?></th>

				</tr>
			</thead>
			<tfoot>
				<tr>
					<th colspan="3">
						<a href="#" class="button plus insert"><?php esc_html_e( 'Agregar Servicio', 'grupo-logistico-andreani' ); ?></a>
						<a href="#" class="button minus remove"><?php esc_html_e( 'Remover Servicio', 'grupo-logistico-andreani' ); ?></a>
					</th>
					<th colspan="6">
  				</th>
				</tr>
			</tfoot>
			<tbody id="rates">
				<?php //global $woocommerce;		
				     if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

					if ( $this->instance_settings['services'] ) {
 
						foreach ( $this->instance_settings['services'] as $key => $box ) {
 							if ( ! is_numeric( $key ) )
								continue;
							?>
							<tr>
								<td class="check-column"><input type="checkbox" /> </td>
								<td>
											<select class="select modalidad" name="woocommerce_andreani_flexipaas_modalidad[<?php echo esc_attr($key); ?>]" id="woocommerce_andreani_flexipaas_modalidad" style="">
													<option value="0" <?php if($box['woocommerce_andreani_flexipaas_modalidad'] == '0') { ?> selected <?php } ?> >Seleccionar</option>
											 		<option value="pasp" <?php if($box['woocommerce_andreani_flexipaas_modalidad'] == 'pasp') { ?> selected <?php } ?> >Puerta a Sucursal - C/P.Destino</option>			
													<option value="papp" <?php if($box['woocommerce_andreani_flexipaas_modalidad'] == 'papp') { ?> selected <?php } ?> >Puerta a Puerta - C/P.Destino</option>												
											</select>
								</td>
								<td><div class="tooltip"><input class="operativa" type="text" size="15" name="service_operativa[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box['operativa'] ); ?>" /> <span class="tooltiptext">Contrato proporcionado por el contacto comercial de Andreani</span></div></td>
								<td><div class="tooltip"><input type="text" size="35" name="service_name[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box['service_name'] ); ?>" />  <span class="tooltiptext">Agregar una descripción para identificar el servicio contratado</span></div></td>
						 
 
 								<td><input type="checkbox" name="service_enabled[<?php echo esc_attr($key); ?>]" <?php checked( ! isset( $box['enabled'] ) || $box['enabled'] == 1, true ); ?> /></td>
							</tr>
							<?php
						}
					}
				?>
			</tbody>
		</table>
		 <?php 
    				wp_register_script( 'services', ANDREANI_PLUGIN_URL.'/includes/services.js', array('jquery'),  ANDREANI_VERSION,  
				   true);
				wp_enqueue_script( 'services' );
		 
		 ?>
	</td>
</tr>
<?php 
 
?>