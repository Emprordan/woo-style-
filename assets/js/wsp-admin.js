/**
 * Woo Style+ Admin Settings JavaScript
 * Live preview synchronizer, tab switcher, and 1-click preset applier
 */

(function($) {
    'use strict';

    var WSPAdmin = {
        init: function() {
            this.initTabs();
            this.initQuickToggle();
            this.initColorPickers();
            this.initLivePreviewSync();
            this.initPresetApplier();
            this.initUpdateChecker();
            this.updateLivePreview();
        },

        /**
         * Tab switching logic
         */
        initTabs: function() {
            $('.wsp-tab-btn').on('click', function(e) {
                e.preventDefault();
                var targetTab = $(this).data('tab');

                $('.wsp-tab-btn').removeClass('active');
                $(this).addClass('active');

                $('.wsp-tab-panel').removeClass('active');
                $('#' + targetTab).addClass('active');
            });
        },

        /**
         * Header enable/disable quick switch
         */
        initQuickToggle: function() {
            $('#wsp_quick_enable_toggle').on('change', function() {
                var isChecked = $(this).is(':checked');
                $('#wsp_hidden_is_enabled').val(isChecked ? 'yes' : 'no');
            });
        },

        /**
         * Initialize WordPress Color Pickers with live preview callbacks
         */
        initColorPickers: function() {
            var self = this;

            $('.wsp-color-picker').wpColorPicker({
                change: function(event, ui) {
                    setTimeout(function() {
                        self.updateLivePreview();
                    }, 50);
                },
                clear: function() {
                    setTimeout(function() {
                        self.updateLivePreview();
                    }, 50);
                }
            });
        },

        /**
         * Listen to input changes for live preview
         */
        initLivePreviewSync: function() {
            var self = this;

            $(document).on('input change', '#wsp-settings-form select, #wsp-settings-form input', function() {
                self.updateLivePreview();
            });
        },

        /**
         * Real-time Mock Card preview updater
         */
        updateLivePreview: function() {
            var cardBg       = $('#card_bg').val() || '#ffffff';
            var primaryCol   = $('#primary_color').val() || '#0f172a';
            var primaryText  = $('#primary_text_color').val() || '#ffffff';
            var accentCol    = $('#accent_color').val() || '#3b82f6';
            var headingCol   = $('#heading_color').val() || '#0f172a';
            var priceCol     = $('#price_color').val() || '#94a3b8';
            var salePriceCol = $('#sale_price_color').val() || '#e11d48';
            var badgeBg      = $('#badge_bg').val() || '#0f172a';
            var badgeText    = $('#badge_text').val() || '#ffffff';
            var starCol      = $('#star_color').val() || '#f59e0b';
            var cardBorderCol= $('#card_border_color').val() || '#e2e8f0';

            var cardRadius   = $('#card_radius').val() || '14px';
            var cardBorderW  = $('#card_border_width').val() || '1px';
            var cardShadow   = $('#card_shadow').val() || '0 4px 20px -2px rgba(15, 23, 42, 0.06)';
            var buttonRadius = $('#button_radius').val() || '10px';
            var imageRadius  = $('#image_border_radius').val() || '10px';
            var imageRatio   = $('#image_ratio').val() || '1/1';
            var badgeStyle   = $('#badge_style').val() || 'pill';
            var btnStyle     = $('#add_to_cart_style').val() || 'modern_button';
            var fontFamily   = $('#font_family').val() || 'inherit';

            // Apply to mock card
            var $card = $('#wspMockCard');
            $card.css({
                'background-color': cardBg,
                'border-radius': cardRadius,
                'border': cardBorderW + ' solid ' + cardBorderCol,
                'box-shadow': cardShadow
            });

            if (fontFamily !== 'inherit') {
                $card.css('font-family', fontFamily);
            } else {
                $card.css('font-family', 'inherit');
            }

            // Image Container
            $('#wspMockImageContainer').css({
                'border-radius': imageRadius,
                'aspect-ratio': imageRatio === 'auto' ? '1/1' : imageRatio
            });

            // Badge
            var $badge = $('#wspMockBadge');
            $badge.css({
                'background-color': badgeBg,
                'color': badgeText
            });

            if (badgeStyle === 'pill') {
                $badge.css('border-radius', '999px');
            } else if (badgeStyle === 'ribbon') {
                $badge.css({ 'border-radius': '0 0 8px 0', 'top': '0', 'left': '0' });
            } else if (badgeStyle === 'square') {
                $badge.css('border-radius', '4px');
            } else if (badgeStyle === 'glass') {
                $badge.css({
                    'backdrop-filter': 'blur(8px)',
                    'background': 'rgba(15, 23, 42, 0.75)',
                    'border-radius': '6px'
                });
            }

            // Texts & Colors
            $('#wspMockTitle').css('color', headingCol);
            $('#wspMockCategory').css('color', accentCol);
            $('#wspMockRatings').css('color', starCol);
            $('#wspMockRegularPrice').css('color', priceCol);
            $('#wspMockSalePrice').css('color', salePriceCol);

            // Button
            var $btn = $('#wspMockBtn');
            $btn.css({
                'border-radius': buttonRadius
            });

            if (btnStyle === 'outline') {
                $btn.css({
                    'background': 'transparent',
                    'color': primaryCol,
                    'border': '1.5px solid ' + primaryCol,
                    'box-shadow': 'none'
                });
            } else if (btnStyle === 'soft_pill') {
                $btn.css({
                    'background': primaryCol,
                    'color': primaryText,
                    'border-radius': '999px',
                    'border': 'none'
                });
            } else {
                $btn.css({
                    'background': primaryCol,
                    'color': primaryText,
                    'border': 'none'
                });
            }
        },

        /**
         * 1-Click Preset Loading via AJAX
         */
        initPresetApplier: function() {
            var self = this;

            $('.wsp-apply-preset-btn').on('click', function(e) {
                e.preventDefault();
                var presetId = $(this).data('preset-id');
                var $btn     = $(this);
                var originalText = $btn.text();

                $btn.prop('disabled', true).text(wspAdminData.i18n.applying);

                $.ajax({
                    url: wspAdminData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wsp_apply_preset',
                        preset_id: presetId,
                        security: wspAdminData.nonce
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text(originalText);

                        if (response.success && response.data.settings) {
                            var settings = response.data.settings;

                            // Update active preset cards UI
                            $('.wsp-preset-card').removeClass('selected');
                            $('.wsp-preset-card[data-preset-id="' + presetId + '"]').addClass('selected');
                            $('#wsp_active_preset').val(presetId);

                            // Update form inputs & color pickers
                            $.each(settings, function(key, val) {
                                var $field = $('#' + key);
                                if ($field.length) {
                                    if ($field.hasClass('wsp-color-picker')) {
                                        $field.wpColorPicker('color', val);
                                    } else {
                                        $field.val(val);
                                    }
                                }
                            });

                            self.updateLivePreview();

                            // Show visual feedback notice
                            alert(response.data.message);
                        } else {
                            alert(response.data.message || wspAdminData.i18n.error);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text(originalText);
                        alert(wspAdminData.i18n.error);
                    }
                });
            });
        },

        /**
         * 1-Click Update Checker from GitHub
         */
        initUpdateChecker: function() {
            $('#wspCheckUpdateBtn').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $status = $('#wspUpdateStatus');
                var $icon = $btn.find('.dashicons-update');

                $btn.prop('disabled', true);
                $icon.addClass('wsp-spin');
                $status.html('<span class="wsp-status-msg wsp-status-loading"><span class="spinner is-active" style="float:none;margin:0 6px 0 0;"></span>' + 'Checking GitHub for updates...' + '</span>');

                $.ajax({
                    url: wspAdminData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wsp_check_github_update',
                        security: wspAdminData.nonce
                    },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $icon.removeClass('wsp-spin');

                        if (response.success && response.data) {
                            var data = response.data;
                            if (data.update_available) {
                                var html = '<div class="wsp-update-available-pill">';
                                html += '<span class="wsp-update-txt">🚀 <strong>Update Available: v' + data.new_version + '</strong></span> ';
                                html += '<a href="' + data.update_url + '" class="button button-primary wsp-update-now-btn">Update Now</a>';
                                html += '</div>';
                                $status.html(html);
                            } else {
                                $status.html('<span class="wsp-status-msg wsp-status-uptodate"><span class="dashicons dashicons-yes-alt"></span> ' + data.message + '</span>');
                            }
                        } else {
                            $status.html('<span class="wsp-status-msg wsp-status-error">' + (response.data && response.data.message ? response.data.message : 'Could not check for updates.') + '</span>');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        $icon.removeClass('wsp-spin');
                        $status.html('<span class="wsp-status-msg wsp-status-error">Error connecting to server. Please try again.</span>');
                    }
                });
            });
        }
    };

    $(document).ready(function() {
        WSPAdmin.init();
    });

})(jQuery);
