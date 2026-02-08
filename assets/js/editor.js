(function ($) {
	'use strict';

	$(function () {
		var $status = $('#genart-editor-generate-status');

		function getCurrentStyle() {
			var value = $('#genart-post-style').val();
			return value ? String(value) : '';
		}

		function getCurrentScheme() {
			var value = $('#genart-post-scheme').val();
			return value ? String(value) : '';
		}

		function ensureButtonInFeaturedImageBox() {
			var $inside = $('#postimagediv .inside');
			if (!$inside.length || $inside.find('.genart-generate-featured-image').length) {
				return;
			}

			var postId = parseInt(GenArtFeaturedImagesEditor.postId, 10) || 0;
			var html = '<p><button type="button" class="button genart-generate-featured-image" data-post-id="' + postId + '">' +
				GenArtFeaturedImagesEditor.i18n.buttonLabel +
				'</button></p>';
			$inside.append(html);
		}

		ensureButtonInFeaturedImageBox();
		$(document).ajaxComplete(ensureButtonInFeaturedImageBox);

		$(document).on('click', '.genart-generate-featured-image', function () {
			var $button = $(this);
			var postId = parseInt($button.data('post-id'), 10) || parseInt(GenArtFeaturedImagesEditor.postId, 10);

			if (!postId) {
				$status.text(GenArtFeaturedImagesEditor.i18n.saveFirst).css('color', '#b32d2e');
				return;
			}

			$button.prop('disabled', true);
			$status.text(GenArtFeaturedImagesEditor.i18n.processing).css('color', '');

			$.post(GenArtFeaturedImagesEditor.ajaxUrl, {
				action: GenArtFeaturedImagesEditor.action,
				nonce: GenArtFeaturedImagesEditor.nonce,
				postId: postId,
				style: getCurrentStyle(),
				scheme: getCurrentScheme()
			})
				.done(function (response) {
					if (!response || !response.success || !response.data) {
						$status.text(GenArtFeaturedImagesEditor.i18n.error).css('color', '#b32d2e');
						return;
					}

					if (response.data.thumbnailHtml && $('#postimagediv .inside').length) {
						$('#postimagediv .inside').html(response.data.thumbnailHtml);
						ensureButtonInFeaturedImageBox();
					}

					if (window.wp && window.wp.data && window.wp.data.dispatch && response.data.attachmentId) {
						try {
							window.wp.data.dispatch('core/editor').editPost({ featured_media: parseInt(response.data.attachmentId, 10) });
						} catch (e) {
							// Classic editor fallback already handled above.
						}
					}

					$status.text(response.data.message || GenArtFeaturedImagesEditor.i18n.success).css('color', '#008a20');
				})
				.fail(function (xhr) {
					var msg = GenArtFeaturedImagesEditor.i18n.error;
					if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						msg = xhr.responseJSON.data.message;
					}
					$status.text(msg).css('color', '#b32d2e');
				})
				.always(function () {
					$button.prop('disabled', false);
				});
		});
	});
})(jQuery);
