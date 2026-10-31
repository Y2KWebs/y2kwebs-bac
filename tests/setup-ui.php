<?php
/** Create disposable local fixtures only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'Y2KWEBS_BAC_LOCAL_TEST' ) ) {
	exit( 'Local tests only.' );
}
update_option( 'woocommerce_currency', 'USD' );
update_option( 'woocommerce_default_country', 'CR' );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option( 'woocommerce_checkout_phone_field', 'required' );
update_option( 'woocommerce_y2kwebs_bac_settings', array(
	'enabled' => 'yes', 'environment' => 'staging', 'availability_mode' => 'everyone',
	'title' => 'Tarjeta de crédito o débito — BAC', 'description' => 'Demostración local con banco simulado.',
	'crc_id' => 'crc-test-id', 'crc_password' => 'crc-test-password',
	'usd_id' => 'usd-test-id', 'usd_password' => 'usd-test-password',
) );
if ( ! get_page_by_path( 'demo-product', OBJECT, 'product' ) ) {
	$product = new WC_Product_Simple();
	$product->set_name( 'Demo product' );
	$product->set_slug( 'demo-product' );
	$product->set_status( 'publish' );
	$product->set_regular_price( '1.00' );
	$product->set_virtual( true );
	$product->save();
}
$checkout_markup = new ReflectionMethod( WC_Install::class, 'get_checkout_block_content' );
$checkout_markup->setAccessible( true );
foreach ( array( 'cart' => '[woocommerce_cart]', 'checkout' => '[woocommerce_checkout]', 'checkout-blocks' => $checkout_markup->invoke( null ) ) as $slug => $content ) {
	$page = get_page_by_path( $slug );
	$id = wp_insert_post( array( 'ID' => $page ? $page->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => ucwords( str_replace( '-', ' ', $slug ) ), 'post_name' => $slug, 'post_content' => $content ) );
	if ( 'checkout-blocks' !== $slug ) {
		update_option( 'woocommerce_' . $slug . '_page_id', $id );
	}
}
echo "LOCAL_UI_FIXTURES_READY\n";
