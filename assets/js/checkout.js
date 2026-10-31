(function () {
	'use strict';

	function digits(value) {
		return value.replace(/\D/g, '');
	}

	document.addEventListener('input', function (event) {
		if (event.target.id === 'y2kwebs-bac-card-pan') {
			event.target.value = digits(event.target.value).slice(0, 19).replace(/(.{4})/g, '$1 ').trim();
		}

		if (event.target.id === 'y2kwebs-bac-card-expiry') {
			var expiry = digits(event.target.value).slice(0, 4);
			event.target.value = expiry.length > 2 ? expiry.slice(0, 2) + '/' + expiry.slice(2) : expiry;
		}

		if (event.target.id === 'y2kwebs-bac-card-cvv') {
			event.target.value = digits(event.target.value).slice(0, 4);
		}
	});
}());
