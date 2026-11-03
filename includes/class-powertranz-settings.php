<?php

defined( 'ABSPATH' ) || exit;

/** Shared protection for WooCommerce's native form and authenticated settings API. */
final class Y2KWEBS_BAC_Settings {
	const OPTION = 'woocommerce_y2kwebs_bac_settings';
	private static $error = false;

	public static function boot() {
		add_filter( 'pre_update_option_' . self::OPTION, array( __CLASS__, 'protect' ), 10, 2 );
		add_filter( 'woocommerce_rest_prepare_payment_gateway', array( __CLASS__, 'redact_response' ), 10, 3 );
		self::migrate();
	}

	/** Migrate only this plugin's earlier settings; never import another gateway. */
	public static function migrate() {
		$settings = get_option( self::OPTION, array() );
		if ( is_array( $settings ) && ( ! empty( $settings['crc_password'] ) || ! empty( $settings['usd_password'] ) ) ) {
			update_option( self::OPTION, $settings, false );
		}
	}

	public static function protect( $new, $old = array() ) {
		if ( ! is_array( $new ) ) {
			return $old;
		}
		self::$error = false;
		$incoming = array();
		foreach ( array( 'crc_password', 'usd_password' ) as $key ) {
			$value = isset( $new[ $key ] ) ? $new[ $key ] : '';
			if ( ! Y2KWEBS_BAC_Secrets::valid( $value ) ) {
				self::$error = true;
				return $old;
			}
			if ( '' !== $value ) {
				$incoming[ $key ] = $value;
			}
			$new[ $key ] = '';
		}
		if ( 'yes' === ( $new['clear_secrets'] ?? '' ) ) {
			delete_option( Y2KWEBS_BAC_Secrets::OPTION );
			$new['enabled'] = 'no';
		} elseif ( $incoming ) {
			try {
				$existing = Y2KWEBS_BAC_Secrets::read();
				$sealed = Y2KWEBS_BAC_Secrets::seal( array_merge( $existing, $incoming ) );
				update_option( Y2KWEBS_BAC_Secrets::OPTION, $sealed, false );
				if ( get_option( Y2KWEBS_BAC_Secrets::OPTION, '' ) !== $sealed ) {
					throw new RuntimeException( 'Protected credentials could not be saved.' );
				}
			} catch ( Throwable $error ) {
				self::$error = true;
				return $old;
			}
		}
		$new['clear_secrets'] = 'no';
		return $new;
	}

	public static function has_error() {
		return self::$error;
	}

	/** WooCommerce may retain the submitted values on its gateway object after a PUT. */
	public static function redact_response( $response, $gateway, $request ) {
		$data = $response->get_data();
		if ( 'y2kwebs_bac' !== ( $data['id'] ?? '' ) ) {
			return $response;
		}
		if ( self::$error && in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$response->set_status( 400 );
			$response->set_data( array( 'code' => 'y2kwebs_bac_credential_save_failed', 'message' => __( 'No se pudieron guardar las contraseñas cifradas. Los ajustes anteriores se conservan.', 'y2kwebs-bac' ) ) );
			return $response;
		}
		$saved = get_option( self::OPTION, array() );
		$data['enabled'] = 'yes' === ( $saved['enabled'] ?? 'no' );
		foreach ( array( 'crc_password', 'usd_password' ) as $key ) {
			if ( isset( $data['settings'][ $key ] ) ) {
				$data['settings'][ $key ]['value'] = '';
				$data['settings'][ $key ]['default'] = '';
			}
		}
		$response->set_data( $data );
		return $response;
	}
}
