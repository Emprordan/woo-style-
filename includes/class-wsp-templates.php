<?php
/**
 * Woo Style+ Template Hooks & Loop Enhancements
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Templates {

    /**
     * Constructor
     */
    public function __construct() {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $this->init_hooks();
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
     * Initialize template hooks
     */
    private function init_hooks() {
        $settings = WSP_Core::get_settings();

        // Custom sale flash badge
        add_filter( 'woocommerce_sale_flash', array( $this, 'custom_sale_badge' ), 20, 3 );

        // Category above title in shop loop
        if ( 'yes' === $settings['show_categories'] ) {
            add_action( 'woocommerce_shop_loop_item_title', array( $this, 'render_loop_category' ), 4 );
        }

        // Hide ratings in shop loop if option unchecked
        if ( 'no' === $settings['show_ratings'] ) {
            remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
        }

        // Single product stock badge
        if ( 'yes' === $settings['single_stock_badge'] ) {
            add_action( 'woocommerce_single_product_summary', array( $this, 'render_stock_pill' ), 11 );
        }

        // Mobile sticky add to cart bar
        if ( 'yes' === $settings['single_sticky_mobile_cta'] ) {
            add_action( 'woocommerce_after_single_product', array( $this, 'render_sticky_mobile_bar' ) );
        }

        // My Account welcome header
        if ( 'yes' === $settings['myaccount_redesign'] && 'yes' === $settings['myaccount_show_user_header'] ) {
            add_action( 'woocommerce_account_content', array( $this, 'render_myaccount_welcome_banner' ), 2 );
        }

        // Empty cart modern message
        if ( 'yes' === $settings['cart_redesign'] ) {
            add_filter( 'wc_empty_cart_message', array( $this, 'render_empty_cart_message' ) );
        }

        // Embedded Checkout Coupon Box inside #order_review
        if ( 'yes' === $settings['checkout_redesign'] && ( ! isset( $settings['checkout_embedded_coupon'] ) || 'yes' === $settings['checkout_embedded_coupon'] ) ) {
            add_action( 'woocommerce_checkout_order_review', array( $this, 'render_checkout_coupon_box' ), 5 );
        }

        // Prevent auto-selecting default shipping method & require manual selection
        if ( 'yes' === $settings['cart_redesign'] || 'yes' === $settings['checkout_redesign'] ) {
            add_filter( 'woocommerce_shipping_chosen_method', array( $this, 'filter_shipping_chosen_method' ), 999, 2 );
            add_action( 'woocommerce_checkout_process', array( $this, 'validate_shipping_method_selection' ) );
            add_action( 'woocommerce_checkout_update_order_review', array( $this, 'track_checkout_shipping_selection' ) );
            add_action( 'woocommerce_cart_emptied', array( $this, 'reset_shipping_selection' ) );
            add_action( 'wp', array( $this, 'maybe_clear_unselected_shipping' ) );
        }

        // Clean accidental <pre class="wp-block-preformatted"> around shortcodes
        add_filter( 'the_content', array( $this, 'clean_preformatted_shortcode_wrappers' ), 1 );
        add_filter( 'the_content', array( $this, 'clean_preformatted_shortcode_wrappers' ), 9999 );

        // Suppress extraneous theme components on Single Product (hero banners, author bio, related blog posts, theme comments)
        if ( 'no' !== WSP_Core::get_setting( 'single_hide_theme_components', 'yes' ) ) {
            add_filter( 'comments_template', array( $this, 'filter_comments_template' ), 999 );
            add_action( 'wp', array( $this, 'maybe_remove_theme_single_product_elements' ), 20 );
            add_action( 'woocommerce_before_single_product', array( $this, 'ensure_single_product_title' ), 1 );
            add_action( 'woocommerce_single_product_summary', array( $this, 'ensure_single_product_title' ), 1 );
        }

        // Custom Category Archive & Search Page Templates
        add_filter( 'template_include', array( $this, 'filter_archive_search_templates' ), 99 );
        add_filter( 'woocommerce_show_page_title', array( $this, 'filter_show_page_title' ) );
        add_action( 'woocommerce_archive_description', array( $this, 'render_category_archive_header' ), 1 );
        add_action( 'pre_get_posts', array( $this, 'maybe_filter_search_query' ) );
    }

    /**
     * Render category tag in shop loop
     */
    public function render_loop_category() {
        global $product;
        if ( ! $product ) {
            return;
        }

        $categories = wc_get_product_category_list( $product->get_id(), ', ' );
        if ( $categories && ! is_wp_error( $categories ) ) {
            echo '<div class="wsp-product-category">' . wp_kses_post( $categories ) . '</div>';
        }
    }

    /**
     * Customize the sale flash badge with percentage discount calculation
     *
     * @param string     $html
     * @param WP_Post    $post
     * @param WC_Product $product
     * @return string
     */
    public function custom_sale_badge( $html, $post, $product ) {
        if ( ! $product ) {
            return $html;
        }

        $settings = WSP_Core::get_settings();
        $badge_style = ! empty( $settings['badge_style'] ) ? $settings['badge_style'] : 'pill';
        $badge_text  = esc_html__( 'Sale', 'woo-style-plus' );

        if ( ! empty( $settings['badge_text_custom'] ) ) {
            $badge_text = esc_html( $settings['badge_text_custom'] );
        }

        // Percentage discount display
        if ( 'yes' === $settings['show_sale_percentage'] ) {
            $percentage = 0;

            if ( $product->is_type( 'simple' ) || $product->is_type( 'external' ) ) {
                $regular = (float) $product->get_regular_price();
                $sale    = (float) $product->get_sale_price();

                if ( $regular > 0 && $sale > 0 && $regular > $sale ) {
                    $percentage = round( ( ( $regular - $sale ) / $regular ) * 100 );
                }
            } elseif ( $product->is_type( 'variable' ) ) {
                $prices = $product->get_variation_prices();
                if ( ! empty( $prices['regular_price'] ) && ! empty( $prices['sale_price'] ) ) {
                    $max_regular = max( $prices['regular_price'] );
                    $min_sale    = min( $prices['sale_price'] );
                    if ( $max_regular > 0 && $min_sale > 0 && $max_regular > $min_sale ) {
                        $percentage = round( ( ( $max_regular - $min_sale ) / $max_regular ) * 100 );
                    }
                }
            }

            if ( $percentage > 0 ) {
                $badge_text = '-' . $percentage . '%';
            }
        }

        return '<span class="onsale wsp-sale-badge wsp-badge-' . esc_attr( $badge_style ) . '">' . $badge_text . '</span>';
    }

    /**
     * Render sleek stock pill on single product page
     */
    public function render_stock_pill() {
        global $product;
        if ( ! $product ) {
            return;
        }

        if ( $product->is_in_stock() ) {
            echo '<div class="wsp-stock-pill in-stock"><span class="wsp-stock-dot"></span>' . esc_html__( 'In Stock', 'woo-style-plus' ) . '</div>';
        } else {
            echo '<div class="wsp-stock-pill out-of-stock"><span class="wsp-stock-dot"></span>' . esc_html__( 'Out of Stock', 'woo-style-plus' ) . '</div>';
        }
    }

    /**
     * Render Mobile Sticky Add to Cart Bar
     */
    public function render_sticky_mobile_bar() {
        global $product;
        if ( ! $product || ! is_product() ) {
            return;
        }

        $image_id  = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();
        $price     = $product->get_price_html();
        $title     = $product->get_name();
        ?>
        <div class="wsp-sticky-mobile-cta" id="wspStickyMobileCTA">
            <div class="wsp-sticky-cta-inner">
                <div class="wsp-sticky-prod-info">
                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="wsp-sticky-thumb">
                    <div class="wsp-sticky-details">
                        <span class="wsp-sticky-title"><?php echo esc_html( wp_trim_words( $title, 5, '...' ) ); ?></span>
                        <span class="wsp-sticky-price"><?php echo wp_kses_post( $price ); ?></span>
                    </div>
                </div>
                <div class="wsp-sticky-action">
                    <button type="button" class="button wsp-sticky-btn" id="wspScrollToCartBtn">
                        <?php esc_html_e( 'Select Options', 'woo-style-plus' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render modern welcome card banner in My Account dashboard
     */
    public function render_myaccount_welcome_banner() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        // Only show banner on main dashboard endpoint
        if ( ! is_account_page() || is_wc_endpoint_url() ) {
            return;
        }

        $current_user = wp_get_current_user();
        if ( ! $current_user || ! $current_user->exists() ) {
            return;
        }

        $user_avatar = get_avatar( $current_user->ID, 68 );
        $user_name   = ! empty( $current_user->display_name ) ? $current_user->display_name : $current_user->user_login;
        $user_email  = $current_user->user_email;
        $order_count = wc_get_customer_order_count( $current_user->ID );
        ?>
        <div class="wsp-account-welcome-banner">
            <div class="wsp-account-user-meta">
                <div class="wsp-account-avatar">
                    <?php echo wp_kses_post( $user_avatar ); ?>
                </div>
                <div class="wsp-account-greeting">
                    <h3><?php printf( esc_html__( 'Hello, %s!', 'woo-style-plus' ), esc_html( $user_name ) ); ?></h3>
                    <p class="wsp-account-email"><?php echo esc_html( $user_email ); ?></p>
                </div>
            </div>
            <div class="wsp-account-quick-stats">
                <div class="wsp-stat-badge">
                    <span class="wsp-stat-num"><?php echo esc_html( $order_count ); ?></span>
                    <span class="wsp-stat-label"><?php esc_html_e( 'Total Orders', 'woo-style-plus' ); ?></span>
                </div>
                <a href="<?php echo esc_url( wc_logout_url() ); ?>" class="wsp-account-logout-btn">
                    <span class="dashicons dashicons-external"></span> <?php esc_html_e( 'Logout', 'woo-style-plus' ); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Remove accidental <pre class="wp-block-preformatted"> wrappers around WooCommerce shortcodes
     *
     * @param string $content
     * @return string
     */
    public function clean_preformatted_shortcode_wrappers( $content ) {
        if ( empty( $content ) || ! is_string( $content ) ) {
            return $content;
        }

        // Clean <pre class="wp-block-preformatted">[woocommerce_...]</pre>
        $content = preg_replace(
            '/<pre[^>]*>\s*(\[(?:woocommerce_[a-z0-9_-]+|products|recent_products|featured_products|sale_products|best_selling_products|top_rated_products)[^\]]*\])\s*<\/pre>/is',
            '$1',
            $content
        );

        // Clean <pre...><div class="woocommerce">...</div></pre>
        $content = preg_replace(
            '/<pre[^>]*>\s*(<div class="woocommerce">[\s\S]*?<\/div>)\s*<\/pre>/is',
            '$1',
            $content
        );

        return $content;
    }

    /**
     * Render enhanced empty cart message with headline and subtext
     *
     * @param string $message
     * @return string
     */
    public function render_empty_cart_message( $message ) {
        return '<span class="wsp-empty-cart-title">' . esc_html__( 'Your cart is currently empty', 'woo-style-plus' ) . '</span>' .
               '<span class="wsp-empty-cart-desc">' . esc_html__( 'Looks like you haven\'t added any products to your shopping cart yet. Explore our shop to discover fresh items and great deals!', 'woo-style-plus' ) . '</span>';
    }

    /**
     * Render sleek embedded coupon widget inside checkout #order_review card
     */
    public function render_checkout_coupon_box() {
        if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
            return;
        }
        ?>
        <div class="wsp-checkout-coupon-box">
            <div class="wsp-coupon-toggle-wrap">
                <button type="button" class="wsp-coupon-toggle-btn" aria-expanded="false">
                    <span class="wsp-coupon-toggle-left">
                        <span class="wsp-coupon-tag-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                <line x1="7" y1="7" x2="7.01" y2="7"></line>
                            </svg>
                        </span>
                        <span class="wsp-coupon-toggle-title"><?php esc_html_e( 'Have a coupon code?', 'woo-style-plus' ); ?></span>
                    </span>
                    <span class="wsp-coupon-toggle-right">
                        <span class="wsp-coupon-toggle-action"><?php esc_html_e( 'Enter code', 'woo-style-plus' ); ?></span>
                        <span class="wsp-coupon-toggle-arrow">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </span>
                </button>
            </div>
            <div class="wsp-coupon-form-wrap" style="display: none;">
                <div class="wsp-coupon-input-group">
                    <div class="wsp-coupon-input-inner">
                        <span class="wsp-coupon-input-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                        </span>
                        <input type="text" class="wsp-coupon-input" placeholder="<?php esc_attr_e( 'Enter coupon code', 'woo-style-plus' ); ?>" autocomplete="off" />
                    </div>
                    <button type="button" class="wsp-coupon-apply-btn"><?php esc_html_e( 'Apply', 'woo-style-plus' ); ?></button>
                </div>
                <div class="wsp-coupon-msg" style="display: none;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Clear shipping selection if the user has not explicitly chosen one
     */
    public function maybe_clear_unselected_shipping() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return;
        }

        // If the request contains explicit selection parameter, mark it chosen
        if ( ! empty( $_REQUEST['wsp_user_chosen_shipping'] ) ) {
            WC()->session->set( 'wsp_user_chosen_shipping', true );
            return;
        }

        // If user hasn't explicitly chosen a method yet, clear chosen_shipping_methods
        if ( ! WC()->session->get( 'wsp_user_chosen_shipping' ) ) {
            WC()->session->set( 'chosen_shipping_methods', array() );
        }
    }

    /**
     * Track shipping selection from checkout update_order_review AJAX
     *
     * @param string $post_data
     */
    public function track_checkout_shipping_selection( $post_data ) {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return;
        }

        parse_str( $post_data, $parsed );

        if ( ! empty( $parsed['wsp_user_chosen_shipping'] ) && ! empty( $parsed['shipping_method'] ) && is_array( $parsed['shipping_method'] ) && ! empty( $parsed['shipping_method'][0] ) ) {
            WC()->session->set( 'wsp_user_chosen_shipping', true );
            WC()->session->set( 'chosen_shipping_methods', wc_clean( $parsed['shipping_method'] ) );
        } elseif ( ! empty( $_POST['wsp_user_chosen_shipping'] ) ) {
            WC()->session->set( 'wsp_user_chosen_shipping', true );
        }
    }

    /**
     * Reset shipping selection when cart is emptied
     */
    public function reset_shipping_selection() {
        if ( function_exists( 'WC' ) && WC()->session ) {
            WC()->session->__unset( 'wsp_user_chosen_shipping' );
            WC()->session->set( 'chosen_shipping_methods', array() );
        }
    }

    /**
     * Prevent WooCommerce from auto-selecting any shipping method by default
     *
     * @param string $default
     * @param array  $available_methods
     * @return string
     */
    public function filter_shipping_chosen_method( $default, $available_methods ) {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return '';
        }

        // If only 1 method is available (e.g. only Free Shipping exists), allow default
        if ( is_array( $available_methods ) && count( $available_methods ) <= 1 ) {
            return $default;
        }

        // Check if an explicit shipping selection was sent in the current request
        if ( ! empty( $_REQUEST['wsp_user_chosen_shipping'] ) ) {
            WC()->session->set( 'wsp_user_chosen_shipping', true );
        }

        // If the customer has not explicitly chosen a method, keep empty
        if ( ! WC()->session->get( 'wsp_user_chosen_shipping' ) ) {
            WC()->session->set( 'chosen_shipping_methods', array() );
            return '';
        }

        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

        // If customer has chosen an existing available method, retain it
        if ( ! empty( $chosen_methods ) && ! empty( $chosen_methods[0] ) && isset( $available_methods[ $chosen_methods[0] ] ) ) {
            return $chosen_methods[0];
        }

        // If the previously chosen method is no longer available in this package, reset
        WC()->session->__unset( 'wsp_user_chosen_shipping' );
        WC()->session->set( 'chosen_shipping_methods', array() );
        return '';
    }

    /**
     * Enforce shipping method selection during checkout process
     */
    public function validate_shipping_method_selection() {
        if ( function_exists( 'WC' ) && WC()->cart && WC()->cart->needs_shipping() ) {
            $available_packages = WC()->shipping() ? WC()->shipping()->get_packages() : array();
            $has_multiple_rates = false;

            if ( ! empty( $available_packages ) ) {
                foreach ( $available_packages as $package ) {
                    if ( isset( $package['rates'] ) && count( $package['rates'] ) > 1 ) {
                        $has_multiple_rates = true;
                        break;
                    }
                }
            } else {
                $has_multiple_rates = true;
            }

            if ( $has_multiple_rates ) {
                $user_chosen    = WC()->session ? WC()->session->get( 'wsp_user_chosen_shipping' ) : false;
                $chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();

                if ( ! $user_chosen || empty( $chosen_methods ) || empty( $chosen_methods[0] ) ) {
                    wc_add_notice( __( 'Please select a delivery zone / shipping method to complete your order.', 'woo-style-plus' ), 'error' );
                }
            }
        }
    }

    /**
     * Remove theme hooks on single product pages
     */
    public function maybe_remove_theme_single_product_elements() {
        if ( function_exists( 'is_product' ) && is_product() ) {
            // Astra
            remove_all_actions( 'astra_single_post_banner_inside' );
            remove_all_actions( 'astra_entry_header' );
            remove_all_actions( 'astra_author_box' );
            remove_all_actions( 'astra_related_posts' );

            // Kadence
            remove_all_actions( 'kadence_entry_hero' );
            remove_all_actions( 'kadence_entry_footer' );
            remove_all_actions( 'kadence_single_post_nav' );

            // OceanWP
            remove_all_actions( 'ocean_page_header' );
            remove_all_actions( 'ocean_after_single_post_content' );

            // GeneratePress
            remove_all_actions( 'generate_after_entry_header' );
            remove_all_actions( 'generate_after_content' );

            // Blocksy
            remove_all_actions( 'blocksy:single:post:top' );
            remove_all_actions( 'blocksy:single:post:bottom' );

            $this->ensure_single_product_title();
        }
    }

    /**
     * Ensure product title is present in single product summary
     */
    public function ensure_single_product_title() {
        if ( ! has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title' ) ) {
            add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
        }
    }

    /**
     * Route Category Archives & Product Search to custom modern templates
     *
     * @param string $template
     * @return string
     */
    public function filter_archive_search_templates( $template ) {
        // 1. Main Shop Page (All Products)
        if ( function_exists( 'is_shop' ) && is_shop() ) {
            $shop_template = WSP_PLUGIN_DIR . 'templates/archive-product.php';
            if ( file_exists( $shop_template ) ) {
                return $shop_template;
            }
        }

        // 2. Category Archive
        if ( is_product_category() || is_tax( 'product_cat' ) ) {
            $cat_template = WSP_PLUGIN_DIR . 'templates/taxonomy-product_cat.php';
            if ( file_exists( $cat_template ) ) {
                return $cat_template;
            }
        }

        // 3. Product Search Page
        if ( is_search() && ! is_admin() ) {
            $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : get_query_var( 'post_type' );
            if ( 'product' === $post_type || ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) || isset( $_GET['wsp_search'] ) ) {
                $search_template = WSP_PLUGIN_DIR . 'templates/search-product.php';
                if ( file_exists( $search_template ) ) {
                    return $search_template;
                }
            }
        }

        return $template;
    }

    /**
     * Suppress default WooCommerce archive page titles to let our modern hero shine
     *
     * @param bool $show
     * @return bool
     */
    public function filter_show_page_title( $show ) {
        if ( is_shop() || is_product_category() || is_tax( 'product_cat' ) || is_tax( 'product_tag' ) || is_search() ) {
            return false;
        }
        return $show;
    }

    /**
     * Ensure product search query targets products
     *
     * @param WP_Query $query
     */
    public function maybe_filter_search_query( $query ) {
        if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
            if ( isset( $_GET['post_type'] ) && 'product' === $_GET['post_type'] ) {
                $query->set( 'post_type', 'product' );
            }
        }
    }

    /**
     * Fallback Category Archive Header Hook for standard WooCommerce templates
     */
    public function render_category_archive_header() {
        if ( ! ( is_product_category() || is_tax( 'product_cat' ) ) ) {
            return;
        }

        if ( defined( 'WSP_CATEGORY_HERO_RENDERED' ) && WSP_CATEGORY_HERO_RENDERED ) {
            return;
        }

        $current_cat = get_queried_object();
        $cat_id      = ( $current_cat && isset( $current_cat->term_id ) ) ? $current_cat->term_id : 0;
        $cat_name    = ( $current_cat && isset( $current_cat->name ) ) ? $current_cat->name : __( 'Category', 'woo-style-plus' );
        $cat_desc    = ( $current_cat && isset( $current_cat->description ) ) ? $current_cat->description : '';
        $total_prods = ( $current_cat && isset( $current_cat->count ) ) ? intval( $current_cat->count ) : 0;

        $all_categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        if ( is_wp_error( $all_categories ) ) {
            $all_categories = array();
        }

        $ancestors = $cat_id ? get_ancestors( $cat_id, 'product_cat' ) : array();
        $ancestors = array_reverse( $ancestors );
        ?>
        <div class="wsp-archive-hero-banner wsp-category-hero-banner">
            <div class="wsp-archive-hero-inner">
                <nav class="wsp-archive-breadcrumbs" aria-label="Breadcrumb">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'woo-style-plus' ); ?></a>
                    <span class="wsp-sep">/</span>
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Shop', 'woo-style-plus' ); ?></a>
                    <?php foreach ( $ancestors as $ancestor_id ) : 
                        $ancestor = get_term( $ancestor_id, 'product_cat' );
                        if ( $ancestor && ! is_wp_error( $ancestor ) ) : ?>
                            <span class="wsp-sep">/</span>
                            <a href="<?php echo esc_url( get_term_link( $ancestor ) ); ?>"><?php echo esc_html( $ancestor->name ); ?></a>
                        <?php endif;
                    endforeach; ?>
                    <span class="wsp-sep">/</span>
                    <span class="wsp-current"><?php echo esc_html( $cat_name ); ?></span>
                </nav>

                <div class="wsp-category-hero-main">
                    <div class="wsp-category-hero-info">
                        <span class="wsp-archive-pill-tag">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            <span><?php esc_html_e( 'Product Collection', 'woo-style-plus' ); ?></span>
                        </span>
                        <h1 class="wsp-archive-hero-title"><?php echo esc_html( $cat_name ); ?></h1>
                    </div>
                    <div class="wsp-archive-count-badge">
                        <span class="wsp-count-num"><?php echo esc_html( $total_prods ); ?></span>
                        <span class="wsp-count-label"><?php echo esc_html( _n( 'Product', 'Products', $total_prods, 'woo-style-plus' ) ); ?></span>
                    </div>
                </div>

                <?php if ( ! empty( $cat_desc ) ) : ?>
                    <div class="wsp-archive-hero-desc">
                        <?php echo wp_kses_post( wpautop( $cat_desc ) ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $all_categories ) ) : ?>
                    <div class="wsp-archive-cat-pills-bar">
                        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wsp-archive-cat-pill">
                            <span class="wsp-cat-pill-icon">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            </span>
                            <span><?php esc_html_e( 'All Products', 'woo-style-plus' ); ?></span>
                        </a>
                        <?php foreach ( $all_categories as $cat ) : ?>
                            <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" 
                               class="wsp-archive-cat-pill <?php echo ( $cat->term_id === $cat_id ) ? 'is-active' : ''; ?>">
                                <span><?php echo esc_html( $cat->name ); ?></span>
                                <span class="wsp-cat-pill-count"><?php echo esc_html( $cat->count ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
    }

    /**
     * Suppress default blog comments.php on single product page
     *
     * @param string $template
     * @return string
     */
    public function filter_comments_template( $template ) {
        if ( function_exists( 'is_product' ) && is_product() ) {
            if ( 'comments.php' === basename( $template ) ) {
                $empty_file = WSP_PLUGIN_DIR . 'templates/empty-comments.php';
                if ( file_exists( $empty_file ) ) {
                    return $empty_file;
                }
            }
        }
        return $template;
    }
}
