jQuery(document).ready(function ($) {
    // Cambio de origen para buscar sucursales
    $('#woocommerce_andreani_eon_origin').change(function () {
        var operativa    = $('.eon_boxes #rates .operativa').val();
        var cuit_ok      = $('#woocommerce_andreani_eon_cuit_number').val();
        var post_code    = $('#woocommerce_andreani_eon_origin').val();
        var api_user     = $('#woocommerce_andreani_eon_api_user').val();
        var api_password = $('#woocommerce_andreani_eon_api_password').val();
        var api_nrocuenta= $('#woocommerce_andreani_eon_api_nrocuenta').val();
        var prod         = 'prod';

        $('#pv_centro_andreani_estandar').remove();
        $("#woocommerce_andreani_eon_sucursal_origin").fadeIn(0)
            .val("Cargando Sucursales...");

        $.ajax({
            type: 'POST',
            cache: false,
            url: ajaxurl,
            data: {
                action: 'check_admision_andreani',
                post_code: post_code,
                operativa: operativa,
                cuit: cuit_ok,
                api_user: api_user,
                api_password: api_password,
                api_nrocuenta: api_nrocuenta,
                prod: prod,
            },
            success: function (data) {
                $("#woocommerce_andreani_eon_sucursal_origin").fadeOut(0)
                    .parent().append(data);

                $('#pv_centro_andreani_estandar').change(function () {
                    $('#woocommerce_andreani_eon_sucursal_origin')
                        .val($('#pv_centro_andreani_estandar').val());
                });

                var selectList = $('#pv_centro_andreani_estandar option');
                var arr = selectList.map(function (_, o) {
                    return { t: $(o).text(), v: o.value };
                }).get();
                arr.sort(function (o1, o2) {
                    return o1.t > o2.t ? 1 : o1.t < o2.t ? -1 : 0;
                });
                selectList.each(function (i, o) {
                    o.value = arr[i].v;
                    $(o).text(arr[i].t);
                });
                $('#pv_centro_andreani_estandar').html(selectList);
                $("#pv_centro_andreani_estandar")
                    .prepend("<option value='0' selected='selected'>Sucursales Disponibles</option>");
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    });

    // Solo números en ajuste precio
    $('#woocommerce_andreani_eon_ajuste_precio').keydown(function (e) {
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
            (e.keyCode >= 35 && e.keyCode <= 40)) {
            return;
        }
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) &&
            (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });

    // Mostrar/ocultar packing options (si lo seguís usando)
    $('#woocommerce_eon_packing_method').change(function () {
        if ($(this).val() == 'box_packing')
            $('#packing_options').show();
        else
            $('#packing_options').hide();
    }).change();

    // Agregar fila de servicio
    $('.eon_boxes .insert').click(function () {
        var $tbody = $('.eon_boxes').find('tbody');
        var size   = $tbody.find('tr').size();
        var code   =
            '<tr class="new">' +
                '<td class="check-column"><input type="checkbox" /></td>' +
                '<td><input type="text" size="35" name="service_name[' + size + ']" /></td>' +
                '<td><input type="text" size="15" name="service_operativa[' + size + ']" /></td>' +
                '<td><select class="select modalidad" name="woocommerce_andreani_eon_modalidad[' + size + ']">' +
                    '<option value="0">Seleccionar</option>' +
                    '<option value="sas">Sucursal a Sucursal</option>' +
                    '<option value="sap">Sucursal a Puerta</option>' +
                    '<option value="pas">Puerta a Sucursal</option>' +
                    '<option value="pap">Puerta a Puerta</option>' +
                    '<option value="sasp">Sucursal a Sucursal - C/P.Destino</option>' +
                    '<option value="sapp">Sucursal a Puerta - C/P.Destino</option>' +
                    '<option value="pasp">Puerta a Sucursal - C/P.Destino</option>' +
                    '<option value="papp">Puerta a Puerta - C/P.Destino</option>' +
                '</select></td>' +
                '<td><input type="checkbox" name="service_enabled[' + size + ']" /></td>' +
            '</tr>';

        $tbody.append(code);
        return false;
    });

    // Remover filas marcadas
    $('.eon_boxes .remove').click(function () {
        var $tbody = $('.eon_boxes').find('tbody');
        $tbody.find('.check-column input:checked').each(function () {
            $(this).closest('tr').hide().find('input, select').val('');
        });
        return false;
    });

    // Ordenar servicios (si usás .eon_services)
    $('.eon_services tbody').sortable({
        items: 'tr',
        cursor: 'move',
        axis: 'y',
        handle: '.sort',
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.65,
        placeholder: 'wc-metabox-sortable-placeholder',
        start: function (event, ui) {
            ui.item.css('background-color', '#f6f6f6');
        },
        stop: function (event, ui) {
            ui.item.removeAttr('style');
            eon_services_row_indexes();
        }
    });

    function eon_services_row_indexes() {
        $('.eon_services tbody tr').each(function (index, el) {
            $('input.order', el).val(parseInt($(el).index('.eon_services tr')));
        });
    }
});
