<?php
/*
  Plugin Name:  Grupo Logistico Andreani
  Plugin URI: https://proyectosweb
  Description: Plugin oficial de Andreani. Simplifica la gestión de tus envíos con Andreani. Este plugin te permite gestionar fácilmente todas tus entregas, optimizando tu logística, con una experiencia de envío confiable y eficiente para tu tienda WooCommerce.
  Version: 1.0
  Author: Franco Amador
  Author URI: https://flexipaas.com
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Global variables for session and database
global $wp_session;
global $wpdb;

// Define plugin version and paths
define( 'ANDREANI_VERSION', '1.0.1' );
define( 'ANDREANI_PLUGIN_FILE', __FILE__ );
define( 'ANDREANI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANDREANI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

 $wp_session['url_andreani_orden'] = 'https://external-services.api.flexipaas.com/woo/seguridad/orden/';
$wp_session['url_andreani_sedes'] = 'https://external-services.api.flexipaas.com/woo/seguridad/sucursales/';
$wp_session['url_andreani_cotizador'] = 'https://external-services.api.flexipaas.com/woo/seguridad/cotizaciones/';
$wp_session['cliente_andreani'] = ""; 

 require_once( 'includes/funciones.php' );

/**
 * Add plugin page links in the admin area
 */
function andreani_plugin_links( $links ) {
    $plugin_links = array(
        '<a href="http://www.andreani.com">' . __( 'Soporte', 'grupo-logistico-andreani' ) . '</a>',
    );

    return array_merge( $plugin_links, $links );
}
 add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'andreani_plugin_links' );

 

register_activation_hook(__FILE__, 'andreani_activation_notice_callback');

 function andreani_activation_notice_callback() {
     if (  ( in_array( 'wanderlust-andreani-shipping/wanderlust-andreani-shipping.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ))))) {
        {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('El plugin grupo logistico andreani no se puede activar debido a conflicto con otro plugin.', 'grupo-logistico-andreani'); ?></p>
        </div>
        <?php
         deactivate_plugins(plugin_basename(__FILE__));
       
    }
}}

// Hook the notice function to the admin_notices hook
add_action('admin_notices', 'andreani_activation_notice_callback');
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    
    function andreani_init() {
        include_once( 'includes/class-andreani-shipping.php' );
    }
    add_action( 'woocommerce_shipping_init', 'andreani_init' ); 

   
    function andreani_add_method( $methods ) {
        $methods[ 'andreani_flexipaas' ] = 'andreani_Shipping';
        return $methods;
    }

    // Hook to register the shipping method
    add_filter( 'woocommerce_shipping_methods', 'andreani_add_method' );

    /**
     * Enqueue admin scripts
     */
    function andreani_scripts() {
        wp_enqueue_script( 'jquery-ui-sortable' );
    }
    add_action( 'admin_enqueue_scripts', 'andreani_scripts' );

    // Retrieve store settings
    $user = "";
    $passw = "";
    $i = 1;
    $operativa=array();
    // Loop through settings to find enabled Andreani settings
      while ($i < 200) {
        $andreani_settings = get_option( 'woocommerce_andreani_flexipaas_'.$i.'_settings', array() );
       
        if (!empty($andreani_settings) && isset($andreani_settings['activo']) && $andreani_settings['activo'] === 'yes') {
            $user = $andreani_settings["andreani_usuario"];
            $passw = $andreani_settings["andreani_password"];
            $wp_session['cuit'] = $andreani_settings["cuit"];
 
            // Check for services and set operational modes
            if (!empty($andreani_settings["tipo_servicio"])) {
                foreach ($andreani_settings["tipo_servicio"] as $i => $v) {
                    if ($v["woocommerce_andreani_flexipaas_modalidad"] == "pasp") {
                        $operativa["pasp"] = $v["contrato_numero"];
                    }
                    if ($v["woocommerce_andreani_flexipaas_modalidad"] == "papp") {
                        $operativa["papp"] = $v["contrato_numero"];
                    }
                }
            }
            $wp_session['contratos_por_modalidad'] = $operativa;
            break;
        }
        $i++;
    }

 
    $wp_session['cliente_andreani'] = base64_encode($user . ":" . $passw);

     add_filter('woocommerce_checkout_fields', 'andreani_modificar_campos_checkout');

    /**
     * Modify checkout fields labels
     */
    function andreani_modificar_campos_checkout($fields) {
        $label = 'Calle y número de domicilio';
        $fields['billing']['billing_address_1']['label'] = $label;
        $fields['billing']['billing_address_2']['label'] = $label;
        $fields['shipping']['shipping_address_1']['label'] = $label;
        $fields['shipping']['shipping_address_2']['label'] = $label;
        return $fields;
    }
}