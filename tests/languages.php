<?php
/** Local integration checks: language packs, checkout fields and merchant overrides. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'Y2KWEBS_BAC_LOCAL_TEST' ) ) { exit( 'Local tests only.' ); }
$checks = 0;
$assert = static function ( $ok, $message ) use ( &$checks ) { if ( ! $ok ) { throw new RuntimeException( $message ); } ++$checks; };
$old = get_option( 'woocommerce_y2kwebs_bac_settings' );
$locale = 'en_US';
$locale_filter = static function () use ( &$locale ) { return $locale; };
add_filter( 'pre_determine_locale', $locale_filter );
add_filter( 'locale', $locale_filter );
try {
    foreach ( array( 'es_ES', 'es_CR', 'es_MX', 'es_AR', 'en_US', 'en_GB' ) as $locale ) {
        $spanish = 0 === strpos( $locale, 'es_' );
        update_option( 'woocommerce_y2kwebs_bac_settings', array() );
        $gateway = new Y2KWEBS_BAC_Gateway();
        $assert( $gateway->get_title() === ( $spanish ? 'Tarjeta de crédito o débito' : 'Credit or debit card' ), $locale . ': default title' );
        $assert( __( 'Check the card details.', 'y2kwebs-bac' ) === ( $spanish ? 'Revise los datos de la tarjeta.' : 'Check the card details.' ), $locale . ': validation' );
        $assert( __( 'Please wait while we process your payment.', 'y2kwebs-bac' ) === ( $spanish ? 'Por favor, espere mientras procesamos su pago.' : 'Please wait while we process your payment.' ), $locale . ': 3DS message' );
        ob_start(); include Y2KWEBS_BAC_PATH . 'templates/payment-fields.php'; $html = ob_get_clean();
        $assert( false !== strpos( $html, $spanish ? 'Nombre del titular' : 'Cardholder name' ), $locale . ': classic checkout' );
        $assert( false !== strpos( $html, $spanish ? 'MM/AA' : 'MM/YY' ), $locale . ': classic expiry' );
        $blocks = new Y2KWEBS_BAC_Blocks_Support(); $blocks->initialize(); $data = $blocks->get_payment_method_data();
        $assert( $data['cardholderLabel'] === ( $spanish ? 'Nombre del titular' : 'Cardholder name' ), $locale . ': Blocks label' );
        $assert( $data['expiryPlaceholder'] === ( $spanish ? 'MM/AA' : 'MM/YY' ), $locale . ': Blocks expiry' );
        update_option( 'woocommerce_y2kwebs_bac_settings', array( 'title' => 'Mi tarjeta BAC', 'description' => 'Texto personalizado del comercio.' ) );
        $gateway = new Y2KWEBS_BAC_Gateway();
        $assert( 'Mi tarjeta BAC' === $gateway->get_title() && 'Texto personalizado del comercio.' === $gateway->get_description(), $locale . ': preserve merchant copy' );
    }
    // A regional translation returned by WordPress takes priority; missing entries use Spanish.
    $locale = 'es_CR';
    $regional = static function ( $translation, $text ) { return 'Cardholder name' === $text ? 'Nombre regional del titular' : $translation; };
    add_filter( 'gettext_y2kwebs-bac', $regional, 5, 2 );
    $assert( 'Nombre regional del titular' === __( 'Cardholder name', 'y2kwebs-bac' ), 'Official regional translation wins' );
    $assert( 'Número de tarjeta' === __( 'Card number', 'y2kwebs-bac' ), 'Partial regional catalogue falls back per string' );
    remove_filter( 'gettext_y2kwebs-bac', $regional, 5 );
    // Read an actual partial regional MO from the same path used by WordPress packs.
    $regional_file = WP_LANG_DIR . '/plugins/y2kwebs-bac-es_XX.mo';
    $assert( ! file_exists( $regional_file ), 'Synthetic regional catalogue must not replace an existing file' );
    wp_mkdir_p( dirname( $regional_file ) );
    $mo = new MO(); $mo->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
    $mo->add_entry( new Translation_Entry( array( 'singular' => 'Cardholder name', 'translations' => array( 'Titular del catálogo regional' ) ) ) );
    $assert( $mo->export_to_file( $regional_file ), 'Create synthetic regional pack' );
    try {
        $locale = 'es_XX';
        $assert( 'Titular del catálogo regional' === __( 'Cardholder name', 'y2kwebs-bac' ), 'Installed regional MO takes precedence' );
        $assert( 'Número de tarjeta' === __( 'Card number', 'y2kwebs-bac' ), 'Missing regional MO entry uses bundled Spanish' );
    } finally { unlink( $regional_file ); }
    // Admin preference and store locale can differ on a checkout AJAX request.
    remove_filter( 'locale', $locale_filter );
    $store = static function () { return 'es_MX'; }; add_filter( 'locale', $store );
    $locale = 'en_US';
    $assert( 'Cardholder name' === __( 'Cardholder name', 'y2kwebs-bac' ), 'Admin uses selected user locale' );
    if ( ! defined( 'WC_DOING_AJAX' ) ) { define( 'WC_DOING_AJAX', true ); }
    $assert( 'Nombre del titular' === __( 'Cardholder name', 'y2kwebs-bac' ), 'Checkout AJAX uses store locale' );
    remove_filter( 'locale', $store );
    remove_filter( 'pre_determine_locale', $locale_filter );
    $assert( switch_to_locale( 'es_ES' ), 'Actual WordPress locale switch succeeds' );
    $assert( 'Nombre del titular' === __( 'Cardholder name', 'y2kwebs-bac' ), 'Locale switch selects Spanish' );
    try {
        $assert( switch_to_locale( 'en_US' ), 'Nested WordPress locale switch succeeds' );
        $assert( 'Cardholder name' === __( 'Cardholder name', 'y2kwebs-bac' ), 'Nested locale selects English' );
        $assert( restore_previous_locale(), 'Restore previous WordPress locale' );
        $assert( 'Nombre del titular' === __( 'Cardholder name', 'y2kwebs-bac' ), 'Restored locale selects Spanish' );
    } finally { restore_current_locale(); }
    echo 'LANGUAGE_ASSERTIONS=' . $checks . PHP_EOL;
} finally {
    remove_filter( 'locale', $locale_filter ); remove_filter( 'pre_determine_locale', $locale_filter );
    update_option( 'woocommerce_y2kwebs_bac_settings', $old );
}
