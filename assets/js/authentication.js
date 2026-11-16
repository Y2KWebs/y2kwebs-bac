(function () {
	'use strict';

	window.addEventListener('message', function (event) {
		var frame = document.getElementById('y2kwebs-bac-frame');
		if (!frame || event.source !== frame.contentWindow || event.origin !== window.location.origin ||
			!event.data || typeof event.data !== 'object' ||
			event.data.type !== frame.getAttribute('data-message-type') || typeof event.data.url !== 'string') {
			return;
		}
		try {
			var destination = new URL(event.data.url, window.location.origin);
			if (destination.origin === window.location.origin) {
				window.location.replace(destination.href);
			}
		} catch (error) {
			return;
		}
	});
}());
