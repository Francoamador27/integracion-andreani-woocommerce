  jQuery(document).ready(function () { 
    if(jQuery('input[name="shipping_method[0]"]:checked').val()=="papp") jQuery("#sucursales_andreani").hide();

    jQuery('#sucursales_andreani').change(function(){ 
       var arrayInfoSucursal = jQuery("#sucursales_andreani").val().split("#");
       const direccionEnvio = jQuery("#sucursales_andreani option:selected").text().split("-");
       jQuery('input[name="shipping_method[0]"][value="pasp"]').attr('checked','checked');
       jQuery("#shipping_address_1").val(direccionEnvio[1]);
       jQuery("#shipping_address_2").val("");

       jQuery("#shipping_city").val(direccionEnvio[2]);
       jQuery("#shipping_postcode").val(arrayInfoSucursal[1]);
       console.log(jQuery('input[name="shipping_method[0]"]:checked').val()=='pasp');
       if ( jQuery('input[name="shipping_method[0]"]:checked').val() == 'pasp' ) {
           jQuery("#shipping_postcode").prop("disabled",false);

       }else 			jQuery('label[for="shipping_postcode"]').html( 'Codigo postal de la sede');
       
       console.log("console"+direccionEnvio[1]);
       jQuery(document.body).trigger("update_checkout");

      return false;
   });

jQuery('#calc_shipping_postcode').attr({ maxLength : 4 });
jQuery('#billing_postcode').attr({ maxLength : 4 });
jQuery('#shipping_postcode').attr({ maxLength : 4 });

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

   jQuery(document.body).on( 'change', 'input.shipping_method', function() {
       if(jQuery('input[name="shipping_method[0]"]:checked').val()=="papp") jQuery("#sucursales_andreani").hide();
       if(jQuery('input[name="shipping_method[0]"]:checked').val()=="pasp") jQuery("#sucursales_andreani").show();

       

   });

}


);
 