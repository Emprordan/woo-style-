<?php
/**
 * Woo Style+ Presets and Default Settings Manager
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Presets {

    /**
     * Get all available design presets
     *
     * @return array
     */
    public static function get_presets() {
        return array(
            'modern_minimal' => array(
                'id'          => 'modern_minimal',
                'name'        => __( 'Modern Minimal', 'woo-style-plus' ),
                'description' => __( 'Clean white aesthetic, crisp slate accents, airy spacing, and subtle elevation.', 'woo-style-plus' ),
                'preview_colors' => array( '#0f172a', '#3b82f6', '#f8fafc', '#ffffff' ),
                'settings'    => array(
                    'primary_color'       => '#0f172a',
                    'primary_hover'       => '#1e293b',
                    'accent_color'        => '#3b82f6',
                    'bg_color'            => '#f8fafc',
                    'card_bg'             => '#ffffff',
                    'text_color'          => '#334155',
                    'heading_color'       => '#0f172a',
                    'price_color'         => '#0f172a',
                    'sale_price_color'    => '#e11d48',
                    'badge_bg'            => '#0f172a',
                    'badge_text'          => '#ffffff',
                    'star_color'          => '#f59e0b',
                    'card_radius'         => '14px',
                    'card_border_width'   => '1px',
                    'card_border_color'   => '#e2e8f0',
                    'card_shadow'         => '0 4px 20px -2px rgba(15, 23, 42, 0.06)',
                    'card_hover_anim'     => 'lift',
                    'button_radius'       => '10px',
                    'badge_style'         => 'pill',
                    'image_ratio'         => '1/1',
                    'grid_gap'            => '28px',
                ),
            ),
            'dark_luxe' => array(
                'id'          => 'dark_luxe',
                'name'        => __( 'Dark Luxe', 'woo-style-plus' ),
                'description' => __( 'Deep obsidian background, luminous gold highlights, and sleek dark cards.', 'woo-style-plus' ),
                'preview_colors' => array( '#d4af37', '#17171c', '#0d0d11', '#ffffff' ),
                'settings'    => array(
                    'primary_color'       => '#d4af37',
                    'primary_hover'       => '#b89628',
                    'accent_color'        => '#f59e0b',
                    'bg_color'            => '#0d0d11',
                    'card_bg'             => '#18181f',
                    'text_color'          => '#94a3b8',
                    'heading_color'       => '#f8fafc',
                    'price_color'         => '#f8fafc',
                    'sale_price_color'    => '#d4af37',
                    'badge_bg'            => '#d4af37',
                    'badge_text'          => '#0d0d11',
                    'star_color'          => '#d4af37',
                    'card_radius'         => '16px',
                    'card_border_width'   => '1px',
                    'card_border_color'   => 'rgba(212, 175, 55, 0.25)',
                    'card_shadow'         => '0 8px 30px rgba(0, 0, 0, 0.5)',
                    'card_hover_anim'     => 'glow',
                    'button_radius'       => '30px',
                    'badge_style'         => 'ribbon',
                    'image_ratio'         => '1/1',
                    'grid_gap'            => '30px',
                ),
            ),
            'nordic_clean' => array(
                'id'          => 'nordic_clean',
                'name'        => __( 'Nordic Clean', 'woo-style-plus' ),
                'description' => __( 'Calm pastel tones, emerald green accents, rounded organic curves, and soft shadows.', 'woo-style-plus' ),
                'preview_colors' => array( '#059669', '#10b981', '#f0fdf4', '#ffffff' ),
                'settings'    => array(
                    'primary_color'       => '#059669',
                    'primary_hover'       => '#047857',
                    'accent_color'        => '#10b981',
                    'bg_color'            => '#f8fafc',
                    'card_bg'             => '#ffffff',
                    'text_color'          => '#475569',
                    'heading_color'       => '#064e3b',
                    'price_color'         => '#064e3b',
                    'sale_price_color'    => '#dc2626',
                    'badge_bg'            => '#10b981',
                    'badge_text'          => '#ffffff',
                    'star_color'          => '#fbbf24',
                    'card_radius'         => '18px',
                    'card_border_width'   => '1px',
                    'card_border_color'   => '#e2e8f0',
                    'card_shadow'         => '0 10px 25px -5px rgba(5, 150, 105, 0.08)',
                    'card_hover_anim'     => 'lift_zoom',
                    'button_radius'       => '12px',
                    'badge_style'         => 'pill',
                    'image_ratio'         => '4/5',
                    'grid_gap'            => '26px',
                ),
            ),
            'sunset_vibrant' => array(
                'id'          => 'sunset_vibrant',
                'name'        => __( 'Sunset Vibrant', 'woo-style-plus' ),
                'description' => __( 'Energetic gradient coral and violet accents, punchy badges, and modern ecommerce punch.', 'woo-style-plus' ),
                'preview_colors' => array( '#ff4757', '#7c3aed', '#fff7ed', '#ffffff' ),
                'settings'    => array(
                    'primary_color'       => '#ff4757',
                    'primary_hover'       => '#ee3344',
                    'accent_color'        => '#7c3aed',
                    'bg_color'            => '#fffaf5',
                    'card_bg'             => '#ffffff',
                    'text_color'          => '#374151',
                    'heading_color'       => '#1f2937',
                    'price_color'         => '#1f2937',
                    'sale_price_color'    => '#ff4757',
                    'badge_bg'            => '#ff4757',
                    'badge_text'          => '#ffffff',
                    'star_color'          => '#f59e0b',
                    'card_radius'         => '16px',
                    'card_border_width'   => '1px',
                    'card_border_color'   => '#fed7aa',
                    'card_shadow'         => '0 10px 25px -3px rgba(255, 71, 87, 0.12)',
                    'card_hover_anim'     => 'lift',
                    'button_radius'       => '999px',
                    'badge_style'         => 'pill',
                    'image_ratio'         => '1/1',
                    'grid_gap'            => '28px',
                ),
            ),
            'soft_neomorphic' => array(
                'id'          => 'soft_neomorphic',
                'name'        => __( 'Soft Neomorphic', 'woo-style-plus' ),
                'description' => __( 'Smooth tactile soft-drop shadows, velvety rounded surfaces, and pastel depth.', 'woo-style-plus' ),
                'preview_colors' => array( '#4f46e5', '#6366f1', '#f3f4f6', '#ffffff' ),
                'settings'    => array(
                    'primary_color'       => '#4f46e5',
                    'primary_hover'       => '#4338ca',
                    'accent_color'        => '#06b6d4',
                    'bg_color'            => '#f3f4f6',
                    'card_bg'             => '#ffffff',
                    'text_color'          => '#4b5563',
                    'heading_color'       => '#111827',
                    'price_color'         => '#111827',
                    'sale_price_color'    => '#e11d48',
                    'badge_bg'            => '#4f46e5',
                    'badge_text'          => '#ffffff',
                    'star_color'          => '#f59e0b',
                    'card_radius'         => '20px',
                    'card_border_width'   => '0px',
                    'card_border_color'   => 'transparent',
                    'card_shadow'         => '6px 6px 18px #d9dbe0, -6px -6px 18px #ffffff',
                    'card_hover_anim'     => 'scale',
                    'button_radius'       => '14px',
                    'badge_style'         => 'pill',
                    'image_ratio'         => '1/1',
                    'grid_gap'            => '32px',
                ),
            ),
        );
    }

    /**
     * Get default settings for fresh installation
     *
     * @return array
     */
    public static function get_default_settings() {
        return array(
            // General
            'is_enabled'              => 'yes',
            'active_preset'           => 'modern_minimal',
            'container_max_width'     => '1280px',
            'override_priority'       => 'high', // standard or high

            // Page Spacing & Paddings
            'page_padding_top'        => '40px',
            'page_padding_bottom'     => '60px',
            'page_padding_horizontal' => '20px',
            'shop_padding_top'        => '',
            'shop_padding_bottom'     => '',
            'single_padding_top'      => '',
            'single_padding_bottom'   => '',
            'cart_padding_top'        => '',
            'cart_padding_bottom'     => '',
            'checkout_padding_top'    => '',
            'checkout_padding_bottom' => '',
            'account_padding_top'     => '',
            'account_padding_bottom'  => '',

            // Colors
            'primary_color'           => '#0f172a',
            'primary_hover'           => '#1e293b',
            'primary_text_color'      => '#ffffff',
            'accent_color'            => '#3b82f6',
            'bg_color'                => '', // empty means inherit theme
            'card_bg'                 => '#ffffff',
            'text_color'              => '#334155',
            'heading_color'           => '#0f172a',
            'price_color'             => '#0f172a',
            'sale_price_color'        => '#e11d48',
            'badge_bg'                => '#0f172a',
            'badge_text'              => '#ffffff',
            'star_color'              => '#f59e0b',
            'border_color'            => '#e2e8f0',

            // Typography
            'font_family'             => 'inherit', // inherit, inter, poppins, roboto, playfair
            'card_title_size'         => '16px',
            'card_price_size'         => '17px',

            // Grid & Shop Layout
            'columns_desktop'         => '4',
            'columns_tablet'          => '2',
            'columns_mobile'          => '2',
            'grid_gap'                => '28px',
            'shop_filter_bar'         => 'yes',
            'shop_pagination_style'   => 'modern_pills',

            // Product Card
            'card_layout'             => 'standard', // standard, compact, inset
            'card_radius'             => '14px',
            'card_border_width'       => '1px',
            'card_border_color'       => '#e2e8f0',
            'card_shadow'             => '0 4px 20px -2px rgba(15, 23, 42, 0.06)',
            'card_hover_anim'         => 'lift', // none, lift, zoom, lift_zoom, glow, scale
            'card_padding'            => '16px',
            'image_ratio'             => '1/1', // 1/1, 4/5, 3/4, 16/9, auto
            'image_border_radius'     => '10px',
            'show_categories'         => 'yes',
            'show_ratings'            => 'yes',
            'show_sale_percentage'    => 'yes',
            'badge_style'             => 'pill', // pill, ribbon, square, glass
            'badge_position'          => 'top_left', // top_left, top_right
            'badge_text_custom'       => '', // e.g. "SALE" or "HOT"
            'add_to_cart_style'       => 'modern_button', // modern_button, icon_only, outline, soft_pill
            'button_radius'           => '10px',

            // Single Product Page
            'single_product_redesign' => 'yes',
            'single_qty_stepper'      => 'yes',
            'single_tabs_style'       => 'modern_pill', // modern_pill, clean_underline, accordion
            'single_sticky_mobile_cta'=> 'yes',
            'single_stock_badge'      => 'yes',

            // Cart & Checkout
            'cart_redesign'           => 'yes',
            'cart_auto_update'        => 'yes',
            'cart_layout'             => 'two_columns',
            'checkout_redesign'       => 'yes',
            'checkout_embedded_coupon' => 'yes',
            'checkout_layout'         => 'two_columns',
            'checkout_modern_inputs'  => 'yes',
            'shipping_methods_style'  => 'dropdown', // dropdown, cards

            // Side Cart Drawer & Elementor Widget
            'side_cart_enabled'       => 'yes',
            'side_cart_auto_open'     => 'yes',
            'side_cart_position'      => 'right', // right, left
            'side_cart_free_shipping' => 'yes',
            'side_cart_fs_threshold'  => '50000',
            'side_cart_title'         => 'Shopping Cart',

            // My Account Page
            'myaccount_redesign'          => 'yes',
            'myaccount_nav_style'         => 'sidebar_cards', // sidebar_cards, horizontal_pills
            'myaccount_show_user_header'  => 'yes',
            'myaccount_order_badges'      => 'yes',
            'myaccount_nav_icons'         => 'yes',

            // Custom CSS
            'custom_css'              => '',
        );
    }

    /**
     * Apply preset settings onto existing options
     *
     * @param string $preset_id
     * @param array  $current_settings
     * @return array
     */
    public static function apply_preset( $preset_id, $current_settings = array() ) {
        $presets = self::get_presets();
        if ( ! isset( $presets[ $preset_id ] ) ) {
            return $current_settings;
        }

        $preset = $presets[ $preset_id ];
        $merged = wp_parse_args( $preset['settings'], $current_settings );
        $merged['active_preset'] = $preset_id;

        return $merged;
    }
}
