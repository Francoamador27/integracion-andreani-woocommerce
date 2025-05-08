jQuery(document).ready(function () {

        // Input validation for numeric input
    // Definición de constantes para códigos de teclas
    const KEY_CODES = {
        DELETE: 46,
        BACKSPACE: 8,
        TAB: 9,
        ESCAPE: 27,
        ENTER: 13,
        NUMPAD_DECIMAL: 110,
        PERIOD: 190,
        HOME: 35,
        END: 36,
        ARROW_LEFT: 37,
        ARROW_UP: 38,
        ARROW_RIGHT: 39,
        ARROW_DOWN: 40,
        DIGIT_0: 48,
        DIGIT_9: 57,
        NUMPAD_0: 96,
        NUMPAD_9: 105
    };

    // Conjunto de teclas válidas para la entrada numérica
    const validKeys = new Set([
        KEY_CODES.DELETE,
        KEY_CODES.BACKSPACE,
        KEY_CODES.TAB,
        KEY_CODES.ESCAPE,
        KEY_CODES.ENTER,
        KEY_CODES.NUMPAD_DECIMAL,
        KEY_CODES.PERIOD
    ]);

    // Función para manejar la validación de entrada
    function validateNumericInput(e) {
        const keyCode = e.keyCode;

        // Permitir teclas válidas
        if (validKeys.has(keyCode) ||
            (keyCode === 65 && (e.ctrlKey || e.metaKey)) || // Ctrl + A
            (keyCode >= KEY_CODES.HOME && keyCode <= KEY_CODES.ARROW_DOWN)) { // Home, End, Arrow keys
            return; // Allow these keys
        }

        // Prevenir entrada no numérica
        if ((e.shiftKey || (keyCode < KEY_CODES.DIGIT_0 || keyCode > KEY_CODES.DIGIT_9)) &&
            (keyCode < KEY_CODES.NUMPAD_0 || keyCode > KEY_CODES.NUMPAD_9)) {
            e.preventDefault(); // Prevent non-numeric input
        }
    }

    // Asignar el evento keydown al campo específico
    jQuery('#woocommerce_andreani_flexipaas_ajuste_precio').keydown(validateNumericInput);

    // Show/hide packing options based on selection
    jQuery('#woocommerce_flexipaas_packing_method').change(function () {
        const isBoxPacking = jQuery(this).val() === 'box_packing';
        jQuery('#packing_options').toggle(isBoxPacking);
    }).change(); // Trigger change to set initial state

    // Insert new service row
    jQuery('.flexipaas_boxes .insert').click(function () {
        const $tbody = jQuery('.flexipaas_boxes tbody');
        const size = $tbody.find('tr').length;

        const newRow = `<tr class="new">
            <td class="check-column"><input type="checkbox" /></td>
            <td>
                <select class="select modalidad" name="woocommerce_andreani_flexipaas_modalidad[${size}]">
                    <option value="0">Seleccionar</option>
                    <option value="pasp">Puerta a Sucursal - C/P.Destino</option>
                    <option value="papp">Puerta a Puerta - C/P.Destino</option>
                </select>
            </td>
            <td>
                <div class="tooltip">
                    <input type="text" size="15" name="contrato_numero[${size}]" />
                    <span class="tooltiptext">Contrato proporcionado por el contacto comercial de Andreani</span>
                </div>
            </td>
            <td>
                <div class="tooltip">
                    <input type="text" size="35" name="contrato_modalidad_desc[${size}]" />
                    <span class="tooltiptext">Agregar una descripción para identificar el servicio contratado</span>
                </div>
            </td>
            <td><input type="checkbox" name="modalidad_activa[${size}]" /></td>
        </tr>`;

        $tbody.append(newRow);
        return false; // Prevent default action
    });

    // Remove selected service rows
    jQuery('.flexipaas_boxes .remove').click(function () {
        const $tbody = jQuery('.flexipaas_boxes tbody');
        $tbody.find('.check-column input:checked').each(function () {
            jQuery(this).closest('tr').hide().find('input').val('');
        });
        return false; // Prevent default action
    });

    // Enable dragging to reorder service rows
    jQuery('.flexipaas_services tbody').sortable({
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
            ui.item.css('background-color', '#f6f6f6'); // Fixed typo
        },
        stop: function () {
            flexipaas_services_row_indexes(); // Update indexes after sorting
        }
    });

    // Update input indexes in each row
    function flexipaas_services_row_indexes() {
        jQuery('.flexipaas_services tbody tr').each(function (index) {
            jQuery('input.order', this).val(index);
        });
    }

});