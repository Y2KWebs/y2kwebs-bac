<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce gateway for PowerTranz SPI Sale transactions.
 */
final class Y2KWEBS_BAC_Gateway extends WC_Payment_Gateway {
	const CALLBACK_SLUG = 'y2kwebs_bac_callback';
	const META_PREFIX   = '_y2kwebs_bac_';

	// PowerTranz Simplified 3DS API v2.8 field limits.
	const CARDHOLDER_NAME_MAX_LENGTH = 45;
	const FIRST_NAME_MAX_LENGTH       = 30;
	const LAST_NAME_MAX_LENGTH        = 30;
	const ADDRESS_LINE_1_MAX_LENGTH   = 30;
	const ADDRESS_LINE_2_MAX_LENGTH   = 50;
	const CITY_MAX_LENGTH             = 25;
	const EMAIL_MAX_LENGTH            = 50;
	const PHONE_MAX_LENGTH            = 20;

	/** @var Y2KWEBS_BAC_Currency_Config */
	private $currency_config;

	/** @var Y2KWEBS_BAC_API_Client */
	private $api_client;

	public function __construct() {
		$this->id                 = 'y2kwebs_bac';
		$this->method_title       = __( 'BAC — Y2KWebs', 'y2kwebs-bac' );
		$this->method_description = __( 'Venta de contado con autenticación SPI 3DS para CRC y USD.', 'y2kwebs-bac' );
		$this->icon               = '';
		$this->has_fields         = true;
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title', __( 'Tarjeta de crédito o débito', 'y2kwebs-bac' ) );
		$this->description = $this->get_option( 'description', __( 'Pago seguro procesado por PowerTranz.', 'y2kwebs-bac' ) );

		$this->currency_config = new Y2KWEBS_BAC_Currency_Config( $this->settings );
		$this->api_client      = new Y2KWEBS_BAC_API_Client( $this->api_root() );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ) );
		add_action( 'wp', array( $this, 'maybe_add_result_notice' ) );
	}

	public function init_form_fields() {
		$secret_description = __( 'Se recomienda definir este secreto mediante una constante de wp-config.php o una variable de entorno. El valor guardado aquí se usa sólo como alternativa.', 'y2kwebs-bac' );

		$this->form_fields = array(
			'enabled'          => array(
				'title'   => __( 'Activar/Desactivar', 'y2kwebs-bac' ),
				'type'    => 'checkbox',
				'label'   => __( 'Activar BAC (PowerTranz)', 'y2kwebs-bac' ),
				'default' => 'no',
			),
			'title'            => array(
				'title'       => __( 'Título', 'y2kwebs-bac' ),
				'type'        => 'text',
				'default'     => __( 'Tarjeta de crédito o débito', 'y2kwebs-bac' ),
				'desc_tip'    => true,
				'description' => __( 'Nombre mostrado durante el checkout.', 'y2kwebs-bac' ),
			),
			'description'      => array(
				'title'       => __( 'Descripción', 'y2kwebs-bac' ),
				'type'        => 'textarea',
				'default'     => __( 'Pago seguro procesado por PowerTranz.', 'y2kwebs-bac' ),
				'description' => __( 'Texto mostrado debajo del método de pago.', 'y2kwebs-bac' ),
			),
			'environment'      => array(
				'title'   => __( 'Entorno', 'y2kwebs-bac' ),
				'type'    => 'select',
				'default' => 'staging',
				'options' => array(
					'staging'    => __( 'Pruebas', 'y2kwebs-bac' ),
					'production' => __( 'Producción', 'y2kwebs-bac' ),
				),
			),
			'availability_mode' => array(
				'title'       => __( 'Visibilidad en el checkout', 'y2kwebs-bac' ),
				'type'        => 'select',
				'default'     => 'administrators',
				'description' => __( 'Durante la certificación, mantenga el método visible sólo para administradores. Cámbielo a todos únicamente cuando esté listo para clientes.', 'y2kwebs-bac' ),
				'options'     => array(
					'administrators' => __( 'Sólo administradores', 'y2kwebs-bac' ),
					'everyone'       => __( 'Todos los clientes', 'y2kwebs-bac' ),
				),
			),
			'api_root'         => array(
				'title'       => __( 'Raíz del API', 'y2kwebs-bac' ),
				'type'        => 'url',
				'description' => __( 'Opcional. Déjelo vacío para usar el endpoint oficial del entorno seleccionado.', 'y2kwebs-bac' ),
				'default'     => '',
			),
			'crc_id'           => array(
				'title'       => __( 'PowerTranz ID — CRC', 'y2kwebs-bac' ),
				'type'        => 'text',
				'description' => $secret_description . ' Y2KWEBS_BAC_CRC_ID',
			),
			'crc_password'     => array(
				'title'       => __( 'Contraseña — CRC', 'y2kwebs-bac' ),
				'type'        => 'password',
				'description' => $secret_description . ' Y2KWEBS_BAC_CRC_PASSWORD',
			),
			'usd_id'           => array(
				'title'       => __( 'PowerTranz ID — USD', 'y2kwebs-bac' ),
				'type'        => 'text',
				'description' => $secret_description . ' Y2KWEBS_BAC_USD_ID',
			),
			'usd_password'     => array(
				'title'       => __( 'Contraseña — USD', 'y2kwebs-bac' ),
				'type'        => 'password',
				'description' => $secret_description . ' Y2KWEBS_BAC_USD_PASSWORD',
			),
			'allow_auth_u'     => array(
				'title'       => __( 'Autenticación no disponible', 'y2kwebs-bac' ),
				'type'        => 'checkbox',
				'label'       => __( 'Permitir completar pagos con AuthenticationStatus U', 'y2kwebs-bac' ),
				'description' => __( 'Desactivado por defecto: las transacciones U no conservan protección frente a determinados contracargos.', 'y2kwebs-bac' ),
				'default'     => 'no',
			),
			'debug_logging'    => array(
				'title'       => __( 'Registro de diagnóstico', 'y2kwebs-bac' ),
				'type'        => 'checkbox',
				'label'       => __( 'Registrar fallos de PowerTranz en los logs de WooCommerce', 'y2kwebs-bac' ),
				'description' => __( 'Registra sólo pedido, entorno, moneda, importe, ISO, mensaje saneado, resultado y campos 3DS seguros, además del PowerTranz ID enmascarado. Nunca registra PAN, CVV, credenciales, SpiToken, RedirectData, CAVV, XID ni DS Transaction ID.', 'y2kwebs-bac' ),
				'default'     => 'no',
			),
		);
	}

	public function is_available() {
		if ( ! parent::is_available() ) {
			return false;
		}

		if ( 'administrators' === $this->get_option( 'availability_mode', 'administrators' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		$currency = get_woocommerce_currency();
		return null !== $this->currency_config->account_for( $currency );
	}

	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( $this->description ) );
		}

		wc_get_template(
			'payment-fields.php',
			array(),
			'',
			Y2KWEBS_BAC_PATH . 'templates/'
		);
	}

	public function enqueue_checkout_assets() {
		if ( ! is_checkout() && ! is_checkout_pay_page() ) {
			return;
		}

		wp_enqueue_style( 'y2kwebs-bac-checkout', Y2KWEBS_BAC_URL . 'assets/css/checkout.css', array(), Y2KWEBS_BAC_VERSION );
		wp_enqueue_script( 'y2kwebs-bac-checkout', Y2KWEBS_BAC_URL . 'assets/js/checkout.js', array(), Y2KWEBS_BAC_VERSION, true );
	}

	public function validate_fields() {
		try {
			$this->validated_card_data();
			return true;
		} catch ( Exception $exception ) {
			wc_add_notice( $exception->getMessage(), 'error' );
			return false;
		}
	}

	public function process_payment( $order_id ) {
		if ( ! $this->acquire_order_lock( $order_id ) ) {
			wc_add_notice( __( 'Ya estamos procesando este pedido. Espere antes de continuar.', 'y2kwebs-bac' ), 'error' );
			return array( 'result' => 'failure' );
		}
		try {
			$order = wc_get_order( $order_id );
			if ( ! $order || $this->id !== $order->get_payment_method() || 'yes' !== $this->enabled
				|| ( 'administrators' === $this->get_option( 'availability_mode', 'administrators' ) && ! current_user_can( 'manage_woocommerce' ) ) ) {
				wc_add_notice( __( 'No pudimos validar el pedido. Por favor, inténtelo de nuevo.', 'y2kwebs-bac' ), 'error' );
				return array( 'result' => 'failure' );
			}
			if ( $order->is_paid() || 'completed' === $order->get_meta( self::META_PREFIX . 'status', true ) ) {
				return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
			}
			$status = $order->get_meta( self::META_PREFIX . 'status', true );
			if ( in_array( $status, array( 'payment_sending', 'unknown', 'starting' ), true ) ) {
				wc_add_notice( __( 'El pago está pendiente de revisión. Contacte con la tienda antes de volver a pagar.', 'y2kwebs-bac' ), 'error' );
				return array( 'result' => 'failure' );
			}
			if ( 'authentication_pending' === $status ) {
				if ( time() - (int) $order->get_meta( self::META_PREFIX . 'started_at', true ) <= 300 ) {
					return array( 'result' => 'success', 'redirect' => $order->get_checkout_payment_url( true ) );
				}
				$this->fail_authenticated_order( $order, 'spi_token_expired', array() );
			}
			if ( ! $order->needs_payment() ) {
				return array( 'result' => 'failure' );
			}
			$account = $this->currency_config->account_for( $order->get_currency() );
			if ( null === $account ) {
				wc_add_notice( __( 'Este método de pago no está disponible para la moneda del pedido.', 'y2kwebs-bac' ), 'error' );
				return array( 'result' => 'failure' );
			}

			try {
				$card = $this->validated_card_data();
			} catch ( Exception $exception ) {
				wc_add_notice( $exception->getMessage(), 'error' );
				return array( 'result' => 'failure' );
			}

			$transaction_id = wp_generate_uuid4();
			$order_reference = sprintf( 'WC-%d-%s', $order->get_id(), substr( str_replace( '-', '', $transaction_id ), 0, 12 ) );
			$state            = wp_generate_password( 48, false, false );
			$callback_url     = add_query_arg(
				array(
					'order_id' => $order->get_id(),
					'key'      => $order->get_order_key(),
					'state'    => $state,
				),
				WC()->api_request_url( self::CALLBACK_SLUG )
			);

			try {
				$payload = $this->sale_payload( $order, $card, $account, $transaction_id, $order_reference, $callback_url );
			} catch ( InvalidArgumentException $exception ) {
				unset( $card );
				wc_add_notice( $exception->getMessage(), 'error' );
				return array( 'result' => 'failure' );
			}

			// Optional merchant policy may restrict an attempt, never bypass bank validation.
			$allowed = apply_filters( 'y2kwebs_bac_can_start_payment', true, $order );
			if ( true !== $allowed ) {
				unset( $payload, $card );
				wc_add_notice( is_wp_error( $allowed ) ? esc_html( $allowed->get_error_message() ) : __( 'No se puede iniciar este pago. Contacte con la tienda.', 'y2kwebs-bac' ), 'error' );
				return array( 'result' => 'failure' );
			}

			$order->update_meta_data( self::META_PREFIX . 'state_hash', wp_hash_password( $state ) );
			$order->update_meta_data( self::META_PREFIX . 'transaction_id', $transaction_id );
			$order->update_meta_data( self::META_PREFIX . 'order_reference', $order_reference );
			$order->update_meta_data( self::META_PREFIX . 'currency_code', $account['currency_code'] );
			$order->update_meta_data( self::META_PREFIX . 'started_at', time() );
			$order->update_meta_data( self::META_PREFIX . 'status', 'starting' );
			$order->save();

			$response = $this->api_client->sale( $payload, $account );
			unset( $payload );
			unset( $card );

			if ( is_wp_error( $response ) ) {
				$this->record_start_failure(
					$order,
					$response->get_error_code(),
					array( 'ResponseMessage' => $response->get_error_message() ),
					$account
				);
				return array( 'result' => 'failure' );
			}

			$initial_response_error = Y2KWEBS_BAC_Diagnostics::initial_response_error(
				$response,
				array(
					'transaction_id'  => $transaction_id,
					'order_reference' => $order_reference,
					'currency_code'   => $account['currency_code'],
					'total_amount'    => $order->get_total(),
				)
			);
			if ( '' !== $initial_response_error ) {
				$this->record_start_failure( $order, $initial_response_error, $response, $account );
				return array( 'result' => 'failure' );
			}

			$order->update_meta_data( self::META_PREFIX . 'redirect_data', (string) $response['RedirectData'] );
			$order->update_meta_data( self::META_PREFIX . 'status', 'authentication_pending' );
			$order->add_order_note( __( 'PowerTranz inició la autenticación 3DS (SP4).', 'y2kwebs-bac' ) );
			$order->save();

			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true ),
			);
		} finally {
			$this->release_order_lock( $order_id );
		}
	}

	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || $this->id !== $order->get_payment_method() || 'authentication_pending' !== $order->get_meta( self::META_PREFIX . 'status', true ) || time() - (int) $order->get_meta( self::META_PREFIX . 'started_at', true ) > 300 ) {
			return;
		}

		$redirect_data = (string) $order->get_meta( self::META_PREFIX . 'redirect_data', true );
		if ( '' === $redirect_data ) {
			echo '<p>' . esc_html__( 'No pudimos cargar el proceso de pago. Por favor, vuelva al pedido e inténtelo de nuevo.', 'y2kwebs-bac' ) . '</p>';
			return;
		}

		$message_type = 'y2kwebs-bac-result-' . $order->get_id();
		?>
		<div class="y2kwebs-bac-authentication">
			<p><?php esc_html_e( 'Por favor, espere mientras procesamos su pago.', 'y2kwebs-bac' ); ?></p>
			<iframe
				id="y2kwebs-bac-frame"
				title="<?php echo esc_attr__( 'Procesamiento seguro del pago', 'y2kwebs-bac' ); ?>"
				<?php // Preserve existing HTML entities through both the attribute parser and srcdoc parser. ?>
				srcdoc="<?php echo esc_attr( str_replace( '&', '&amp;', $redirect_data ) ); ?>"
			></iframe>
		</div>
		<script>
		(function () {
			'use strict';
			var frame = document.getElementById('y2kwebs-bac-frame');
			window.addEventListener('message', function (event) {
				if (!frame || event.source !== frame.contentWindow || !event.data || event.data.type !== <?php echo wp_json_encode( $message_type ); ?>) {
					return;
				}
				try {
					var destination = new URL(event.data.url, window.location.origin);
					if (destination.origin === window.location.origin) {
						window.location.replace(destination.href);
					}
				} catch (error) {
					return;
				}
			});
		}());
		</script>
		<?php
	}

	public function handle_callback() {
		nocache_headers();
		header( 'Referrer-Policy: no-referrer' );
		if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
			status_header( 405 );
			exit;
		}
		// The bank cannot provide a browser nonce. The processor validates the order key,
		// a hashed one-time state, the attempt, and the bank's financial response instead.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Authenticated bank callback; see process_callback().
		$result = $this->process_callback( wp_unslash( $_GET ), wp_unslash( $_POST ) );
		if ( is_wp_error( $result ) ) {
			do_action( 'y2kwebs_bac_callback_rejected', $result );
			status_header( (int) $result->get_error_data() );
			exit;
		}
		$this->output_callback_redirect( $result['order'], $result['url'] );
	}

	/**
	 * Process unslashed bank input. No caller-provided values may select an amount or account.
	 *
	 * @param array $query Callback URL query.
	 * @param array $post Bank POST body.
	 * @return array|WP_Error Local redirect or rejection; never contains a bank token.
	 */
	public function process_callback( array $query, array $post ) {
		$order_id = isset( $query['order_id'] ) && is_scalar( $query['order_id'] ) ? absint( $query['order_id'] ) : 0;
		$key      = self::exact_input( $query, 'key' );
		$state    = self::exact_input( $query, 'state' );
		if ( ! $order_id || ! $this->acquire_order_lock( $order_id ) ) {
			return new WP_Error( 'y2kwebs_bac_busy', 'Callback unavailable.', 409 );
		}
		try {
			$order = wc_get_order( $order_id );
			if ( ! $order || $this->id !== $order->get_payment_method() || '' === $key || ! hash_equals( $order->get_order_key(), $key ) ) {
				return new WP_Error( 'y2kwebs_bac_callback', 'Invalid callback.', 400 );
			}
			$state_hash = (string) $order->get_meta( self::META_PREFIX . 'state_hash', true );
			if ( '' === $state || '' === $state_hash || ! wp_check_password( $state, $state_hash ) ) {
				return new WP_Error( 'y2kwebs_bac_callback', 'Invalid callback.', 400 );
			}
			$status = $order->get_meta( self::META_PREFIX . 'status', true );
			if ( $order->is_paid() || 'completed' === $status ) {
				return array( 'order' => $order, 'url' => $this->get_return_url( $order ) );
			}
			if ( in_array( $status, array( 'payment_sending', 'unknown' ), true ) ) {
				return array( 'order' => $order, 'url' => $this->get_return_url( $order ) );
			}
			if ( 'authentication_pending' !== $status ) {
				return new WP_Error( 'y2kwebs_bac_callback', 'Invalid attempt.', 400 );
			}
			$json = isset( $post['Response'] ) && is_string( $post['Response'] ) ? $post['Response'] : '';
			$response = strlen( $json ) <= 65536 ? json_decode( $json, true ) : null;
			$spi_token = self::exact_input( $post, 'SpiToken' );
			if ( is_array( $response ) && '' === $spi_token ) {
				$spi_token = self::exact_input( $response, 'SpiToken' );
			}
			if ( ! is_array( $response ) || '' === $spi_token || ! $this->valid_callback_response( $response, $order ) ) {
				return new WP_Error( 'y2kwebs_bac_callback', 'Invalid bank data.', 400 );
			}
			$authentication_status = isset( $response['RiskManagement']['ThreeDSecure']['AuthenticationStatus'] ) && is_string( $response['RiskManagement']['ThreeDSecure']['AuthenticationStatus'] )
				? strtoupper( $response['RiskManagement']['ThreeDSecure']['AuthenticationStatus'] ) : '';
			if ( ! $this->authentication_can_complete( $authentication_status ) ) {
				$this->fail_authenticated_order( $order, $this->authentication_failure_reason( $authentication_status, $response ), $response );
				return array( 'order' => $order, 'url' => $this->failed_return_url( $order ) );
			}
			$started_at = (int) $order->get_meta( self::META_PREFIX . 'started_at', true );
			if ( $started_at <= 0 || time() - $started_at > 300 ) {
				$this->fail_authenticated_order( $order, 'spi_token_expired', $response );
				return array( 'order' => $order, 'url' => $this->failed_return_url( $order ) );
			}
			// Persist before the financial call. A process crash must never make the token reusable.
			$order->update_meta_data( self::META_PREFIX . 'status', 'payment_sending' );
			$order->save();
			$payment = $this->api_client->payment( $spi_token );
			unset( $spi_token, $json, $post );
			if ( is_wp_error( $payment ) || ! $this->valid_payment_response( $payment, $order ) ) {
				$definitive_decline = is_array( $payment ) && $this->valid_callback_response( $payment, $order )
					&& isset( $payment['Approved'], $payment['IsoResponseCode'] ) && false === $payment['Approved']
					&& is_string( $payment['IsoResponseCode'] ) && preg_match( '/^[0-9]{2}$/', $payment['IsoResponseCode'] ) && '00' !== $payment['IsoResponseCode'];
				if ( $definitive_decline ) {
					$this->fail_authenticated_order( $order, 'payment_not_approved', $payment );
					return array( 'order' => $order, 'url' => $this->failed_return_url( $order ) );
				}
				$this->hold_uncertain_order( $order );
				return array( 'order' => $order, 'url' => $this->get_return_url( $order ) );
			}
			$transaction_id = (string) $payment['TransactionIdentifier'];
			$order->update_meta_data( self::META_PREFIX . 'status', 'completed' );
			$order->update_meta_data( self::META_PREFIX . 'authentication_status', $authentication_status );
			$order->update_meta_data( self::META_PREFIX . 'iso_response_code', '00' );
			foreach ( array( 'AuthorizationCode' => 'authorization_code', 'RRN' => 'rrn' ) as $field => $meta ) {
				if ( isset( $payment[ $field ] ) && is_scalar( $payment[ $field ] ) ) {
					$order->update_meta_data( self::META_PREFIX . $meta, sanitize_text_field( (string) $payment[ $field ] ) );
				}
			}
			$order->delete_meta_data( self::META_PREFIX . 'redirect_data' );
			$order->delete_meta_data( self::META_PREFIX . 'state_hash' );
			$order->save();
			$order->payment_complete( $transaction_id );
			/* translators: 1: 3DS authentication status, 2: bank transaction identifier. */
			$order->add_order_note( sprintf( __( 'Pago PowerTranz aprobado. 3DS: %1$s; ISO: 00; transacción: %2$s.', 'y2kwebs-bac' ), $authentication_status, $transaction_id ) );
			return array( 'order' => $order, 'url' => $this->get_return_url( $order ) );
		} finally {
			$this->release_order_lock( $order_id );
		}
	}

	/** Reject altered tokens instead of sanitizing them into a different value. */
	private static function exact_input( array $input, $key ) {
		if ( ! isset( $input[ $key ] ) || ! is_string( $input[ $key ] ) ) {
			return '';
		}
		$value = $input[ $key ];
		return strlen( $value ) <= 16384 && preg_match( '/^[A-Za-z0-9_+.=\/-]+$/D', $value ) ? $value : '';
	}

	private function hold_uncertain_order( WC_Order $order ) {
		$order->update_meta_data( self::META_PREFIX . 'status', 'unknown' );
		$order->delete_meta_data( self::META_PREFIX . 'redirect_data' );
		$order->save();
		$order->update_status( 'on-hold', __( 'No se pudo confirmar el resultado bancario. Revise PowerTranz antes de aceptar otro pago. No se ha repetido el cobro.', 'y2kwebs-bac' ) );
	}

	public function maybe_add_result_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice flag; never changes an order or payment.
		if ( ! is_checkout() || ! isset( $_GET['y2kwebs_bac_result'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice flag.
		$result = sanitize_key( wp_unslash( $_GET['y2kwebs_bac_result'] ) );
		if ( 'failed' === $result ) {
			wc_add_notice( __( 'No pudimos completar el pago. Por favor, inténtelo de nuevo.', 'y2kwebs-bac' ), 'error' );
		}
	}

	private function api_root() {
		if ( defined( 'Y2KWEBS_BAC_API_ROOT' ) && Y2KWEBS_BAC_API_ROOT ) {
			return (string) Y2KWEBS_BAC_API_ROOT;
		}

		$environment_root = getenv( 'Y2KWEBS_BAC_API_ROOT' );
		if ( false !== $environment_root && '' !== trim( (string) $environment_root ) ) {
			return trim( (string) $environment_root );
		}

		$custom_root = trim( (string) $this->get_option( 'api_root', '' ) );
		if ( '' !== $custom_root ) {
			return $custom_root;
		}

		return 'production' === $this->get_option( 'environment', 'staging' )
			? 'https://gateway.ptranz.com/api/spi'
			: 'https://staging.ptranz.com/api/spi';
	}

	/**
	 * @return array<string, string>
	 */
	private function validated_card_data() {
		$holder = $this->posted_value( 'y2kwebs_bac_cardholder' );
		$pan    = preg_replace( '/\D+/', '', $this->posted_value( 'y2kwebs_bac_card_pan' ) );
		$expiry = $this->posted_value( 'y2kwebs_bac_card_expiry' );
		$cvv    = preg_replace( '/\D+/', '', $this->posted_value( 'y2kwebs_bac_card_cvv' ) );

		if ( strlen( $holder ) < 2 || strlen( $holder ) > self::CARDHOLDER_NAME_MAX_LENGTH ) {
			throw new Exception( esc_html__( 'Ingrese el nombre del titular de la tarjeta.', 'y2kwebs-bac' ) );
		}

		if ( strlen( $pan ) < 12 || strlen( $pan ) > 19 || ! $this->passes_luhn( $pan ) ) {
			throw new Exception( esc_html__( 'El número de tarjeta no es válido.', 'y2kwebs-bac' ) );
		}

		if ( ! preg_match( '/^(0[1-9]|1[0-2])\/(\d{2}|\d{4})$/', $expiry, $matches ) ) {
			throw new Exception( esc_html__( 'La fecha de vencimiento no es válida.', 'y2kwebs-bac' ) );
		}

		$month = (int) $matches[1];
		$year  = (int) $matches[2];
		$year  = $year < 100 ? 2000 + $year : $year;
		if ( $year < (int) gmdate( 'Y' ) || ( $year === (int) gmdate( 'Y' ) && $month < (int) gmdate( 'n' ) ) ) {
			throw new Exception( esc_html__( 'La tarjeta está vencida.', 'y2kwebs-bac' ) );
		}

		if ( ! preg_match( '/^\d{3,4}$/', $cvv ) ) {
			throw new Exception( esc_html__( 'El código de seguridad no es válido.', 'y2kwebs-bac' ) );
		}

		return array(
			'holder'     => $holder,
			'pan'        => $pan,
			'cvv'        => $cvv,
			'expiration' => substr( (string) $year, -2 ) . sprintf( '%02d', $month ),
		);
	}

	private function posted_value( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce validates checkout/pay-order or Store API session CSRF before calling the gateway.
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	private function passes_luhn( $pan ) {
		$sum    = 0;
		$parity = strlen( $pan ) % 2;
		foreach ( str_split( $pan ) as $index => $digit ) {
			$value = (int) $digit;
			if ( $index % 2 === $parity ) {
				$value *= 2;
				$value = $value > 9 ? $value - 9 : $value;
			}
			$sum += $value;
		}
		return 0 === $sum % 10;
	}

	private function sale_payload( WC_Order $order, array $card, array $account, $transaction_id, $order_reference, $callback_url ) {
		$email        = sanitize_email( $order->get_billing_email() );
		$phone        = preg_replace( '/\D+/', '', (string) $order->get_billing_phone() );
		$country_code = Y2KWEBS_BAC_Country_Codes::numeric( $order->get_billing_country() );

		if ( ! is_email( $email ) || strlen( $email ) > self::EMAIL_MAX_LENGTH || strlen( $phone ) < 7 || strlen( $phone ) > self::PHONE_MAX_LENGTH ) {
			throw new InvalidArgumentException( esc_html__( 'Revise el correo y el teléfono de facturación.', 'y2kwebs-bac' ) );
		}

		if ( null === $country_code ) {
			throw new InvalidArgumentException( esc_html__( 'Seleccione un país de facturación válido.', 'y2kwebs-bac' ) );
		}

		return array(
			'TransactionIdentifier' => $transaction_id,
			'TotalAmount'           => wc_format_decimal( $order->get_total(), 2 ),
			'CurrencyCode'          => $account['currency_code'],
			'ThreeDSecure'          => true,
			'Source'                => array(
				'CardPan'        => $card['pan'],
				'CardCvv'        => $card['cvv'],
				'CardExpiration' => $card['expiration'],
				'CardholderName' => $this->powertranz_text( $card['holder'], self::CARDHOLDER_NAME_MAX_LENGTH ),
			),
			'OrderIdentifier'       => $order_reference,
			'BillingAddress'        => array(
				'FirstName'    => $this->powertranz_text( $order->get_billing_first_name(), self::FIRST_NAME_MAX_LENGTH ),
				'LastName'     => $this->powertranz_text( $order->get_billing_last_name(), self::LAST_NAME_MAX_LENGTH ),
				'Line1'        => $this->powertranz_text( $order->get_billing_address_1() ?: 'NO ADDRESS', self::ADDRESS_LINE_1_MAX_LENGTH ),
				'Line2'        => $this->powertranz_text( $order->get_billing_address_2(), self::ADDRESS_LINE_2_MAX_LENGTH ),
				'City'         => $this->powertranz_text( $order->get_billing_city(), self::CITY_MAX_LENGTH ),
				'CountryCode'  => $country_code,
				'EmailAddress' => $email,
				'PhoneNumber'  => $phone,
			),
			'AddressMatch'          => false,
			'ExtendedData'          => array(
				'ThreeDSecure'       => array(
					'ChallengeWindowSize' => 4,
					'ChallengeIndicator'  => '01',
				),
				'MerchantResponseUrl' => esc_url_raw( $callback_url ),
			),
		);
	}

	private function powertranz_text( $value, $maximum_length ) {
		$value = remove_accents( sanitize_text_field( (string) $value ) );
		$value = preg_replace( '/[^A-Za-z0-9 .-]/', '', $value );
		$value = trim( preg_replace( '/\s+/', ' ', $value ) );

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $maximum_length ) : substr( $value, 0, $maximum_length );
	}

	private function valid_callback_response( array $response, WC_Order $order ) {
		return $this->response_matches_order(
			$response,
			$order,
			(string) $order->get_meta( self::META_PREFIX . 'transaction_id', true ),
			(string) $order->get_meta( self::META_PREFIX . 'order_reference', true ),
			(string) $order->get_meta( self::META_PREFIX . 'currency_code', true )
		);
	}

	private function valid_payment_response( $response, WC_Order $order ) {
		return is_array( $response )
			&& isset( $response['Approved'] ) && true === $response['Approved']
			&& isset( $response['IsoResponseCode'] )
			&& '00' === (string) $response['IsoResponseCode']
			&& $this->valid_callback_response( $response, $order );
	}

	private function response_matches_order( array $response, WC_Order $order, $transaction_id, $order_reference, $currency_code ) {
		if ( ! isset( $response['TransactionIdentifier'], $response['OrderIdentifier'], $response['TotalAmount'], $response['CurrencyCode'] ) || count( array_filter( array( $response['TransactionIdentifier'], $response['OrderIdentifier'], $response['TotalAmount'], $response['CurrencyCode'] ), 'is_scalar' ) ) !== 4 ) {
			return false;
		}

		$expected_amount = wc_format_decimal( $order->get_total(), 2 );
		$actual_amount   = wc_format_decimal( $response['TotalAmount'], 2 );

		return hash_equals( (string) $transaction_id, (string) $response['TransactionIdentifier'] )
			&& hash_equals( (string) $order_reference, (string) $response['OrderIdentifier'] )
			&& hash_equals( (string) $currency_code, (string) $response['CurrencyCode'] )
			&& hash_equals( (string) $expected_amount, (string) $actual_amount );
	}

	private function authentication_can_complete( $status ) {
		return Y2KWEBS_BAC_Authentication_Policy::can_complete( $status, 'yes' === $this->get_option( 'allow_auth_u', 'no' ) );
	}

	private function authentication_failure_reason( $status, array $response ) {
		$iso_response_code = isset( $response['IsoResponseCode'] ) && is_scalar( $response['IsoResponseCode'] )
			? strtoupper( trim( (string) $response['IsoResponseCode'] ) )
			: '';

		return '' === (string) $status && '' !== $iso_response_code && '3D0' !== $iso_response_code
			? 'three_ds_not_completed'
			: 'authentication_status_not_allowed';
	}

	private function record_start_failure( WC_Order $order, $reason, array $response, array $account ) {
		$context = Y2KWEBS_BAC_Diagnostics::failure_context(
			$order,
			$reason,
			$response,
			$account,
			$this->get_option( 'environment', 'staging' )
		);

		$order->update_meta_data( self::META_PREFIX . 'status', 'start_failed' );
		$order->delete_meta_data( self::META_PREFIX . 'state_hash' );
		$order->add_order_note( Y2KWEBS_BAC_Diagnostics::order_note( $context ) );
		$order->save();

		if ( 'yes' === $this->get_option( 'debug_logging', 'no' ) ) {
			Y2KWEBS_BAC_Diagnostics::write_log( $context );
		}

		wc_add_notice( __( 'No pudimos iniciar el pago. Por favor, inténtelo de nuevo.', 'y2kwebs-bac' ), 'error' );
	}

	private function fail_authenticated_order( WC_Order $order, $reason, array $response ) {
		$account = $this->currency_config->account_for( $order->get_currency() );
		$context = Y2KWEBS_BAC_Diagnostics::failure_context(
			$order,
			$reason,
			$response,
			is_array( $account ) ? $account : array(),
			$this->get_option( 'environment', 'staging' )
		);

		$order->update_meta_data( self::META_PREFIX . 'status', 'failed' );
		$meta_fields = array(
			'authentication_status'        => 'authentication_status',
			'iso_response_code'            => 'iso_response_code',
			'gateway_response_code'        => 'gateway_response_code',
			'response_message'             => 'response_message',
			'three_ds_response_code'       => 'three_ds_response_code',
			'eci'                          => 'eci',
			'protocol_version'             => 'protocol_version',
			'fingerprint_indicator'        => 'fingerprint_indicator',
			'authentication_status_reason' => 'authentication_status_reason',
			'transaction_status_reason'    => 'transaction_status_reason',
			'cardholder_info'              => 'cardholder_info',
		);
		foreach ( $meta_fields as $meta_key => $context_key ) {
			if ( '' !== (string) $context[ $context_key ] ) {
				$order->update_meta_data( self::META_PREFIX . $meta_key, $context[ $context_key ] );
			}
		}
		$order->delete_meta_data( self::META_PREFIX . 'redirect_data' );
		$order->delete_meta_data( self::META_PREFIX . 'state_hash' );
		$order->add_order_note( Y2KWEBS_BAC_Diagnostics::authentication_order_note( $context ) );
		$order->update_status( 'failed' );
		$order->save();

		if ( 'yes' === $this->get_option( 'debug_logging', 'no' ) ) {
			Y2KWEBS_BAC_Diagnostics::write_authentication_log( $context );
		}
	}

	private function failed_return_url( WC_Order $order ) {
		return add_query_arg( 'y2kwebs_bac_result', 'failed', $order->get_checkout_payment_url( true ) );
	}

	/** Database connection locks are released by the server if the PHP process exits. */
	private static $held_locks = array();

	private function acquire_order_lock( $order_id ) {
		global $wpdb;
		$key = 'y2kwebs_bac_' . md5( DB_NAME . ':' . $wpdb->prefix . ':' . absint( $order_id ) );
		if ( isset( self::$held_locks[ $key ] ) ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Connection-scoped atomic lock cannot be cached.
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $key ) );
		if ( '1' !== (string) $locked ) {
			return false;
		}
		self::$held_locks[ $key ] = true;
		return true;
	}

	private function release_order_lock( $order_id ) {
		global $wpdb;
		$key = 'y2kwebs_bac_' . md5( DB_NAME . ':' . $wpdb->prefix . ':' . absint( $order_id ) );
		if ( isset( self::$held_locks[ $key ] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Release the connection-scoped lock.
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $key ) );
			unset( self::$held_locks[ $key ] );
		}
	}

	private function output_callback_redirect( WC_Order $order, $url ) {
		$message_type = 'y2kwebs-bac-result-' . $order->get_id();
		$url          = wp_validate_redirect( $url, wc_get_checkout_url() );

		nocache_headers();
		header( "Content-Security-Policy: default-src 'none'; script-src 'unsafe-inline'; frame-ancestors 'self'" );
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		?>
		<!doctype html>
		<html><head><meta charset="utf-8"><title><?php esc_html_e( 'Resultado del pago', 'y2kwebs-bac' ); ?></title></head>
		<body>
		<script>
		window.parent.postMessage({type: <?php echo wp_json_encode( $message_type ); ?>, url: <?php echo wp_json_encode( $url ); ?>}, window.location.origin);
		</script>
		<noscript><a href="<?php echo esc_url( $url ); ?>" target="_top"><?php esc_html_e( 'Continuar', 'y2kwebs-bac' ); ?></a></noscript>
		</body></html>
		<?php
		exit;
	}
}
