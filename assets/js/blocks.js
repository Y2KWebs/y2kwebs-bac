(function () {
	'use strict';

	var settings = window.wc.wcSettings.getSetting('y2kwebs_bac_data', {});
	var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
	var element = window.wp.element;
	var decodeEntities = window.wp.htmlEntities.decodeEntities;
	var el = element.createElement;
	var useEffect = element.useEffect;
	var useState = element.useState;

	function digits(value) {
		return String(value || '').replace(/\D/g, '');
	}

	function passesLuhn(value) {
		var pan = digits(value);
		var sum = 0;
		var parity = pan.length % 2;
		for (var index = 0; index < pan.length; index += 1) {
			var digit = Number(pan.charAt(index));
			if (index % 2 === parity) {
				digit *= 2;
				if (digit > 9) {
					digit -= 9;
				}
			}
			sum += digit;
		}
		return pan.length >= 12 && pan.length <= 19 && sum % 10 === 0;
	}

	function Content(props) {
		var holderState = useState('');
		var panState = useState('');
		var expiryState = useState('');
		var cvvState = useState('');
		var holder = holderState[0];
		var setHolder = holderState[1];
		var pan = panState[0];
		var setPan = panState[1];
		var expiry = expiryState[0];
		var setExpiry = expiryState[1];
		var cvv = cvvState[0];
		var setCvv = cvvState[1];
		var responseTypes = props.emitResponse.responseTypes;

		useEffect(function () {
			return props.eventRegistration.onPaymentSetup(function () {
				if (holder.trim().length < 2 || !passesLuhn(pan) || !/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry) || !/^\d{3,4}$/.test(cvv)) {
					return {
						type: responseTypes.ERROR,
						message: settings.validationMessage || 'Revise los datos de la tarjeta.'
					};
				}

				return {
					type: responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							y2kwebs_bac_cardholder: holder.trim(),
							y2kwebs_bac_card_pan: digits(pan),
							y2kwebs_bac_card_expiry: expiry,
							y2kwebs_bac_card_cvv: digits(cvv)
						}
					}
				};
			});
		}, [holder, pan, expiry, cvv, props.eventRegistration, responseTypes.ERROR, responseTypes.SUCCESS]);

		return el('div', { className: 'y2kwebs-bac-block-fields' },
			el('p', null, decodeEntities(settings.description || '')),
			el('label', null, settings.cardholderLabel || 'Nombre del titular',
				el('input', { type: 'text', autoComplete: 'cc-name', maxLength: 45, value: holder, onChange: function (event) { setHolder(event.target.value); } })
			),
			el('label', null, settings.panLabel || 'Número de tarjeta',
				el('input', { type: 'text', inputMode: 'numeric', autoComplete: 'cc-number', maxLength: 23, value: pan, onChange: function (event) { setPan(digits(event.target.value).slice(0, 19).replace(/(.{4})/g, '$1 ').trim()); } })
			),
			el('div', { className: 'y2kwebs-bac-block-row' },
				el('label', null, settings.expiryLabel || 'Vencimiento',
					el('input', { type: 'text', inputMode: 'numeric', autoComplete: 'cc-exp', maxLength: 5, placeholder: 'MM/AA', value: expiry, onChange: function (event) { var value = digits(event.target.value).slice(0, 4); setExpiry(value.length > 2 ? value.slice(0, 2) + '/' + value.slice(2) : value); } })
				),
				el('label', null, settings.cvvLabel || 'Código de seguridad',
					el('input', { type: 'password', inputMode: 'numeric', autoComplete: 'cc-csc', maxLength: 4, value: cvv, onChange: function (event) { setCvv(digits(event.target.value).slice(0, 4)); } })
				)
			),
			el('small', null, settings.privacyMessage || '')
		);
	}

	function Label() {
		return decodeEntities(settings.title || 'Tarjeta de crédito o débito');
	}

	registerPaymentMethod({
		name: 'y2kwebs_bac',
		label: el(Label),
		content: el(Content),
		edit: el('div', null, decodeEntities(settings.description || '')),
		canMakePayment: function (data) {
			var currency = data && data.cartTotals ? data.cartTotals.currency_code : '';
			return !currency || (settings.supportedCurrencies || []).indexOf(currency) !== -1;
		},
		ariaLabel: decodeEntities(settings.title || 'Tarjeta de crédito o débito'),
		supports: { features: settings.supports || ['products'] }
	});
}());
