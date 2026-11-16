(function () {
	'use strict';

	var body = document.body;
	var messageType = body && body.getAttribute('data-message-type');
	var returnUrl = body && body.getAttribute('data-return-url');
	if (!messageType || !returnUrl) {
		return;
	}
	try {
		var destination = new URL(returnUrl, window.location.origin);
		if (destination.origin === window.location.origin) {
			window.parent.postMessage({ type: messageType, url: destination.href }, window.location.origin);
		}
	} catch (error) {
		return;
	}
}());
