=== Y2KWebs Gateway for BAC ===
Contributors: y2kwebs
Tags: woocommerce, payments, bac, powertranz, costa rica
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept card payments with BAC Costa Rica through PowerTranz SPI 3DS. Supports CRC, USD, classic checkout, Blocks and HPOS.

== Description ==

Y2KWebs Gateway for BAC connects WooCommerce to the PowerTranz/FAC SPI payment service used by compatible BAC Costa Rica merchant accounts.

The plugin is free and open source. You need your own merchant agreement and compatible PowerTranz SPI credentials. Bank fees and payment-service conditions are separate. No Y2KWebs account, subscription or paid support is required. Optional installation and professional support are available from [Y2KWebs](https://y2kwebs.com/).

The gateway interface, checkout messages and user documentation are currently in Spanish. This English directory readme is provided for WordPress.org review. An English interface and official language packs are not included in this release.

This is an independent integration. It is not an official plugin from BAC, PowerTranz or WooCommerce and does not imply their endorsement. All trademarks belong to their respective owners.

Features:

* Separate CRC and USD merchant accounts, selected using the order currency.
* Classic checkout and Checkout Blocks.
* HPOS-compatible order access.
* 3DS authentication and financial-response checks before confirming an order.
* Protection against concurrent or repeated payment requests.
* Uncertain financial results held for manual bank reconciliation.
* Optional diagnostics with sensitive fields excluded.
* Credentials entered in WooCommerce with encrypted, write-only password storage. Editing wp-config.php is optional.

Only one-time sales are supported. Automatic refunds, recurring payments, stored cards, installments and currency conversion are not included. Compatibility with other countries or every BAC payment product is not claimed.

Requirements: WooCommerce 9.0 or later, HTTPS, PHP OpenSSL with AES-256-GCM and MySQL/MariaDB with GET_LOCK support, in addition to the WordPress/PHP versions listed above. Live Visa and Mastercard approvals have been verified in both CRC and USD. Automated tests use a simulated bank; provider staging validation remains pending. This does not establish support for every card brand or merchant account.

= External service and privacy =

PowerTranz/FAC is required to authenticate and process payments. When a customer submits card details, the plugin transmits the cardholder name, card number, expiry and CVV, billing name and address, country, email, phone, order reference, transaction identifier, amount, currency and merchant callback URL to the configured PowerTranz endpoint. The initial request also includes merchant credentials. The bank/issuer may load its own 3DS challenge in an iframe and collect device or authentication information.

Staging endpoint: https://staging.ptranz.com/api/spi
Production endpoint: https://gateway.ptranz.com/api/spi
Provider documentation: https://developer.powertranz.com/ (access may require provider credentials).
Provider website terms: https://powertranz.com/terms-of-use
Provider privacy policy: https://powertranz.com/privacy-policy
BAC merchant information: https://ayuda.baccredomatic.com/para_empresas_o_negocios/comercios_afiliados/e-commerce
BAC Costa Rica e-commerce terms: https://www.baccredomatic.com/sites/default/files/2024-07/cr_carga_de_terminos_condiciones_e-commerce.PDF

Website terms do not replace the merchant's payment-service agreement. Confirm applicable conditions and card-data compliance obligations with BAC/PowerTranz.

PAN, CVV and SPI tokens are not persisted by this plugin. SPI direct transmits card data through the merchant server in memory; this is not hosted tokenization. WooCommerce retains its normal order and billing records. The plugin retains non-sensitive transaction references, status and redacted diagnostics; temporary 3DS redirect HTML and a hashed callback state are used during authentication. Merchant credentials saved in WooCommerce remain in its database; server constants or environment variables are recommended. There is no analytics or telemetry sent to Y2KWebs.

== Installation ==

1. Install and activate WooCommerce. Use HTTPS.
2. Upload the plugin ZIP through Plugins > Add Plugin > Upload Plugin and activate it.
3. Open WooCommerce > Settings > Payments > BAC — Y2KWebs.
4. Leave the gateway in staging with administrator-only visibility while testing.
5. Enter compatible CRC/USD staging credentials, or configure the documented server constants/environment variables.
6. Enable the gateway and test the currencies enabled by your bank.
7. Configure your own production credentials and open customer visibility only when ready.

== Frequently Asked Questions ==

= What language does the plugin use? =

The gateway settings, checkout fields, payment messages and user guides are in Spanish. The directory readme is in English as required by WordPress.org. No translation setup is needed to use the Spanish interface. WooCommerce and WordPress keep their own language settings. Official directory translations and an English gateway interface are deferred.

= Does this work with any BAC merchant account? =

No. It requires a BAC Costa Rica account provisioned for the compatible PowerTranz/FAC SPI service. Ask the bank which integration and currencies your account supports.

= Do I have to edit wp-config.php? =

No. Enter the CRC/USD merchant identifiers and passwords in WooCommerce > Settings > Payments > BAC — Y2KWebs. Passwords are encrypted using AES-256-GCM in a separate non-autoloaded option. Forms and WooCommerce API responses never return them. Blank password fields keep saved values; explicit deletion removes both passwords and disables the gateway. Changing WordPress authentication salts requires entering the passwords again. Constants and environment variables remain optional alternatives for managed installations.

= Which authentication results are allowed? =

Y and A. U is disabled by default and requires an explicit setting. N, R and unknown results cannot proceed to financial completion. A valid authentication result alone does not complete the WooCommerce order: the final bank approval must also match the order.

= What happens after a timeout? =

If financial completion was sent but its outcome cannot be established, the order is placed on hold and further financial requests for that attempt are blocked. Reconcile it with the bank before accepting another payment. There is no automatic financial retry or bank-status query in this version.

= Can I refund a payment from WooCommerce? =

Automatic bank refunds are not implemented. Refund through the bank and record the refund manually in WooCommerce. Changing an order status does not send money.

= Where are logs? =

Enable diagnostic logging, then open WooCommerce > Status > Logs and select y2kwebs-bac. Never publish real order exports or raw bank responses. Diagnostic fields exclude card data, passwords, SPI tokens, challenge HTML and cryptographic authentication values.

= What happens when the plugin is removed? =

Deactivation leaves order records and settings available. Uninstall deletes this gateway's settings, including database-stored credentials, and preserves order transaction history for reconciliation. No other gateway's settings are migrated or deleted.

== Screenshots ==

1. Classic checkout in Spanish, supplied by the maintainer from their demo store. Store-specific titles and styling are configurable.
2. Checkout Blocks with fictitious demo data.

== Changelog ==

= 1.0.0 =

* First public edition for BAC Costa Rica using PowerTranz/FAC SPI 3DS.
* CRC/USD accounts, classic checkout, Checkout Blocks and HPOS.
* Serialized payment attempts and manual reconciliation for uncertain financial results.

== Credits ==

Developed and maintained by Y2KWebs. Credit to JARS Costa Rica for the historical reference integration; this maintained implementation uses an independent architecture and identity.
