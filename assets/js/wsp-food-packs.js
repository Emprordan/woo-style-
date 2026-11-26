/**
 * Woo Style+ Food Packs Frontend Ordering JS
 */
(function ($) {
	'use strict';

	$(function () {
		var $root = $('#wsp-fp-root');
		if (!$root.length || typeof wspFoodPacks === 'undefined') {
			return;
		}

		var selectedPacks = {}; // { packId: { title, price, qty } }

		function formatMoney(amount) {
			var decimals = parseInt(wspFoodPacks.decimals, 10);
			if (isNaN(decimals)) {
				decimals = 2;
			}
			var decSep = wspFoodPacks.decimalSep || '.';
			var thouSep = wspFoodPacks.thousandSep || ',';
			var symbol = wspFoodPacks.currencySymbol || '₦';
			var pos = wspFoodPacks.currencyPos || 'left';

			var fixed = Number(amount || 0).toFixed(decimals);
			var parts = fixed.split('.');
			parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thouSep);
			var numStr = parts.join(decSep);

			switch (pos) {
				case 'right':
					return numStr + symbol;
				case 'left_space':
					return symbol + ' ' + numStr;
				case 'right_space':
					return numStr + ' ' + symbol;
				case 'left':
				default:
					return symbol + numStr;
			}
		}

		function updateSummary() {
			var $summaryList = $('#wsp-fp-summary-items');
			var $grandTotal = $('#wsp-fp-grand-total');
			var $submitText = $('#wsp-fp-submit-text');
			var $floatingBar = $('#wsp-fp-floating-bar');
			var $floatingSummary = $('#wsp-fp-floating-summary');
			var $floatingTotal = $('#wsp-fp-floating-total');

			$summaryList.empty();

			var total = 0;
			var count = 0;
			var packSummaries = [];

			$.each(selectedPacks, function (packId, item) {
				if (!item || item.qty <= 0) {
					return;
				}
				count += item.qty;
				var lineTotal = item.price * item.qty;
				total += lineTotal;
				packSummaries.push(item.title + ' × ' + item.qty);

				var $row = $('<div class="wsp-fp-summary-row"></div>').attr('data-pack-id', packId);
				var $left = $('<div class="wsp-fp-summary-row-info"></div>');
				$left.append($('<div class="wsp-fp-summary-row-name"></div>').text(item.title));

				var $miniStepper = $('<div class="wsp-fp-summary-stepper"></div>');
				$miniStepper.append(
					$('<button type="button" class="wsp-fp-summary-qty-btn wsp-fp-summary-minus" aria-label="Decrease quantity">&minus;</button>'),
					$('<span class="wsp-fp-summary-qty-val"></span>').text(item.qty),
					$('<button type="button" class="wsp-fp-summary-qty-btn wsp-fp-summary-plus" aria-label="Increase quantity">+</button>')
				);
				$left.append($miniStepper);

				var $right = $('<div class="wsp-fp-summary-row-price"></div>').text(formatMoney(lineTotal));

				$row.append($left).append($right);
				$summaryList.append($row);
			});

			if (count === 0) {
				$summaryList.append(
					$('<p class="wsp-fp-summary-empty"></p>').text(wspFoodPacks.i18n.noPacksSelected)
				);
				$submitText.text(wspFoodPacks.i18n.completeOrder);
				$floatingBar.prop('hidden', true);
			} else {
				$submitText.text(wspFoodPacks.i18n.completeOrder + ' — ' + formatMoney(total));
				$floatingSummary.text(packSummaries.join(' • '));
				$floatingTotal.text(formatMoney(total));
				$floatingBar.prop('hidden', false);
			}

			$grandTotal.text(formatMoney(total));
		}

		function setCardQty($card, qty) {
			var packId = String($card.data('pack-id'));
			var title = String($card.data('pack-title'));
			var price = parseFloat($card.data('pack-price')) || 0;

			qty = Math.max(0, Math.min(999, parseInt(qty, 10) || 0));

			var $qtyWrap = $card.find('.wsp-fp-qty-wrap');
			var $qtyInput = $card.find('.wsp-fp-qty-input');

			$qtyInput.val(qty);

			if (qty > 0) {
				$card.addClass('is-selected');
				$qtyWrap.prop('hidden', false);
				selectedPacks[packId] = {
					title: title,
					price: price,
					qty: qty
				};
			} else {
				$card.removeClass('is-selected');
				$qtyWrap.prop('hidden', true);
				delete selectedPacks[packId];
			}

			updateSummary();
		}

		function showFeedback(message, type) {
			var $feedback = $('#wsp-fp-feedback');
			$feedback
				.removeClass('wsp-fp-alert--error wsp-fp-alert--success')
				.addClass(type === 'success' ? 'wsp-fp-alert--success' : 'wsp-fp-alert--error')
				.text(message)
				.prop('hidden', false);
		}

		function clearFeedback() {
			$('#wsp-fp-feedback').prop('hidden', true).text('');
		}

		// Select Pack button
		$root.on('click', '.wsp-fp-select-btn', function () {
			clearFeedback();
			var $card = $(this).closest('.wsp-fp-card');
			setCardQty($card, 1);
		});

		// Quantity + / -
		$root.on('click', '.wsp-fp-qty-plus', function () {
			clearFeedback();
			var $card = $(this).closest('.wsp-fp-card');
			var current = parseInt($card.find('.wsp-fp-qty-input').val(), 10) || 0;
			setCardQty($card, current + 1);
		});

		$root.on('click', '.wsp-fp-qty-minus', function () {
			clearFeedback();
			var $card = $(this).closest('.wsp-fp-card');
			var current = parseInt($card.find('.wsp-fp-qty-input').val(), 10) || 0;
			setCardQty($card, current - 1);
		});

		// Direct numeric typing inside pack quantity input
		$root.on('change blur', '.wsp-fp-qty-input', function () {
			clearFeedback();
			var $card = $(this).closest('.wsp-fp-card');
			var entered = parseInt($(this).val(), 10);
			if (isNaN(entered) || entered < 0) {
				entered = 0;
			}
			setCardQty($card, entered);
		});

		// Quantity + / - directly inside Order Summary
		$root.on('click', '.wsp-fp-summary-plus', function () {
			clearFeedback();
			var packId = $(this).closest('.wsp-fp-summary-row').data('pack-id');
			var $card = $root.find('.wsp-fp-card[data-pack-id="' + packId + '"]');
			if ($card.length) {
				var current = parseInt($card.find('.wsp-fp-qty-input').val(), 10) || 0;
				setCardQty($card, current + 1);
			}
		});

		$root.on('click', '.wsp-fp-summary-minus', function () {
			clearFeedback();
			var packId = $(this).closest('.wsp-fp-summary-row').data('pack-id');
			var $card = $root.find('.wsp-fp-card[data-pack-id="' + packId + '"]');
			if ($card.length) {
				var current = parseInt($card.find('.wsp-fp-qty-input').val(), 10) || 0;
				setCardQty($card, current - 1);
			}
		});

		// Modal Open & Close
		function openCheckoutModal() {
			clearFeedback();
			$('#wsp-fp-checkout-modal').prop('hidden', false);
			$('body').addClass('wsp-fp-modal-open');
		}

		function closeCheckoutModal() {
			$('#wsp-fp-checkout-modal').prop('hidden', true);
			$('body').removeClass('wsp-fp-modal-open');
		}

		$root.on('click', '#wsp-fp-open-modal-btn', function () {
			openCheckoutModal();
		});

		$root.on('click', '[data-wsp-fp-close-modal]', function () {
			closeCheckoutModal();
		});

		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' && !$('#wsp-fp-checkout-modal').prop('hidden')) {
				closeCheckoutModal();
			}
		});

		// Gateway switching
		$root.on('change', 'input[name="payment_method"]', function () {
			clearFeedback();
			var $items = $root.find('.wsp-fp-gateway-item');
			$items.removeClass('is-active').find('.wsp-fp-gateway-fields').prop('hidden', true);

			var $current = $(this).closest('.wsp-fp-gateway-item');
			$current.addClass('is-active').find('.wsp-fp-gateway-fields').prop('hidden', false);
		});

		// Form Submit -> AJAX WooCommerce Order & Payment Gateway
		$('#wsp-fp-order-form').on('submit', function (e) {
			e.preventDefault();
			clearFeedback();

			var itemsPayload = {};
			var totalQty = 0;
			$.each(selectedPacks, function (packId, item) {
				if (item && item.qty > 0) {
					itemsPayload[packId] = item.qty;
					totalQty += item.qty;
				}
			});

			if (totalQty === 0) {
				showFeedback(wspFoodPacks.i18n.selectAtLeastOne, 'error');
				return;
			}

			var fullName = $.trim($('#wsp_fp_full_name').val());
			var email = $.trim($('#wsp_fp_email').val());
			var phone = $.trim($('#wsp_fp_phone').val());
			var address = $.trim($('#wsp_fp_address').val());
			var city = $.trim($('#wsp_fp_city').val());
			var state = $.trim($('#wsp_fp_state').val());
			var notes = $.trim($('#wsp_fp_notes').val());
			var paymentMethod = $root.find('input[name="payment_method"]:checked').val() || '';

			if (!fullName || !email || !phone || !address) {
				showFeedback(wspFoodPacks.i18n.fillRequired, 'error');
				return;
			}

			if (!paymentMethod) {
				showFeedback(wspFoodPacks.i18n.selectGateway, 'error');
				return;
			}

			var $btn = $('#wsp-fp-submit-btn');
			var prevText = $('#wsp-fp-submit-text').text();
			$btn.prop('disabled', true);
			$('#wsp-fp-submit-text').text(wspFoodPacks.i18n.processing);

			var postData = {
				action: 'wsp_fp_submit_order',
				nonce: wspFoodPacks.nonce,
				items: JSON.stringify(itemsPayload),
				full_name: fullName,
				email: email,
				phone: phone,
				address: address,
				city: city,
				state: state,
				notes: notes,
				payment_method: paymentMethod
			};

			// Include any custom fields rendered by the active WooCommerce payment gateway
			$root.find('.wsp-fp-gateway-item.is-active .wsp-fp-gateway-fields :input').each(function () {
				var name = $(this).attr('name');
				if (!name) {
					return;
				}
				if (($(this).is(':checkbox') || $(this).is(':radio')) && !$(this).is(':checked')) {
					return;
				}
				postData[name] = $(this).val();
			});

			$.post(wspFoodPacks.ajaxUrl, postData)
				.done(function (response) {
					if (response && response.success && response.data && response.data.redirect) {
						window.location.href = response.data.redirect;
					} else {
						var msg = (response && response.data && response.data.message)
							? response.data.message
							: wspFoodPacks.i18n.genericError;
						showFeedback(msg, 'error');
						$btn.prop('disabled', false);
						$('#wsp-fp-submit-text').text(prevText);
					}
				})
				.fail(function () {
					showFeedback(wspFoodPacks.i18n.genericError, 'error');
					$btn.prop('disabled', false);
					$('#wsp-fp-submit-text').text(prevText);
				});
		});
	});
})(jQuery);
