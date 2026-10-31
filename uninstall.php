<?php
/** Remove settings only; preserve order transaction history for accounting. */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
delete_option( 'woocommerce_y2kwebs_bac_settings' );
