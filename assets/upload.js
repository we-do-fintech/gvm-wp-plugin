/**
 * Upload helper for the gvm-wp-plugin protected download file.
 *
 * Wires up `.gvm-upload` fields (classic editor meta box): clicking the button
 * opens a file picker and uploads the file to the protected directory via AJAX.
 */
(function () {
	'use strict';

	var cfg = window.gvmUpload || {};

	function wire(container) {
		if (container.dataset.gvmWired) {
			return;
		}
		container.dataset.gvmWired = '1';

		var fileInput = container.querySelector('.gvm-upload-file');
		var btn = container.querySelector('.gvm-upload-btn');
		var nameInput = container.querySelector('.gvm-upload-filename');
		var status = container.querySelector('.gvm-upload-status');

		if (!fileInput || !btn || !nameInput) {
			return;
		}

		btn.addEventListener('click', function () {
			fileInput.click();
		});

		fileInput.addEventListener('change', function () {
			var file = fileInput.files && fileInput.files[0];
			if (!file) {
				return;
			}

			var fd = new FormData();
			fd.append('action', cfg.action);
			fd.append('nonce', cfg.nonce);
			fd.append('file', file);

			if (status) {
				status.textContent = 'Uploading…';
			}

			fetch(cfg.ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res && res.success) {
						nameInput.value = res.data.filename;
						if (status) { status.textContent = 'Uploaded'; }
					} else {
						if (status) { status.textContent = (res && res.data && res.data.message) || 'Upload failed'; }
					}
				})
				.catch(function () {
					if (status) { status.textContent = 'Upload failed'; }
				})
				.finally(function () {
					fileInput.value = '';
				});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		var fields = document.querySelectorAll('.gvm-upload');
		for (var i = 0; i < fields.length; i++) {
			wire(fields[i]);
		}
	});
})();
