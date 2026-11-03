<?php

defined( 'ABSPATH' ) || exit;

/**
 * Resolves ISO currency data and the matching PowerTranz account.
 */
final class Y2KWEBS_BAC_Currency_Config {
	/** @var array<string, mixed> */
	private $settings;

	/**
	 * @param array<string, mixed> $settings Gateway settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @return array<string, string>|null
	 */
	public function account_for( $currency ) {
		$currency = strtoupper( (string) $currency );
		$codes    = self::supported_currencies();

		if ( ! isset( $codes[ $currency ] ) ) {
			return null;
		}

		$prefix   = strtolower( $currency );
		$id       = $this->secret( 'Y2KWEBS_BAC_' . $currency . '_ID', $prefix . '_id' );
		$password = $this->secret( 'Y2KWEBS_BAC_' . $currency . '_PASSWORD', $prefix . '_password' );

		if ( '' === $id || '' === $password ) {
			return null;
		}

		return array(
			'currency'      => $currency,
			'currency_code' => $codes[ $currency ],
			'id'            => $id,
			'password'      => $password,
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function supported_currencies() {
		return array(
			'CRC' => '188',
			'USD' => '840',
		);
	}

	public static function numeric_code( $currency ) {
		$codes    = self::supported_currencies();
		$currency = strtoupper( (string) $currency );

		return isset( $codes[ $currency ] ) ? $codes[ $currency ] : null;
	}

	private function secret( $constant_name, $setting_key ) {
		if ( defined( $constant_name ) ) {
			return trim( (string) constant( $constant_name ) );
		}

		$environment_value = getenv( $constant_name );
		if ( false !== $environment_value && '' !== trim( (string) $environment_value ) ) {
			return trim( (string) $environment_value );
		}

		if ( in_array( $setting_key, array( 'crc_password', 'usd_password' ), true ) ) {
			return trim( Y2KWEBS_BAC_Secrets::get( $setting_key ) );
		}
		return isset( $this->settings[ $setting_key ] ) ? trim( (string) $this->settings[ $setting_key ] ) : '';
	}
}
