<?php
/**
 * Woo Style+ Frontend Asset & Style Injection Handler
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Frontend {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 20 );
        add_filter( 'body_class', array( $this, 'add_body_classes' ) );
        add_filter( 'post_class', array( $this, 'add_product_card_classes' ), 10, 3 );
        add_filter( 'loop_shop_columns', array( $this, 'filter_loop_shop_columns' ), 999 );
        add_filter( 'woocommerce_loop_columns', array( $this, 'filter_loop_shop_columns' ), 999 );
    }

    /**
     * Check if plugin is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        return 'yes' === WSP_Core::get_setting( 'is_enabled', 'yes' );
    }

    /**
     * Check if we should load Woo Style+ assets on current page
     *
     * @return bool
     */
    public function should_load_assets() {
        if ( ! $this->is_enabled() ) {
            return false;
        }

        // Always load on WooCommerce standard pages
        if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
            return true;
        }
        if ( function_exists( 'is_product' ) && is_product() ) {
            return true;
        }
        if ( function_exists( 'is_cart' ) && is_cart() ) {
            return true;
        }
        if ( function_exists( 'is_checkout' ) && is_checkout() ) {
            return true;
        }
        if ( function_exists( 'is_account_page' ) && is_account_page() ) {
            return true;
        }

        // Check for WooCommerce shortcodes in post content
        global $post;
        if ( is_a( $post, 'WP_Post' ) ) {
            $shortcodes = array(
                'products',
                'recent_products',
                'sale_products',
                'best_selling_products',
                'top_rated_products',
                'featured_products',
                'product_category',
                'product_categories',
                'product_attribute',
                'woocommerce_cart',
                'woocommerce_checkout',
                'woocommerce_order_tracking',
                'woocommerce_my_account',
                'wsp_custom_shop',
                'woo_style_custom_shop',
                'woo_style_shop',
            );

            foreach ( $shortcodes as $shortcode ) {
                if ( has_shortcode( $post->post_content, $shortcode ) ) {
                    return true;
                }
            }

            // Check for WooCommerce Gutenberg blocks
            if ( function_exists( 'has_block' ) ) {
                $blocks = array(
                    'woocommerce/all-products',
                    'woocommerce/featured-product',
                    'woocommerce/handpicked-products',
                    'woocommerce/product-best-sellers',
                    'woocommerce/product-top-rated',
                    'woocommerce/product-on-sale',
                    'woocommerce/product-new',
                    'woocommerce/product-category',
                    'woocommerce/cart',
                    'woocommerce/checkout',
                );

                foreach ( $blocks as $block ) {
                    if ( has_block( $block, $post ) ) {
                        return true;
                    }
                }
            }
        }

        // Allow theme/plugin filtering for custom landing pages
        return apply_filters( 'wsp_should_load_assets', true );
    }

    /**
     * Enqueue frontend CSS and JavaScript
     */
    public function enqueue_frontend_assets() {
        if ( ! $this->should_load_assets() ) {
            return;
        }

        $settings = WSP_Core::get_settings();

        // Enqueue Google Fonts if selected
        $font_family = $settings['font_family'];
        if ( 'inherit' !== $font_family ) {
            wp_enqueue_style(
                'wsp-google-fonts',
                'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap',
                array(),
                WSP_VERSION
            );
        }

        // Core Frontend CSS
        wp_enqueue_style(
            'wsp-frontend-style',
            WSP_PLUGIN_URL . 'assets/css/wsp-frontend.css',
            array(),
            WSP_VERSION
        );

        // Dynamic Scoped CSS Variables & Settings
        $dynamic_css = $this->generate_dynamic_css( $settings );
        wp_add_inline_style( 'wsp-frontend-style', $dynamic_css );

        // Frontend JS
        wp_enqueue_script(
            'wsp-frontend-script',
            WSP_PLUGIN_URL . 'assets/js/wsp-frontend.js',
            array( 'jquery' ),
            WSP_VERSION,
            true
        );

        wp_localize_script( 'wsp-frontend-script', 'wspFrontendData', array(
            'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
            'nonce'                => wp_create_nonce( 'wsp_custom_shop_nonce' ),
            'qtyStepper'           => ( 'yes' === $settings['single_qty_stepper'] ),
            'stickyMobileCTA'      => ( 'yes' === $settings['single_sticky_mobile_cta'] ),
            'cartRedesign'         => ( 'yes' === $settings['cart_redesign'] ),
            'cartAutoUpdate'       => ( 'yes' === WSP_Core::get_setting( 'cart_auto_update', 'yes' ) ),
            'checkoutRedesign'       => ( 'yes' === $settings['checkout_redesign'] ),
            'checkoutEmbeddedCoupon' => ( 'yes' === WSP_Core::get_setting( 'checkout_embedded_coupon', 'yes' ) ),
            'shippingMethodsStyle'   => isset( $settings['shipping_methods_style'] ) ? $settings['shipping_methods_style'] : 'dropdown',
        ) );
    }

    /**
     * Generate dynamic inline CSS from saved settings
     *
     * @param array $settings
     * @return string
     */
    public function generate_dynamic_css( $settings ) {
        $primary          = ! empty( $settings['primary_color'] ) ? $settings['primary_color'] : '#0f172a';
        $primary_hover    = ! empty( $settings['primary_hover'] ) ? $settings['primary_hover'] : '#1e293b';
        $primary_text     = ! empty( $settings['primary_text_color'] ) ? $settings['primary_text_color'] : '#ffffff';
        $accent           = ! empty( $settings['accent_color'] ) ? $settings['accent_color'] : '#3b82f6';
        $bg_color         = ! empty( $settings['bg_color'] ) ? $settings['bg_color'] : 'transparent';
        $card_bg          = ! empty( $settings['card_bg'] ) ? $settings['card_bg'] : '#ffffff';
        $text_color       = ! empty( $settings['text_color'] ) ? $settings['text_color'] : '#334155';
        $heading_color    = ! empty( $settings['heading_color'] ) ? $settings['heading_color'] : '#0f172a';
        $price_color      = ! empty( $settings['price_color'] ) ? $settings['price_color'] : '#0f172a';
        $sale_price_color = ! empty( $settings['sale_price_color'] ) ? $settings['sale_price_color'] : '#e11d48';
        $badge_bg         = ! empty( $settings['badge_bg'] ) ? $settings['badge_bg'] : '#0f172a';
        $badge_text       = ! empty( $settings['badge_text'] ) ? $settings['badge_text'] : '#ffffff';
        $star_color       = ! empty( $settings['star_color'] ) ? $settings['star_color'] : '#f59e0b';
        $card_border_col  = ! empty( $settings['card_border_color'] ) ? $settings['card_border_color'] : '#e2e8f0';

        $card_radius      = ! empty( $settings['card_radius'] ) ? $settings['card_radius'] : '14px';
        $card_border_w    = isset( $settings['card_border_width'] ) ? $settings['card_border_width'] : '1px';
        $card_shadow      = ! empty( $settings['card_shadow'] ) ? $settings['card_shadow'] : '0 4px 20px -2px rgba(15, 23, 42, 0.06)';
        $card_padding     = ! empty( $settings['card_padding'] ) ? $settings['card_padding'] : '16px';
        $button_radius    = ! empty( $settings['button_radius'] ) ? $settings['button_radius'] : '10px';
        $grid_gap         = ! empty( $settings['grid_gap'] ) ? $settings['grid_gap'] : '28px';
        $container_max_w  = ! empty( $settings['container_max_width'] ) ? $settings['container_max_width'] : '1280px';
        $image_radius     = ! empty( $settings['image_border_radius'] ) ? $settings['image_border_radius'] : '10px';
        $font_family      = ! empty( $settings['font_family'] ) ? $settings['font_family'] : 'inherit';

        $cols_desktop     = ! empty( $settings['columns_desktop'] ) ? intval( $settings['columns_desktop'] ) : 4;
        $cols_tablet      = ! empty( $settings['columns_tablet'] ) ? intval( $settings['columns_tablet'] ) : 2;
        $cols_mobile      = ! empty( $settings['columns_mobile'] ) ? intval( $settings['columns_mobile'] ) : 2;

        $image_ratio      = ! empty( $settings['image_ratio'] ) ? $settings['image_ratio'] : '1/1';

        $format_dim = function( $val, $default ) {
            $val = trim( (string) $val );
            if ( '' === $val ) {
                return $default;
            }
            if ( is_numeric( $val ) ) {
                return $val . 'px';
            }
            return $val;
        };

        $page_pad_top        = $format_dim( isset( $settings['page_padding_top'] ) ? $settings['page_padding_top'] : '', '40px' );
        $page_pad_bottom     = $format_dim( isset( $settings['page_padding_bottom'] ) ? $settings['page_padding_bottom'] : '', '60px' );
        $page_pad_horiz      = $format_dim( isset( $settings['page_padding_horizontal'] ) ? $settings['page_padding_horizontal'] : '', '20px' );

        $shop_pad_top        = $format_dim( isset( $settings['shop_padding_top'] ) ? $settings['shop_padding_top'] : '', $page_pad_top );
        $shop_pad_bottom     = $format_dim( isset( $settings['shop_padding_bottom'] ) ? $settings['shop_padding_bottom'] : '', $page_pad_bottom );

        $single_pad_top      = $format_dim( isset( $settings['single_padding_top'] ) ? $settings['single_padding_top'] : '', $page_pad_top );
        $single_pad_bottom   = $format_dim( isset( $settings['single_padding_bottom'] ) ? $settings['single_padding_bottom'] : '', $page_pad_bottom );

        $cart_pad_top        = $format_dim( ( isset( $settings['cart_padding_top'] ) && '' !== $settings['cart_padding_top'] ) ? $settings['cart_padding_top'] : '', '20px' );
        $cart_pad_bottom     = $format_dim( isset( $settings['cart_padding_bottom'] ) ? $settings['cart_padding_bottom'] : '', $page_pad_bottom );

        $checkout_pad_top    = $format_dim( ( isset( $settings['checkout_padding_top'] ) && '' !== $settings['checkout_padding_top'] ) ? $settings['checkout_padding_top'] : '', '20px' );
        $checkout_pad_bottom = $format_dim( isset( $settings['checkout_padding_bottom'] ) ? $settings['checkout_padding_bottom'] : '', $page_pad_bottom );

        $account_pad_top     = $format_dim( isset( $settings['account_padding_top'] ) ? $settings['account_padding_top'] : '', $page_pad_top );
        $account_pad_bottom  = $format_dim( isset( $settings['account_padding_bottom'] ) ? $settings['account_padding_bottom'] : '', $page_pad_bottom );

        $css = "
        :root {
            --wsp-primary: {$primary};
            --wsp-primary-hover: {$primary_hover};
            --wsp-primary-text: {$primary_text};
            --wsp-accent: {$accent};
            --wsp-bg: {$bg_color};
            --wsp-card-bg: {$card_bg};
            --wsp-text: {$text_color};
            --wsp-heading: {$heading_color};
            --wsp-price: {$price_color};
            --wsp-sale-price: {$sale_price_color};
            --wsp-badge-bg: {$badge_bg};
            --wsp-badge-text: {$badge_text};
            --wsp-star: {$star_color};
            --wsp-border: {$card_border_col};
            --wsp-card-radius: {$card_radius};
            --wsp-card-border-width: {$card_border_w};
            --wsp-card-shadow: {$card_shadow};
            --wsp-card-padding: {$card_padding};
            --wsp-btn-radius: {$button_radius};
            --wsp-grid-gap: {$grid_gap};
            --wsp-container-max: {$container_max_w};
            --wsp-img-radius: {$image_radius};
            --wsp-img-ratio: {$image_ratio};
            --wsp-font-family: {$font_family};
            --wsp-page-padding-top: {$page_pad_top};
            --wsp-page-padding-bottom: {$page_pad_bottom};
            --wsp-page-padding-horizontal: {$page_pad_horiz};
            --wsp-shop-padding-top: {$shop_pad_top};
            --wsp-shop-padding-bottom: {$shop_pad_bottom};
            --wsp-single-padding-top: {$single_pad_top};
            --wsp-single-padding-bottom: {$single_pad_bottom};
            --wsp-cart-padding-top: {$cart_pad_top};
            --wsp-cart-padding-bottom: {$cart_pad_bottom};
            --wsp-checkout-padding-top: {$checkout_pad_top};
            --wsp-checkout-padding-bottom: {$checkout_pad_bottom};
            --wsp-account-padding-top: {$account_pad_top};
            --wsp-account-padding-bottom: {$account_pad_bottom};
        }
        ";

        if ( 'inherit' !== $font_family ) {
            $css .= "
            body.wsp-active .woocommerce,
            body.wsp-active .woocommerce-page,
            body.wsp-active ul.products li.product {
                font-family: var(--wsp-font-family);
            }
            ";
        }

        // Custom page background if specified
        if ( ! empty( $settings['bg_color'] ) && 'transparent' !== $settings['bg_color'] ) {
            $css .= "
            body.wsp-active.woocommerce,
            body.wsp-active.woocommerce-page {
                background-color: var(--wsp-bg);
            }
            ";
        }

        // Responsive grid columns
        $css .= "
        html body.wsp-active ul.products,
        html body.wsp-active .woocommerce ul.products,
        html body.wsp-active .woocommerce-page ul.products,
        html body.wsp-active #primary ul.products,
        html body.wsp-active #main ul.products,
        html body.wsp-active #content ul.products,
        html body.wsp-active .site-main ul.products,
        html body.wsp-active .ast-woocommerce-container ul.products,
        html body.wsp-active div.products ul.products,
        html body.wsp-active ul.products[class*='columns-'],
        html body.wsp-active .woocommerce ul.products[class*='columns-'],
        html body.wsp-active .woocommerce-page ul.products[class*='columns-'],
        html body.wsp-active.archive ul.products,
        html body.wsp-active.tax-product_cat ul.products,
        html body.wsp-active.tax-product_tag ul.products,
        html body.wsp-active.post-type-archive-product ul.products,
        html body.wsp-active.woocommerce-shop ul.products,
        html body.wsp-active .wc-block-grid__products,
        html body.wsp-active .wc-block-product-template,
        html body.wsp-active .wp-block-woocommerce-product-template,
        html body.wsp-active .elementor-products-grid ul.products,
        html body.wsp-active .elementor-widget-wc-archive-products ul.products,
        html body.wsp-active .wsp-custom-shop-grid {
            display: grid !important;
            grid-template-columns: repeat({$cols_desktop}, minmax(0, 1fr)) !important;
            gap: var(--wsp-grid-gap) !important;
            float: none !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        html body.wsp-active ul.products::before,
        html body.wsp-active ul.products::after,
        html body.wsp-active .woocommerce ul.products::before,
        html body.wsp-active .woocommerce ul.products::after,
        html body.wsp-active .woocommerce-page ul.products::before,
        html body.wsp-active .woocommerce-page ul.products::after,
        html body.wsp-active div.products ul.products::before,
        html body.wsp-active div.products ul.products::after {
            display: none !important;
            content: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        html body.wsp-active ul.products li.product,
        html body.wsp-active .woocommerce ul.products li.product,
        html body.wsp-active .woocommerce-page ul.products li.product,
        html body.wsp-active #primary ul.products li.product,
        html body.wsp-active #main ul.products li.product,
        html body.wsp-active #content ul.products li.product,
        html body.wsp-active .site-main ul.products li.product,
        html body.wsp-active .ast-woocommerce-container ul.products li.product,
        html body.wsp-active ul.products[class*='columns-'] li.product,
        html body.wsp-active .woocommerce ul.products[class*='columns-'] li.product,
        html body.wsp-active .woocommerce-page ul.products[class*='columns-'] li.product,
        html body.wsp-active.archive ul.products li.product,
        html body.wsp-active.tax-product_cat ul.products li.product,
        html body.wsp-active.tax-product_tag ul.products li.product,
        html body.wsp-active.post-type-archive-product ul.products li.product,
        html body.wsp-active.woocommerce-shop ul.products li.product,
        html body.wsp-active .wc-block-grid__product,
        html body.wsp-active .wc-block-product-template__product,
        html body.wsp-active .wp-block-woocommerce-product-template__product,
        html body.wsp-active .elementor-products-grid ul.products li.product,
        html body.wsp-active .elementor-widget-wc-archive-products ul.products li.product,
        html body.wsp-active .wsp-custom-shop-grid li.product {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            float: none !important;
            clear: none !important;
            box-sizing: border-box !important;
        }

        @media (max-width: 1024px) {
            html body.wsp-active ul.products,
            html body.wsp-active .woocommerce ul.products,
            html body.wsp-active .woocommerce-page ul.products,
            html body.wsp-active #primary ul.products,
            html body.wsp-active #main ul.products,
            html body.wsp-active .site-main ul.products,
            html body.wsp-active ul.products[class*='columns-'],
            html body.wsp-active .wc-block-grid__products,
            html body.wsp-active .wc-block-product-template,
            html body.wsp-active .wp-block-woocommerce-product-template,
            html body.wsp-active .elementor-products-grid ul.products,
            html body.wsp-active .wsp-custom-shop-grid {
                grid-template-columns: repeat({$cols_tablet}, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 768px) {
            html body.wsp-active ul.products,
            html body.wsp-active .woocommerce ul.products,
            html body.wsp-active .woocommerce-page ul.products,
            html body.wsp-active #primary ul.products,
            html body.wsp-active #main ul.products,
            html body.wsp-active #content ul.products,
            html body.wsp-active .site-main ul.products,
            html body.wsp-active .ast-woocommerce-container ul.products,
            html body.wsp-active div.products ul.products,
            html body.wsp-active ul.products[class*='columns-'],
            html body.wsp-active .woocommerce ul.products[class*='columns-'],
            html body.wsp-active .woocommerce-page ul.products[class*='columns-'],
            html body.wsp-active.archive ul.products,
            html body.wsp-active.tax-product_cat ul.products,
            html body.wsp-active.tax-product_tag ul.products,
            html body.wsp-active.post-type-archive-product ul.products,
            html body.wsp-active.woocommerce-shop ul.products,
            html body.wsp-active .wc-block-grid__products,
            html body.wsp-active .wc-block-product-template,
            html body.wsp-active .wp-block-woocommerce-product-template,
            html body.wsp-active .elementor-products-grid ul.products,
            html body.wsp-active .elementor-widget-wc-archive-products ul.products,
            html body.wsp-active .wsp-custom-shop-grid {
                grid-template-columns: repeat({$cols_mobile}, minmax(0, 1fr)) !important;
                gap: 12px !important;
            }

            html body.wsp-active ul.products li.product,
            html body.wsp-active .woocommerce ul.products li.product,
            html body.wsp-active .woocommerce-page ul.products li.product,
            html body.wsp-active #primary ul.products li.product,
            html body.wsp-active #main ul.products li.product,
            html body.wsp-active .ast-woocommerce-container ul.products li.product,
            html body.wsp-active ul.products[class*='columns-'] li.product,
            html body.wsp-active .woocommerce ul.products[class*='columns-'] li.product,
            html body.wsp-active .woocommerce-page ul.products[class*='columns-'] li.product,
            html body.wsp-active.archive ul.products li.product,
            html body.wsp-active.tax-product_cat ul.products li.product,
            html body.wsp-active.tax-product_tag ul.products li.product,
            html body.wsp-active.post-type-archive-product ul.products li.product,
            html body.wsp-active.woocommerce-shop ul.products li.product,
            html body.wsp-active .wc-block-grid__product,
            html body.wsp-active .wc-block-product-template__product,
            html body.wsp-active .wp-block-woocommerce-product-template__product,
            html body.wsp-active .elementor-products-grid ul.products li.product,
            html body.wsp-active .wsp-custom-shop-grid li.product {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                padding: 10px 10px 14px 10px !important;
                border-radius: 12px !important;
                margin: 0 !important;
                float: none !important;
                clear: none !important;
                box-sizing: border-box !important;
            }
        }
        ";

        // Append custom user CSS
        if ( ! empty( $settings['custom_css'] ) ) {
            $css .= "\n/* User Custom CSS */\n" . $settings['custom_css'];
        }

        return $css;
    }

    /**
     * Add classes to body tag
     *
     * @param array $classes
     * @return array
     */
    public function add_body_classes( $classes ) {
        if ( ! $this->is_enabled() ) {
            return $classes;
        }

        $settings = WSP_Core::get_settings();

        $classes[] = 'wsp-active';
        $classes[] = 'wsp-override-' . sanitize_html_class( $settings['override_priority'] );
        $classes[] = 'wsp-preset-' . sanitize_html_class( $settings['active_preset'] );
        $classes[] = 'wsp-anim-' . sanitize_html_class( $settings['card_hover_anim'] );
        $classes[] = 'wsp-badge-' . sanitize_html_class( $settings['badge_style'] );
        $classes[] = 'wsp-btn-style-' . sanitize_html_class( $settings['add_to_cart_style'] );

        if ( 'no' !== WSP_Core::get_setting( 'single_product_redesign', 'yes' ) ) {
            $classes[] = 'wsp-single-redesign';
        }
        if ( 'yes' === $settings['cart_redesign'] ) {
            $classes[] = 'wsp-cart-redesign';
        }
        if ( 'yes' === WSP_Core::get_setting( 'cart_auto_update', 'yes' ) ) {
            $classes[] = 'wsp-cart-auto-update';
        }
        if ( 'yes' === $settings['checkout_redesign'] ) {
            $classes[] = 'wsp-checkout-redesign';
        }
        if ( 'yes' === $settings['myaccount_redesign'] ) {
            $classes[] = 'wsp-account-redesign';
            $nav_style = ! empty( $settings['myaccount_nav_style'] ) ? $settings['myaccount_nav_style'] : 'sidebar_cards';
            $classes[] = 'wsp-acc-nav-' . sanitize_html_class( $nav_style );
            if ( isset( $settings['myaccount_nav_icons'] ) && 'no' === $settings['myaccount_nav_icons'] ) {
                $classes[] = 'wsp-acc-icons-disabled';
            }
        }

        return $classes;
    }

    /**
     * Add card class to product items in shop loop and shortcodes
     *
     * @param array  $classes
     * @param string $class
     * @param int    $post_id
     * @return array
     */
    public function add_product_card_classes( $classes, $class, $post_id ) {
        if ( ! $this->is_enabled() ) {
            return $classes;
        }

        if ( 'product' === get_post_type( $post_id ) ) {
            $classes[] = 'wsp-product-card';
        }

        return $classes;
    }

    /**
     * Filter WooCommerce catalog loop columns to ensure 2 columns on mobile screens
     *
     * @param int $cols
     * @return int
     */
    public function filter_loop_shop_columns( $cols ) {
        if ( ! $this->is_enabled() ) {
            return $cols;
        }

        if ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) {
            $mobile_cols = WSP_Core::get_setting( 'columns_mobile', '2' );
            return ! empty( $mobile_cols ) ? intval( $mobile_cols ) : 2;
        }

        $desktop_cols = WSP_Core::get_setting( 'columns_desktop', '4' );
        return ! empty( $desktop_cols ) ? intval( $desktop_cols ) : ( $cols ? $cols : 4 );
    }
}
