<?php
/**
 * Woo Style+ Admin Settings Class
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Admin {

    /**
     * Settings option group
     */
    const SETTINGS_GROUP = 'wsp_settings_group';

    /**
     * Settings option name
     */
    const OPTION_NAME = 'wsp_settings';

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'plugin_action_links_' . WSP_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
        add_action( 'wp_ajax_wsp_apply_preset', array( $this, 'ajax_apply_preset' ) );
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function add_admin_menu() {
        // Main Menu
        $main_page = add_menu_page(
            __( 'Woo Style+ Settings', 'woo-style-plus' ),
            __( 'Woo Style+', 'woo-style-plus' ),
            'manage_options',
            'woo-style-plus',
            array( $this, 'render_settings_page' ),
            'dashicons-art',
            58
        );

        // Submenu under WooCommerce as well for convenience
        add_submenu_page(
            'woocommerce',
            __( 'Woo Style+', 'woo-style-plus' ),
            __( 'Woo Style+', 'woo-style-plus' ),
            'manage_options',
            'woo-style-plus',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Add settings link to plugins list
     *
     * @param array $links
     * @return array
     */
    public function add_action_links( $links ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=woo-style-plus' ) ) . '">' . esc_html__( 'Settings', 'woo-style-plus' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Register plugin settings with WordPress Settings API
     */
    public function register_settings() {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            array(
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => WSP_Presets::get_default_settings(),
            )
        );
    }

    /**
     * Sanitize settings before saving
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings( $input ) {
        $defaults = WSP_Presets::get_default_settings();
        $output   = array();

        // Checkboxes & toggles
        $checkbox_keys = array(
            'is_enabled',
            'shop_filter_bar',
            'show_categories',
            'show_ratings',
            'show_sale_percentage',
            'single_product_redesign',
            'single_qty_stepper',
            'single_sticky_mobile_cta',
            'single_stock_badge',
            'cart_redesign',
            'checkout_redesign',
            'checkout_modern_inputs',
            'myaccount_redesign',
            'myaccount_show_user_header',
            'myaccount_order_badges',
        );

        foreach ( $checkbox_keys as $key ) {
            $output[ $key ] = ! empty( $input[ $key ] ) ? 'yes' : 'no';
        }

        // Color keys
        $color_keys = array(
            'primary_color',
            'primary_hover',
            'primary_text_color',
            'accent_color',
            'bg_color',
            'card_bg',
            'text_color',
            'heading_color',
            'price_color',
            'sale_price_color',
            'badge_bg',
            'badge_text',
            'star_color',
            'card_border_color',
            'border_color',
        );

        foreach ( $color_keys as $ckey ) {
            if ( ! empty( $input[ $ckey ] ) ) {
                $sanitized_color = sanitize_hex_color( $input[ $ckey ] );
                $output[ $ckey ] = $sanitized_color ? $sanitized_color : sanitize_text_field( $input[ $ckey ] );
            } else {
                $output[ $ckey ] = isset( $defaults[ $ckey ] ) ? $defaults[ $ckey ] : '';
            }
        }

        // Numeric or measurement keys
        $text_keys = array(
            'active_preset',
            'container_max_width',
            'override_priority',
            'font_family',
            'card_title_size',
            'card_price_size',
            'columns_desktop',
            'columns_tablet',
            'columns_mobile',
            'grid_gap',
            'shop_pagination_style',
            'card_layout',
            'card_radius',
            'card_border_width',
            'card_shadow',
            'card_hover_anim',
            'card_padding',
            'image_ratio',
            'image_border_radius',
            'badge_style',
            'badge_position',
            'badge_text_custom',
            'add_to_cart_style',
            'button_radius',
            'single_tabs_style',
            'cart_layout',
            'checkout_layout',
            'myaccount_nav_style',
        );

        foreach ( $text_keys as $tkey ) {
            if ( isset( $input[ $tkey ] ) ) {
                $output[ $tkey ] = sanitize_text_field( $input[ $tkey ] );
            } else {
                $output[ $tkey ] = isset( $defaults[ $tkey ] ) ? $defaults[ $tkey ] : '';
            }
        }

        // Custom CSS - strip harmful tags but preserve CSS
        if ( isset( $input['custom_css'] ) ) {
            $output['custom_css'] = wp_strip_all_tags( $input['custom_css'] );
        } else {
            $output['custom_css'] = '';
        }

        return $output;
    }

    /**
     * AJAX handler to load a preset instantly
     */
    public function ajax_apply_preset() {
        check_ajax_referer( 'wsp_admin_nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-style-plus' ) ) );
        }

        $preset_id = isset( $_POST['preset_id'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_id'] ) ) : '';
        $presets   = WSP_Presets::get_presets();

        if ( ! isset( $presets[ $preset_id ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid preset selected.', 'woo-style-plus' ) ) );
        }

        $current_settings = WSP_Core::get_settings();
        $updated_settings = WSP_Presets::apply_preset( $preset_id, $current_settings );

        update_option( self::OPTION_NAME, $updated_settings );

        wp_send_json_success( array(
            'message'  => sprintf( __( 'Preset "%s" applied successfully!', 'woo-style-plus' ), esc_html( $presets[ $preset_id ]['name'] ) ),
            'settings' => $updated_settings,
        ) );
    }

    /**
     * Enqueue admin CSS and JS
     *
     * @param string $hook
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_woo-style-plus' !== $hook && 'woocommerce_page_woo-style-plus' !== $hook ) {
            return;
        }

        // WordPress Color Picker
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // Google Fonts for font preview
        wp_enqueue_style( 'wsp-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap', array(), WSP_VERSION );

        // Admin CSS
        wp_enqueue_style(
            'wsp-admin-style',
            WSP_PLUGIN_URL . 'assets/css/wsp-admin.css',
            array( 'wp-color-picker' ),
            WSP_VERSION
        );

        // Admin JS
        wp_enqueue_script(
            'wsp-admin-script',
            WSP_PLUGIN_URL . 'assets/js/wsp-admin.js',
            array( 'jquery', 'wp-color-picker' ),
            WSP_VERSION,
            true
        );

        wp_localize_script( 'wsp-admin-script', 'wspAdminData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wsp_admin_nonce' ),
            'presets' => WSP_Presets::get_presets(),
            'i18n'    => array(
                'applying' => __( 'Applying preset...', 'woo-style-plus' ),
                'applied'  => __( 'Preset applied!', 'woo-style-plus' ),
                'error'    => __( 'Failed to apply preset.', 'woo-style-plus' ),
            ),
        ) );
    }

    /**
     * Render the admin settings page
     */
    public function render_settings_page() {
        $settings = WSP_Core::get_settings();
        $presets  = WSP_Presets::get_presets();
        ?>
        <div class="wrap wsp-settings-wrap">
            <!-- Header Banner -->
            <div class="wsp-header">
                <div class="wsp-header-brand">
                    <div class="wsp-logo-badge">
                        <span class="dashicons dashicons-art"></span>
                    </div>
                    <div>
                        <h1><?php esc_html_e( 'Woo Style+', 'woo-style-plus' ); ?> <span class="wsp-version-pill">v<?php echo esc_html( WSP_VERSION ); ?></span></h1>
                        <p class="wsp-header-tagline"><?php esc_html_e( 'Modern WooCommerce Redesign & Style Overrides for Shop, Cards, Single Product, Cart, Checkout & My Account.', 'woo-style-plus' ); ?></p>
                    </div>
                </div>
                <div class="wsp-header-actions">
                    <div class="wsp-quick-toggle">
                        <label class="wsp-switch">
                            <input type="checkbox" id="wsp_quick_enable_toggle" <?php checked( $settings['is_enabled'], 'yes' ); ?>>
                            <span class="wsp-slider round"></span>
                        </label>
                        <span class="wsp-toggle-label"><?php esc_html_e( 'Plugin Active', 'woo-style-plus' ); ?></span>
                    </div>
                </div>
            </div>

            <?php settings_errors(); ?>

            <form method="post" action="options.php" id="wsp-settings-form">
                <?php settings_fields( self::SETTINGS_GROUP ); ?>
                <input type="hidden" name="wsp_settings[is_enabled]" id="wsp_hidden_is_enabled" value="<?php echo esc_attr( $settings['is_enabled'] ); ?>">

                <div class="wsp-layout-container">
                    <!-- Left Column: Navigation Tabs & Settings Fields -->
                    <div class="wsp-main-col">
                        <!-- Navigation Tabs -->
                        <div class="wsp-nav-tabs">
                            <button type="button" class="wsp-tab-btn active" data-tab="tab-presets">
                                <span class="dashicons dashicons-layout"></span> <?php esc_html_e( 'Presets & Core', 'woo-style-plus' ); ?>
                            </button>
                            <button type="button" class="wsp-tab-btn" data-tab="tab-colors">
                                <span class="dashicons dashicons-color-picker"></span> <?php esc_html_e( 'Colors & Brand', 'woo-style-plus' ); ?>
                            </button>
                            <button type="button" class="wsp-tab-btn" data-tab="tab-cards">
                                <span class="dashicons dashicons-grid-view"></span> <?php esc_html_e( 'Shop & Product Cards', 'woo-style-plus' ); ?>
                            </button>
                            <button type="button" class="wsp-tab-btn" data-tab="tab-single">
                                <span class="dashicons dashicons-products"></span> <?php esc_html_e( 'Single Product', 'woo-style-plus' ); ?>
                            </button>
                            <button type="button" class="wsp-tab-btn" data-tab="tab-checkout">
                                <span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Cart & Checkout', 'woo-style-plus' ); ?>
                            </button>
                            <button type="button" class="wsp-tab-btn" data-tab="tab-account">
                                <span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'My Account', 'woo-style-plus' ); ?>
                            </button>
                            <button type="button" class="wsp-tab-btn" data-tab="tab-custom-css">
                                <span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'Custom CSS', 'woo-style-plus' ); ?>
                            </button>
                        </div>

                        <!-- Tab 1: Presets & Core -->
                        <div class="wsp-tab-panel active" id="tab-presets">
                            <div class="wsp-card-box">
                                <div class="wsp-box-header">
                                    <h2><?php esc_html_e( '1-Click Design Presets', 'woo-style-plus' ); ?></h2>
                                    <p><?php esc_html_e( 'Select a pre-designed modern theme. Clicking "Apply Preset" instantly styles all elements.', 'woo-style-plus' ); ?></p>
                                </div>
                                
                                <div class="wsp-presets-grid">
                                    <?php foreach ( $presets as $pid => $preset_data ) : ?>
                                        <div class="wsp-preset-card <?php echo ( $settings['active_preset'] === $pid ) ? 'selected' : ''; ?>" data-preset-id="<?php echo esc_attr( $pid ); ?>">
                                            <div class="wsp-preset-color-bars">
                                                <?php foreach ( $preset_data['preview_colors'] as $pcol ) : ?>
                                                    <span style="background-color: <?php echo esc_attr( $pcol ); ?>;"></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="wsp-preset-info">
                                                <h3><?php echo esc_html( $preset_data['name'] ); ?></h3>
                                                <p><?php echo esc_html( $preset_data['description'] ); ?></p>
                                            </div>
                                            <div class="wsp-preset-actions">
                                                <button type="button" class="button wsp-apply-preset-btn" data-preset-id="<?php echo esc_attr( $pid ); ?>">
                                                    <?php ( $settings['active_preset'] === $pid ) ? esc_html_e( 'Active Preset', 'woo-style-plus' ) : esc_html_e( 'Apply Preset', 'woo-style-plus' ); ?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="wsp_settings[active_preset]" id="wsp_active_preset" value="<?php echo esc_attr( $settings['active_preset'] ); ?>">
                            </div>

                            <div class="wsp-card-box">
                                <div class="wsp-box-header">
                                    <h2><?php esc_html_e( 'Theme Override & Compatibility', 'woo-style-plus' ); ?></h2>
                                    <p><?php esc_html_e( 'Ensure Woo Style+ cleanly takes precedence over your current theme without breaking layout containers.', 'woo-style-plus' ); ?></p>
                                </div>

                                <div class="wsp-form-row">
                                    <label for="override_priority"><?php esc_html_e( 'Override Priority Level', 'woo-style-plus' ); ?></label>
                                    <select name="wsp_settings[override_priority]" id="override_priority" class="wsp-select">
                                        <option value="high" <?php selected( $settings['override_priority'], 'high' ); ?>><?php esc_html_e( 'High Priority (Recommended - Overrides tough themes)', 'woo-style-plus' ); ?></option>
                                        <option value="standard" <?php selected( $settings['override_priority'], 'standard' ); ?>><?php esc_html_e( 'Standard Priority (Gentle overrides)', 'woo-style-plus' ); ?></option>
                                    </select>
                                    <span class="wsp-help-text"><?php esc_html_e( 'High priority ensures your button styles, card shadows, and grid gaps override aggressive themes like Astra, OceanWP, Divi, Storefront, etc.', 'woo-style-plus' ); ?></span>
                                </div>

                                <div class="wsp-form-row">
                                    <label for="font_family"><?php esc_html_e( 'Font Family', 'woo-style-plus' ); ?></label>
                                    <select name="wsp_settings[font_family]" id="font_family" class="wsp-select">
                                        <option value="inherit" <?php selected( $settings['font_family'], 'inherit' ); ?>><?php esc_html_e( 'Inherit from Current Theme', 'woo-style-plus' ); ?></option>
                                        <option value="'Inter', -apple-system, BlinkMacSystemFont, sans-serif" <?php selected( $settings['font_family'], "'Inter', -apple-system, BlinkMacSystemFont, sans-serif" ); ?>><?php esc_html_e( 'Inter (Clean & Modern Tech)', 'woo-style-plus' ); ?></option>
                                        <option value="'Poppins', sans-serif" <?php selected( $settings['font_family'], "'Poppins', sans-serif" ); ?>><?php esc_html_e( 'Poppins (Geometric & Trendy)', 'woo-style-plus' ); ?></option>
                                        <option value="'Roboto', sans-serif" <?php selected( $settings['font_family'], "'Roboto', sans-serif" ); ?>><?php esc_html_e( 'Roboto (Balanced & Neutral)', 'woo-style-plus' ); ?></option>
                                        <option value="'Playfair Display', serif" <?php selected( $settings['font_family'], "'Playfair Display', serif" ); ?>><?php esc_html_e( 'Playfair Display (Luxury & Boutique)', 'woo-style-plus' ); ?></option>
                                    </select>
                                </div>

                                <div class="wsp-form-row">
                                    <label for="container_max_width"><?php esc_html_e( 'WooCommerce Container Max Width', 'woo-style-plus' ); ?></label>
                                    <input type="text" name="wsp_settings[container_max_width]" id="container_max_width" class="wsp-input" value="<?php echo esc_attr( $settings['container_max_width'] ); ?>" placeholder="1280px">
                                    <span class="wsp-help-text"><?php esc_html_e( 'e.g. 1280px or 100% or 1400px. Keeps your shop and checkout sections centered with modern margins.', 'woo-style-plus' ); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Colors & Brand -->
                        <div class="wsp-tab-panel" id="tab-colors">
                            <div class="wsp-card-box">
                                <div class="wsp-box-header">
                                    <h2><?php esc_html_e( 'Color Palette & Typography', 'woo-style-plus' ); ?></h2>
                                    <p><?php esc_html_e( 'Define your brand colors. These variables seamlessly cascade through every WooCommerce element.', 'woo-style-plus' ); ?></p>
                                </div>

                                <div class="wsp-color-grid">
                                    <div class="wsp-color-field">
                                        <label for="primary_color"><?php esc_html_e( 'Primary Brand Color', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[primary_color]" id="primary_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['primary_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Buttons, badges, focus outlines', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="primary_hover"><?php esc_html_e( 'Primary Hover Color', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[primary_hover]" id="primary_hover" class="wsp-color-picker" value="<?php echo esc_attr( $settings['primary_hover'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Button hover state', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="primary_text_color"><?php esc_html_e( 'Primary Button Text', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[primary_text_color]" id="primary_text_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['primary_text_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Text inside primary buttons', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="accent_color"><?php esc_html_e( 'Accent / Link Color', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[accent_color]" id="accent_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['accent_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Secondary highlights & links', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="card_bg"><?php esc_html_e( 'Card Background', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[card_bg]" id="card_bg" class="wsp-color-picker" value="<?php echo esc_attr( $settings['card_bg'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Product card & summary box background', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="bg_color"><?php esc_html_e( 'Page Background (Optional)', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[bg_color]" id="bg_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['bg_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Leave blank to use theme background', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="heading_color"><?php esc_html_e( 'Heading Color', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[heading_color]" id="heading_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['heading_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Titles, h1, h2, h3, card titles', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="text_color"><?php esc_html_e( 'Body Text Color', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[text_color]" id="text_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['text_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Descriptions, labels, metadata', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="price_color"><?php esc_html_e( 'Regular Price Color', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[price_color]" id="price_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['price_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Product standard price tag', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="sale_price_color"><?php esc_html_e( 'Sale Price Color', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[sale_price_color]" id="sale_price_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['sale_price_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Discounted price highlight', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="badge_bg"><?php esc_html_e( 'Sale Badge Background', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[badge_bg]" id="badge_bg" class="wsp-color-picker" value="<?php echo esc_attr( $settings['badge_bg'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Sale & discount badge background', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="badge_text"><?php esc_html_e( 'Sale Badge Text', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[badge_text]" id="badge_text" class="wsp-color-picker" value="<?php echo esc_attr( $settings['badge_text'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Text inside sale badge', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="star_color"><?php esc_html_e( 'Star Rating Color', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[star_color]" id="star_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['star_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Customer review star icons', 'woo-style-plus' ); ?></span>
                                    </div>

                                    <div class="wsp-color-field">
                                        <label for="card_border_color"><?php esc_html_e( 'Border / Divider Color', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[card_border_color]" id="card_border_color" class="wsp-color-picker" value="<?php echo esc_attr( $settings['card_border_color'] ); ?>">
                                        <span class="wsp-color-desc"><?php esc_html_e( 'Card borders, inputs, tables', 'woo-style-plus' ); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Shop & Product Cards -->
                        <div class="wsp-tab-panel" id="tab-cards">
                            <div class="wsp-card-box">
                                <div class="wsp-box-header">
                                    <h2><?php esc_html_e( 'Grid Layout & Responsiveness', 'woo-style-plus' ); ?></h2>
                                    <p><?php esc_html_e( 'Applies to Shop page, category archives, and all WooCommerce shortcodes like [products].', 'woo-style-plus' ); ?></p>
                                </div>

                                <div class="wsp-form-grid-3">
                                    <div class="wsp-form-row">
                                        <label for="columns_desktop"><?php esc_html_e( 'Desktop Columns', 'woo-style-plus' ); ?></label>
                                        <select name="wsp_settings[columns_desktop]" id="columns_desktop" class="wsp-select">
                                            <option value="2" <?php selected( $settings['columns_desktop'], '2' ); ?>>2 Columns</option>
                                            <option value="3" <?php selected( $settings['columns_desktop'], '3' ); ?>>3 Columns</option>
                                            <option value="4" <?php selected( $settings['columns_desktop'], '4' ); ?>>4 Columns (Default)</option>
                                            <option value="5" <?php selected( $settings['columns_desktop'], '5' ); ?>>5 Columns</option>
                                            <option value="6" <?php selected( $settings['columns_desktop'], '6' ); ?>>6 Columns</option>
                                        </select>
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="columns_tablet"><?php esc_html_e( 'Tablet Columns', 'woo-style-plus' ); ?></label>
                                        <select name="wsp_settings[columns_tablet]" id="columns_tablet" class="wsp-select">
                                            <option value="2" <?php selected( $settings['columns_tablet'], '2' ); ?>>2 Columns (Default)</option>
                                            <option value="3" <?php selected( $settings['columns_tablet'], '3' ); ?>>3 Columns</option>
                                            <option value="1" <?php selected( $settings['columns_tablet'], '1' ); ?>>1 Column</option>
                                        </select>
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="columns_mobile"><?php esc_html_e( 'Mobile Columns', 'woo-style-plus' ); ?></label>
                                        <select name="wsp_settings[columns_mobile]" id="columns_mobile" class="wsp-select">
                                            <option value="1" <?php selected( $settings['columns_mobile'], '1' ); ?>>1 Column (Default)</option>
                                            <option value="2" <?php selected( $settings['columns_mobile'], '2' ); ?>>2 Columns</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="wsp-form-row">
                                    <label for="grid_gap"><?php esc_html_e( 'Grid Spacing Gap', 'woo-style-plus' ); ?></label>
                                    <input type="text" name="wsp_settings[grid_gap]" id="grid_gap" class="wsp-input" value="<?php echo esc_attr( $settings['grid_gap'] ); ?>" placeholder="28px">
                                    <span class="wsp-help-text"><?php esc_html_e( 'Spacing between cards (e.g. 24px, 28px, 32px)', 'woo-style-plus' ); ?></span>
                                </div>
                            </div>

                            <div class="wsp-card-box">
                                <div class="wsp-box-header">
                                    <h2><?php esc_html_e( 'Product Card Styling & Animation', 'woo-style-plus' ); ?></h2>
                                    <p><?php esc_html_e( 'Elevate each product card with modern shadows, borders, and smooth hover effects.', 'woo-style-plus' ); ?></p>
                                </div>

                                <div class="wsp-form-grid-2">
                                    <div class="wsp-form-row">
                                        <label for="card_radius"><?php esc_html_e( 'Card Border Radius', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[card_radius]" id="card_radius" class="wsp-input" value="<?php echo esc_attr( $settings['card_radius'] ); ?>" placeholder="14px">
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="card_border_width"><?php esc_html_e( 'Card Border Width', 'woo-style-plus' ); ?></label>
                                        <select name="wsp_settings[card_border_width]" id="card_border_width" class="wsp-select">
                                            <option value="0px" <?php selected( $settings['card_border_width'], '0px' ); ?>>0px (No Border)</option>
                                            <option value="1px" <?php selected( $settings['card_border_width'], '1px' ); ?>>1px (Subtle)</option>
                                            <option value="2px" <?php selected( $settings['card_border_width'], '2px' ); ?>>2px (Distinct)</option>
                                        </select>
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="card_shadow"><?php esc_html_e( 'Card Shadow Elevation', 'woo-style-plus' ); ?></label>
                                        <select name="wsp_settings[card_shadow]" id="card_shadow" class="wsp-select">
                                            <option value="none" <?php selected( $settings['card_shadow'], 'none' ); ?>><?php esc_html_e( 'Flat (No Shadow)', 'woo-style-plus' ); ?></option>
                                            <option value="0 2px 8px rgba(0, 0, 0, 0.04)" <?php selected( $settings['card_shadow'], '0 2px 8px rgba(0, 0, 0, 0.04)' ); ?>><?php esc_html_e( 'Subtle Light', 'woo-style-plus' ); ?></option>
                                            <option value="0 4px 20px -2px rgba(15, 23, 42, 0.06)" <?php selected( $settings['card_shadow'], '0 4px 20px -2px rgba(15, 23, 42, 0.06)' ); ?>><?php esc_html_e( 'Modern Soft (Default)', 'woo-style-plus' ); ?></option>
                                            <option value="0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)" <?php selected( $settings['card_shadow'], '0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)' ); ?>><?php esc_html_e( 'Elevated Floating', 'woo-style-plus' ); ?></option>
                                            <option value="0 8px 30px rgba(0, 0, 0, 0.5)" <?php selected( $settings['card_shadow'], '0 8px 30px rgba(0, 0, 0, 0.5)' ); ?>><?php esc_html_e( 'Dark Luxe Glow', 'woo-style-plus' ); ?></option>
                                        </select>
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="card_hover_anim"><?php esc_html_e( 'Card Hover Animation', 'woo-style-plus' ); ?></label>
                                        <select name="wsp_settings[card_hover_anim]" id="card_hover_anim" class="wsp-select">
                                            <option value="lift" <?php selected( $settings['card_hover_anim'], 'lift' ); ?>><?php esc_html_e( 'Smooth Lift Up (Default)', 'woo-style-plus' ); ?></option>
                                            <option value="zoom" <?php selected( $settings['card_hover_anim'], 'zoom' ); ?>><?php esc_html_e( 'Image Subtle Zoom', 'woo-style-plus' ); ?></option>
                                            <option value="lift_zoom" <?php selected( $settings['card_hover_anim'], 'lift_zoom' ); ?>><?php esc_html_e( 'Lift Up + Image Zoom', 'woo-style-plus' ); ?></option>
                                            <option value="glow" <?php selected( $settings['card_hover_anim'], 'glow' ); ?>><?php esc_html_e( 'Border & Shadow Glow', 'woo-style-plus' ); ?></option>
                                            <option value="scale" <?php selected( $settings['card_hover_anim'], 'scale' ); ?>><?php esc_html_e( 'Scale Up', 'woo-style-plus' ); ?></option>
                                            <option value="none" <?php selected( $settings['card_hover_anim'], 'none' ); ?>><?php esc_html_e( 'None (Static)', 'woo-style-plus' ); ?></option>
                                        </select>
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="image_ratio"><?php esc_html_e( 'Product Image Aspect Ratio', 'woo-style-plus' ); ?></label>
                                        <select name="wsp_settings[image_ratio]" id="image_ratio" class="wsp-select">
                                            <option value="1/1" <?php selected( $settings['image_ratio'], '1/1' ); ?>>1:1 Square (Recommended)</option>
                                            <option value="4/5" <?php selected( $settings['image_ratio'], '4/5' ); ?>>4:5 Portrait (Apparel & Fashion)</option>
                                            <option value="3/4" <?php selected( $settings['image_ratio'], '3/4' ); ?>>3:4 Tall</option>
                                            <option value="16/9" <?php selected( $settings['image_ratio'], '16/9' ); ?>>16:9 Landscape</option>
                                            <option value="auto" <?php selected( $settings['image_ratio'], 'auto' ); ?>>Original / Auto</option>
                                        </select>
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="image_border_radius"><?php esc_html_e( 'Image Border Radius', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[image_border_radius]" id="image_border_radius" class="wsp-input" value="<?php echo esc_attr( $settings['image_border_radius'] ); ?>" placeholder="10px">
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="badge_style"><?php esc_html_e( 'Sale Badge Style', 'woo-style-plus' ); ?></label>
                                        <select name="wsp_settings[badge_style]" id="badge_style" class="wsp-select">
                                            <option value="pill" <?php selected( $settings['badge_style'], 'pill' ); ?>><?php esc_html_e( 'Modern Rounded Pill', 'woo-style-plus' ); ?></option>
                                            <option value="ribbon" <?php selected( $settings['badge_style'], 'ribbon' ); ?>><?php esc_html_e( 'Corner Ribbon', 'woo-style-plus' ); ?></option>
                                            <option value="square" <?php selected( $settings['badge_style'], 'square' ); ?>><?php esc_html_e( 'Minimal Square', 'woo-style-plus' ); ?></option>
                                            <option value="glass" <?php selected( $settings['badge_style'], 'glass' ); ?>><?php esc_html_e( 'Glassmorphic Blur', 'woo-style-plus' ); ?></option>
                                        </select>
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="add_to_cart_style"><?php esc_html_e( 'Add to Cart Button Style', 'woo-style-plus' ); ?></label>
                                        <select name="wsp_settings[add_to_cart_style]" id="add_to_cart_style" class="wsp-select">
                                            <option value="modern_button" <?php selected( $settings['add_to_cart_style'], 'modern_button' ); ?>><?php esc_html_e( 'Modern Solid Button (Default)', 'woo-style-plus' ); ?></option>
                                            <option value="soft_pill" <?php selected( $settings['add_to_cart_style'], 'soft_pill' ); ?>><?php esc_html_e( 'Soft Pill Full-Width', 'woo-style-plus' ); ?></option>
                                            <option value="outline" <?php selected( $settings['add_to_cart_style'], 'outline' ); ?>><?php esc_html_e( 'Clean Outline / Ghost Button', 'woo-style-plus' ); ?></option>
                                        </select>
                                    </div>

                                    <div class="wsp-form-row">
                                        <label for="button_radius"><?php esc_html_e( 'Button Border Radius', 'woo-style-plus' ); ?></label>
                                        <input type="text" name="wsp_settings[button_radius]" id="button_radius" class="wsp-input" value="<?php echo esc_attr( $settings['button_radius'] ); ?>" placeholder="10px">
                                    </div>
                                </div>

                                <div class="wsp-toggles-list">
                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[show_sale_percentage]" value="yes" <?php checked( $settings['show_sale_percentage'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Display exact discount percentage on badge (e.g. -25% OFF)', 'woo-style-plus' ); ?></span>
                                    </label>
                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[show_categories]" value="yes" <?php checked( $settings['show_categories'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Show subtle category breadcrumb tag above product title', 'woo-style-plus' ); ?></span>
                                    </label>
                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[show_ratings]" value="yes" <?php checked( $settings['show_ratings'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Display star ratings on product cards', 'woo-style-plus' ); ?></span>
                                    </label>
                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[shop_filter_bar]" value="yes" <?php checked( $settings['shop_filter_bar'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Modernize Shop result count & ordering dropdown layout', 'woo-style-plus' ); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Single Product -->
                        <div class="wsp-tab-panel" id="tab-single">
                            <div class="wsp-card-box">
                                <div class="wsp-box-header">
                                    <h2><?php esc_html_e( 'Single Product Page Redesign', 'woo-style-plus' ); ?></h2>
                                    <p><?php esc_html_e( 'Transform your product details page with high-converting layouts, stepper controls, and clean tabs.', 'woo-style-plus' ); ?></p>
                                </div>

                                <div class="wsp-toggles-list">
                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[single_product_redesign]" value="yes" <?php checked( $settings['single_product_redesign'], 'yes' ); ?>>
                                        <span><strong><?php esc_html_e( 'Enable Single Product Modern Redesign', 'woo-style-plus' ); ?></strong></span>
                                    </label>

                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[single_qty_stepper]" value="yes" <?php checked( $settings['single_qty_stepper'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Modern Quantity Stepper with (+ / -) interactive buttons', 'woo-style-plus' ); ?></span>
                                    </label>

                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[single_stock_badge]" value="yes" <?php checked( $settings['single_stock_badge'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Modern In-Stock / Low-Stock pill badge indicator', 'woo-style-plus' ); ?></span>
                                    </label>

                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[single_sticky_mobile_cta]" value="yes" <?php checked( $settings['single_sticky_mobile_cta'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Sticky Add-to-Cart bar on Mobile when scrolling', 'woo-style-plus' ); ?></span>
                                    </label>
                                </div>

                                <div class="wsp-form-row" style="margin-top: 20px;">
                                    <label for="single_tabs_style"><?php esc_html_e( 'Product Information Tabs Style', 'woo-style-plus' ); ?></label>
                                    <select name="wsp_settings[single_tabs_style]" id="single_tabs_style" class="wsp-select">
                                        <option value="modern_pill" <?php selected( $settings['single_tabs_style'], 'modern_pill' ); ?>><?php esc_html_e( 'Modern Pill Tabs (Card Style)', 'woo-style-plus' ); ?></option>
                                        <option value="clean_underline" <?php selected( $settings['single_tabs_style'], 'clean_underline' ); ?>><?php esc_html_e( 'Clean Underline Tabs', 'woo-style-plus' ); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 5: Cart & Checkout -->
                        <div class="wsp-tab-panel" id="tab-checkout">
                            <div class="wsp-card-box">
                                <div class="wsp-box-header">
                                    <h2><?php esc_html_e( 'Cart & Checkout Redesign', 'woo-style-plus' ); ?></h2>
                                    <p><?php esc_html_e( 'Create a seamless, distraction-free checkout flow that builds trust and boosts conversion.', 'woo-style-plus' ); ?></p>
                                </div>

                                <div class="wsp-toggles-list">
                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[cart_redesign]" value="yes" <?php checked( $settings['cart_redesign'], 'yes' ); ?>>
                                        <span><strong><?php esc_html_e( 'Enable Cart Redesign (Modern 2-column layout with sticky totals)', 'woo-style-plus' ); ?></strong></span>
                                    </label>

                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[checkout_redesign]" value="yes" <?php checked( $settings['checkout_redesign'], 'yes' ); ?>>
                                        <span><strong><?php esc_html_e( 'Enable Checkout Redesign (Clean 2-column billing & order card)', 'woo-style-plus' ); ?></strong></span>
                                    </label>

                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[checkout_modern_inputs]" value="yes" <?php checked( $settings['checkout_modern_inputs'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Modern Form Fields (Rounded borders, focus glow, sleek payment method cards)', 'woo-style-plus' ); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 6: My Account -->
                        <div class="wsp-tab-panel" id="tab-account">
                            <div class="wsp-card-box">
                                <div class="wsp-box-header">
                                    <h2><?php esc_html_e( 'My Account Page Redesign', 'woo-style-plus' ); ?></h2>
                                    <p><?php esc_html_e( 'Elevate customer portal experience: modern login/register forms, sleek sidebar navigation cards, user greetings, and status badges.', 'woo-style-plus' ); ?></p>
                                </div>

                                <div class="wsp-toggles-list">
                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[myaccount_redesign]" value="yes" <?php checked( $settings['myaccount_redesign'], 'yes' ); ?>>
                                        <span><strong><?php esc_html_e( 'Enable My Account Redesign (Modern portal cards, login forms & order tables)', 'woo-style-plus' ); ?></strong></span>
                                    </label>

                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[myaccount_show_user_header]" value="yes" <?php checked( $settings['myaccount_show_user_header'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Show Modern Welcome Banner with Customer Avatar & Greeting', 'woo-style-plus' ); ?></span>
                                    </label>

                                    <label class="wsp-checkbox-item">
                                        <input type="checkbox" name="wsp_settings[myaccount_order_badges]" value="yes" <?php checked( $settings['myaccount_order_badges'], 'yes' ); ?>>
                                        <span><?php esc_html_e( 'Show Colored Status Pill Badges in Orders Table (Completed, Processing, On Hold, Cancelled)', 'woo-style-plus' ); ?></span>
                                    </label>
                                </div>

                                <div class="wsp-form-row" style="margin-top: 24px;">
                                    <label for="myaccount_nav_style"><?php esc_html_e( 'Account Navigation Menu Style', 'woo-style-plus' ); ?></label>
                                    <select name="wsp_settings[myaccount_nav_style]" id="myaccount_nav_style" class="wsp-select">
                                        <option value="sidebar_cards" <?php selected( $settings['myaccount_nav_style'], 'sidebar_cards' ); ?>><?php esc_html_e( 'Vertical Sidebar Cards with Active Fill (Recommended)', 'woo-style-plus' ); ?></option>
                                        <option value="horizontal_pills" <?php selected( $settings['myaccount_nav_style'], 'horizontal_pills' ); ?>><?php esc_html_e( 'Horizontal Navigation Pills', 'woo-style-plus' ); ?></option>
                                    </select>
                                    <span class="wsp-help-text"><?php esc_html_e( 'Vertical sidebar cards place navigation neatly beside dashboard content on desktop and collapses gracefully on mobile.', 'woo-style-plus' ); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 7: Custom CSS -->
                        <div class="wsp-tab-panel" id="tab-custom-css">
                            <div class="wsp-card-box">
                                <div class="wsp-box-header">
                                    <h2><?php esc_html_e( 'Custom CSS Overrides', 'woo-style-plus' ); ?></h2>
                                    <p><?php esc_html_e( 'Write custom CSS to target any theme-specific quirks. All CSS Custom Properties (--wsp-primary, --wsp-card-bg, etc.) are available here.', 'woo-style-plus' ); ?></p>
                                </div>

                                <div class="wsp-form-row">
                                    <textarea name="wsp_settings[custom_css]" id="wsp_custom_css" rows="12" class="wsp-textarea-code" placeholder="/* Custom CSS */
.woocommerce ul.products li.product {
    /* Your additional styles */
}"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Save Button -->
                        <div class="wsp-footer-save">
                            <?php submit_button( __( 'Save All Changes', 'woo-style-plus' ), 'primary large', 'submit', false ); ?>
                            <span class="wsp-save-hint"><?php esc_html_e( 'Changes are automatically injected via scoped CSS variables without modifying database products.', 'woo-style-plus' ); ?></span>
                        </div>
                    </div>

                    <!-- Right Column: Live Interactive Preview -->
                    <div class="wsp-preview-col">
                        <div class="wsp-preview-card-sticky">
                            <div class="wsp-preview-header">
                                <h3><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Live Card Preview', 'woo-style-plus' ); ?></h3>
                                <span class="wsp-preview-badge"><?php esc_html_e( 'Real-time', 'woo-style-plus' ); ?></span>
                            </div>

                            <div class="wsp-preview-body">
                                <div class="wsp-mock-card-wrapper" id="wspMockWrapper">
                                    <div class="wsp-mock-card" id="wspMockCard">
                                        <div class="wsp-mock-badge" id="wspMockBadge"><?php echo ( 'yes' === $settings['show_sale_percentage'] ) ? '-30% OFF' : 'SALE'; ?></div>
                                        <div class="wsp-mock-image-container" id="wspMockImageContainer">
                                            <div class="wsp-mock-image-placeholder">
                                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                    <polyline points="21 15 16 10 5 21"></polyline>
                                                </svg>
                                                <span>Product Image</span>
                                            </div>
                                        </div>
                                        <div class="wsp-mock-content">
                                            <div class="wsp-mock-category" id="wspMockCategory">Premium Lifestyle</div>
                                            <h4 class="wsp-mock-title" id="wspMockTitle">Minimalist Noise-Cancelling Headphones</h4>
                                            <div class="wsp-mock-ratings" id="wspMockRatings">
                                                ★★★★★ <span class="wsp-mock-rating-count">(4.9)</span>
                                            </div>
                                            <div class="wsp-mock-price-wrap">
                                                <span class="wsp-mock-regular-price" id="wspMockRegularPrice">$199.00</span>
                                                <span class="wsp-mock-sale-price" id="wspMockSalePrice">$139.00</span>
                                            </div>
                                            <button type="button" class="wsp-mock-btn" id="wspMockBtn">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;">
                                                    <circle cx="9" cy="21" r="1"></circle>
                                                    <circle cx="20" cy="21" r="1"></circle>
                                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                                </svg>
                                                Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="wsp-preview-footer">
                                <p><small><?php esc_html_e( 'Hover over the preview card above to test animations and shadows.', 'woo-style-plus' ); ?></small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}
