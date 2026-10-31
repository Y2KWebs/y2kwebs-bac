<?php

defined( 'ABSPATH' ) || exit;

/**
 * Small HTTP client for the PowerTranz SPI endpoints.
 */
final class Y2KWEBS_BAC_API_Client {
	/** @var string */
	private $api_root;

	public function __construct( $api_root ) {
		$this->api_root = untrailingslashit( esc_url_raw( (string) $api_root ) );
	}

	/**
	 * Starts a SPI Sale request.
	 *
	 * @param array<string, mixed> $payload Request body.
	 * @param array<string, string> $account PowerTranz credentials.
	 * @return array<string, mixed>|WP_Error
	 */
	public function sale( array $payload, array $account ) {
		return $this->post(
			'sale',
			$payload,
			array(
				'PowerTranz-PowerTranzId'       => $account['id'],
				'PowerTranz-PowerTranzPassword' => $account['password'],
			),
			true
		);
	}

	/**
	 * Completes a SPI payment. PowerTranz specifies that this request has no
	 * merchant credential headers and its JSON body is the quoted SPI token.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function payment( $spi_token ) {
		return $this->post( 'payment', (string) $spi_token );
	}

	/**
	 * @param mixed                $payload JSON serializable value.
	 * @param array<string,string> $headers Extra headers.
	 * @return array<string, mixed>|WP_Error
	 */
	private function post( $endpoint, $payload, array $headers = array(), $fixed_decimal_amount = false ) {
		$url = $this->api_root . '/' . ltrim( (string) $endpoint, '/' );

		if ( ! wp_http_validate_url( $url ) || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return new WP_Error( 'y2kwebs_bac_invalid_url', __( 'La URL de PowerTranz debe ser HTTPS y válida.', 'y2kwebs-bac' ) );
		}

		$body = $fixed_decimal_amount ? $this->encode_sale_payload( $payload ) : wp_json_encode( $payload );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( false === $body ) {
			return new WP_Error( 'y2kwebs_bac_invalid_payload', __( 'No fue posible preparar la solicitud para PowerTranz.', 'y2kwebs-bac' ) );
		}

		$response = wp_safe_remote_post(
			$url,
			array(
				'timeout'     => 30,
				'redirection' => 0,
				'sslverify'   => true,
				'headers'     => array_merge(
					array(
						'Accept'       => 'application/json',
						'Content-Type' => 'application/json',
					),
					$headers
				),
				'body'        => $body,
				'data_format' => 'body',
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'y2kwebs_bac_transport_error', __( 'No fue posible comunicarse con PowerTranz.', 'y2kwebs-bac' ) );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 || ! is_array( $body ) ) {
			return new WP_Error( 'y2kwebs_bac_invalid_response', __( 'PowerTranz devolvió una respuesta no válida.', 'y2kwebs-bac' ) );
		}

		return $body;
	}

	/**
	 * Encodes TotalAmount as an exact JSON number with two decimal places.
	 *
	 * PHP floats can turn values such as 210.43 into 210.43000000000001 when
	 * JSON is encoded with a high serialize_precision. PowerTranz validates the
	 * lexical amount, so keep WooCommerce's fixed decimal representation and
	 * remove only the JSON quotes around this validated top-level field.
	 *
	 * @param mixed $payload Sale request payload.
	 * @return string|WP_Error
	 */
	private function encode_sale_payload( $payload ) {
		if ( ! is_array( $payload ) || ! isset( $payload['TotalAmount'] ) || ! is_string( $payload['TotalAmount'] ) ) {
			return new WP_Error( 'y2kwebs_bac_invalid_amount', __( 'El importe del pedido no tiene un formato válido.', 'y2kwebs-bac' ) );
		}

		$amount = $payload['TotalAmount'];
		if ( ! preg_match( '/^(?:0|[1-9][0-9]{0,15})\.[0-9]{2}$/', $amount ) ) {
			return new WP_Error( 'y2kwebs_bac_invalid_amount', __( 'El importe del pedido no tiene un formato válido.', 'y2kwebs-bac' ) );
		}

		list( $whole, $cents ) = explode( '.', $amount, 2 );
		$maximum              = '1000000000000000';
		if ( strlen( $whole ) > strlen( $maximum ) || ( strlen( $whole ) === strlen( $maximum ) && strcmp( $whole, $maximum ) > 0 ) || ( $maximum === $whole && '00' !== $cents ) ) {
			return new WP_Error( 'y2kwebs_bac_invalid_amount', __( 'El importe del pedido está fuera del rango permitido.', 'y2kwebs-bac' ) );
		}

		$encoded = wp_json_encode( $payload );
		if ( false === $encoded ) {
			return new WP_Error( 'y2kwebs_bac_invalid_payload', __( 'No fue posible preparar la solicitud para PowerTranz.', 'y2kwebs-bac' ) );
		}

		$quoted_field = '"TotalAmount":' . wp_json_encode( $amount );
		if ( 1 !== substr_count( $encoded, $quoted_field ) ) {
			return new WP_Error( 'y2kwebs_bac_invalid_amount', __( 'El importe del pedido no tiene un formato válido.', 'y2kwebs-bac' ) );
		}

		return str_replace( $quoted_field, '"TotalAmount":' . $amount, $encoded );
	}
}
