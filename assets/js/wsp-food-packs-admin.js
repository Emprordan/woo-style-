/**
 * Woo Style+ Food Packs Admin JS (Media Uploader, Shortcode Copy, 1-Click Page Creator)
 */
(function ($) {
	'use strict';

	$(function () {
		var mediaFrame;

		// Pack Image Uploader
		$(document).on('click', '#wsp-fp-upload-image', function (e) {
			e.preventDefault();

			if (mediaFrame) {
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media({
				title: 'Select Food Pack Image',
				button: { text: 'Use this image' },
				multiple: false,
				library: { type: 'image' }
			});

			mediaFrame.on('select', function () {
				var attachment = mediaFrame.state().get('selection').first().toJSON();
				var imgUrl = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;

				$('#wsp_fp_image_id').val(attachment.id);
				$('#wsp-fp-image-preview').html(
					'<img src="' + imgUrl + '" alt="" style="max-width:100%;max-height:220px;border-radius:8px;display:block;" />'
				);
				$('#wsp-fp-upload-image').text('Change Image');
				$('#wsp-fp-remove-image').show();
			});

			mediaFrame.open();
		});

		// Remove Pack Image
		$(document).on('click', '#wsp-fp-remove-image', function (e) {
			e.preventDefault();
			$('#wsp_fp_image_id').val('');
			$('#wsp-fp-image-preview').html(
				'<span style="color:#64748b;font-size:13px;text-align:center;padding:16px;">No pack image selected yet</span>'
			);
			$('#wsp-fp-upload-image').text('Select Image');
			$(this).hide();
		});

		// Copy Shortcode
		$(document).on('click', '#wsp-fp-copy-shortcode', function () {
			var $btn = $(this);
			var shortcode = $btn.data('shortcode') || '[wsp_food_packs]';
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(shortcode).then(function () {
					var orig = $btn.text();
					$btn.text('Copied!');
					setTimeout(function () {
						$btn.text(orig);
					}, 1800);
				});
			}
		});

		// 1-Click Create Standalone Ordering Page
		$(document).on('click', '#wsp-fp-create-page', function () {
			var $btn = $(this);
			var nonce = $btn.data('nonce');
			$btn.prop('disabled', true).text('Creating...');

			$.post(ajaxurl, {
				action: 'wsp_fp_create_order_page',
				nonce: nonce
			})
				.done(function (res) {
					if (res && res.success && res.data && res.data.url) {
						$btn.replaceWith(
							'<a href="' + res.data.url + '" target="_blank" class="button button-primary" style="background:#16a34a;border-color:#15803d;">View Ordering Page</a>'
						);
					} else {
						$btn.prop('disabled', false).text('Create Standalone Ordering Page');
					}
				})
				.fail(function () {
					$btn.prop('disabled', false).text('Create Standalone Ordering Page');
				});
		});
	});
})(jQuery);
