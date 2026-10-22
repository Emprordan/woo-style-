/**
 * Woo Style+ Side Cart Drawer & Trigger JavaScript
 */

(function($) {
    'use strict';

    var updateTimers = {};

    var WooStyleSideCart = {
        /**
         * Initialize side cart features
         */
        init: function() {
            var self = this;

            $(document).ready(function() {
                self.bindEvents();
            });

            // Handle Elementor frontend hook if active
            $(window).on('elementor/frontend/init', function() {
                if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
                    elementorFrontend.hooks.addAction('frontend/element_ready/wsp_cart_icon.default', function($scope) {
                        // Scope ready
                    });
                }
            });
        },

        /**
         * Bind DOM events
         */
        bindEvents: function() {
            var self = this;

            // Trigger click -> Open Drawer
            $(document).on('click', '[data-wsp-cart-trigger]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.openDrawer();
            });

            // Close Drawer on close button or backdrop click
            $(document).on('click', '[data-wsp-close-drawer]', function(e) {
                e.preventDefault();
                self.closeDrawer();
            });

            // Close Drawer on Escape key
            $(document).on('keydown', function(e) {
                if ((e.key === 'Escape' || e.keyCode === 27) && $('#wsp-side-cart-drawer').hasClass('is-open')) {
                    self.closeDrawer();
                }
            });

            // Auto-open on WooCommerce Add to Cart
            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
                // Pulse count badges
                var $badges = $('.wsp-cart-count-badge');
                $badges.removeClass('wsp-badge-hidden').addClass('wsp-badge-pulse');
                setTimeout(function() {
                    $badges.removeClass('wsp-badge-pulse');
                }, 500);

                // Auto-open side cart if enabled
                if (typeof wspSideCartData !== 'undefined' && wspSideCartData.autoOpen) {
                    setTimeout(function() {
                        self.openDrawer();
                    }, 120);
                }
            });

            // Quantity Stepper Click (+ / -)
            $(document).on('click', '.wsp-sc-step-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var $stepper = $btn.closest('.wsp-sc-item-stepper');
                var cartKey = $stepper.data('cart-item-key');
                var $input = $stepper.find('.wsp-sc-step-input');
                var currentQty = parseInt($input.val(), 10) || 1;
                var maxQty = parseInt($input.attr('max'), 10);
                var isPlus = $btn.hasClass('wsp-sc-step-plus');
                var newQty = isPlus ? (currentQty + 1) : (currentQty - 1);

                if (newQty < 1) {
                    self.removeItem(cartKey, $stepper.closest('.wsp-sc-item'));
                    return;
                }

                if (!isNaN(maxQty) && maxQty > 0 && newQty > maxQty) {
                    return;
                }

                $input.val(newQty);
                var $item = $stepper.closest('.wsp-sc-item');
                $item.addClass('is-updating');

                self.updateItemQty(cartKey, newQty, $item);
            });

            // Item Remove Click
            $(document).on('click', '.wsp-sc-item-remove', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var cartKey = $btn.data('cart-item-key');
                var $item = $btn.closest('.wsp-sc-item');

                self.removeItem(cartKey, $item);
            });
        },

        /**
         * Open the side panel drawer
         */
        openDrawer: function() {
            var $drawer = $('#wsp-side-cart-drawer');
            if (!$drawer.length) {
                return;
            }

            $drawer.addClass('is-open').attr('aria-hidden', 'false');
            $('body').addClass('wsp-side-cart-open');
        },

        /**
         * Close the side panel drawer
         */
        closeDrawer: function() {
            var $drawer = $('#wsp-side-cart-drawer');
            if (!$drawer.length) {
                return;
            }

            $drawer.removeClass('is-open').attr('aria-hidden', 'true');
            $('body').removeClass('wsp-side-cart-open');
        },

        /**
         * Send AJAX request to update item quantity (debounced 250ms)
         */
        updateItemQty: function(cartKey, qty, $item) {
            var self = this;

            clearTimeout(updateTimers[cartKey]);
            updateTimers[cartKey] = setTimeout(function() {
                if (typeof wspSideCartData === 'undefined') {
                    return;
                }

                $.ajax({
                    url: wspSideCartData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wsp_update_cart_qty',
                        security: wspSideCartData.nonce,
                        cart_item_key: cartKey,
                        quantity: qty
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success && response.data && response.data.fragments) {
                            self.replaceFragments(response.data.fragments);
                        } else {
                            $(document.body).trigger('wc_fragment_refresh');
                        }
                    },
                    error: function() {
                        $(document.body).trigger('wc_fragment_refresh');
                    },
                    complete: function() {
                        $item.removeClass('is-updating');
                    }
                });
            }, 250);
        },

        /**
         * Send AJAX request to remove item from cart
         */
        removeItem: function(cartKey, $item) {
            var self = this;

            if (typeof wspSideCartData === 'undefined') {
                return;
            }

            $item.addClass('is-removing');

            setTimeout(function() {
                $.ajax({
                    url: wspSideCartData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wsp_remove_cart_item',
                        security: wspSideCartData.nonce,
                        cart_item_key: cartKey
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success && response.data && response.data.fragments) {
                            self.replaceFragments(response.data.fragments);
                        } else {
                            $(document.body).trigger('wc_fragment_refresh');
                        }
                    },
                    error: function() {
                        $item.removeClass('is-removing');
                        $(document.body).trigger('wc_fragment_refresh');
                    }
                });
            }, 180);
        },

        /**
         * Safely replace WooCommerce fragments in DOM
         */
        replaceFragments: function(fragments) {
            if (!fragments) {
                return;
            }

            $.each(fragments, function(selector, html) {
                var $target = $(selector);
                if ($target.length) {
                    $target.replaceWith(html);
                }
            });

            $(document.body).trigger('wc_fragments_refreshed');
        }
    };

    WooStyleSideCart.init();

})(jQuery);
