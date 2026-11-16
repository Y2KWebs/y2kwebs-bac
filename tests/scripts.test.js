'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const source = name => fs.readFileSync(path.join(__dirname, '../assets/js', name), 'utf8');

test('3DS listener installed before the frame exists accepts only its local order result', () => {
	let listener;
	let frame = null;
	const redirects = [];
	const origin = 'https://shop.example';
	const context = {
		URL,
		document: { getElementById: () => frame },
		window: { location: { origin, replace: url => redirects.push(url) }, addEventListener: (type, callback) => { assert.equal(type, 'message'); listener = callback; } }
	};
	vm.runInNewContext(source('authentication.js'), context);
	listener({});
	frame = { contentWindow: {}, getAttribute: () => 'y2kwebs-bac-result-42' };
	const valid = { source: frame.contentWindow, origin, data: { type: 'y2kwebs-bac-result-42', url: '/checkout/order-received/42/?key=demo&result=ok' } };
	for (const invalid of [
		{ ...valid, source: {} },
		{ ...valid, origin: 'https://issuer.example' },
		{ ...valid, origin: 'null' },
		{ ...valid, data: null },
		{ ...valid, data: { ...valid.data, type: 'y2kwebs-bac-result-43' } },
		{ ...valid, data: { ...valid.data, url: 'https://other.example/pay' } },
		{ ...valid, data: { ...valid.data, url: '//other.example/pay' } },
		{ ...valid, data: { ...valid.data, url: 'javascript:alert(1)' } },
		{ ...valid, data: { ...valid.data, url: 'http://[' } },
		{ ...valid, data: { ...valid.data, url: {} } }
	]) listener(invalid);
	assert.deepEqual(redirects, []);
	listener(valid);
	assert.deepEqual(redirects, [origin + valid.data.url]);
});

test('callback sends only validated result data to its own origin', () => {
	const origin = 'https://shop.example';
	for (const returnUrl of ['/checkout/order-received/42/?key=demo&result=ok', 'https://other.example', 'javascript:alert(1)', 'http://[', null]) {
		const messages = [];
		vm.runInNewContext(source('callback.js'), {
			URL,
			document: { body: { getAttribute: name => name === 'data-message-type' ? 'y2kwebs-bac-result-42' : returnUrl } },
			window: { location: { origin }, parent: { postMessage: (message, target) => messages.push(JSON.parse(JSON.stringify({ message, target }))) } }
		});
		assert.deepEqual(messages, returnUrl && returnUrl.startsWith('/') ? [{ message: { type: 'y2kwebs-bac-result-42', url: origin + returnUrl }, target: origin }] : []);
	}
});

test('Blocks renders localized settings and uses the localized validation message', () => {
	for (const locale of ['es_ES', 'es_CR', 'es_MX', 'es_AR', 'en_US', 'en_GB']) {
		const es = locale.startsWith('es_');
		const settings = {
			title: 'Mi tarjeta BAC', description: 'Descripción guardada',
			cardholderLabel: es ? 'Nombre del titular' : 'Cardholder name',
			panLabel: es ? 'Número de tarjeta' : 'Card number',
			expiryLabel: es ? 'Vencimiento' : 'Expiry date',
			expiryPlaceholder: es ? 'MM/AA' : 'MM/YY',
			cvvLabel: es ? 'Código de seguridad' : 'Security code',
			validationMessage: es ? 'Revise los datos de la tarjeta.' : 'Check the card details.'
		};
		let registered, paymentSetup;
		const element = {createElement: (type, props, ...children) => ({type, props, children}), useState: () => ['', () => {}], useEffect: callback => callback()};
		vm.runInNewContext(source('blocks.js'), {window: {
			wc: {wcSettings: {getSetting: () => settings}, wcBlocksRegistry: {registerPaymentMethod: value => {registered = value;}}},
			wp: {element, htmlEntities: {decodeEntities: text => text}}
		}});
		const tree = registered.content.type({emitResponse: {responseTypes: {ERROR: 'error', SUCCESS: 'success'}}, eventRegistration: {onPaymentSetup: callback => {paymentSetup = callback;}}});
		const rendered = JSON.stringify(tree);
		for (const value of [settings.description, settings.cardholderLabel, settings.panLabel, settings.expiryLabel, settings.expiryPlaceholder, settings.cvvLabel]) assert.ok(rendered.includes(value), locale + ': ' + value);
		assert.equal(registered.label.type(), settings.title);
		assert.equal(paymentSetup().message, settings.validationMessage);
	}
});
