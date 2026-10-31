<?php

defined( 'ABSPATH' ) || exit;

/**
 * Explicit policy for deciding whether a 3DS result can proceed to /payment.
 */
final class Y2KWEBS_BAC_Authentication_Policy {
	public static function can_complete( $authentication_status, $allow_unavailable = false ) {
		$status  = strtoupper( trim( (string) $authentication_status ) );
		$allowed = array( 'Y', 'A' );

		if ( $allow_unavailable ) {
			$allowed[] = 'U';
		}

		return in_array( $status, $allowed, true );
	}
}
