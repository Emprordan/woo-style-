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

            $(document).ready(function() {
                if (typeof wspFrontendData !== 'undefined' && wspFrontendData.qtyStepper) {
                    self.initQuantitySteppers();
                }

                if (typeof wspFrontendData !== 'undefined' && wspFrontendData.stickyMobileCTA) {
                    self.initStickyMobileCTA();
                }

                if (typeof wspFrontendData === 'undefined' || wspFrontendData.shippingMethodsStyle !== 'cards') {
                    self.initShippingDropdown();
                }

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
         * Initialize compact modern dropdown for delivery / shipping methods
         */
        initShippingDropdown: function() {
            $('ul#shipping_method, ul.woocommerce-shipping-methods').each(function() {
                var $ul = $(this);
                var $radios = $ul.find('input.shipping_method');

                // If only 1 option or none, keep standard presentation and clean up any dropdown
                if ($radios.length <= 1) {
                    $ul.siblings('.wsp-shipping-dropdown-wrap').remove();
                    $ul.removeClass('wsp-shipping-hidden');
                    return;
                }

                // Hide native radio list visually while keeping accessible for WooCommerce AJAX forms
                $ul.addClass('wsp-shipping-hidden');

                // Remove existing dropdown wrapper if present to refresh with latest options
                $ul.siblings('.wsp-shipping-dropdown-wrap').remove();

                var $wrap = $(
                    '<div class="wsp-shipping-dropdown-wrap">' +
                        '<select class="wsp-shipping-select" aria-label="Select delivery method"></select>' +
                        '<span class="wsp-shipping-dropdown-icon" aria-hidden="true">' +
                            '<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">' +
                                '<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />' +
                            '</svg>' +
                        '</span>' +
                    '</div>'
                );

                var $select = $wrap.find('select.wsp-shipping-select');
                var hasChecked = false;

                $radios.each(function() {
                    var $radio = $(this);
                    var val = $radio.val();
                    var isChecked = $radio.is(':checked');
                    var $li = $radio.closest('li');
                    var $label = $li.find('label');

                    var labelText = '';
                    if ($label.length) {
                        var $clone = $label.clone();
                        $clone.find('script, style, noscript').remove();
                        labelText = $clone.text().replace(/\s+/g, ' ').trim();
                    }

                    if (!labelText) {
                        labelText = val;
                    }

                    var $opt = $('<option></option>').val(val).text(labelText);

                    if (isChecked) {
                        $opt.prop('selected', true);
                        hasChecked = true;
                    }

                    $select.append($opt);
                });

                // Default to first option if none is marked checked
                if (!hasChecked && $radios.length) {
                    $radios.first().prop('checked', true);
                    $select.val($radios.first().val());
                }

                // Handle dropdown change -> sync with hidden radio and fire WooCommerce recalculation
                $select.on('change', function(e) {
                    e.stopPropagation();
                    var newVal = $(this).val();
                    var $targetRadio = $ul.find('input.shipping_method').filter(function() {
                        return $(this).val() === newVal;
                    });

                    if ($targetRadio.length) {
                        $targetRadio.prop('checked', true).trigger('change');
                    }
                });

                // Handle external radio button changes
                $ul.find('input.shipping_method').off('change.wsp_sync').on('change.wsp_sync', function() {
                    var checkedVal = $ul.find('input.shipping_method:checked').val();
                    if (checkedVal && $select.val() !== checkedVal) {
                        $select.val(checkedVal);
                    }
                });

                $ul.before($wrap);
            });
        }
    };

    WooStylePlus.init();

})(jQuery);
