<?php

class FunctionsAndreani
{
	public function __construct()
	{
		add_action('wp_ajax_check_sucursales_andreani', array($this, 'check_sucursales_andreani'), 10);
		add_action('wp_ajax_nopriv_check_sucursales_andreani', array($this, 'check_sucursales_andreani'), 10);
		add_action('wp_ajax_nopriv_purchase_order_eon_andreani', array($this, 'purchase_order_eon_andreani'), 10);
		add_action('wp_ajax_imprimir_etiqueta_andreani', [$this, 'ajax_imprimir_etiqueta']);
		add_action('wp_ajax_purchase_order_eon_andreani', array($this, 'purchase_order_eon_andreani'), 10);

		add_action('wp_ajax_andreani_update_sucursal_id', [$this, 'ajax_update_sucursal_id']);
		add_action('wp_ajax_andreani_update_sucursal_completa', [$this, 'ajax_update_sucursal_completa']); // 👈 NUEVO

		add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'render_admin_andreani']);
		add_action(
			'woocommerce_email_after_order_table',
			[$this, 'add_tracking_to_completed_email'],
			20,
			4
		);
		add_filter('manage_edit-shop_order_columns', [$this, 'add_andreani_column'], 999);
		add_action('manage_shop_order_posts_custom_column', [$this, 'render_andreani_column'], 999, 2);
		add_action('admin_footer-edit.php', [$this, 'admin_footer_andreani_scripts']);

	}

	public function ajax_update_sucursal_completa()
	{
		try {
			if (!current_user_can('edit_shop_orders')) {
				wp_send_json_error(['message' => 'Permisos insuficientes.']);
			}

			$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
			$sucursal_json_raw = isset($_POST['sucursal_data']) ? $_POST['sucursal_data'] : '';

			if (!$order_id || empty($sucursal_json_raw)) {
				wp_send_json_error(['message' => 'Datos incompletos (order_id / sucursal_data).']);
			}

			// Decodificar la sucursal enviada desde el frontend
			$nueva_sucursal = json_decode(stripslashes($sucursal_json_raw), true);

			if (!is_array($nueva_sucursal) || empty($nueva_sucursal['id'])) {
				wp_send_json_error(['message' => 'Los datos de la sucursal no son válidos.']);
			}

			// Guardar la sucursal completa en el meta
			$encoded = wp_json_encode($nueva_sucursal, JSON_UNESCAPED_UNICODE);
			if (!$encoded) {
				wp_send_json_error(['message' => 'No se pudo codificar el JSON de sucursal.']);
			}

			update_post_meta($order_id, '_sucursal_andreani_c', $encoded);

	

			wp_send_json_success([
				'message' => 'Sucursal actualizada correctamente.',
				'sucursal_id' => $nueva_sucursal['id'],
				'sucursal_desc' => $nueva_sucursal['descripcion'] ?? '',
			]);

		} catch (Throwable $e) {
			wp_send_json_error(['message' => 'Error interno al actualizar la sucursal.']);
		}
	}
	public function ajax_update_sucursal_id()
	{
		try {
			if (!current_user_can('edit_shop_orders')) {
				wp_send_json_error(['message' => 'Permisos insuficientes.']);
			}

			$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
			$nuevo_id = isset($_POST['sucursal_id']) ? sanitize_text_field($_POST['sucursal_id']) : '';

			if (!$order_id || !$nuevo_id) {
				wp_send_json_error(['message' => 'Datos incompletos (order_id / sucursal_id).']);
			}

			// Obtener meta actual
			$sucursal_json = get_post_meta($order_id, '_sucursal_andreani_c', true);
			if (!$sucursal_json) {
				wp_send_json_error(['message' => 'No se encontró meta _sucursal_andreani_c para esta orden.']);
			}

			// Limpiar posibles escapes tipo \u00e1
			$sucursal_json = $this->decode_unicode_escape($sucursal_json);
			$sucursal = json_decode($sucursal_json, true);

			if (!is_array($sucursal)) {
				wp_send_json_error(['message' => 'El JSON de sucursal no es válido.']);
			}

			// Cambiar el ID
			$sucursal['id'] = $nuevo_id;

			// Guardar devuelta el JSON
			$encoded = wp_json_encode($sucursal, JSON_UNESCAPED_UNICODE);
			if (!$encoded) {
				wp_send_json_error(['message' => 'No se pudo codificar el JSON de sucursal.']);
			}

			update_post_meta($order_id, '_sucursal_andreani_c', $encoded);

			wp_send_json_success([
				'message' => 'Sucursal actualizada.',
				'nuevo_id' => $nuevo_id,
			]);

		} catch (Throwable $e) {
			wp_send_json_error(['message' => 'Error interno al actualizar la sucursal.']);
		}
	}

	public function add_andreani_column($columns)
	{
		$new_columns = [];
		foreach ($columns as $key => $value) {
			$new_columns[$key] = $value;
			if ($key === 'order_status') {
				$new_columns['andreani_envios'] = __('Andreani Envíos', 'woocommerce');
			}
		}
		return $new_columns;
	}


	public function render_andreani_column($column, $post_id)
	{
		if ($column !== 'andreani_envios') {
			return;
		}

		$agrupador = get_post_meta($post_id, '_agrupador_andreani', true);
		$tracking = get_post_meta($post_id, '_tracking_number', true);
		$etiqueta = get_post_meta($post_id, '_etiqueta_andreani', true);

		$order = wc_get_order($post_id);
		if (!$order)
			return;

		// Verificar si el método de envío pertenece a Andreani
		$has_andreani = false;
		foreach ($order->get_shipping_methods() as $method) {
			if (strpos($method->get_method_id(), 'andreani_eon') !== false) {
				$has_andreani = true;
				break;
			}
		}

		if (!$has_andreani) {
			echo '<span style="color:#999;">—</span>';
			return;
		}

		// Mostrar botón correspondiente
		if (!empty($agrupador) && !empty($tracking) && !empty($etiqueta)) {
			echo '<a href="#" class="button imprimir-etiqueta-andreani" 
                data-id="' . esc_attr($post_id) . '" 
                style="background:#d71920;color:#fff;border-color:#b51c1c;">Imprimir</a>';
			echo '<p style="margin-top:4px;font-size:11px;">' . esc_html($tracking) . '</p>';
		} else {
			echo '<a href="#" class="button generar-etiqueta-andreani" 
                data-id="' . esc_attr($post_id) . '" 
                style="background:#777;color:#fff;">Generar</a>';
		}
	}

	public function admin_footer_andreani_scripts()
	{
		$screen = get_current_screen();
		if ($screen->id !== 'edit-shop_order') {
			return;
		}
		?>
		<script type="text/javascript">
			(function ($) {
				$(document).on('click', '.generar-etiqueta-andreani', function (e) {
					e.preventDefault();
					const $btn = $(this);
					const orderId = $btn.data('id');
					$btn.text('Generando...').prop('disabled', true);

					$.post(ajaxurl, { action: 'purchase_order_eon_andreani', dataid: orderId }, function (response) {
						$btn.closest('td').html('<span style="color:green;">Etiqueta generada</span>');
						location.reload();
					}).fail(function () {
						alert('Error al generar etiqueta');
						$btn.text('Generar').prop('disabled', false);
					});
				});

				$(document).on('click', '.imprimir-etiqueta-andreani', async function (e) {
					e.preventDefault();
					const $btn = $(this);
					const orderId = $btn.data('id');
					$btn.text('Imprimiendo...').prop('disabled', true);

					try {
						const url = ajaxurl + '?' + $.param({
							action: 'imprimir_etiqueta_andreani',
							order_id: orderId
						});
						const res = await fetch(url);
						if (!res.ok) throw new Error('HTTP ' + res.status);

						const blob = await res.blob();
						const objectUrl = URL.createObjectURL(blob);
						const a = document.createElement('a');
						a.href = objectUrl;
						a.download = 'etiqueta-andreani.pdf';
						a.click();
						URL.revokeObjectURL(objectUrl);
						$btn.text('Imprimir').prop('disabled', false);
					} catch (err) {
						console.error(err);
						alert('Error al imprimir etiqueta');
						$btn.text('Imprimir').prop('disabled', false);
					}
				});
			})(jQuery);
		</script>
		<?php
	}
	private function get_andreani_tracking($order_id)
	{
		$tracking = get_post_meta($order_id, '_tracking_number', true);
		if (!$tracking) {
			$tracking = get_post_meta($order_id, '_andreani_estado_numero_andreani', true);
		}
		return $tracking ?: '';
	}
	public function add_tracking_to_completed_email($order, $sent_to_admin, $plain_text, $email)
	{
		if (!$email || $email->id !== 'customer_completed_order') {
			return;
		}

		$order_id = $order instanceof WC_Order ? $order->get_id() : (int) $order;
		if (!$order_id)
			return;

		$tracking = $this->get_andreani_tracking($order_id);
		if (!$tracking)
			return;
		$sucursal_json = get_post_meta($order_id, '_sucursal_andreani_c', true);
		$sucursal_json = $this->decode_unicode_escape($sucursal_json);
		$sucursal = json_decode($sucursal_json, true);
		$es_envio_sucursal = is_array($sucursal) && !empty($sucursal['id']);
		$url = 'https://seguimiento.andreani.com/envio/' . rawurlencode($tracking);
		if ($plain_text) {
			echo "\n--- Seguimiento del envío (Andreani) ---\n";
			echo "Código de seguimiento: {$tracking}\n";
			echo "Ver estado: {$url}\n";

			if ($es_envio_sucursal) {
				echo "\n--- Sucursal de retiro ---\n";
				echo "Sucursal: " . ($sucursal['descripcion'] ?? 'N/A') . "\n";

				if (!empty($sucursal['direccion'])) {
					$dir = $sucursal['direccion'];
					echo "Dirección: ";
					echo ($dir['calle'] ?? '') . ' ' . ($dir['numero'] ?? '') . ', ';
					echo ($dir['localidad'] ?? '') . ', ' . ($dir['provincia'] ?? '') . "\n";
					echo "Código Postal: " . ($dir['codigoPostal'] ?? 'N/A') . "\n";
				}

				if (!empty($sucursal['horarioDeAtencion'])) {
					echo "Horario: " . $sucursal['horarioDeAtencion'] . "\n";
				}
			}

			echo "\n";
			return;
		}

		?>
		<table role="presentation" cellspacing="0" cellpadding="0" border="0"
			style="margin-top:20px; width:100%; max-width:600px; border:1px solid #e0e0e0; border-radius:8px; font-family: Arial, sans-serif;">
			<tr>
				<td style="background:#d71920; padding:16px; border-radius:8px 8px 0 0;">
					<h3 style="margin:0; font-size:18px; color:#ffffff; font-weight:600;">
						📦 Seguimiento de tu envío
					</h3>
				</td>
			</tr>
			<tr>
				<td style="padding:20px; background:#ffffff;">
					<p style="margin:0 0 8px; font-size:14px; color:#666;">
						<strong style="color:#333;">Código de seguimiento:</strong>
					</p>
					<p style="margin:0 0 16px; font-size:16px; color:#d71920; font-weight:600;">
						<?php echo esc_html($tracking); ?>
					</p>

					<a href="<?php echo esc_url($url); ?>" target="_blank"
						style="display:inline-block; padding:12px 24px; text-decoration:none; background:#d71920; color:#fff; border-radius:4px; font-size:14px; font-weight:600;">
						Ver estado del envío →
					</a>
				</td>
			</tr>

			<?php if ($es_envio_sucursal): ?>
				<tr>
					<td style="padding:0 20px;">
						<div style="height:1px; background:#e0e0e0;"></div>
					</td>
				</tr>
				<tr>
					<td style="padding:20px; background:#f9f9f9;">
						<p style="margin:0 0 12px; font-size:16px; color:#333; font-weight:600;">
							📍 Tu pedido será enviado a la siguiente sucursal:
						</p>

						<p style="margin:0 0 8px; font-size:15px; color:#d71920; font-weight:600;">
							<?php echo esc_html($sucursal['descripcion'] ?? 'Sucursal Andreani'); ?>
						</p>

						<?php if (!empty($sucursal['direccion'])):
							$dir = $sucursal['direccion'];
							?>
							<!-- Dirección -->
							<p style="margin:0 0 4px; font-size:14px; color:#555; line-height:1.5;">
								<strong>Dirección:</strong><br>
								<?php
								echo esc_html($dir['calle'] ?? '') . ' ' . esc_html($dir['numero'] ?? '');
								if (!empty($dir['localidad']) || !empty($dir['provincia'])) {
									echo '<br>' . esc_html($dir['localidad'] ?? '') . ', ' . esc_html($dir['provincia'] ?? '');
								}
								if (!empty($dir['codigoPostal'])) {
									echo '<br>CP: ' . esc_html($dir['codigoPostal']);
								}
								?>
							</p>
						<?php endif; ?>

						<?php if (!empty($sucursal['horarioDeAtencion'])): ?>
							<!-- Horario -->
							<p
								style="margin:12px 0 0; font-size:13px; color:#666; padding:10px; background:#ffffff; border-left:3px solid #d71920; border-radius:4px;">
								<strong style="color:#333;">⏰ Horario de atención:</strong><br>
								<?php echo esc_html($sucursal['horarioDeAtencion']); ?>
							</p>
						<?php endif; ?>

						<!-- Nota informativa -->
						<p style="margin:16px 0 0; font-size:12px; color:#888; font-style:italic;">
							💡 Recordá llevar tu DNI al retirar el paquete
						</p>
					</td>
				</tr>
			<?php endif; ?>

			<!-- Pie con logo -->
			<tr>
				<td style="padding:16px; background:#f5f5f5; text-align:center; border-radius:0 0 8px 8px;">
					<p style="margin:0; font-size:12px; color:#999;">
						Envío gestionado por <strong style="color:#d71920;">Andreani</strong>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}
	private function decode_unicode_escape($string)
	{
		return preg_replace_callback('/u([0-9a-fA-F]{4})/', function ($matches) {
			$hex = $matches[1];
			$char_code = hexdec($hex);
			return mb_chr($char_code, 'UTF-8');
		}, $string);
	}
	public function ajax_imprimir_etiqueta()
	{
		try {
			$order_id = intval($_REQUEST['order_id'] ?? 0);
			if (!$order_id) {
				throw new Exception('ID de orden inválido o no recibido.');
			}

			$agrupador = get_post_meta($order_id, '_agrupador_andreani', true);
			if (!$agrupador) {
				throw new Exception("No se encontró el agrupador Andreani para la orden #{$order_id}");
			}

			$token = $this->login_andreani();
			if (empty($token)) {
				throw new Exception('No se pudo obtener el token de autenticación de Andreani.');
			}

			$url_base = $this->get_andreani_api_url();
			$url = "{$url_base}/v2/ordenes-de-envio/{$agrupador}/etiquetas";

			$response = wp_remote_get($url, [
				'headers' => [
					'x-authorization-token' => $token,
				],
				'timeout' => 30,
			]);

			if (is_wp_error($response)) {
				throw new Exception('Error al conectarse a Andreani: ' . $response->get_error_message());
			}
			$code = wp_remote_retrieve_response_code($response);
			$headers = wp_remote_retrieve_headers($response);
			$body = wp_remote_retrieve_body($response);
			if ($code !== 200) {
				throw new Exception("La API devolvió un código {$code}. Respuesta: {$body}");
			}

			if (empty($body) || strlen($body) < 100) {
				throw new Exception("El cuerpo del PDF está vacío o incompleto (longitud: " . strlen($body) . ")");
			}

			nocache_headers();
			while (ob_get_level())
				ob_end_clean();

			header('Content-Type: application/pdf');
			header('Content-Length: ' . mb_strlen($body, '8bit'));
			header('Content-Disposition: attachment; filename="etiqueta-andreani.pdf"');

			echo $body;
			exit;

		} catch (Throwable $e) {

			status_header(500);
			wp_die('Ocurrió un error al generar la etiqueta Andreani. Ver logs para más detalles.');
		}
	}



	public function render_admin_andreani($order)
	{
		$post_id = $order->get_id();
		$site_url = get_site_url();
		$shipping = $order->get_items('shipping');
		$sucursal_json = get_post_meta($post_id, '_sucursal_andreani_c', true);
		$sucursal_json = $this->decode_unicode_escape($sucursal_json);
		$sucursal = json_decode($sucursal_json, true);
		$envio_seleccionado = '';

		$codigo_postal_envio = $order->get_shipping_postcode();
		$select_sucursales = $this->get_sucursal_by_cp($codigo_postal_envio);

		if (!empty($shipping)) {
			foreach ($shipping as $shipping_item) {
				$envio_seleccionado = $shipping_item->get_method_id();
			}
		}

		if (empty($shipping) || $envio_seleccionado !== 'andreani_eon') {
			return;
		}

		echo '<div class="andreani-single" style="background:#f8f8f8;padding:15px;margin-top:15px;border:1px solid #ddd;">';
		echo '<img src="https://componentesui.blob.core.windows.net/recursos/logos-gla/isologo-195.png" alt="Logo de Andreani" style="max-width:100%;height:auto;">';
		echo '<p><strong>Contrato:</strong><br>';
		foreach ($shipping as $method) {
			echo esc_html($method['name']) . '<br>';
		}
		echo '</p>';

		if (is_array($sucursal)) {
			$descripcion = $sucursal['descripcion'] ?? 'Sin descripción';
			$direccion = $sucursal['direccion']['calle'] . ' ' .
				$sucursal['direccion']['numero'] . ', ' .
				$sucursal['direccion']['localidad'] . ', ' .
				$sucursal['direccion']['provincia'];
			$codigo_postal = $sucursal['direccion']['codigoPostal'] ?? '';
			$horario = $sucursal['horarioDeAtencion'] ?? 'Sin horario disponible';
			$id_sucursal = $sucursal['id'] ?? '';

			echo '<div style="background:#fff;padding:15px;border:1px solid #ddd;margin-top:10px;">';
			echo '<h4 style="margin:0;">📍 Sucursal Andreani Seleccionada</h4>';
			echo '<p><strong>Descripción:</strong> ' . esc_html($descripcion) . '</p>';
			echo '<p><strong>Dirección:</strong> ' . esc_html($direccion) . '</p>';
			echo '<p><strong>Código Postal:</strong> ' . esc_html($codigo_postal) . '</p>';
			echo '<p><strong>Horario:</strong> ' . esc_html($horario) . '</p>';

			// 🔹 SELECT de sucursales en lugar de input
			echo '<hr style="margin:10px 0;">';
			echo '<p><strong>Cambiar sucursal de retiro:</strong></p>';
			echo '<p>';
			echo '<select id="andreani_sucursal_select_' . esc_attr($post_id) . '" style="width:100%;max-width:500px;padding:8px;">';

			if (!empty($select_sucursales) && is_array($select_sucursales)) {
				foreach ($select_sucursales as $suc) {
					$suc_id = $suc->id ?? '';
					$suc_desc = $suc->descripcion ?? '';
					$suc_dir = '';

					// Construir la dirección
					if (!empty($suc->direccion->calle)) {
						$suc_dir = $suc->direccion->calle;
						if (!empty($suc->direccion->numero)) {
							$suc_dir .= ' ' . $suc->direccion->numero;
						}
					}

					// Texto de la opción
					$option_text = $suc_desc;
					if (!empty($suc_dir)) {
						$option_text .= ' - ' . $suc_dir;
					}

					// Marcar como seleccionada si coincide con la actual
					$selected = ($suc_id == $id_sucursal) ? 'selected' : '';

					// Guardar el objeto completo en data-sucursal
					echo '<option value="' . esc_attr($suc_id) . '" ' . $selected . ' data-sucursal=\'' . esc_attr(json_encode($suc)) . '\'>';
					echo esc_html($option_text);
					echo '</option>';
				}
			} else {
				echo '<option value="">No hay sucursales disponibles para este CP (' . esc_html($codigo_postal_envio) . ')</option>';
			}

			echo '</select>';
			echo '</p>';
			echo '<p>';
			echo '<button type="button" 
					class="button button-primary andreani-actualizar-sucursal" 
					data-order-id="' . esc_attr($post_id) . '">
					Actualizar Sucursal
			  </button>';
			echo '</p>';
			echo '<p id="andreani-update-msg-' . esc_attr($post_id) . '" style="font-size:11px;"></p>';

			echo '</div>';
		}

		echo '</div>';

		// 🧾 Metadatos de la orden
		$tracking = get_post_meta($post_id, '_tracking_number', true);
		$etiqueta = get_post_meta($post_id, '_etiqueta_andreani', true);
		$agrupador = get_post_meta($post_id, '_agrupador_andreani', true);

		// 🟩 Si ya tiene agrupador y etiqueta → mostrar botón Imprimir
		if (!empty($agrupador) && !empty($tracking) && !empty($etiqueta)) {
			echo '<div style="width:100%;">
			<a id="imprimir-etiqueta-andreani" data-id="' . $post_id . '"
			   style="width:93%;text-align:center;background:#d71920;color:white;padding:10px;margin:10px;display:block;text-decoration:none;cursor:pointer;">
			   IMPRIMIR ETIQUETA
			</a>
		  </div>';
			echo '<p id="andreani-result"></p>';
			echo '<div style="width:100%;"><a style="width:93%;text-align:center;background:#d71920;color:white;padding:10px;margin:10px;display:block;text-decoration:none;" href="http://seguimiento.andreani.com/envio/' . $tracking . '" target="_blank">Seguir Paquete</a></div>';
			echo '<div style="width:100%;">Nro. Seguimiento: ' . esc_html($tracking) . '</div>';
		} else {
			// 🟥 No tiene agrupador → mostrar botón Generar
			echo '<style type="text/css">
			#generar-andreani {
				background:#d71920;
				color:white;
				width:100%;
				text-align:center;
				height:40px;
				line-height:37px;
				cursor:pointer;
			}
		  </style>';

			echo '<div id="generar-andreani" class="button" data-id="' . $post_id . '">Generar Etiqueta</div>';
			echo '<input type="hidden" value="' . $site_url . '" id="site" name="site" />';
			echo '<div class="andreani-single-label"></div>';
		}
		?>

		<!-- Scripts -->
		<script type="text/javascript">
			(function ($) {

				$(document).ready(function () {

					// 🔹 Generar etiqueta
					$('body').on('click', '#generar-andreani', function (e) {
						e.preventDefault();
						$(this).hide();

						const site = $('#site').val();
						const dataid = $(this).data('id');
						const url = site + '/wp-admin/admin-ajax.php';

						$.ajax({
							type: 'POST',
							cache: false,
							url: url,
							data: { action: 'purchase_order_eon_andreani', dataid: dataid },
							success: function (data) {
								$(".andreani-single-label").fadeIn(400).html(data);
							},
							error: function () {
								alert('Error al generar etiqueta');
							}
						});
					});

					// 🔹 Imprimir etiqueta
					$('body').on('click', '#imprimir-etiqueta-andreani', async function (e) {
						e.preventDefault();
						$('#andreani-result').text('Generando etiqueta...');

						const order_id = $(this).data('id');

						const url = ajaxurl + '?' + $.param({
							action: 'imprimir_etiqueta_andreani',
							order_id: order_id
						});

						try {
							const res = await fetch(url, { method: 'GET' });
							if (!res.ok) throw new Error('HTTP ' + res.status);

							const blob = await res.blob();
							const objectUrl = URL.createObjectURL(blob);

							const a = document.createElement('a');
							a.href = objectUrl;
							a.download = 'etiqueta-andreani.pdf';
							document.body.appendChild(a);
							a.click();
							a.remove();
							URL.revokeObjectURL(objectUrl);

							$('#andreani-result').html('<p style="color:green;">Descargado</p>');
							setTimeout(() => $('#andreani-result').empty(), 3000);

						} catch (err) {
							console.error(err);
							$('#andreani-result').html('<p style="color:red;">Error al imprimir la etiqueta</p>');
						}
					});

					// 🔹 Actualizar sucursal completa (no solo ID)
					$('body').on('click', '.andreani-actualizar-sucursal', function (e) {
						e.preventDefault();

						const $btn = $(this);
						const orderId = $btn.data('order-id');
						const $select = $('#andreani_sucursal_select_' + orderId);
						const selectedOption = $select.find('option:selected');
						const sucursalDataRaw = selectedOption.attr('data-sucursal');
						const $msg = $('#andreani-update-msg-' + orderId);

						if (!sucursalDataRaw) {
							alert('Por favor, seleccioná una sucursal válida.');
							return;
						}

						let sucursalData;
						try {
							sucursalData = JSON.parse(sucursalDataRaw);
						} catch (e) {
							alert('Error al procesar los datos de la sucursal.');
							return;
						}

						$btn.prop('disabled', true);
						$msg.css('color', 'black').text('Actualizando sucursal...');

						$.post(ajaxurl, {
							action: 'andreani_update_sucursal_completa',
							order_id: orderId,
							sucursal_data: JSON.stringify(sucursalData)
						}, function (response) {
							if (response && response.success) {
								$msg.css('color', 'green').text('Sucursal actualizada correctamente. Recargando...');
								setTimeout(() => location.reload(), 1500);
							} else {
								const msg = response?.data?.message ?? 'No se pudo actualizar la sucursal.';
								$msg.css('color', 'red').text(msg);
								$btn.prop('disabled', false);
							}
						}).fail(function () {
							$msg.css('color', 'red').text('Error de conexión al guardar.');
							$btn.prop('disabled', false);
						});
					});

				});

			})(jQuery);
		</script>

		<?php
	}

	public function register_andreani_meta_box()
	{
		add_meta_box(
			'woocommerce-andreani-box',
			__('Andreani - Detalles Envio', 'woocommerce-andreani'),
			array($this, 'woocommerce_andreani_box_create_box_content'),
			'shop_order',
			'side',
			'default'
		);
	}

	private function log_to_file($data)
	{
		$log_file = plugin_dir_path(__FILE__) . 'andreani_log.txt';
		$log_data = "Log entry at " . date("Y-m-d H:i:s") . "\n";
		$log_data .= print_r($data, true) . "\n";
		file_put_contents($log_file, $log_data, FILE_APPEND);
	}
	private function get_andreani_api_url($fallback = 'https://apis.andreani.com')
	{
		$delivery_zones = WC_Shipping_Zones::get_zones();

		foreach ($delivery_zones as $zone) {
			foreach ($zone['shipping_methods'] as $method) {
				if ($method->id === 'andreani_eon' && $method->enabled === 'yes') {
					$environment = $method->instance_settings['entorno_api'] ?? 'produccion';
					return $environment === 'desarrollo'
						? 'https://apisqa.andreani.com'
						: 'https://apis.andreani.com';
				}
			}
		}

		return $fallback;
	}
	private function get_user_password()
	{
		$delivery_zones = WC_Shipping_Zones::get_zones();

		foreach ($delivery_zones as $zone) {
			foreach ($zone["shipping_methods"] as $method) {
				if ($method->id === "andreani_eon" && $method->enabled === "yes") {
					return [
						'user' => $method->instance_settings['api_user'] ?? '',
						'password' => $method->instance_settings['api_password'] ?? ''
					];
				}
			}
		}

		// Si no se encontró nada
		return ['user' => '', 'password' => ''];
	}

	private function login_andreani()
	{
		try {
			$transient_key = 'token_andreani_login';
			$token = get_transient($transient_key);

			if ($token) {
				return $token;
			}

			$url = $this->get_andreani_api_url() . '/login';

			$credenciales = $this->get_user_password();

			$body = [
				"userName" => $credenciales['user'],
				"password" => $credenciales['password'],
			];

			$args = [
				'method' => 'POST',
				'headers' => [
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
				],
				'body' => json_encode($body),
				'timeout' => 15,
			];

			$response = wp_remote_post($url, $args);

			if (is_wp_error($response)) {
				return null;
			}

			$json = wp_remote_retrieve_body($response);
			$data = json_decode($json, true);

			if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
				return null;
			}

			$token = $data['token'] ?? null;

			if ($token) {
				// Guardar el token durante 23 horas
				set_transient($transient_key, $token, 23 * HOUR_IN_SECONDS);
			}

			return $token;

		} catch (Throwable $e) {
			return null;
		}
	}

	public function check_sucursales_andreani()
	{
		global $woocommerce, $wp_session;

		try {
			session_start();

			if (!isset($_POST['post_code']) || !isset($_POST['instance_id'])) {
				throw new Exception('Código postal o instance_id no definido.');
			}

			$settings_key = 'woocommerce_andreani_eon_' . $_POST['instance_id'] . '_settings';
			$settings_andreani = get_option($settings_key);

			if (!$settings_andreani || empty($settings_andreani['api_user']) || empty($settings_andreani['api_password'])) {
				throw new Exception('Faltan credenciales de API en la configuración.');
			}

			$api_user = $settings_andreani['api_user'];
			$api_password = $settings_andreani['api_password'];
			$provincia = isset($_POST['provincia']) ? sanitize_text_field($_POST['provincia']) : '';

			$params = array(
				"method" => array(
					"get_centros_destino" => array(
						'api_user' => $api_user,
						'api_password' => $api_password,
						'api_confirmarretiro' => $settings_andreani['api_confirmarretiro'] ?? '',
						'api_nrocuenta' => $settings_andreani['api_nrocuenta'] ?? '',
						'operativa' => $_POST['operativa'] ?? '',
						'cp_destino' => $_POST['post_code'],
					)
				)
			);

			$res_andreani_oficial = $this->get_sucursal_by_cp($_POST['post_code'], $api_user, $api_password, $provincia);

			echo '<h3 class="title-selector-andreani" style="text-align:left; font-family: Roboto,sans-serif;     display: flex;
 		   justify-content: flex-start; padding: 5px 0px;">Seleccione una sucursal Andreani</h3>';
			echo '<select id="pv_centro_andreani_estandar" name="pv_centro_andreani_estandar">';

			if (!empty($res_andreani_oficial)) {
				foreach ($res_andreani_oficial as $sucursal) {
					$idCentroImposicion = $sucursal->id;
					$direccion = $sucursal->descripcion;

					$partes = [];

					if (!empty($sucursal->direccion->calle)) {
						$parte = $sucursal->direccion->calle;

						if (!empty($sucursal->direccion->numero)) {
							$parte .= ' ' . $sucursal->direccion->numero;
						}

						$partes[] = $parte;
					}

					if (empty($partes) && !empty($sucursal->direccion->region)) {
						$partes[] = $sucursal->direccion->region;
					}

					if (!empty($partes)) {
						$direccion .= ', ' . implode(', ', $partes);
					}

					echo '<option value="' . esc_attr($idCentroImposicion) . '">' . esc_html($direccion) . '</option>';
				}

			} else {
				echo '<option value="">No se encontraron sucursales para esta provincia.</option>';
			}

			echo '</select>';

			$_SESSION['listado_andreani'] = $res_andreani_oficial;
			$_SESSION['params_andreani'] = $params;

		} catch (Throwable $e) {
			echo '<select id="pv_centro_andreani_estandar" name="pv_centro_andreani_estandar">';
			echo '<option value="">Error al obtener las sucursales: ' . esc_html($e->getMessage()) . '</option>';
			echo '</select>';
		}

		die();
	}


	private function get_sucursal_by_cp($cp_destino)
	{
		try {
			$key_transient = 'andreani_sucursales_v4' . $cp_destino;
			$cache = get_transient($key_transient);
			if ($cache !== false) {
				return $cache;
			}

			$all_sucursales = $this->get_sucursales_andreani();
			$filtradas = [];
			$codigos_agregados = [];

			foreach ($all_sucursales as $sucursal) {

				$cp_directo = $sucursal->direccion->codigoPostal ?? null;
				$cp_atendidos = $sucursal->codigosPostalesAtendidos ?? [];

				if (!is_array($cp_atendidos)) {
					$cp_atendidos = [];
				}

				$coincide_cp = (
					(string) $cp_destino === (string) $cp_directo ||
					in_array((string) $cp_destino, array_map('strval', $cp_atendidos))
				);
				$entrega_envios =
					isset($sucursal->datosAdicionales->entregaEnvios) &&
					$sucursal->datosAdicionales->entregaEnvios === true;

				$hace_atencion =
					isset($sucursal->datosAdicionales->seHaceAtencionAlCliente) &&
					$sucursal->datosAdicionales->seHaceAtencionAlCliente === true;

				$codigo = strtoupper(trim($sucursal->codigo ?? ''));

				if (
					$coincide_cp &&
					$entrega_envios &&
					$hace_atencion &&
					!in_array($codigo, $codigos_agregados)
				) {
					$filtradas[] = $sucursal;
					$codigos_agregados[] = $codigo;
				}
			}

			set_transient($key_transient, $filtradas, 60 * 60);

			return $filtradas;

		} catch (Exception $e) {
			error_log('❌ Error en get_sucursal_by_cp: ' . $e->getMessage());
			return [];
		}
	}

	private function get_sucursales_andreani()
	{
		$transient_key = 'andreani_sucursales_v5';
		$token = $this->login_andreani();

		$url = 'https://apis.andreani.com/v2/sucursales?canal=B2C';

		$response = wp_remote_get($url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json',
			]
		]);


		if (is_wp_error($response)) {
			return null;
		}
		$body = wp_remote_retrieve_body($response);
		$decoded = json_decode($body);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return null;
		}

		// 💾 Guardar en cache por 1 mes
		set_transient($transient_key, $decoded, MONTH_IN_SECONDS);

		return $decoded;
	}



