/* Local browser fixtures only. Never included in the plugin package. */
(function () {
	'use strict';
	var destination = 'http://localhost:8098/wp-content/plugins/y2kwebs-bac/tests/message-fixture.html#accepted';
	if (document.getElementById('result')) {
		if (new URL(window.location.href).searchParams.has('foreign')) {
			document.getElementById('y2kwebs-bac-frame').src = 'http://127.0.0.1:8098/wp-content/plugins/y2kwebs-bac/tests/message-sender.html';
		}
		document.getElementById('wrong-window').addEventListener('click', function () {
			window.postMessage({ type: 'y2kwebs-bac-result-42', url: destination }, window.location.origin);
		});
		window.addEventListener('hashchange', function () { document.getElementById('result').textContent = 'Accepted valid result'; });
	} else {
		document.addEventListener('click', function (event) {
			var kind = event.target.getAttribute('data-case');
			if (!kind) { return; }
			window.parent.postMessage({
				type: kind === 'wrong-order' ? 'y2kwebs-bac-result-99' : 'y2kwebs-bac-result-42',
				url: kind === 'external' ? 'https://example.invalid/' : kind === 'invalid' ? 'http://[' : destination
			}, '*');
		});
	}
}());
