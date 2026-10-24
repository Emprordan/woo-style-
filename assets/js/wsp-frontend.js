/**
 * Woo Style+ Frontend JavaScript
 * Modern UI interactions, quantity steppers, and sticky mobile CTAs
 */

(function($) {
    'use strict';

    var WooStylePlus = {
        /**
         * Initialize frontend features
         */
        init: function() {
            var self = this;

            // Global AJAX Prefilter: attach user selection flag when shipping has been explicitly chosen
            $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
                if (sessionStorage.getItem('wsp_shipping_user_selected') === '1') {
                    if (typeof options.data === 'string' && options.data.indexOf('wsp_user_chosen_shipping') === -1) {
                        options.data += (options.data.length ? '&' : '') + 'wsp_user_chosen_shipping=1';
                    }
                }
            });

            $(document).ready(function() {
                // If cart is empty, clear shipping selection from session storage
                if ($('.cart-empty').length || $('.woocommerce-empty-cart-wrapper').length) {
                    sessionStorage.removeItem('wsp_shipping_user_selected');
                    sessionStorage.removeItem('wsp_shipping_chosen_id');
                }

                if (typeof wspFrontendData !== 'undefined' && wspFrontendData.qtyStepper) {
                    self.initQuantitySteppers();
                }

                if (typeof wspFrontendData !== 'undefined' && wspFrontendData.stickyMobileCTA) {
                    self.initStickyMobileCTA();
                }

                if (typeof wspFrontendData === 'undefined' || wspFrontendData.shippingMethodsStyle !== 'cards') {
                    self.initShippingDropdown();
                }

                self.initCheckoutLayout();
                self.initCheckoutCoupon();

                if (typeof wspFrontendData === 'undefined' || wspFrontendData.cartAutoUpdate) {
                    self.initCartAutoUpdate();
                }

                self.cleanSingleProductThemeComponents();
                self.cleanArchiveThemeComponents();

                // Global close for custom shipping dropdowns when clicking outside
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.wsp-custom-shipping-select').length) {
                        $('.wsp-custom-shipping-select').removeClass('is-open');
                        $('.wsp-css-trigger').attr('aria-expanded', 'false');
                    }
                });

                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' || e.keyCode === 27) {
                        $('.wsp-custom-shipping-select').removeClass('is-open');
                        $('.wsp-css-trigger').attr('aria-expanded', 'false');
                    }
                });

                self.initCustomShop();
            });

            // Re-initialize features on WooCommerce AJAX updates
            $(document.body).on('updated_wc_div updated_cart_totals updated_checkout country_to_state_changed applied_coupon removed_coupon wc_fragments_refreshed wsp_custom_shop_updated', function() {
                if (typeof wspFrontendData !== 'undefined' && wspFrontendData.qtyStepper) {
                    self.initQuantitySteppers();
                }

                if (typeof wspFrontendData === 'undefined' || wspFrontendData.shippingMethodsStyle !== 'cards') {
                    self.initShippingDropdown();
                }

                self.initCheckoutLayout();
                self.initCheckoutCoupon();

                if (typeof wspFrontendData === 'undefined' || wspFrontendData.cartAutoUpdate) {
                    self.initCartAutoUpdate();
                }
            });

            // Catch any background WooCommerce AJAX completions
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (typeof wspFrontendData === 'undefined' || wspFrontendData.shippingMethodsStyle !== 'cards') {
                    if (settings && settings.url && (settings.url.indexOf('wc-ajax') !== -1 || settings.url.indexOf('admin-ajax.php') !== -1)) {
                        setTimeout(function() {
                            self.initShippingDropdown();
                        }, 50);
                    }
                }

                if (settings && settings.url && settings.url.indexOf('apply_coupon') !== -1) {
                    var $couponBox = $('.wsp-checkout-coupon-box');
                    var $applyBtn  = $couponBox.find('.wsp-coupon-apply-btn');
                    var $input     = $couponBox.find('.wsp-coupon-input');
                    var $msgBox    = $couponBox.find('.wsp-coupon-msg');

                    $applyBtn.prop('disabled', false).removeClass('is-loading').text('Apply');

                    var resp = xhr.responseText || '';
                    if (resp.indexOf('woocommerce-error') !== -1) {
                        var $temp = $('<div>').html(resp);
                        var errText = $temp.find('.woocommerce-error li').first().text() || $temp.find('.woocommerce-error').text() || 'Invalid coupon code.';
                        $msgBox.html(
                            '<div class="wsp-coupon-badge wsp-coupon-badge-error">' +
                                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>' +
                                '<span>' + $('<div>').text(errText.trim()).html() + '</span>' +
                            '</div>'
                        ).slideDown(150);
                        $input.select();
                    } else if (resp.indexOf('woocommerce-message') !== -1 || resp.indexOf('applied_coupon') !== -1) {
                        $msgBox.html(
                            '<div class="wsp-coupon-badge wsp-coupon-badge-success">' +
                                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' +
                                '<span>Coupon applied successfully!</span>' +
                            '</div>'
                        ).slideDown(150);
                        $input.val('');
                    }
                }
            });
        },

        /**
         * Initialize modern + and - quantity steppers
         */
        initQuantitySteppers: function() {
            $('.quantity:not(.wsp-stepper-ready)').each(function() {
                var $qtyWrapper = $(this);
                var $input = $qtyWrapper.find('input.qty');

                if (!$input.length || $input.prop('type') === 'hidden') {
                    return;
                }

                $qtyWrapper.addClass('wsp-stepper-ready');

                var $minusBtn = $('<button type="button" class="wsp-qty-btn wsp-qty-minus" aria-label="Decrease quantity">−</button>');
                var $plusBtn  = $('<button type="button" class="wsp-qty-btn wsp-qty-plus" aria-label="Increase quantity">+</button>');

                $input.before($minusBtn);
                $input.after($plusBtn);

                $minusBtn.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var currentVal = parseFloat($input.val()) || 0;
                    var step       = parseFloat($input.attr('step')) || 1;
                    var min        = parseFloat($input.attr('min'));

                    if (isNaN(min)) {
                        min = 1;
                    }

                    if (currentVal > min) {
                        var newVal = currentVal - step;
                        // Avoid floating point precision quirks
                        newVal = Math.round(newVal * 100) / 100;
                        if (newVal < min) {
                            newVal = min;
                        }
                        $input.val(newVal).trigger('change');
                    }
                });

                $plusBtn.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var currentVal = parseFloat($input.val()) || 0;
                    var step       = parseFloat($input.attr('step')) || 1;
                    var max        = parseFloat($input.attr('max'));

                    if (isNaN(max) || currentVal < max) {
                        var newVal = currentVal + step;
                        newVal = Math.round(newVal * 100) / 100;
                        if (!isNaN(max) && newVal > max) {
                            newVal = max;
                        }
                        $input.val(newVal).trigger('change');
                    }
                });
            });
        },

        /**
         * Initialize mobile sticky add to cart bar on single product
         */
        initStickyMobileCTA: function() {
            var $stickyBar = $('#wspStickyMobileCTA');
            var $mainBtn   = $('form.cart .single_add_to_cart_button');

            if (!$stickyBar.length || !$mainBtn.length) {
                return;
            }

            var checkScroll = function() {
                var triggerOffset = $mainBtn.offset().top + $mainBtn.outerHeight();
                var scrollPos     = $(window).scrollTop();

                if (scrollPos > triggerOffset) {
                    $stickyBar.addClass('visible');
                } else {
                    $stickyBar.removeClass('visible');
                }
            };

            $(window).on('scroll resize', checkScroll);
            checkScroll();

            $('#wspScrollToCartBtn').on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('form.cart').offset().top - 80
                }, 400);
            });
        },

        /**
         * Initialize Custom Shop Shortcode Interactions & Live AJAX Filter
         */
        initCustomShop: function() {
            $('.wsp-custom-shop-wrapper').each(function() {
                var $wrapper     = $(this);
                var $catalogue   = $wrapper.find('.wsp-custom-shop-catalogue');
                var $grid        = $wrapper.find('.wsp-cs-grid-container');
                var $pagination  = $wrapper.find('.wsp-cs-pagination-container');
                var $countBox    = $wrapper.find('.wsp-cs-result-count');
                var $searchInput = $wrapper.find('.wsp-cs-search-input');
                var $clearBtn    = $wrapper.find('.wsp-cs-search-clear');
                var $sortSelect  = $wrapper.find('.wsp-cs-sort-select');

                var columns      = $wrapper.data('columns') || 4;
                var limit        = $wrapper.data('limit') || 12;
                var defaultOrder = $wrapper.data('default-order') || 'DESC';

                var searchTimer  = null;
                var activeXHR    = null;

                // Function to perform AJAX filter request
                var fetchProducts = function(page, scrollToTop) {
                    var searchVal = $.trim($searchInput.val());
                    var activeCat = $wrapper.find('.wsp-cs-cat-btn.is-active').data('category') || '';
                    var orderby   = $sortSelect.length ? $sortSelect.val() : 'date';
                    var paged     = page || 1;

                    // Show loading overlay
                    $catalogue.addClass('is-loading');

                    // Abort any ongoing filter request
                    if (activeXHR && activeXHR.readyState !== 4) {
                        activeXHR.abort();
                    }

                    var postData = {
                        action:   'wsp_custom_shop_filter',
                        security: (typeof wspFrontendData !== 'undefined' ? wspFrontendData.nonce : ''),
                        search:   searchVal,
                        category: activeCat,
                        orderby:  orderby,
                        order:    defaultOrder,
                        limit:    limit,
                        columns:  columns,
                        paged:    paged
                    };

                    activeXHR = $.ajax({
                        url:      (typeof wspFrontendData !== 'undefined' ? wspFrontendData.ajaxUrl : '/wp-admin/admin-ajax.php'),
                        type:     'POST',
                        data:     postData,
                        dataType: 'json',
                        success:  function(response) {
                            $catalogue.removeClass('is-loading');
                            if (response && response.success && response.data) {
                                $grid.html(response.data.html);
                                if ($pagination.length) {
                                    $pagination.html(response.data.pagination);
                                }
                                if ($countBox.length && response.data.count_text) {
                                    if ($countBox.find('.wsp-cs-count-label').length) {
                                        $countBox.find('.wsp-cs-count-label').text(response.data.count_text);
                                    } else {
                                        $countBox.text(response.data.count_text);
                                    }
                                }

                                // Re-trigger WooCommerce fragments / events
                                $(document.body).trigger('wsp_custom_shop_updated', [$wrapper, response.data]);

                                // Scroll to catalogue if requested
                                if (scrollToTop) {
                                    var targetTop = $wrapper.offset().top - 40;
                                    $('html, body').animate({ scrollTop: targetTop }, 300);
                                }
                            }
                        },
                        error: function(xhr, status) {
                            if (status !== 'abort') {
                                $catalogue.removeClass('is-loading');
                            }
                        }
                    });
                };

                // Category pill click
                $wrapper.on('click', '.wsp-cs-cat-btn', function(e) {
                    e.preventDefault();
                    var $btn = $(this);
                    if ($btn.hasClass('is-active')) {
                        return;
                    }
                    $wrapper.find('.wsp-cs-cat-btn').removeClass('is-active').attr('aria-selected', 'false');
                    $btn.addClass('is-active').attr('aria-selected', 'true');
                    fetchProducts(1, false);
                });

                // Search input live typing (debounced 350ms)
                $searchInput.on('input', function() {
                    var val = $.trim($(this).val());
                    if (val.length > 0) {
                        $clearBtn.show();
                    } else {
                        $clearBtn.hide();
                    }

                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function() {
                        fetchProducts(1, false);
                    }, 350);
                });

                // Clear button click
                $clearBtn.on('click', function(e) {
                    e.preventDefault();
                    $searchInput.val('').focus();
                    $clearBtn.hide();
                    fetchProducts(1, false);
                });

                // Form submit
                $wrapper.find('.wsp-cs-search-form').on('submit', function(e) {
                    e.preventDefault();
                    clearTimeout(searchTimer);
                    fetchProducts(1, false);
                });

                // Sort dropdown change
                $sortSelect.on('change', function() {
                    fetchProducts(1, false);
                });

                // Pagination click
                $wrapper.on('click', '.wsp-cs-page-btn', function(e) {
                    e.preventDefault();
                    var pageNum = $(this).data('page');
                    if (pageNum) {
                        fetchProducts(pageNum, true);
                    }
                });

                // Reset Filters click (from empty state)
                $wrapper.on('click', '.wsp-cs-reset-filters-btn', function(e) {
                    e.preventDefault();
                    $searchInput.val('');
                    $clearBtn.hide();
                    $wrapper.find('.wsp-cs-cat-btn').removeClass('is-active').attr('aria-selected', 'false');
                    $wrapper.find('.wsp-cs-cat-btn[data-category=""]').addClass('is-active').attr('aria-selected', 'true');
                    if ($sortSelect.length) {
                        $sortSelect.val('date');
                    }
                    fetchProducts(1, false);
                });
            });
        },

        /**
         * Initialize fancy modern dropdown for delivery / shipping methods
         */
        initShippingDropdown: function() {
            $('ul#shipping_method, ul.woocommerce-shipping-methods').each(function() {
                var $ul = $(this);
                var $radios = $ul.find('input.shipping_method');

                // If only 1 option or none, keep standard presentation and clean up any custom dropdown
                if ($radios.length <= 1) {
                    $ul.siblings('.wsp-custom-shipping-select').remove();
                    $ul.siblings('.wsp-shipping-dropdown-wrap').remove();
                    $ul.removeClass('wsp-shipping-hidden');
                    return;
                }

                // Hide native radio list visually while keeping accessible for WooCommerce AJAX forms
                $ul.addClass('wsp-shipping-hidden');

                // Remove existing dropdown wrapper if present to refresh with latest options
                $ul.siblings('.wsp-custom-shipping-select').remove();
                $ul.siblings('.wsp-shipping-dropdown-wrap').remove();

                var userHasChosen = sessionStorage.getItem('wsp_shipping_user_selected') === '1';
                var savedMethodVal = sessionStorage.getItem('wsp_shipping_chosen_id');

                // Force uncheck all radios if user has not explicitly picked a zone yet
                if (!userHasChosen) {
                    $radios.prop('checked', false);
                }

                var methods = [];
                var activeMethod = null;

                $radios.each(function() {
                    var $radio = $(this);
                    var val = $radio.val();
                    var isChecked = userHasChosen && ($radio.is(':checked') || (savedMethodVal && val === savedMethodVal));

                    if (isChecked && !activeMethod) {
                        $radio.prop('checked', true);
                    } else if (!userHasChosen) {
                        $radio.prop('checked', false);
                    }

                    var $li = $radio.closest('li');
                    var $label = $li.find('label');

                    var priceText = '';
                    var titleText = '';

                    var $priceEl = $label.find('.woocommerce-Price-amount, .amount');
                    if ($priceEl.length) {
                        priceText = $priceEl.first().text().replace(/\s+/g, ' ').trim();
                        var $clone = $label.clone();
                        $clone.find('.woocommerce-Price-amount, .amount, script, style').remove();
                        titleText = $clone.text().replace(/\s+/g, ' ').trim();
                        titleText = titleText.replace(/[:\-–—\s]+$/, '').trim();
                    } else {
                        var fullText = $label.text().replace(/\s+/g, ' ').trim();
                        var parts = fullText.split(':');
                        if (parts.length > 1) {
                            priceText = parts.pop().trim();
                            titleText = parts.join(':').trim();
                        } else {
                            titleText = fullText || val;
                            priceText = '';
                        }
                    }

                    var methodObj = {
                        val: val,
                        title: titleText,
                        price: priceText,
                        isChecked: isChecked
                    };

                    methods.push(methodObj);

                    if (isChecked && !activeMethod) {
                        activeMethod = methodObj;
                    }
                });

                var placeholderText = 'Select delivery zone...';
                var isPlaceholder = !activeMethod;
                var triggerTitle = activeMethod ? activeMethod.title : placeholderText;

                // Build HTML for Fancy Custom Dropdown
                var $dropdown = $('<div class="wsp-custom-shipping-select"></div>');

                // Trigger button
                var $trigger = $(
                    '<button type="button" class="wsp-css-trigger' + (isPlaceholder ? ' is-placeholder' : '') + '" aria-haspopup="listbox" aria-expanded="false">' +
                        '<span class="wsp-css-trigger-content">' +
                            '<span class="wsp-css-trigger-title' + (isPlaceholder ? ' wsp-css-placeholder' : '') + '">' + triggerTitle + '</span>' +
                            (activeMethod && activeMethod.price ? '<span class="wsp-css-trigger-price">' + activeMethod.price + '</span>' : '') +
                        '</span>' +
                        '<span class="wsp-css-trigger-arrow" aria-hidden="true">' +
                            '<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">' +
                                '<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>' +
                            '</svg>' +
                        '</span>' +
                    '</button>'
                );

                // Floating Menu container
                var $menu = $('<div class="wsp-css-menu" role="listbox"></div>');

                // Populate option rows
                $.each(methods, function(idx, m) {
                    var isSel = (activeMethod && activeMethod.val === m.val);
                    var $opt = $(
                        '<div class="wsp-css-option' + (isSel ? ' is-selected' : '') + '" role="option" aria-selected="' + (isSel ? 'true' : 'false') + '" data-value="' + m.val + '">' +
                            '<span class="wsp-css-opt-indicator" aria-hidden="true">' +
                                '<svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12">' +
                                    '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>' +
                                '</svg>' +
                            '</span>' +
                            '<span class="wsp-css-opt-title">' + m.title + '</span>' +
                            (m.price ? '<span class="wsp-css-opt-price">' + m.price + '</span>' : '') +
                        '</div>'
                    );

                    $opt.on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        // Mark as chosen in session
                        sessionStorage.setItem('wsp_shipping_user_selected', '1');
                        sessionStorage.setItem('wsp_shipping_chosen_id', m.val);

                        // Inject hidden flag inputs into forms
                        var $cartForm = $('form.woocommerce-cart-form');
                        if ($cartForm.length && !$cartForm.find('input[name="wsp_user_chosen_shipping"]').length) {
                            $cartForm.append('<input type="hidden" name="wsp_user_chosen_shipping" value="1">');
                        }
                        var $checkoutForm = $('form.checkout, #order_review');
                        if ($checkoutForm.length && !$checkoutForm.find('input[name="wsp_user_chosen_shipping"]').length) {
                            $('form.checkout').append('<input type="hidden" name="wsp_user_chosen_shipping" value="1">');
                        }

                        // Close menu
                        $dropdown.removeClass('is-open');
                        $trigger.removeClass('is-placeholder wsp-field-required-pulse').attr('aria-expanded', 'false');

                        // Update trigger display
                        $trigger.find('.wsp-css-trigger-title').removeClass('wsp-css-placeholder').text(m.title);
                        var $priceBadge = $trigger.find('.wsp-css-trigger-price');
                        if (m.price) {
                            if ($priceBadge.length) {
                                $priceBadge.text(m.price);
                            } else {
                                $trigger.find('.wsp-css-trigger-content').append('<span class="wsp-css-trigger-price">' + m.price + '</span>');
                            }
                        } else {
                            $priceBadge.remove();
                        }

                        // Update option selected styling
                        $menu.find('.wsp-css-option').removeClass('is-selected').attr('aria-selected', 'false');
                        $opt.addClass('is-selected').attr('aria-selected', 'true');

                        // Check corresponding native radio and trigger WooCommerce update
                        var $targetRadio = $ul.find('input.shipping_method').filter(function() {
                            return $(this).val() === m.val;
                        });
                        if ($targetRadio.length) {
                            $targetRadio.prop('checked', true).trigger('change');
                        }
                    });

                    $menu.append($opt);
                });

                // Toggle menu on trigger click
                $trigger.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var isOpen = $dropdown.hasClass('is-open');
                    // Close other dropdowns
                    $('.wsp-custom-shipping-select').not($dropdown).removeClass('is-open').find('.wsp-css-trigger').attr('aria-expanded', 'false');

                    if (isOpen) {
                        $dropdown.removeClass('is-open');
                        $trigger.attr('aria-expanded', 'false');
                    } else {
                        $dropdown.addClass('is-open');
                        $trigger.attr('aria-expanded', 'true');
                    }
                });

                // Handle external radio button changes
                $ul.find('input.shipping_method').off('change.wsp_sync').on('change.wsp_sync', function() {
                    var checkedVal = $ul.find('input.shipping_method:checked').val();
                    if (checkedVal) {
                        sessionStorage.setItem('wsp_shipping_user_selected', '1');
                        sessionStorage.setItem('wsp_shipping_chosen_id', checkedVal);

                        var matchedOpt = null;
                        $.each(methods, function(idx, m) {
                            if (m.val === checkedVal) {
                                matchedOpt = m;
                                return false;
                            }
                        });
                        if (matchedOpt) {
                            $trigger.removeClass('is-placeholder wsp-field-required-pulse');
                            $trigger.find('.wsp-css-trigger-title').removeClass('wsp-css-placeholder').text(matchedOpt.title);
                            var $priceBadge = $trigger.find('.wsp-css-trigger-price');
                            if (matchedOpt.price) {
                                if ($priceBadge.length) {
                                    $priceBadge.text(matchedOpt.price);
                                } else {
                                    $trigger.find('.wsp-css-trigger-content').append('<span class="wsp-css-trigger-price">' + matchedOpt.price + '</span>');
                                }
                            } else {
                                $priceBadge.remove();
                            }
                            $menu.find('.wsp-css-option').removeClass('is-selected').attr('aria-selected', 'false');
                            $menu.find('.wsp-css-option[data-value="' + matchedOpt.val + '"]').addClass('is-selected').attr('aria-selected', 'true');
                        }
                    } else {
                        $trigger.addClass('is-placeholder');
                        $trigger.find('.wsp-css-trigger-title').addClass('wsp-css-placeholder').text(placeholderText);
                        $trigger.find('.wsp-css-trigger-price').remove();
                        $menu.find('.wsp-css-option').removeClass('is-selected').attr('aria-selected', 'false');
                    }
                });

                $dropdown.append($trigger).append($menu);
                $ul.before($dropdown);
            });

            // Enforce required shipping selection on Cart "Proceed to checkout"
            $(document).off('click.wsp_req_shipping', '.checkout-button, a.checkout-button, .wc-proceed-to-checkout a')
                       .on('click.wsp_req_shipping', '.checkout-button, a.checkout-button, .wc-proceed-to-checkout a', function(e) {
                var $radios = $('input.shipping_method');
                var isSelected = sessionStorage.getItem('wsp_shipping_user_selected') === '1' && $radios.filter(':checked').length > 0;
                if ($radios.length > 1 && !isSelected) {
                    e.preventDefault();
                    e.stopPropagation();

                    var $dropdown = $('.wsp-custom-shipping-select').first();
                    if ($dropdown.length) {
                        $dropdown.find('.wsp-css-trigger').addClass('wsp-field-required-pulse');
                        $dropdown.addClass('is-open').find('.wsp-css-trigger').attr('aria-expanded', 'true');
                        $('html, body').animate({
                            scrollTop: $dropdown.offset().top - 120
                        }, 300);
                    }

                    $('.woocommerce-error, .woocommerce-message').remove();
                    var alertHtml = '<ul class="woocommerce-error" role="alert"><li>Please select a delivery zone / shipping method before proceeding to checkout.</li></ul>';
                    var $notices = $('.woocommerce-notices-wrapper').first();
                    if ($notices.length) {
                        $notices.html(alertHtml);
                    } else {
                        $('form.woocommerce-cart-form').before(alertHtml);
                    }
                }
            });

            // Enforce required shipping selection on Checkout "Place order"
            $(document).off('click.wsp_req_shipping_checkout', '#place_order')
                       .on('click.wsp_req_shipping_checkout', '#place_order', function(e) {
                var $radios = $('input.shipping_method');
                var isSelected = sessionStorage.getItem('wsp_shipping_user_selected') === '1' && $radios.filter(':checked').length > 0;
                if ($radios.length > 1 && !isSelected) {
                    e.preventDefault();
                    e.stopPropagation();

                    var $dropdown = $('.wsp-custom-shipping-select').first();
                    if ($dropdown.length) {
                        $dropdown.find('.wsp-css-trigger').addClass('wsp-field-required-pulse');
                        $dropdown.addClass('is-open').find('.wsp-css-trigger').attr('aria-expanded', 'true');
                        $('html, body').animate({
                            scrollTop: $dropdown.offset().top - 140
                        }, 300);
                    }

                    $('.woocommerce-error, .woocommerce-message').remove();
                    var alertHtml = '<ul class="woocommerce-error" role="alert"><li>Please select a delivery zone / shipping method to complete your order.</li></ul>';
                    var $notices = $('.woocommerce-notices-wrapper').first();
                    if ($notices.length) {
                        $notices.html(alertHtml);
                    } else {
                        $('form.checkout').before(alertHtml);
                    }
                    return false;
                }
            });
        },

        /**
         * Guarantee clean 2-column checkout layout by nesting #order_review_heading into #order_review
         * and consolidating customer details, shipping options, and order notes into unified cards.
         */
        initCheckoutLayout: function() {
            var $form = $('form.checkout');
            var $review = $form.find('#order_review');
            if (!$review.length) {
                $review = $('#order_review');
            }

            if ($review.length) {
                var $outsideHeadings = $('form.checkout > #order_review_heading, #order_review_heading').not('#order_review #order_review_heading');
                if (!$review.find('#order_review_heading, .wsp-order-review-heading').length) {
                    if ($outsideHeadings.length) {
                        $review.prepend($outsideHeadings.first().show());
                        $outsideHeadings.slice(1).remove();
                    } else {
                        $review.prepend('<h3 id="order_review_heading" class="wsp-order-review-heading">Your order</h3>');
                    }
                } else if ($outsideHeadings.length) {
                    $outsideHeadings.remove();
                }
            }

            // Consolidate any orphaned customer details / shipping / additional fields into #customer_details
            var $customerDetails = $form.find('#customer_details');
            if ($customerDetails.length) {
                var $orphanedFields = $form.children('.woocommerce-shipping-fields, .woocommerce-additional-fields, #ship-to-different-address, .shipping_address');
                if ($orphanedFields.length) {
                    $customerDetails.append($orphanedFields);
                }
            }
        },

        /**
         * Initialize embedded Checkout Coupon Box inside #order_review card
         */
        initCheckoutCoupon: function() {
            if (typeof wspFrontendData !== 'undefined' && wspFrontendData.checkoutEmbeddedCoupon === false) {
                return;
            }

            var $review = $('form.checkout #order_review, #order_review');
            if (!$review.length) {
                return;
            }

            // If coupon box does not exist inside #order_review, inject it
            var $couponBox = $review.find('.wsp-checkout-coupon-box');
            if (!$couponBox.length) {
                var $outsideBox = $('.wsp-checkout-coupon-box');
                if ($outsideBox.length) {
                    $couponBox = $outsideBox.first();
                } else {
                    $couponBox = $(
                        '<div class="wsp-checkout-coupon-box">' +
                            '<div class="wsp-coupon-toggle-wrap">' +
                                '<button type="button" class="wsp-coupon-toggle-btn" aria-expanded="false">' +
                                    '<span class="wsp-coupon-toggle-left">' +
                                        '<span class="wsp-coupon-tag-icon">' +
                                            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                                                '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>' +
                                                '<line x1="7" y1="7" x2="7.01" y2="7"></line>' +
                                            '</svg>' +
                                        '</span>' +
                                        '<span class="wsp-coupon-toggle-title">Have a coupon code?</span>' +
                                    '</span>' +
                                    '<span class="wsp-coupon-toggle-right">' +
                                        '<span class="wsp-coupon-toggle-action">Enter code</span>' +
                                        '<span class="wsp-coupon-toggle-arrow">' +
                                            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
                                                '<polyline points="6 9 12 15 18 9"></polyline>' +
                                            '</svg>' +
                                        '</span>' +
                                    '</span>' +
                                '</button>' +
                            '</div>' +
                            '<div class="wsp-coupon-form-wrap" style="display: none;">' +
                                '<div class="wsp-coupon-input-group">' +
                                    '<div class="wsp-coupon-input-inner">' +
                                        '<span class="wsp-coupon-input-icon">' +
                                            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                                                '<rect x="2" y="5" width="20" height="14" rx="2"></rect>' +
                                                '<line x1="2" y1="10" x2="22" y2="10"></line>' +
                                            '</svg>' +
                                        '</span>' +
                                        '<input type="text" class="wsp-coupon-input" placeholder="Enter coupon code" autocomplete="off" />' +
                                    '</div>' +
                                    '<button type="button" class="wsp-coupon-apply-btn">Apply</button>' +
                                '</div>' +
                                '<div class="wsp-coupon-msg" style="display: none;"></div>' +
                            '</div>' +
                        '</div>'
                    );
                }

                // Place directly below heading
                var $heading = $review.find('#order_review_heading, .wsp-order-review-heading');
                if ($heading.length) {
                    $heading.after($couponBox);
                } else {
                    $review.prepend($couponBox);
                }
            } else {
                // Ensure correct position right under the heading before review table
                var $heading = $review.find('#order_review_heading, .wsp-order-review-heading');
                if ($heading.length && $heading.next()[0] !== $couponBox[0]) {
                    $heading.after($couponBox);
                }
            }

            // Bind toggle button click
            $couponBox.find('.wsp-coupon-toggle-btn').off('click.wsp_coupon').on('click.wsp_coupon', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $box = $(this).closest('.wsp-checkout-coupon-box');
                var isOpen = $box.hasClass('is-open');
                var $formWrap = $box.find('.wsp-coupon-form-wrap');
                var $btn = $(this);

                if (isOpen) {
                    $box.removeClass('is-open');
                    $btn.attr('aria-expanded', 'false');
                    $formWrap.slideUp(200);
                } else {
                    $box.addClass('is-open');
                    $btn.attr('aria-expanded', 'true');
                    $formWrap.slideDown(200, function() {
                        $box.find('.wsp-coupon-input').focus();
                    });
                }
            });

            // Function to execute coupon apply
            var handleApply = function() {
                var $box = $('.wsp-checkout-coupon-box');
                var $input = $box.find('.wsp-coupon-input');
                var $applyBtn = $box.find('.wsp-coupon-apply-btn');
                var $msg = $box.find('.wsp-coupon-msg');
                var code = $.trim($input.val());

                if (!code) {
                    $input.focus();
                    return;
                }

                $applyBtn.prop('disabled', true).addClass('is-loading').text('Applying...');
                $msg.slideUp(100).empty();

                // Check native WooCommerce checkout_coupon form
                var $nativeForm = $('form.checkout_coupon');
                if ($nativeForm.length) {
                    $nativeForm.find('input[name="coupon_code"]').val(code);
                    $nativeForm.trigger('submit');
                } else {
                    var ajaxUrl = (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.wc_ajax_url)
                        ? wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon')
                        : '/?wc-ajax=apply_coupon';
                    var nonce = (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.apply_coupon_nonce)
                        ? wc_checkout_params.apply_coupon_nonce
                        : '';

                    $.ajax({
                        type: 'POST',
                        url: ajaxUrl,
                        data: {
                            security: nonce,
                            coupon_code: code
                        },
                        dataType: 'html',
                        success: function(response) {
                            $('.woocommerce-error, .woocommerce-message').remove();
                            if (response) {
                                $('form.checkout').before(response);
                                $(document.body).trigger('applied_coupon_in_checkout', [code]);
                                $(document.body).trigger('update_checkout', { update_shipping_method: false });
                            }
                        }
                    });
                }
            };

            // Bind Apply button click
            $couponBox.find('.wsp-coupon-apply-btn').off('click.wsp_apply').on('click.wsp_apply', function(e) {
                e.preventDefault();
                handleApply();
            });

            // Bind Enter key in coupon input
            $couponBox.find('.wsp-coupon-input').off('keydown.wsp_enter').on('keydown.wsp_enter', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    e.stopPropagation();
                    handleApply();
                }
            });
        },

        /**
         * Auto-update cart when quantities change without needing an "Update cart" button
         */
        initCartAutoUpdate: function() {
            var cartTimer = null;

            $(document).off('change.wsp_auto_cart input.wsp_auto_cart', 'table.cart input.qty, .woocommerce-cart-form input.qty')
                       .on('change.wsp_auto_cart input.wsp_auto_cart', 'table.cart input.qty, .woocommerce-cart-form input.qty', function() {
                
                clearTimeout(cartTimer);
                var $input = $(this);
                var $form = $input.closest('form.woocommerce-cart-form');
                if (!$form.length) {
                    $form = $('form.woocommerce-cart-form');
                }

                cartTimer = setTimeout(function() {
                    var $updateBtn = $form.find('button[name="update_cart"], input[name="update_cart"]');
                    if ($updateBtn.length) {
                        $updateBtn.prop('disabled', false).trigger('click');
                    } else {
                        $form.trigger('submit');
                    }
                }, 550);
            });
        },

        /**
         * Hide extraneous theme components on Single Product page
         * (Hero header banners, author bio, related blog posts, non-Woo blog comments)
         */
        cleanSingleProductThemeComponents: function() {
            if (!$('body').hasClass('single-product')) {
                return;
            }

            var $product = $('div.product').first();
            if (!$product.length) {
                return;
            }

            // 1. Hide theme hero headers, title bars, or entry-headers outside div.product
            $('.entry-hero, .hero-section, .site-hero, .theme-hero, .page-header, .page-title-bar, .page-banner, .ast-single-post-order, .ast-title-bar, .custom-page-header, .title-section, .entry-header-wrap, .page-title-section, .banner-section, .post-header, .single-post-header, .custom-shape-divider-bottom, .wave-divider, .shape-divider').hide();

            $('header.entry-header, .entry-header').each(function() {
                if (!$(this).closest('div.product').length) {
                    $(this).hide();
                }
            });

            // Hide any top header inside main content area that appears before div.product
            $('#primary, #main, .site-main, .content-area, #content, .site-content').find('> header, > .page-header, > .entry-header, > .hero-section, > .entry-hero').hide();

            // 2. Hide author box outside div.product
            $('.author-box, .post-author, .entry-author, .author-bio, .about-author, .post-author-box, .author-info, .author-description, .single-author, .ast-author-box, .w-author-box').hide();

            // 3. Hide non-WooCommerce related posts / "You may also like!"
            $('.related-posts, .post-related, .theme-related-posts, .entry-related, .single-post-related, .related-articles, .ast-related-posts-wrap, .related-posts-wrapper, .you-may-also-like, .jp-relatedposts').hide();

            // Specifically detect any section or container containing "You may also like" outside div.product that does not contain product cards
            $('section, div').filter(function() {
                var $el = $(this);
                if ($el.closest('div.product').length) {
                    return false;
                }
                if ($el.hasClass('products') || $el.find('ul.products').length) {
                    return false;
                }
                var heading = $el.find('> h2, > h3, > h4, > .heading').text().toLowerCase();
                return heading.indexOf('you may also like') !== -1;
            }).hide();

            // 4. Hide blog comment section outside WooCommerce tabs
            $('#comments, .comments-area, #respond, .comment-respond').each(function() {
                if (!$(this).closest('.woocommerce-tabs').length && !$(this).closest('#reviews').length) {
                    $(this).hide();
                }
            });

            // 5. Hide post navigation (Prev / Next Post)
            $('.post-navigation, .navigation.post-navigation, .nav-links, .single-post-nav, .ast-single-post-navigation').hide();

            // 6. Uncut / ThemePure theme elements
            $('.tp-breadcrumb-area, .tp-hero-cpr-shape, .tp-breadcrumb-cpr-wrap, .tp-breadcrumb-cpr-overly, .tp-postbox-cpr-left-social, .tp-postbox-cpr-comment, .tp-postbox-cpr-social, .tp-postbox-cpr-social-wrap, .tp-blog-area, .tp-blog-cpr-title, .tp-postbox-details-form').hide();
            $('.tp-postbox-area .col-lg-2').hide();
            $('.tp-postbox-area .col-lg-8, .tp-postbox-area .col-lg-10, .tp-postbox-cpr-details-wrapper').css({
                'flex': '0 0 100%',
                'max-width': '100%',
                'width': '100%'
            });
            $('.tp-postbox-area').css({
                'padding-top': '35px',
                'padding-bottom': '50px'
            });

            // 7. Ensure Product Title is present in summary card
            var $summary = $product.find('div.summary').first();
            if ($summary.length && !$summary.find('.product_title, h1').length) {
                var titleText = $('.tp-breadcrumb-area .breadcrumb__title, .tp-breadcrumb-title, .breadcrumb-title, .tp-postbox-details-title, h1.entry-title').first().text().trim();
                if (!titleText) {
                    var ogTitle = $('meta[property="og:title"]').attr('content');
                    if (ogTitle) {
                        titleText = ogTitle.split('–')[0].split('-')[0].split('|')[0].trim();
                    }
                }
                if (!titleText && document.title) {
                    titleText = document.title.split('–')[0].split('-')[0].split('|')[0].trim();
                }
                if (titleText) {
                    var $h1 = $('<h1 class="product_title entry-title"></h1>').text(titleText);
                    var $stockPill = $summary.find('.wsp-single-stock-pill');
                    if ($stockPill.length) {
                        $stockPill.before($h1);
                    } else if ($summary.find('.price').length) {
                        $summary.find('.price').before($h1);
                    } else {
                        $summary.prepend($h1);
                    }
                }
            }

            // 8. Ensure Add to Cart button text is present
            $product.find('form.cart .single_add_to_cart_button, form.cart button[name="add-to-cart"]').each(function() {
                var $btn = $(this);
                if (!$.trim($btn.text())) {
                    $btn.text('Add to cart');
                }
            });
        },

        /**
         * Clean theme clutter on Category Archive & Search result pages
         */
        cleanArchiveThemeComponents: function() {
            if (!$('body').is('.tax-product_cat, .tax-product_tag, .post-type-archive-product, .search-results, .search')) {
                return;
            }

            // Suppress Uncut theme breadcrumbs, decorative wave shapes, and hero overlays
            $('.tp-breadcrumb-area, .tp-hero-cpr-shape, .tp-breadcrumb-cpr-overly, .tp-breadcrumb-cpr-wrap').hide();

            // Normalize blog wrapper if present
            $('.tp-blog-area').css({
                'padding-top': '24px',
                'padding-bottom': '50px'
            });

            // Expand postbox details wrapper to full width
            $('.tp-postbox-cpr-details-wrapper').css({
                'flex': '0 0 100%',
                'max-width': '100%',
                'width': '100%'
            });
        }
    };

    WooStylePlus.init();

})(jQuery);
