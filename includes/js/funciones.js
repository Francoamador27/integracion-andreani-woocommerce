jQuery(document).ready(function () {
   // Ocultar sucursales si el método de envío seleccionado es "papp"
  toggleSucursales();

  // Manejar el cambio en el selector de sucursales
  jQuery('#sucursales_andreani').on('change', function () {
      const arrayInfoSucursal = jQuery(this).val().split("#");
      const direccionEnvio = jQuery(this).find('option:selected').text().split("-");
       // Cambiar el método de envío a "pasp"
      jQuery('input[name="shipping_method[0]"][value="pasp"]').prop('checked', true);
      
      // Actualizar las direcciones de envío
      jQuery("#shipping_address_1").val(direccionEnvio[1]);
      jQuery("#shipping_address_2").val("");
      jQuery("#shipping_city").val(direccionEnvio[2]);
      jQuery("#shipping_postcode").val(arrayInfoSucursal[1]);

      // Habilitar o deshabilitar el código postal según el método de envío
      toggleShippingPostcode();

      // Actualizar el checkout
      jQuery(document.body).trigger("update_checkout");

      return false;
  });

  // Establecer longitud máxima para los códigos postales
  setMaxLengthForPostcodes();
   // Validar que solo se ingresen números en los campos de código postal
  validatePostcodeInput("#calc_shipping_postcode");
  validatePostcodeInput("#billing_postcode");
  validatePostcodeInput("#shipping_postcode");

  // Manejar el cambio en el método de envío
  jQuery(document.body).on('change', 'input.shipping_method', function () {
      toggleSucursales();      

  });

  // Función para ocultar o mostrar sucursales
  function toggleSucursales() {
      const selectedMethod = jQuery('input[name="shipping_method[0]"]:checked').val();
      if (selectedMethod === "papp") {
          jQuery("#sucursales_andreani").hide();
      } else if (selectedMethod === "pasp") {
          jQuery("#sucursales_andreani").show();
      }
  }

  // Función para habilitar o deshabilitar el código postal
  function toggleShippingPostcode() {
      if (jQuery('input[name="shipping_method[0]"]:checked').val() === 'pasp') {
          jQuery("#shipping_postcode").prop("disabled", false);
      } else {
          jQuery("#shipping_postcode").prop("disabled", true);
          jQuery('label[for="shipping_postcode"]').html('Codigo postal de la sede');
      }
  }

  // Función para establecer la longitud máxima de los códigos postales
  function setMaxLengthForPostcodes() {
      jQuery('#calc_shipping_postcode, #billing_postcode, #shipping_postcode').attr({ maxLength: 4 });
  }

  // Función para validar la entrada de códigos postales
  function validatePostcodeInput(selector) {
      jQuery(selector).on('keypress', function (e) {
          if (e.which !== 8 && e.which !== 0 && (e.which < 48 || e.which > 57)) {
              return false;
          }
      });
  }
});