public function purchase_order_eon_andreani()
{
	global $woocommerce, $post, $wp_session;

	try {
		$order_id = isset($_POST['dataid']) ? (int) $_POST['dataid'] : 0;
		if (!$order_id) {
			throw new Exception('ID de pedido inválido.');
		}

		$order = wc_get_order($order_id);
		if (!$order) {
			throw new Exception('No se encontró el pedido.');
		}

		$params_andreani   = get_post_meta($order_id, '_params_andreani', true);
		$chosen_shipping   = get_post_meta($order_id, '_chosen_shipping', true);
		$instance_id       = substr($chosen_shipping, strpos($chosen_shipping, "instance_id") + 11, -1);
		$sucursal_andreani_c = get_post_meta($order_id, '_sucursal_andreani_c', true);

		// ─────────────────────────────────────
		// ORIGEN (incluye meta_key del DNI)
		// ─────────────────────────────────────
		$origen_datos_array = $this->build_origen_datos_array($order); // devuelve [ stdClass ]
		$origen_obj = isset($origen_datos_array[0]) ? $origen_datos_array[0] : null;

		// si alguna vez usás sucursal_origen en el origen
		$sucursal_origen = $origen_obj->sucursal_origen ?? null;

		// meta key configurable del DNI, con fallback a _billing_dni
		$dni_meta_key = !empty($origen_obj->dni_meta_key) ? $origen_obj->dni_meta_key : '_billing_dni';

		// leer DNI de la orden usando la meta key configurada
		$dni = get_post_meta($order_id, $dni_meta_key, true);

		// ─────────────────────────────────────
		// DESTINO (datos del cliente)
		// ─────────────────────────────────────
		$destino_datos_arr = [
			[
				'nroremito'       => $order_id,
				'apellido'        => $order->get_shipping_last_name(),
				'nombre'          => $order->get_shipping_first_name(),
				'calle'           => $order->get_shipping_address_1(),
				'nro'             => $order->get_shipping_address_2(),
				'piso'            => '',
				'depto'           => '',
				'localidad'       => $order->get_shipping_city(),
				'provincia'       => $order->get_shipping_state(),
				'cp'              => $order->get_shipping_postcode(),
				'telefono'        => $order->get_billing_phone(),
				'email'           => $order->get_billing_email(),
				'celular'         => $order->get_billing_phone(),
				'sucursal_origen' => $sucursal_origen,
				'andreani_tarifa' => $order->get_shipping_total(),
				'dni'             => $dni,
			]
		];

		$params = [
			"method" => [
				"get_etiquetas" => [
					'sucursal_andreani_c' => $sucursal_andreani_c ?? '',
					'origen_datos'        => wp_json_encode($origen_datos_array),
					'destino_datos'       => wp_json_encode($destino_datos_arr),
					'chosen_shipping'     => $chosen_shipping,
				]
			]
		];

		$token  = $this->login_andreani();
		$params = $params['method']['get_etiquetas'] ?? [];

		// ─────────────────────────────────────
		// Normalización de origen / destino
		// ─────────────────────────────────────
		$origen_datos_decoded   = json_decode($params['origen_datos'], true);
		$destino_datos_decoded  = json_decode($params['destino_datos'], true);
		$origen_datos           = isset($origen_datos_decoded[0]) ? $origen_datos_decoded[0] : $origen_datos_decoded;
		$destino_datos          = isset($destino_datos_decoded[0]) ? $destino_datos_decoded[0] : $destino_datos_decoded;

		$largo = (int)   ($origen_datos['andreani_lenth']   ?? 0);
		$ancho = (int)   ($origen_datos['andreani_width']   ?? 0);
		$alto  = (int)   ($origen_datos['andreani_height']  ?? 0);
		$peso  = (float) ($origen_datos['andreani_weightb'] ?? 0);
		$valor = (float) ($origen_datos['andreani_amount']  ?? 0);

		preg_match('/operativa(\d+)/', $params['chosen_shipping'], $matches);
		$contrato = $matches[1] ?? '';

		// ─────────────────────────────────────
		// Sucursal / domicilio
		// ─────────────────────────────────────
		$sucursal_arr = null;
		if (!empty($sucursal_andreani_c)) {
			$sucursal_json = $this->decode_unicode_escape($sucursal_andreani_c);
			$tmp = json_decode($sucursal_json, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
				$sucursal_arr = $tmp;
			}
		}

		$is_sucursal  = is_array($sucursal_arr) && !empty($sucursal_arr['id']);
		$tipoServicio = $is_sucursal ? 'Sucursal' : 'Domicilio';

		$destino = [
			'postal' => [
				'codigoPostal'            => $destino_datos['cp']      ?? '',
				'calle'                   => $destino_datos['calle']   ?? '',
				'numero'                  => !empty($destino_datos['nro']) ? $destino_datos['nro'] : '0',
				'localidad'               => $destino_datos['localidad'] ?? '',
				'region'                  => '',
				'pais'                    => 'AR',
				'componentesDeDireccion'  => [
					['meta' => 'piso',         'contenido' => !empty($destino_datos['piso'])  ? $destino_datos['piso']  : '0'],
					['meta' => 'departamento', 'contenido' => !empty($destino_datos['depto']) ? $destino_datos['depto'] : '0'],
				],
			],
		];

		if ($is_sucursal && !empty($sucursal_arr['id'])) {
			$destino['sucursal'] = [
				'id' => (string) $sucursal_arr['id'],
			];
		}

		if ($is_sucursal) {
			unset($destino['postal']);
			$tipoServicio = 'Sucursal';
		} else {
			unset($destino['sucursal']);
			$tipoServicio = 'Domicilio';
		}
		$body = [
			'contrato'     => $contrato,
			'idPedido'     => $destino_datos['nroremito'] ?? '',
			'tipoServicio' => $tipoServicio,

			'origen' => [
				'postal' => [
					'codigoPostal'           => $origen_datos['origin']            ?? '',
					'calle'                  => $origen_datos['origin_calle']      ?? '',
					'numero'                 => $origen_datos['origin_numero']     ?? '',
					'localidad'              => $origen_datos['origin_landreanilidad'] ?? '',
					'region'                 => 'AR-X',
					'pais'                   => 'AR',
					'componentesDeDireccion' => [
						['meta' => 'entreCalle', 'contenido' => ''],
					],
				],
			],

			'destino' => $destino,

			'remitente' => [
				'nombreCompleto' => $origen_datos['origin_contacto'] ?? '',
				'email'          => $origen_datos['origin_email']    ?? '',
				'documentoTipo'  => 'DNI',
				'documentoNumero'=> '',
				'telefonos'      => [
					['tipo' => 1, 'numero' => '3511234567'],
				],
			],

			'destinatario' => [
				[
					'nombreCompleto' => trim(($destino_datos['nombre'] ?? '') . ' ' . ($destino_datos['apellido'] ?? '')),
					'email'          => $destino_datos['email'] ?? '',
					'documentoTipo'  => 'DNI',
					'documentoNumero'=> $destino_datos['dni']   ?? '',
					'telefonos'      => [
						[
							'tipo'   => 2,
							'numero' => !empty($destino_datos['telefono'])
								? $destino_datos['telefono']
								: (!empty($destino_datos['celular']) ? $destino_datos['celular'] : '3517654321')
						]
					],
				]
			],

			'remito' => [
				'numeroRemito' => 'wc_order_' . ($destino_datos['nroremito'] ?? ''),
			],

			'bultos' => [
				[
					'anchoCm'                    => $ancho,
					'altoCm'                     => $alto,
					'largoCm'                    => $largo,
					'kilos'                      => $peso,
					'volumenCm'                  => $ancho * $alto * $largo,
					'valorDeclaradoSinImpuestos'=> $valor,
					'valorDeclaradoConImpuestos'=> round($valor * 1.21, 2),
					'referencias'               => [
						['meta' => 'producto'],
						['meta' => 'idCliente', 'contenido' => '41'],
						['meta' => 'observaciones', 'contenido' => $origen_datos['origin_observaciones'] ?? ''],
					],
				]
			],
		];

		$payload_json = wp_json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if (isset($_GET['debug_andreani']) && current_user_can('manage_woocommerce')) {
			echo '<details open style="margin:10px 0;padding:8px;border:1px solid #ddd;background:#fafafa">';
			echo '<summary><strong>Payload a Andreani (FINAL)</strong></summary>';
			echo '<pre style="white-space:pre-wrap;">' . esc_html($payload_json) . '</pre>';
			echo '</details>';
		}

		$headers = [
			'Content-Type'         => 'application/json',
			'x-authorization-token'=> $token,
		];

		$url  = $this->get_andreani_api_url() . '/v2/ordenes-de-envio';
		$args = [
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => wp_json_encode($body),
			'timeout' => 20,
		];

		$andreani_response = wp_remote_post($url, $args);
		if (is_wp_error($andreani_response)) {
			echo '<div style="color:red;">Ocurrió un error al conectar con Andreani. Por favor, intente nuevamente.</div>';
			die();
		}

		$body_resp = wp_remote_retrieve_body($andreani_response);
		if (empty($body_resp)) {
			echo '<div style="color:red;">La respuesta de Andreani está vacía. Por favor, intente nuevamente.</div>';
			die();
		}

		$response_data = json_decode($body_resp);

		$numero_envio = $response_data->bultos[0]->numeroDeEnvio        ?? '';
		$etiqueta     = $response_data->bultos[0]->linking[0]->contenido ?? '';
		$agrupador    = $response_data->agrupadorDeBultos                ?? '';
		$estado       = $response_data->estado                           ?? '';
		$date         = current_time('Y-m-d H:i:s');

		update_post_meta($order_id, '_tracking_number',                  $numero_envio);
		update_post_meta($order_id, '_custom_tracking_provider',         'Andreani');
		update_post_meta($order_id, '_custom_tracking_link',             'https://www.andreani.com/');
		update_post_meta($order_id, '_date_shipped',                     $date);
		update_post_meta($order_id, '_etiqueta_andreani',                $etiqueta);
		update_post_meta($order_id, '_agrupador_andreani',               $agrupador);
		update_post_meta($order_id, '_andreani_estado',                  $estado);
		update_post_meta($order_id, '_andreani_estado_numero_andreani',  $numero_envio);

		echo '<div style="width:100%;"><a data-id="' . esc_attr($order_id) . '" style="width:90%;text-align:center;background:#d71920;color:#fff;padding:10px;margin:10px;display:inline-block;text-decoration:none;cursor:pointer;" id="imprimir-etiqueta-andreani">IMPRIMIR ETIQUETA</a></div>';
		echo '<p id="andreani-result"></p>';
		echo '<div style="width:100%;"><a style="width:90%;text-align:center;background:#d71920;color:#fff;padding:10px;margin:10px;display:inline-block;text-decoration:none;" href="#" target="_blank">' . esc_html($numero_envio) . '</a></div>';
		die();

	} catch (Throwable $e) {
		echo '<div style="color:red;">Ocurrió un error al procesar el pedido. Por favor, intente nuevamente.</div>';
		die();
	}
}




	private function build_origen_datos_array($order)
	{
		$shipping_methods = $order->get_shipping_methods();
		$method_instance = null;

		foreach ($shipping_methods as $method) {
			$method_id_full = $method->get_method_id();
			$instance_id = $method->get_instance_id();
			if (strpos($method_id_full, 'andreani_eon') !== false) {
				$available_methods = WC_Shipping_Zones::get_zone_matching_package([
					'destination' => [
						'country' => $order->get_shipping_country(),
						'state' => $order->get_shipping_state(),
						'postcode' => $order->get_shipping_postcode(),
						'city' => $order->get_shipping_city(),
					]
				])->get_shipping_methods();

				foreach ($available_methods as $available_method) {
					if ($available_method->instance_id == $instance_id) {
						$method_instance = $available_method;
						break;
					}
				}

				if ($method_instance)
					break;
			}
		}

		if (!$method_instance) {
			error_log('❌ No se encontró el método de envío Andreani activo');
			return [];
		}

		$origen_obj = new stdClass();
		$origen_obj->origin = $method_instance->get_option('origin');
		$origen_obj->api_key = $method_instance->get_option('api_key');
		$origen_obj->origin_contacto = $method_instance->get_option('origin_contacto');
		$origen_obj->origin_email = $method_instance->get_option('origin_email');
		$origen_obj->origin_calle = $method_instance->get_option('origin_calle');
		$origen_obj->origin_numero = $method_instance->get_option('origin_numero');
		$origen_obj->origin_piso = $method_instance->get_option('origin_piso');
		$origen_obj->origin_depto = $method_instance->get_option('origin_depto');
		$origen_obj->origin_landreanilidad = $method_instance->get_option('origin_landreanilidad');
		$origen_obj->origin_provincia = $method_instance->get_option('origin_provincia');
		$origen_obj->origin_observaciones = $method_instance->get_option('origin_observaciones');
		$origen_obj->api_user = $method_instance->get_option('api_user');
		$origen_obj->api_password = $method_instance->get_option('api_password');
		$origen_obj->api_nrocuenta = $method_instance->get_option('api_nrocuenta');
		$origen_obj->api_confirmarretiro = $method_instance->get_option('api_confirmarretiro');
		$origen_obj->sucursal_origin = $method_instance->get_option('sucursal_origin');
		$origen_obj->dni_meta_key = $method_instance->get_option('dni_meta_key') ?: '_billing_dni';

		$items = $order->get_items();
		$total_amount = 0;
		$total_weight = 0;
		$max_length = $max_width = $max_height = 0;

		foreach ($items as $item) {
			$product = $item->get_product();
			$qty = $item->get_quantity();

			$total_amount += $product->get_price() * $qty;
			$total_weight += floatval($product->get_weight()) * $qty;

			$max_length = max($max_length, floatval($product->get_length()));
			$max_width = max($max_width, floatval($product->get_width()));
			$max_height = max($max_height, floatval($product->get_height()));
		}

		$origen_obj->andreani_lenth = $max_length ?: 0.1;
		$origen_obj->andreani_width = $max_width ?: 0.1;
		$origen_obj->andreani_height = $max_height ?: 0.05;
		$origen_obj->andreani_amount = $total_amount;
		$origen_obj->andreani_weightb = $total_weight;

		return array($origen_obj);
	}


	public function woocommerce_andreani_box_create_box_content()
	{
		global $post;
		$site_url = get_site_url();
		$order = wc_get_order($post->ID);
		$shipping = $order->get_items('shipping');
		$sucursal_andreani_c = get_post_meta($post->ID, '_sucursal_andreani_c', true);
		echo '<div class="andreani-single">';
		echo '<strong>Contrato</strong></br>';
		foreach ($shipping as $method) {
			echo $method['name'];
		}
		//		var_dump($sucursal_andreani_c);
		echo '</div>';

		//ETIQUETA
		$andreani_shipping_label_tracking = get_post_meta($post->ID, '_tracking_number', true);
		$etiqueta = get_post_meta($post->ID, '_etiqueta_andreani', true);
		$andreani_estado_ordenretiro = get_post_meta($post->ID, '_andreani_estado_ordenretiro', true);
		$andreani_estado_numeroenvio = get_post_meta($post->ID, '_andreani_estado_numeroenvio', true);

		if (!empty($etiqueta) and !empty($andreani_shipping_label_tracking)) {
			echo '<div style=" width: 100%; "><a style=" width: 90%;text-align: center;background: #d71920;color: white;padding: 10px;margin: 10px;float: left;text-decoration: none;" href="' . $etiqueta . '" target="_blank">IMPRIMIR ETIQUETA</a></div>';
		}

		if (!empty($andreani_shipping_label_tracking)) {
			echo '<div style=" width: 100%; " ><a style=" width: 90%; text-align: center;background: #d71920;color: white;padding: 10px;margin: 10px;float: left;text-decoration: none;" href="http://seguimiento.andreani.com/envio/' . $andreani_shipping_label_tracking . '" target="_blank">Seguir Paquete</a></div>';
			echo '<div style=" width: 100%; " >Nro. Seguimiento: ' . $andreani_shipping_label_tracking . '</div>';
		}

		if (empty($andreani_shipping_label_tracking)) { ?>

			<style type="text/css">
				#generar-andreani {
					background: #d71920;
					color: white;
					width: 100%;
					text-align: center;
					height: 40px;
					padding: 0px;
					line-height: 37px;
				}
			</style>

			<?php if (!$andreani_shipping_label_tracking) { ?>

				<div id="generar-andreani" class="button" data-id="<?php echo $post->ID; ?>">Generar Etiqueta</div>
			<?php } ?>
			<input type="hidden" value="<?php echo $site_url; ?>" id="site" name="site" />

			<div class="andreani-single-label"> </div>
			<script type="text/javascript">
				jQuery('body').on('click', '#generar-andreani', function (e) {
					e.preventDefault();
					jQuery(this).hide();
					var site = jQuery('#site').val();
					var urls = " " + site + "/wp-admin/admin-ajax.php";
					var dataid = jQuery(this).data("id");
					jQuery.ajax({
						type: 'POST',
						cache: false,
						url: urls,
						data: { action: 'purchase_order_eon_andreani', dataid: dataid, },
						success: function (data, textStatus, XMLHttpRequest) {
							jQuery(".andreani-single-label").fadeIn(400);
							jQuery(".andreani-single-label").html('');
							jQuery(".andreani-single-label").append(data);
							//jQuery("#generar-andreani").fadeOut(100);

							//landreanition.reload();
						},
						error: function (MLHttpRequest, textStatus, errorThrown) { }
					});
				});	
			</script>
		<?php }
	}




}
new FunctionsAndreani();



