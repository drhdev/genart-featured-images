(function ($) {
	'use strict';

	$(function () {
		var $dryRunButton = $('#genart-dry-run');
		var $startBulkButton = $('#genart-start-bulk');
		var $dryRunResults = $('#dry-run-results');
		var $bulkStatus = $('#bulk-status');
		var isProcessing = false;

		function setStatus(message, isError) {
			if (isError) {
				$bulkStatus.html('<span style="color:#b32d2e;">' + message + '</span>');
				return;
			}
			$bulkStatus.text(message);
		}

		function getAjaxPayload(action) {
			return {
				action: action,
				_ajax_nonce: GenArtFeaturedImages.nonce
			};
		}

		function handleError(xhr) {
			var message = GenArtFeaturedImages.i18n.requestFailed;
			if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
				message = xhr.responseJSON.data.message;
			}
			setStatus(message, true);
		}

		function processStep() {
			if (!isProcessing) {
				return;
			}

			setStatus(GenArtFeaturedImages.i18n.processing, false);

			$.post(GenArtFeaturedImages.ajaxUrl, getAjaxPayload(GenArtFeaturedImages.bulkAction))
				.done(function (response) {
					if (!response || !response.success || !response.data) {
						setStatus(GenArtFeaturedImages.i18n.requestFailed, true);
						isProcessing = false;
						$startBulkButton.prop('disabled', false);
						return;
					}

					setStatus(response.data.message || '', false);

					if (Array.isArray(response.data.errors) && response.data.errors.length > 0) {
						$dryRunResults.append('<p><em>' + response.data.errors.join(' | ') + '</em></p>');
					}

					if (response.data.remaining > 0) {
						window.setTimeout(processStep, 250);
						return;
					}

					isProcessing = false;
					setStatus(GenArtFeaturedImages.i18n.completed, false);
					$startBulkButton.prop('disabled', false);
				})
				.fail(function (xhr) {
					handleError(xhr);
					isProcessing = false;
					$startBulkButton.prop('disabled', false);
				});
		}

		$dryRunButton.on('click', function () {
			$dryRunResults.text(GenArtFeaturedImages.i18n.runningDryRun);
			$startBulkButton.hide();
			setStatus('', false);

			$.post(GenArtFeaturedImages.ajaxUrl, getAjaxPayload(GenArtFeaturedImages.dryRunAction))
				.done(function (response) {
					if (!response || !response.success || !response.data || !response.data.html) {
						$dryRunResults.text(GenArtFeaturedImages.i18n.requestFailed);
						return;
					}

					$dryRunResults.html(response.data.html);
					$startBulkButton.show();
				})
				.fail(function (xhr) {
					handleError(xhr);
				});
		});

		$startBulkButton.on('click', function () {
			if (isProcessing) {
				return;
			}
			isProcessing = true;
			$startBulkButton.prop('disabled', true);
			processStep();
		});
	});
})(jQuery);
