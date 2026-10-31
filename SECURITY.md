# Security

Report vulnerabilities privately to **info@y2kwebs.com** with the plugin version, affected behavior and reproduction steps using fictitious data. Do not send card data, passwords, bank tokens, raw production responses or customer records. Do not disclose an unresolved vulnerability through a public issue.

The maintained release line is 1.x. This plugin uses PowerTranz SPI direct, so card data passes through the merchant server in memory. It does not persist PAN, CVV or SPI tokens. Merchants must prevent request-body logging in their own infrastructure and confirm their payment-service obligations with their acquiring bank.

The bank callback uses an order key, a hashed random state and attempt/financial-response correlation. A browser nonce is not available on the bank's POST. Regular checkout and settings requests use WooCommerce's existing session, permission and CSRF controls. The callback never trusts a browser-supplied amount or currency to construct a charge.

Financial requests are serialized with a connection-scoped database lock. The attempt is marked before sending financial completion. Unknown results must be reconciled manually; clearing plugin metadata to force another charge is unsafe.
