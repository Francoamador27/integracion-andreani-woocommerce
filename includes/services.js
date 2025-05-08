
  			jQuery(document).ready(function () {
				jQuery('#sucursales_andreani').change(function(){});
  				jQuery('#woocommerce_andreani_flexipaas_origin').change(function(){ 
 						var operativa = jQuery('.flexipaas_boxes #rates .operativa').val();
						var cuit_ok = jQuery('#woocommerce_andreani_flexipaas_cuit_number').val();					
						var post_code = jQuery('#woocommerce_andreani_flexipaas_origin').val();
 						jQuery('#pv_centro_andreani_estandar').remove();
						jQuery("#woocommerce_andreani_flexipaas_sucursal_origin").fadeIn(0);
						jQuery('#woocommerce_andreani_flexipaas_sucursal_origin').val("Cargando Sucursales...");
 						jQuery.ajax({
				    		type: 'POST',
				    		cache: false,
								url: ajaxurl,
				    		data: {
 									action: 'check_admision',
									post_code: post_code,
									operativa: operativa,
									cuit: cuit_ok,								
				    		},
				    		success: function(data, textStatus, XMLHttpRequest){
 											jQuery("#woocommerce_andreani_flexipaas_sucursal_origin").fadeOut(0);
											jQuery("#woocommerce_andreani_flexipaas_sucursal_origin").parent().append(data);
 											jQuery('#pv_centro_andreani_estandar').change(function(){
												jQuery('#woocommerce_andreani_flexipaas_sucursal_origin').val(jQuery('#pv_centro_andreani_estandar').val());
											});
												var selectList = jQuery('#pv_centro_andreani_estandar option');
												var arr = selectList.map(function(_, o) { return { t: jQuery(o).text(), v: o.value }; }).get();
												arr.sort(function(o1, o2) { return o1.t > o2.t ? 1 : o1.t < o2.t ? -1 : 0; });
												selectList.each(function(i, o) {
													o.value = arr[i].v;
													jQuery(o).text(arr[i].t);
												});
												jQuery('#pv_centro_andreani_estandar').html(selectList);
												jQuery("#pv_centro_andreani_estandar").prepend("<option value='0' selected='selected'>Sucursales Disponibles</option>");	
										},
								error: function(MLHttpRequest, textStatus, errorThrown){
								//	alert(errorThrown);
										}
						});
				});	
				
			 jQuery('#woocommerce_andreani_flexipaas_ajuste_precio').keydown(function (e) {
						// Allow: backspace, delete, tab, escape, enter and .
						if (jQuery.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
								 // Allow: Ctrl+A, Command+A
								(e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
								 // Allow: home, end, left, right, down, up
								(e.keyCode >= 35 && e.keyCode <= 40)) {
										 // let it happen, don't do anything
										 return;
						}
						// Ensure that it is a number and stop the keypress
						if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
								e.preventDefault();
						}
				});				
				
				jQuery('#woocommerce_flexipaas_packing_method').change(function(){
 
					if ( jQuery(this).val() == 'box_packing' )
						jQuery('#packing_options').show();
					else
						jQuery('#packing_options').hide();
				}).change();

				jQuery('.flexipaas_boxes .insert').click( function() {   
 
					var $tbody = jQuery('.flexipaas_boxes').find('tbody');
					var size = $tbody.find('tr').size();
					var code = '<tr class="new">\
							<td class="check-column"><input type="checkbox" /></td>\
							<td><select class="select modalidad" name="woocommerce_andreani_flexipaas_modalidad[' + size + ']" id="woocommerce_andreani_flexipaas_modalidad" style=""><option value="0">Seleccionar</option>  <option value="pasp">Puerta a Sucursal - C/P.Destino</option><option value="papp">Puerta a Puerta - C/P.Destino</option></select></td>\
							<td><div class="tooltip"><input type="text" size="15" name="service_operativa[' + size + '] Andreani" /><span class="tooltiptext">Contrato proporcionado por el contacto comercial de Andreani</span></div></td>\
							<td><div class="tooltip"><input type="text" size="35" name="service_name[' + size + ']" /> <span class="tooltiptext">Agregar una descripción para identificar el servicio contratado</span></div></td>\
												<td><input type="checkbox" name="service_enabled[' + size + ']" /></td>\  	</tr>';
					$tbody.append( code );
					return false;
				});

				jQuery('.flexipaas_boxes .remove').click(function() {
					var $tbody = jQuery('.flexipaas_boxes').find('tbody');
					$tbody.find('.check-column input:checked').each(function() {
						jQuery(this).closest('tr').hide().find('input').val('');
					});
					return false;
				});

				// Ordering
				jQuery('.flexipaas_services tbody').sortable({
					items:'tr',
					cursor:'move',
					axis:'y',
					handle: '.sort',
					scrollSensitivity:40,
					forcePlaceholderSize: true,
					helper: 'clone',
					opacity: 0.65,
					placeholder: 'wc-metabox-sortable-placeholder',
					start:function(event,ui){
						ui.item.css('baclbsround-color','#f6f6f6');
					},
					stop:function(event,ui){
						ui.item.removeAttr('style');
						flexipaas_services_row_indexes();
					}
				});

				function flexipaas_services_row_indexes() {				 

					jQuery('.flexipaas_services tbody tr').each(function(index, el){
						jQuery('input.order', el).val( parseInt( jQuery(el).index('.flexipaas_services tr') ) );
					});
				};

			});

	