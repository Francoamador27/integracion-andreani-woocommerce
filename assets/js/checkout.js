jQuery(document).ready(function () {
    jQuery('#order_sucursal_main').insertAfter(jQuery('.woocommerce-checkout-review-order-table'));
    jQuery('#calc_shipping_postcode').attr({ maxLength: 4 });
    jQuery('#billing_postcode').attr({ maxLength: 4 });
    jQuery('#shipping_postcode').attr({ maxLength: 4 });

    jQuery("#calc_shipping_postcode").keypress(function (e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            return false;
        }
    });
    jQuery("#billing_postcode").keypress(function (e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            return false;
        }
    });
    jQuery("#shipping_postcode").keypress(function (e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            return false;
        }
    });


    jQuery('#billing_postcode').focusout(function () {
        if (jQuery('#ship-to-different-address-checkbox').is(':checked')) {
            var state = jQuery('#shipping_state').val();
            var post_code = jQuery('#shipping_postcode').val();
        } else {
            var state = jQuery('#billing_postcode').val();
            var post_code = jQuery('#billing_postcode').val();
        }


        var selectedMethod = jQuery('input:checked', '#shipping_method').attr('id');
        var selectedMethodb = jQuery("#order_review .shipping .shipping_method option:selected").val();
        if (selectedMethod == null) {
            if (selectedMethodb != null) {
                selectedMethod = selectedMethodb;
            } else {
                return false;
            }
        }
        var order_sucursal = 'ok';
        var instance_id = selectedMethod.substr(selectedMethod.indexOf("instance_id") + 11);
        var operativa = selectedMethod.substr(selectedMethod.indexOf("operativa") + 9)
        var cuit = selectedMethod.substr(selectedMethod.indexOf("api_nrocuenta") + 4)
        var cuit_ok = cuit.substr(0, 9);
        var operativaok = operativa.substr(0, 9);

        jQuery("#order_sucursal_main_result").fadeOut(100);
        jQuery("#order_sucursal_main_result_cargando").fadeIn(100);
        jQuery.ajax({
            type: 'POST',
            cache: false,
            url: wc_checkout_params.ajax_url,
            data: {
                action: 'check_sucursales_andreani',
                post_code: post_code,
                order_sucursal: order_sucursal,
                operativa: operativaok,
                cuit: cuit_ok,
                instance_id: instance_id,
                provincia: state,
            },
            success: function (data, textStatus, XMLHttpRequest) {
                jQuery("#order_sucursal_main_result").fadeIn(100);
                jQuery("#order_sucursal_main_result_cargando").fadeOut(100);
                jQuery("#order_sucursal_main_result").html('');
                jQuery("#order_sucursal_main_result").append(data);

                var selectList = jQuery('#pv_centro_andreani_estandar option');
                var arr = selectList.map(function (_, o) { return { t: jQuery(o).text(), v: o.value }; }).get();
                arr.sort(function (o1, o2) { return o1.t > o2.t ? 1 : o1.t < o2.t ? -1 : 0; });
                selectList.each(function (i, o) {
                    o.value = arr[i].v;
                    jQuery(o).text(arr[i].t);
                });
                jQuery('#pv_centro_andreani_estandar').html(selectList);
                jQuery("#pv_centro_andreani_estandar").prepend("<option value='0' selected='selected'>Sucursales Disponibles</option>");

            },
            error: function (MLHttpRequest, textStatus, errorThrown) { alert(errorThrown); }
        });
        return false;

    });

});

function toggleCustomBox() {
    var selectedMethod = jQuery('input:checked', '#shipping_method').attr('id');
    var selectedMethodb = jQuery("#order_review .shipping .shipping_method option:selected").val();
    if (selectedMethod == null) {
        if (selectedMethodb != null) {
            selectedMethod = selectedMethodb;
        } else {
            return false;
        }
    }
    //sas, sasp, pasp, pas
    if (selectedMethod.indexOf("-sas") >= 0 || selectedMethod.indexOf("-sasp") >= 0 || selectedMethod.indexOf("-pasp") >= 0 || selectedMethod.indexOf("-pas") >= 0) {

        jQuery('#order_sucursal_main').show();
        jQuery('#order_sucursal_main').insertAfter(jQuery('.shop_table'));

        // Ejecutar la búsqueda de sucursales
        checkSucursales(selectedMethod);

    } else {
        jQuery('#order_sucursal_main').hide();
    }
}

