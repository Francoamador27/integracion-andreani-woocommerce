<?php
/*
	Plugin Name:❚ EON Andreani Shipping 
	Plugin URI: https://github.com/grupoeon/plugin.andreani-eon
	Description: Obtiene las tarifas dinamicas de Andreani y permite generar etiquetas e imprimirlas.
	Version: 2.6
	Author: Grupo EON
	Author URI: https://grupoeon.com.ar/
  	WC tested up to: 7.9.0
	Copyright: 2007-2023 eon-webdesign.com.
*/


/**
 * Plugin global API URL
*/
global $wp_session;
define('EON_ANDREANI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EON_ANDREANI_PLUGIN_FILE', __FILE__);

$plugin_path = plugin_dir_path( __FILE__ );

require_once( $plugin_path . 'includes/functions.php' );
require_once( $plugin_path . 'includes/config.php' );


