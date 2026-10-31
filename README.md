# Y2KWebs Gateway for BAC

Free, open-source WooCommerce card payments for BAC Costa Rica merchants using PowerTranz/FAC SPI 3DS. Independent integration by [Y2KWebs](https://y2kwebs.com/); no endorsement by BAC, PowerTranz or WooCommerce is implied.

## Features

- Separate merchant credentials for CRC and USD; no currency conversion.
- Classic checkout, Checkout Blocks and HPOS.
- Correlated 3DS authentication and financial approval before completing an order.
- Concurrent-request protection and manual review for uncertain financial results.
- Optional redacted WooCommerce diagnostics.

The plugin is free and does not require a Y2KWebs account or subscription. Merchants need their own BAC/PowerTranz agreement and compatible SPI credentials. Bank fees are separate. Professional installation and support from Y2KWebs are optional.

## Installation

Download the installable ZIP from [Releases](https://github.com/Y2KWebs/y2kwebs-bac/releases), upload it in WordPress, activate it and open **WooCommerce > Settings > Payments > BAC — Y2KWebs**. Start in staging with administrator-only visibility. See [configuration](docs/configuration.md).

Target requirements: WordPress 6.9+, WooCommerce 9.0+, PHP 7.4+, HTTPS and a MySQL/MariaDB database supporting connection locks. Exact verified versions and outstanding bank tests are recorded in [validation](docs/validation.md). A release candidate must pass the documented checks before public release.

## Limits and payment data

This version supports one-time sales only. Automatic refunds, subscriptions, saved cards, installments, other currencies and other countries are not advertised. Refund through the bank and record it manually in WooCommerce.

SPI direct sends card data through the merchant server to PowerTranz in memory. The plugin does not persist PAN, CVV or SPI tokens. Review server, proxy and monitoring settings to prevent request-body logging. Confirm the applicable PCI DSS requirements with your acquiring bank.

Do not retry a payment marked uncertain. Reconcile it in the bank portal first; see [operation and testing](docs/pilot.md).

## Development and support

- [Local development and tests](docs/development.md)
- [Validation evidence](docs/validation.md)
- [Release and WordPress.org submission](docs/wordpress-org.md)
- [Security reporting](SECURITY.md)
- [Contributing](CONTRIBUTING.md)

License: [GPL-2.0-or-later](LICENSE). Credit to JARS Costa Rica for the historical reference integration. This maintained implementation uses its own architecture and identifiers.
