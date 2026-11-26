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

		// Save Ordering Page Heading, Subtitle & Accent Color
		$(document).on('submit', '#wsp-fp-settings-form', function (e) {
			e.preventDefault();
			var $form = $(this);
			var $btn = $('#wsp-fp-save-settings-btn');
			var $status = $('#wsp-fp-settings-status');

			$btn.prop('disabled', true).text('Saving...');
			$status.hide();

			$.post(ajaxurl, {
				action: 'wsp_fp_save_settings',
				nonce: $form.find('input[name="nonce"]').val(),
				hero_title: $('#wsp_fp_hero_title').val(),
				hero_subtitle: $('#wsp_fp_hero_subtitle').val(),
				accent_color: $('#wsp_fp_accent_color').val(),
				christmas_enabled: $('#wsp_fp_christmas').is(':checked') ? 'yes' : 'no',
				hero_notice_text: $('#wsp_fp_hero_notice_text').val(),
				hero_notice_bg: $('#wsp_fp_hero_notice_bg').val(),
				hero_notice_marquee: $('#wsp_fp_hero_notice_marquee').is(':checked') ? 'yes' : 'no'
			})
				.done(function (res) {
					$btn.prop('disabled', false).text('Save Page Text');
					if (res && res.success) {
						$status.css('color', '#16a34a').text('Saved!').fadeIn(150);
						setTimeout(function () {
							$status.fadeOut(300);
						}, 2200);
					} else {
						var msg = (res && res.data && res.data.message) ? res.data.message : 'Error saving';
						$status.css('color', '#dc2626').text(msg).show();
					}
				})
				.fail(function () {
					$btn.prop('disabled', false).text('Save Page Text');
					$status.css('color', '#dc2626').text('Network error').show();
				});
		});
	});
})(jQuery);
