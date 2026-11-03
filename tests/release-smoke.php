<?php
/** Mounted separately from the extracted release. Never run on a remote store. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'Y2KWEBS_BAC_LOCAL_TEST' ) ) { exit( 'Local tests only.' ); }
$checks = 0;
$assert = static function ( $ok, $message ) use ( &$checks ) { if ( ! $ok ) { throw new RuntimeException( $message ); } ++$checks; };
add_filter( 'pre_http_request', static function () { return new WP_Error( 'release_external_blocked', 'External HTTP blocked.' ); }, 999 );
$assert( defined( 'Y2KWEBS_BAC_VERSION' ) && '1.0.0' === Y2KWEBS_BAC_VERSION, 'Expected release version.' );
$assert( ! is_dir( Y2KWEBS_BAC_PATH . 'tests' ) && ! is_dir( Y2KWEBS_BAC_PATH . '.runtime' ), 'Extracted release contains no development files.' );
$gateways = WC()->payment_gateways()->payment_gateways();
$assert( isset( $gateways['y2kwebs_bac'] ), 'Gateway registered.' );
$gateway = $gateways['y2kwebs_bac'];
$assert( array( 'products' ) === $gateway->supports, 'Only one-time sales advertised.' );
ob_start(); $gateway->payment_fields(); $html = ob_get_clean();
$assert( 4 === substr_count( $html, '<input ' ), 'Classic checkout has all four card fields.' );
$assert( false !== strpos( $html, 'y2kwebs_bac_card_pan' ), 'Classic field identity matches server.' );
$header = get_file_data( Y2KWEBS_BAC_FILE, array( 'domain' => 'Text Domain', 'requires' => 'Requires Plugins', 'update' => 'Update URI' ) );
$assert( 'y2kwebs-bac' === $header['domain'] && 'woocommerce' === $header['requires'] && '' === $header['update'], 'WordPress.org header identity.' );
$assert( class_exists( 'Y2KWEBS_BAC_Blocks_Support' ), 'Blocks integration loaded.' );
foreach ( array( 'checkout.png', 'blocks.jpg' ) as $name ) { $assert( is_file( Y2KWEBS_BAC_PATH . 'docs/images/' . $name ), 'Reviewed screenshot exists.' ); }
echo "PASS: $checks extracted-release assertions; external HTTP blocked.\n";
