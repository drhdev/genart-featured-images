(function ($) {
	'use strict';

	$(function () {
		var $dryRunButton = $('#genart-dry-run');
		var $startBulkButton = $('#genart-start-bulk');
		var $dryRunResults = $('#dry-run-results');
		var $bulkStatus = $('#bulk-status');
		var $cleanupButton = $('#genart-run-cleanup');
		var $cleanupStatus = $('#genart-cleanup-status');
		var $schemesTableBody = $('#genart-custom-schemes-table tbody');
		var $addSchemeButton = $('#genart-add-scheme-row');
		var $rulesTableBody = $('#genart-rules-table tbody');
		var $addRuleButton = $('#genart-add-rule-row');
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

		$addSchemeButton.on('click', function () {
			var rowIndex = Date.now();
			var rowHtml = '' +
				'<tr class="genart-custom-scheme-row">' +
				'<td>' +
				'<input type="hidden" name="genart_featured_images_settings[custom_schemes][' + rowIndex + '][id]" value="">' +
				'<input type="text" class="regular-text" name="genart_featured_images_settings[custom_schemes][' + rowIndex + '][name]" value="">' +
				'</td>' +
				'<td><input type="text" class="regular-text" name="genart_featured_images_settings[custom_schemes][' + rowIndex + '][colors]" value="" placeholder="#112233, #445566, #778899"></td>' +
				'<td><label><input type="checkbox" name="genart_featured_images_settings[custom_schemes][' + rowIndex + '][remove]" value="1"> Remove</label></td>' +
				'</tr>';

			$schemesTableBody.find('.genart-no-schemes-row').remove();
			$schemesTableBody.append(rowHtml);
		});

		function toArrayOptions(mapObj) {
			var html = '';
			Object.keys(mapObj || {}).forEach(function (key) {
				html += '<option value=\"' + key + '\">' + mapObj[key] + '</option>';
			});
			return html;
		}

		function schemeOptions() {
			var html = '';
			Object.keys(GenArtFeaturedImages.schemes || {}).forEach(function (key) {
				var s = GenArtFeaturedImages.schemes[key];
				html += '<option value=\"' + key + '\">' + (s && s.name ? s.name : key) + '</option>';
			});
			return html;
		}

		function termOptions(taxonomy) {
			var terms = (GenArtFeaturedImages.terms && GenArtFeaturedImages.terms[taxonomy]) ? GenArtFeaturedImages.terms[taxonomy] : {};
			var html = '<option value=\"\">Select term</option>';
			Object.keys(terms).forEach(function (termId) {
				html += '<option value=\"' + termId + '\">' + terms[termId] + '</option>';
			});
			return html;
		}

		function bindRuleRowEvents($row) {
			$row.find('.genart-rule-taxonomy').on('change', function () {
				var taxonomy = $(this).val();
				$row.find('.genart-rule-term').html(termOptions(taxonomy));
			});
		}

		$addRuleButton.on('click', function () {
			var rowIndex = Date.now();
			var optionName = GenArtFeaturedImages.optionName;
			var rowHtml = '' +
				'<tr class=\"genart-rule-row\">' +
				'<td>' +
				'<select name=\"' + optionName + '[rules][' + rowIndex + '][taxonomy]\" class=\"genart-rule-taxonomy\">' +
				'<option value=\"category\">Category</option>' +
				'<option value=\"post_tag\">Tag</option>' +
				'</select>' +
				'</td>' +
				'<td><select name=\"' + optionName + '[rules][' + rowIndex + '][term_id]\" class=\"genart-rule-term\">' + termOptions('category') + '</select></td>' +
				'<td><select multiple class=\"genart-scroll-select genart-rule-algos\" size=\"3\" name=\"' + optionName + '[rules][' + rowIndex + '][algos][]\">' + toArrayOptions(GenArtFeaturedImages.algorithms) + '</select></td>' +
				'<td><select multiple class=\"genart-scroll-select genart-rule-schemes\" size=\"6\" name=\"' + optionName + '[rules][' + rowIndex + '][schemes][]\">' + schemeOptions() + '</select></td>' +
				'<td><label><input type=\"checkbox\" name=\"' + optionName + '[rules][' + rowIndex + '][remove]\" value=\"1\"> Remove</label></td>' +
				'</tr>';

			$rulesTableBody.find('.genart-no-rules-row').remove();
			var $row = $(rowHtml);
			$rulesTableBody.append($row);
			bindRuleRowEvents($row);
		});

		$rulesTableBody.find('.genart-rule-row').each(function () {
			bindRuleRowEvents($(this));
		});

		$cleanupButton.on('click', function () {
			var confirmed = window.confirm(GenArtFeaturedImages.i18n.cleanupConfirm || 'This action is permanent. Continue?');
			if (!confirmed) {
				return;
			}

			$cleanupButton.prop('disabled', true);
			$cleanupStatus.text(GenArtFeaturedImages.i18n.cleanupRunning).css('color', '');

			$.post(GenArtFeaturedImages.ajaxUrl, getAjaxPayload(GenArtFeaturedImages.cleanupAction))
				.done(function (response) {
					if (!response || !response.success || !response.data) {
						$cleanupStatus.text(GenArtFeaturedImages.i18n.requestFailed).css('color', '#b32d2e');
						return;
					}
					$cleanupStatus.text(response.data.message || GenArtFeaturedImages.i18n.cleanupDone).css('color', '#008a20');
				})
				.fail(function (xhr) {
					var message = GenArtFeaturedImages.i18n.requestFailed;
					if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						message = xhr.responseJSON.data.message;
					}
					$cleanupStatus.text(message).css('color', '#b32d2e');
				})
				.always(function () {
					$cleanupButton.prop('disabled', false);
				});
		});
	});
})(jQuery);
