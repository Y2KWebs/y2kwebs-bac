<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Y2KWEBS_BAC_Blocks_Support extends AbstractPaymentMethodType {
	/** @var Y2KWEBS_BAC_Gateway|null */
	private $gateway;

	protected $name = 'y2kwebs_bac';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_y2kwebs_bac_settings', array() );
		$gateways       = WC()->payment_gateways()->payment_gateways();
		$this->gateway  = isset( $gateways[ $this->name ] ) ? $gateways[ $this->name ] : null;
	}

	public function is_active() {
		return $this->gateway && $this->gateway->is_available();
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-y2kwebs-bac-blocks',
			Y2KWEBS_BAC_URL . 'assets/js/blocks.js',
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ),
			Y2KWEBS_BAC_VERSION,
			true
		);

		wp_register_style(
			'wc-y2kwebs-bac-blocks',
			Y2KWEBS_BAC_URL . 'assets/css/checkout.css',
			array(),
			Y2KWEBS_BAC_VERSION
		);
		wp_enqueue_style( 'wc-y2kwebs-bac-blocks' );

		return array( 'wc-y2kwebs-bac-blocks' );
	}

	public function get_payment_method_data() {
		return array(
			'title'               => $this->get_setting( 'title' ),
			'description'         => $this->get_setting( 'description' ),
			'supports'            => $this->gateway ? array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ) : array( 'products' ),
			'supportedCurrencies' => array_keys( Y2KWEBS_BAC_Currency_Config::supported_currencies() ),
			'cardholderLabel'     => __( 'Nombre del titular', 'y2kwebs-bac' ),
			'panLabel'            => __( 'Número de tarjeta', 'y2kwebs-bac' ),
			'expiryLabel'         => __( 'Vencimiento', 'y2kwebs-bac' ),
			'cvvLabel'            => __( 'Código de seguridad', 'y2kwebs-bac' ),
			'validationMessage'   => __( 'Revise los datos de la tarjeta.', 'y2kwebs-bac' ),
			'privacyMessage'      => __( 'Los datos de la tarjeta se transmiten a PowerTranz para este pago y no se guardan en WordPress.', 'y2kwebs-bac' ),
		);
	}
}
