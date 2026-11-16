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
			'title'               => $this->gateway ? $this->gateway->get_title() : __( 'Credit or debit card', 'y2kwebs-bac' ),
			'description'         => $this->gateway ? $this->gateway->get_description() : __( 'Secure payment processed by PowerTranz.', 'y2kwebs-bac' ),
			'supports'            => $this->gateway ? array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ) : array( 'products' ),
			'supportedCurrencies' => array_keys( Y2KWEBS_BAC_Currency_Config::supported_currencies() ),
			'cardholderLabel'     => __( 'Cardholder name', 'y2kwebs-bac' ),
			'panLabel'            => __( 'Card number', 'y2kwebs-bac' ),
			'expiryPlaceholder'   => __( 'MM/YY', 'y2kwebs-bac' ),
			'expiryLabel'         => __( 'Expiry date', 'y2kwebs-bac' ),
			'cvvLabel'            => __( 'Security code', 'y2kwebs-bac' ),
			'validationMessage'   => __( 'Check the card details.', 'y2kwebs-bac' ),
			'privacyMessage'      => __( 'Card data is transmitted to PowerTranz for this payment and is not stored in WordPress.', 'y2kwebs-bac' ),
		);
	}
}
