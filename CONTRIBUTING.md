# Contributing

Use issues for reproducible bugs and pull requests for changes. Use only synthetic merchant, customer and card data. Keep bank manuals, credentials, real payment logs and operational screenshots outside the repository.

Read [development](docs/development.md), run the relevant regression suites and explain the resulting customer behavior. Changes to payment serialization, callback correlation, authentication policy, amounts or uncertainty handling require regression coverage. Never replace the bank callback's correlation with a browser-only nonce or remove security checks to silence a scanner.
