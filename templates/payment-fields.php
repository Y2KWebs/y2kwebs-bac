<?php

defined( 'ABSPATH' ) || exit;
?>
<fieldset class="y2kwebs-bac-fields" id="y2kwebs-bac-fields">
	<p class="form-row form-row-wide">
		<label for="y2kwebs-bac-cardholder">
			<?php esc_html_e( 'Cardholder name', 'y2kwebs-bac' ); ?> <span class="required">*</span>
		</label>
		<input id="y2kwebs-bac-cardholder" name="y2kwebs_bac_cardholder" type="text" autocomplete="cc-name" maxlength="45" />
	</p>
	<p class="form-row form-row-wide">
		<label for="y2kwebs-bac-card-pan">
			<?php esc_html_e( 'Card number', 'y2kwebs-bac' ); ?> <span class="required">*</span>
		</label>
		<input id="y2kwebs-bac-card-pan" name="y2kwebs_bac_card_pan" type="text" inputmode="numeric" autocomplete="cc-number" maxlength="23" placeholder="•••• •••• •••• ••••" />
	</p>
	<p class="form-row form-row-first">
		<label for="y2kwebs-bac-card-expiry">
			<?php esc_html_e( 'Expiry date', 'y2kwebs-bac' ); ?> <span class="required">*</span>
		</label>
		<input id="y2kwebs-bac-card-expiry" name="y2kwebs_bac_card_expiry" type="text" inputmode="numeric" autocomplete="cc-exp" maxlength="5" placeholder="<?php esc_attr_e( 'MM/YY', 'y2kwebs-bac' ); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="y2kwebs-bac-card-cvv">
			<?php esc_html_e( 'Security code', 'y2kwebs-bac' ); ?> <span class="required">*</span>
		</label>
		<input id="y2kwebs-bac-card-cvv" name="y2kwebs_bac_card_cvv" type="password" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="CVV" />
	</p>
	<div class="clear"></div>
	<p class="y2kwebs-bac-privacy"><?php esc_html_e( 'Card data is transmitted to PowerTranz for this payment and is not stored in WordPress.', 'y2kwebs-bac' ); ?></p>
</fieldset>
