<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<tr valign="top" id="packing_options">
    <th scope="row" class="titledesc">
        <?php _e( 'Contrato para envíos', 'wc_eon' ); ?>
    </th>
    <td class="forminp">
        <table class="eon_boxes widefat">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" />
                    </th>
                    <th><?php _e( 'Servicio', 'wc_eon' ); ?></th>
                    <th><?php _e( 'N. Contrato', 'wc_eon' ); ?></th>
                    <th><?php _e( 'Modalidad', 'wc_eon' ); ?></th>
                    <th><?php _e( 'Activo', 'wc_eon' ); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th colspan="3">
                        <a href="#" class="button plus insert">
                            <?php _e( 'Agregar Servicio', 'wc_eon' ); ?>
                        </a>
                        <a href="#" class="button minus remove">
                            <?php _e( 'Remover Servicio', 'wc_eon' ); ?>
                        </a>
                    </th>
                    <th colspan="6"></th>
                </tr>
            </tfoot>
            <tbody id="rates">
                <?php
                if ( ! empty( $this->instance_settings['services'] ) ) {
                    foreach ( $this->instance_settings['services'] as $key => $box ) {
                        if ( ! is_numeric( $key ) ) {
                            continue;
                        }
                        ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" />
                            </td>
                            <td>
                                <input type="text" size="35"
                                       name="service_name[<?php echo $key; ?>]"
                                       value="<?php echo esc_attr( $box['service_name'] ); ?>" />
                            </td>
                            <td>
                                <input class="operativa" type="text" size="15"
                                       name="service_operativa[<?php echo $key; ?>]"
                                       value="<?php echo esc_attr( $box['operativa'] ); ?>" />
                            </td>
                            <td>
                                <select class="select modalidad"
                                        name="woocommerce_andreani_eon_modalidad[<?php echo $key; ?>]">
                                    <option value="0"  <?php selected( $box['woocommerce_andreani_eon_modalidad'], '0' );  ?>>Seleccionar</option>
                                    <option value="sas"<?php selected( $box['woocommerce_andreani_eon_modalidad'], 'sas'); ?>>Sucursal a Sucursal</option>
                                    <option value="sap"<?php selected( $box['woocommerce_andreani_eon_modalidad'], 'sap'); ?>>Sucursal a Puerta</option>
                                    <option value="pas"<?php selected( $box['woocommerce_andreani_eon_modalidad'], 'pas'); ?>>Puerta a Sucursal</option>
                                    <option value="pap"<?php selected( $box['woocommerce_andreani_eon_modalidad'], 'pap'); ?>>Puerta a Puerta</option>
                                    <option value="sasp"<?php selected( $box['woocommerce_andreani_eon_modalidad'], 'sasp'); ?>>Sucursal a Sucursal - C/P.Destino</option>
                                    <option value="sapp"<?php selected( $box['woocommerce_andreani_eon_modalidad'], 'sapp'); ?>>Sucursal a Puerta - C/P.Destino</option>
                                    <option value="pasp"<?php selected( $box['woocommerce_andreani_eon_modalidad'], 'pasp'); ?>>Puerta a Sucursal - C/P.Destino</option>
                                    <option value="papp"<?php selected( $box['woocommerce_andreani_eon_modalidad'], 'papp'); ?>>Puerta a Puerta - C/P.Destino</option>
                                </select>
                            </td>
                            <td>
                                <input type="checkbox"
                                       name="service_enabled[<?php echo $key; ?>]"
                                       <?php checked( ! isset( $box['enabled'] ) || $box['enabled'] == 1, true ); ?> />
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </td>
</tr>
