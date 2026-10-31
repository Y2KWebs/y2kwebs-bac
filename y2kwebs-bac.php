<?php
/**
 * Plugin Name: Y2KWebs Gateway for BAC
 * Description: Pagos de contado mediante PowerTranz SPI 3DS para cuentas CRC y USD.
 * Version: 1.0.0
 * Plugin URI: https://github.com/Y2KWebs/y2kwebs-bac
 * Author: Y2KWebs
 * Author URI: https://y2kwebs.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * WC requires at least: 9.0
 * WC tested up to: 11.2
 * Text Domain: y2kwebs-bac
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'Y2KWEBS_BAC_VERSION', '1.0.0' );
define( 'Y2KWEBS_BAC_FILE', __FILE__ );
define( 'Y2KWEBS_BAC_PATH', plugin_dir_path( __FILE__ ) );
define( 'Y2KWEBS_BAC_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		require_once Y2KWEBS_BAC_PATH . 'includes/class-powertranz-currency-config.php';
		require_once Y2KWEBS_BAC_PATH . 'includes/class-powertranz-country-codes.php';
		require_once Y2KWEBS_BAC_PATH . 'includes/class-powertranz-authentication-policy.php';
		require_once Y2KWEBS_BAC_PATH . 'includes/class-powertranz-api-client.php';
		require_once Y2KWEBS_BAC_PATH . 'includes/class-powertranz-diagnostics.php';
		require_once Y2KWEBS_BAC_PATH . 'includes/class-powertranz-gateway.php';

		// Register independently of checkout: a bank POST has no cart or customer session.
		add_action( 'woocommerce_api_y2kwebs_bac_callback', static function () {
			$gateways = WC()->payment_gateways()->payment_gateways();
			$gateways['y2kwebs_bac']->handle_callback();
		} );

		add_filter(
			'woocommerce_payment_gateways',
			static function ( $gateways ) {
				$gateways[] = 'Y2KWEBS_BAC_Gateway';
				return $gateways;
			}
		);
	}
);

add_action(
	'woocommerce_blocks_loaded',
	static function () {
		if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
			return;
		}

		require_once Y2KWEBS_BAC_PATH . 'blocks/class-y2kwebs-bac-blocks-support.php';

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry ) {
				$registry->register( new Y2KWEBS_BAC_Blocks_Support() );
			}
		);
	}
);