// Función separada para verificar sucursales
function checkSucursales(selectedMethod) {
    // Usar un pequeño delay para asegurar que el DOM se haya actualizado
    setTimeout(function () {
        if (jQuery('#ship-to-different-address-checkbox').is(':checked')) {
            var state = jQuery('#shipping_state option:selected').text();
            var post_code = jQuery('#shipping_postcode').val();
        } else {
            var state = jQuery('#billing_state option:selected').text();
            var post_code = jQuery('#billing_postcode').val();
        }

        var order_sucursal = 'ok';
        var instance_id = selectedMethod.substr(selectedMethod.indexOf("instance_id") + 11);
        var operativa = selectedMethod.substr(selectedMethod.indexOf("operativa") + 9);
        var cuit = selectedMethod.substr(selectedMethod.indexOf("api_nrocuenta") + 4);
        var cuit_ok = cuit.substr(0, 9);
        var operativaok = operativa.substr(0, 9);

        jQuery("#order_sucursal_main_result").fadeOut(100);
        jQuery("#order_sucursal_main_result_cargando").fadeIn(100);

        jQuery.ajax({
            type: 'POST',
            cache: false,
            url: wc_checkout_params.ajax_url,
            data: {
                action: 'check_sucursales_andreani',
                post_code: post_code,
                provincia: state,
                order_sucursal: order_sucursal,
                operativa: operativaok,
                cuit: cuit_ok,
                instance_id: instance_id,
            },
            success: function (data, textStatus, XMLHttpRequest) {
                jQuery("#order_sucursal_main_result").fadeIn(100);
                jQuery("#order_sucursal_main_result_cargando").fadeOut(100);
                jQuery("#order_sucursal_main_result").html('');
                jQuery("#order_sucursal_main_result").append(data);

                var selectList = jQuery('#pv_centro_andreani_estandar option');
                var arr = selectList.map(function (_, o) { return { t: jQuery(o).text(), v: o.value }; }).get();
                arr.sort(function (o1, o2) { return o1.t > o2.t ? 1 : o1.t < o2.t ? -1 : 0; });
                selectList.each(function (i, o) {
                    o.value = arr[i].v;
                    jQuery(o).text(arr[i].t);
                });
                jQuery('#pv_centro_andreani_estandar').html(selectList);
                jQuery("#pv_centro_andreani_estandar").prepend("<option value='0' selected='selected'>Sucursales Disponibles</option>");
            },
            error: function (MLHttpRequest, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    }, 100); // Delay de 100ms para asegurar que el DOM se actualice
}

// Event listeners para detectar cambios en provincia
jQuery(document).ready(function () {

    // Listener para cambio en provincia de envío
    jQuery(document).on('change', '#shipping_state', function () {
        console.log('Cambio detectado en shipping_state:', jQuery(this).val());

        // Verificar si hay un método de envío seleccionado que requiera sucursales
        var selectedMethod = jQuery('input:checked', '#shipping_method').attr('id');
        var selectedMethodb = jQuery("#order_review .shipping .shipping_method option:selected").val();

        if (selectedMethod == null && selectedMethodb != null) {
            selectedMethod = selectedMethodb;
        }

        // Si hay un método seleccionado que requiere sucursales, recargar
        if (selectedMethod && (selectedMethod.indexOf("-sas") >= 0 || selectedMethod.indexOf("-sasp") >= 0 || selectedMethod.indexOf("-pasp") >= 0 || selectedMethod.indexOf("-pas") >= 0)) {
            // Limpiar selección anterior de sucursal
            jQuery('#pv_centro_andreani_estandar').val('0');

            // Recargar sucursales con la nueva provincia
            checkSucursales(selectedMethod);
        }
    });

    // Listener para cambio en provincia de facturación (cuando no se envía a dirección diferente)
    jQuery(document).on('change', '#billing_state', function () {
        console.log('Cambio detectado en billing_state:', jQuery(this).val());

        // Solo si no está marcado "enviar a dirección diferente"
        if (!jQuery('#ship-to-different-address-checkbox').is(':checked')) {
            var selectedMethod = jQuery('input:checked', '#shipping_method').attr('id');
            var selectedMethodb = jQuery("#order_review .shipping .shipping_method option:selected").val();

            if (selectedMethod == null && selectedMethodb != null) {
                selectedMethod = selectedMethodb;
            }

            if (selectedMethod && (selectedMethod.indexOf("-sas") >= 0 || selectedMethod.indexOf("-sasp") >= 0 || selectedMethod.indexOf("-pasp") >= 0 || selectedMethod.indexOf("-pas") >= 0)) {
                jQuery('#pv_centro_andreani_estandar').val('0');
                checkSucursales(selectedMethod);
            }
        }
    });

    // Listener para cuando se marca/desmarca "enviar a dirección diferente"
    jQuery(document).on('change', '#ship-to-different-address-checkbox', function () {
        console.log('Cambio detectado en ship-to-different-address-checkbox:', jQuery(this).is(':checked'));

        var selectedMethod = jQuery('input:checked', '#shipping_method').attr('id');
        var selectedMethodb = jQuery("#order_review .shipping .shipping_method option:selected").val();

        if (selectedMethod == null && selectedMethodb != null) {
            selectedMethod = selectedMethodb;
        }

        if (selectedMethod && (selectedMethod.indexOf("-sas") >= 0 || selectedMethod.indexOf("-sasp") >= 0 || selectedMethod.indexOf("-pasp") >= 0 || selectedMethod.indexOf("-pas") >= 0)) {
            jQuery('#pv_centro_andreani_estandar').val('0');
            checkSucursales(selectedMethod);
        }
    });

    // Listener adicional para eventos de WooCommerce
    jQuery(document.body).on('updated_checkout', function () {
        console.log('WooCommerce checkout updated');

        // Re-aplicar listeners después de actualización del checkout
        setTimeout(function () {
            var selectedMethod = jQuery('input:checked', '#shipping_method').attr('id');
            var selectedMethodb = jQuery("#order_review .shipping .shipping_method option:selected").val();

            if (selectedMethod == null && selectedMethodb != null) {
                selectedMethod = selectedMethodb;
            }

            if (selectedMethod && (selectedMethod.indexOf("-sas") >= 0 || selectedMethod.indexOf("-sasp") >= 0 || selectedMethod.indexOf("-pasp") >= 0 || selectedMethod.indexOf("-pas") >= 0)) {
                if (jQuery('#order_sucursal_main').is(':visible')) {
                    checkSucursales(selectedMethod);
                }
            }
        }, 200);
    });

    // Listener adicional usando input event (más sensible a cambios)
    jQuery(document).on('input change', '#shipping_state, #billing_state', function () {
        console.log('Input/Change detectado en:', jQuery(this).attr('id'), 'Valor:', jQuery(this).val());

        var isShipping = jQuery(this).attr('id') === 'shipping_state';
        var shouldProcess = isShipping || !jQuery('#ship-to-different-address-checkbox').is(':checked');

        if (shouldProcess) {
            var selectedMethod = jQuery('input:checked', '#shipping_method').attr('id');
            var selectedMethodb = jQuery("#order_review .shipping .shipping_method option:selected").val();

            if (selectedMethod == null && selectedMethodb != null) {
                selectedMethod = selectedMethodb;
            }

            if (selectedMethod && (selectedMethod.indexOf("-sas") >= 0 || selectedMethod.indexOf("-sasp") >= 0 || selectedMethod.indexOf("-pasp") >= 0 || selectedMethod.indexOf("-pas") >= 0)) {
                jQuery('#pv_centro_andreani_estandar').val('0');
                checkSucursales(selectedMethod);
            }
        }
    });
});


jQuery(document).ready(toggleCustomBox);
jQuery(document).on('change', '#shipping_method input:radio', toggleCustomBox);
jQuery(document).on('change', '#order_review .shipping .shipping_method', toggleCustomBox);

jQuery(document).on('change', '#shipping_state', function () {
    const selected = jQuery(this).find('option:selected').text();
    console.log('Provincia cambiada:', selected);

    // Podés llamar directamente toggleCustomBox() si querés refrescar las sucursales:
    toggleCustomBox();
});
