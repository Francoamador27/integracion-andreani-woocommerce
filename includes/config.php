<?php
class Config
{
    public function __construct()
    {
        add_action('woocommerce_after_order_notes', array($this, 'order_sucursal_main_andreani_custom'), 1);

        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_assets'));

        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));


        add_action('woocommerce_checkout_process', array($this, 'check_andreani_sucursal'));

        add_action('woocommerce_checkout_update_order_meta', array($this, 'order_sucursal_main_update_order_meta_andreani'));


        add_filter('plugin_action_links_' . plugin_basename(EON_ANDREANI_PLUGIN_FILE), array($this, 'plugin_links'));

        // Solo si WooCommerce está activo
        if ($this->is_woocommerce_active()) {
            add_action('woocommerce_shipping_init', array($this, 'init_shipping_method'));
            add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_sortable_script'));
        }

    }
    private function is_woocommerce_active()
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * Links en la página de plugins
     */
    public function plugin_links($links)
    {
        $plugin_links = array(
            '<a href="https://grupoeon.com.ar/">' . __('Soporte', 'woocommerce-shipping-andreani') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }

    /**
     * Inicializar el método de envío
     */
    public function init_shipping_method()
    {
    include_once plugin_dir_path(EON_ANDREANI_PLUGIN_FILE) . 'includes/class-wc-shipping-andreani.php';
    }

    /**
     * Agregar el método de envío a WooCommerce
     */
    public function add_shipping_method($methods)
    {
        $methods['andreani_eon'] = 'WC_Shipping_Andreani';
        return $methods;
    }

    /**
     * Cargar jQuery UI Sortable para admin
     */
    public function enqueue_sortable_script()
    {
        wp_enqueue_script('jquery-ui-sortable');
    }
    public function enqueue_admin_assets($hook)
    {

        wp_enqueue_style(
            'eon-andreani-admin-services',
            EON_ANDREANI_PLUGIN_URL . 'assets/styles/admin.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'eon-andreani-admin-services',
            EON_ANDREANI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            '1.0.0',
            true
        );
    }

    public function enqueue_checkout_assets()
    {
        if (!is_checkout()) {
            return;
        }

        wp_enqueue_script(
            'eon-andreani-checkout',
            EON_ANDREANI_PLUGIN_URL . 'assets/js/checkout.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'eon-andreani-checkout-style',
            EON_ANDREANI_PLUGIN_URL . 'assets/styles/checkout.css',
            array(),
            '1.0.0'
        );
    }

    public function order_sucursal_main_andreani_custom($checkout)
    {
        global $woocommerce;
        session_start();
        $items = $woocommerce->cart->cart_contents;
        foreach ($items as $item) {
            $user_id = $item['data']->post->post_author;
        }
        $_SESSION['user_id'] = $user_id;

        echo '<input type="hidden" value="' . $user_id . '" id="user_id_vendor" name="user_id_vendor" />';

        echo '<div id="order_sucursal_main" style="display:none; margin-bottom:50px;">';
        echo '<div id="order_sucursal_main_result_cargando">Cargando Sucursales...</div>';
        echo '<div id="order_sucursal_main_result" style="display:none;">Cargando Sucursales...</div>';
        echo '</div>';
    }

    public function check_andreani_sucursal()
    {
        global $woocommerce;
        session_start();

        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];
        $_SESSION['chosen_shipping'] = $chosen_shipping;
        if (strpos($chosen_shipping, '-saspapi_nrocuenta') !== false || strpos($chosen_shipping, '-paspapi_nrocuenta') !== false || strpos($chosen_shipping, '-pasapi_nrocuenta') !== false || strpos($chosen_shipping, '-sasapi_nrocuenta') !== false) {
            if (empty($_POST['pv_centro_andreani_estandar']))
                wc_add_notice(__('Por favor, seleccionar una sucursal de retiro.'), 'error');
        }
    }

    public function order_sucursal_main_update_order_meta_andreani($order_id)
    {
        session_start();
        if (!empty($_POST['pv_centro_andreani_estandar'])) {
            foreach ($_SESSION['listado_andreani'] as $opciones) {
                if ($_POST['pv_centro_andreani_estandar'] == $opciones->id) {
                    $opciones = json_encode($opciones);
                    update_post_meta($order_id, '_sucursal_andreani_c', $opciones);
                }
            }
        }
        $chosen_shipping = json_encode($_SESSION['chosen_shipping']);
        $params_andreani = json_encode($_SESSION['params_andreani']);
        update_post_meta($order_id, '_params_andreani', $params_andreani);
        if (isset($_COOKIE['andreani_origen_datos'])) {
            update_post_meta($order_id, '_origen_datos', $_COOKIE['andreani_origen_datos']);
        } else {
            update_post_meta($order_id, '_origen_datos', $_SESSION['origen_datos']);
        }

        update_post_meta($order_id, '_chosen_shipping', $chosen_shipping);
    }
}
new Config();


