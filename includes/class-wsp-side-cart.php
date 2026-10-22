<?php
/**
 * Woo Style+ Side Cart Drawer & Trigger Handler
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Side_Cart {

    /**
     * Singleton instance
     *
     * @var WSP_Side_Cart|null
     */
    private static $instance = null;

    /**
     * Flag to ensure drawer HTML is only rendered once per page
     *
     * @var bool
     */
    private static $drawer_rendered = false;

    /**
     * Get singleton instance
     *
     * @return WSP_Side_Cart
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Enqueue assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 25 );
        add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Render drawer shell in footer
        add_action( 'wp_footer', array( $this, 'render_drawer_markup' ) );

        // WooCommerce AJAX fragments filter
        add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'add_cart_fragments' ), 20 );

        // Custom AJAX handlers for interactive stepper and item removal
        add_action( 'wp_ajax_wsp_update_cart_qty', array( $this, 'ajax_update_cart_qty' ) );
        add_action( 'wp_ajax_nopriv_wsp_update_cart_qty', array( $this, 'ajax_update_cart_qty' ) );

        add_action( 'wp_ajax_wsp_remove_cart_item', array( $this, 'ajax_remove_cart_item' ) );
        add_action( 'wp_ajax_nopriv_wsp_remove_cart_item', array( $this, 'ajax_remove_cart_item' ) );

        // Shortcodes
        add_shortcode( 'wsp_cart_icon', array( $this, 'render_cart_icon_shortcode' ) );
        add_shortcode( 'wsp_cart_drawer', array( $this, 'render_cart_drawer_shortcode' ) );
    }

    /**
     * Check if side cart feature is enabled
     *
     * @return bool
     */
    public static function is_enabled() {
        return 'yes' === WSP_Core::get_setting( 'side_cart_enabled', 'yes' );
    }

    /**
     * Enqueue side cart CSS and JS
     */
    public function enqueue_assets() {
        if ( ! self::is_enabled() ) {
            return;
        }

        // Ensure WooCommerce cart fragments script is available
        if ( function_exists( 'wp_enqueue_script' ) ) {
            wp_enqueue_script( 'wc-cart-fragments' );
        }

        // Side cart CSS
        wp_enqueue_style(
            'wsp-side-cart',
            WSP_PLUGIN_URL . 'assets/css/wsp-side-cart.css',
            array(),
            WSP_VERSION
        );

        // Side cart JS
        wp_enqueue_script(
            'wsp-side-cart',
            WSP_PLUGIN_URL . 'assets/js/wsp-side-cart.js',
            array( 'jquery' ),
            WSP_VERSION,
            true
        );

        $settings = WSP_Core::get_settings();

        wp_localize_script( 'wsp-side-cart', 'wspSideCartData', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'wsp_side_cart_nonce' ),
            'autoOpen'   => ( 'yes' === WSP_Core::get_setting( 'side_cart_auto_open', 'yes' ) ),
            'position'   => WSP_Core::get_setting( 'side_cart_position', 'right' ),
            'isCart'     => function_exists( 'is_cart' ) && is_cart(),
            'isCheckout' => function_exists( 'is_checkout' ) && is_checkout(),
        ) );
    }

    /**
     * Render the slide-out cart drawer markup in wp_footer
     */
    public function render_drawer_markup() {
        if ( self::$drawer_rendered || ! self::is_enabled() ) {
            return;
        }

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        self::$drawer_rendered = true;
        $position = WSP_Core::get_setting( 'side_cart_position', 'right' );
        ?>
        <div id="wsp-side-cart-drawer" class="wsp-side-cart-drawer wsp-sc-pos-<?php echo esc_attr( $position ); ?>" aria-hidden="true" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Shopping Cart', 'woo-style-plus' ); ?>">
            <div class="wsp-sc-backdrop" data-wsp-close-drawer tabindex="-1"></div>
            <div class="wsp-sc-panel">
                <div class="wsp-sc-content-wrap">
                    <?php echo self::get_drawer_inner_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Generate complete inner content for the side cart drawer
     *
     * @return string HTML
     */
    public static function get_drawer_inner_html() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return '';
        }

        $cart       = WC()->cart;
        $count      = $cart->get_cart_contents_count();
        $is_empty   = $cart->is_empty();
        $settings   = WSP_Core::get_settings();
        $title      = ! empty( $settings['side_cart_title'] ) ? $settings['side_cart_title'] : __( 'Shopping Cart', 'woo-style-plus' );

        ob_start();
        ?>
        <!-- Drawer Header -->
        <div class="wsp-sc-header">
            <div class="wsp-sc-header-title-wrap">
                <h3 class="wsp-sc-header-title"><?php echo esc_html( $title ); ?></h3>
                <span class="wsp-sc-header-count">(<span class="wsp-sc-count-num"><?php echo esc_html( $count ); ?></span>)</span>
            </div>
            <button type="button" class="wsp-sc-close-btn" data-wsp-close-drawer aria-label="<?php esc_attr_e( 'Close cart', 'woo-style-plus' ); ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <?php if ( $is_empty ) : ?>
            <!-- Empty Cart State -->
            <div class="wsp-sc-empty">
                <div class="wsp-sc-empty-icon">
                    <svg viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                </div>
                <h4 class="wsp-sc-empty-title"><?php esc_html_e( 'Your cart is empty', 'woo-style-plus' ); ?></h4>
                <p class="wsp-sc-empty-desc"><?php esc_html_e( "Looks like you haven't added anything to your cart yet.", 'woo-style-plus' ); ?></p>
                <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wsp-sc-btn wsp-sc-btn-shop">
                    <span><?php esc_html_e( 'Start Shopping', 'woo-style-plus' ); ?></span>
                    <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </a>
            </div>
        <?php else : ?>
            <!-- Scrollable Items List -->
            <div class="wsp-sc-body">
                <div class="wsp-sc-items-list">
                    <?php
                    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) :
                        $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                        $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

                        if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) :
                            $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                            $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( array( 80, 80 ) ), $cart_item, $cart_item_key );
                            $product_price     = apply_filters( 'woocommerce_cart_item_price', $cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                            $max_qty           = $_product->get_max_purchase_quantity();
                            ?>
                            <div class="wsp-sc-item" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
                                <div class="wsp-sc-item-thumb">
                                    <?php if ( $product_permalink ) : ?>
                                        <a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                                    <?php else : ?>
                                        <?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php endif; ?>
                                </div>
                                <div class="wsp-sc-item-details">
                                    <div class="wsp-sc-item-head">
                                        <h4 class="wsp-sc-item-title">
                                            <?php if ( $product_permalink ) : ?>
                                                <a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $_product->get_name() ); ?></a>
                                            <?php else : ?>
                                                <?php echo wp_kses_post( $_product->get_name() ); ?>
                                            <?php endif; ?>
                                        </h4>
                                        <button type="button" class="wsp-sc-item-remove" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Remove this item', 'woo-style-plus' ); ?>">
                                            <svg viewBox="0 0 20 20" width="14" height="14" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>

                                    <?php
                                    // Variation details
                                    $item_data = wc_get_formatted_cart_item_data( $cart_item );
                                    if ( ! empty( $item_data ) ) :
                                        echo '<div class="wsp-sc-item-meta">' . wp_kses_post( $item_data ) . '</div>';
                                    endif;
                                    ?>

                                    <div class="wsp-sc-item-foot">
                                        <div class="wsp-sc-item-stepper" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
                                            <button type="button" class="wsp-sc-step-btn wsp-sc-step-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'woo-style-plus' ); ?>">−</button>
                                            <input type="number" class="wsp-sc-step-input" value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" min="1" <?php echo ( $max_qty > 0 ) ? 'max="' . esc_attr( $max_qty ) . '"' : ''; ?> step="1" readonly>
                                            <button type="button" class="wsp-sc-step-btn wsp-sc-step-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'woo-style-plus' ); ?>">+</button>
                                        </div>
                                        <div class="wsp-sc-item-price">
                                            <?php echo $product_price; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>

            <!-- Sticky Drawer Footer -->
            <div class="wsp-sc-footer">
                <div class="wsp-sc-subtotal-row">
                    <span class="wsp-sc-subtotal-label"><?php esc_html_e( 'Subtotal', 'woo-style-plus' ); ?></span>
                    <span class="wsp-sc-subtotal-val"><?php echo $cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                </div>
                <p class="wsp-sc-tax-note"><?php esc_html_e( 'Shipping and taxes calculated at checkout.', 'woo-style-plus' ); ?></p>
                <div class="wsp-sc-actions">
                    <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="wsp-sc-btn wsp-sc-btn-checkout">
                        <span><?php esc_html_e( 'Proceed to Checkout', 'woo-style-plus' ); ?></span>
                        <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wsp-sc-btn wsp-sc-btn-cart">
                        <?php esc_html_e( 'View Cart', 'woo-style-plus' ); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Add updated cart drawer and count badge fragments to WooCommerce AJAX response
     *
     * @param array $fragments
     * @return array
     */
    public function add_cart_fragments( $fragments ) {
        $count    = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
        $subtotal = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_subtotal() : '';

        // Update full inner drawer content
        $fragments['div.wsp-sc-content-wrap'] = '<div class="wsp-sc-content-wrap">' . self::get_drawer_inner_html() . '</div>';

        // Update floating cart count badges
        $fragments['span.wsp-cart-count-badge'] = '<span class="wsp-cart-count-badge' . ( 0 === $count ? ' wsp-badge-hidden' : '' ) . '" data-count="' . esc_attr( $count ) . '">' . esc_html( $count ) . '</span>';

        // Update subtotal text next to trigger icons
        $fragments['span.wsp-cart-subtotal-val'] = '<span class="wsp-cart-subtotal-val">' . $subtotal . '</span>';

        return $fragments;
    }

    /**
     * AJAX handler to update item quantity in side cart
     */
    public function ajax_update_cart_qty() {
        check_ajax_referer( 'wsp_side_cart_nonce', 'security' );

        $cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
        $qty           = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1;

        if ( ! empty( $cart_item_key ) && function_exists( 'WC' ) && WC()->cart ) {
            if ( $qty > 0 ) {
                WC()->cart->set_quantity( $cart_item_key, $qty, true );
            } else {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
            WC()->cart->calculate_totals();

            $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );

            wp_send_json_success( array(
                'fragments' => $fragments,
                'count'     => WC()->cart->get_cart_contents_count(),
                'subtotal'  => WC()->cart->get_cart_subtotal(),
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Unable to update item quantity.', 'woo-style-plus' ) ) );
    }

    /**
     * AJAX handler to remove item from side cart
     */
    public function ajax_remove_cart_item() {
        check_ajax_referer( 'wsp_side_cart_nonce', 'security' );

        $cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';

        if ( ! empty( $cart_item_key ) && function_exists( 'WC' ) && WC()->cart ) {
            WC()->cart->remove_cart_item( $cart_item_key );
            WC()->cart->calculate_totals();

            $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );

            wp_send_json_success( array(
                'fragments' => $fragments,
                'count'     => WC()->cart->get_cart_contents_count(),
                'subtotal'  => WC()->cart->get_cart_subtotal(),
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Unable to remove item from cart.', 'woo-style-plus' ) ) );
    }

    /**
     * Render Trigger Cart Icon
     *
     * @param array $args
     * @return string HTML
     */
    public static function render_cart_icon( $args = array() ) {
        $defaults = array(
            'icon_type'        => 'bag1', // bag1, bag2, cart, basket, custom
            'custom_icon'      => '',
            'trigger_style'    => 'icon_only', // icon_only, boxed, pill
            'show_badge'       => 'yes',
            'hide_badge_empty' => 'no',
            'show_subtotal'    => 'no',
            'subtotal_pos'     => 'after', // after, before
            'subtotal_label'   => '',
            'align'            => 'left',
            'extra_classes'    => '',
            'custom_id'        => '',
        );

        $params = wp_parse_args( $args, $defaults );

        $count    = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
        $subtotal = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_subtotal() : '';

        // Select SVG icon
        $icon_html = '';
        switch ( $params['icon_type'] ) {
            case 'bag2':
                $icon_html = '<svg class="wsp-cart-svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9z"/></svg>';
                break;
            case 'cart':
                $icon_html = '<svg class="wsp-cart-svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';
                break;
            case 'basket':
                $icon_html = '<svg class="wsp-cart-svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 11 4-7M19 11l-4-7M2 11h20M3.5 11l1.6 8.2c.2 1 .9 1.8 2 1.8h9.8c1.1 0 1.8-.8 2-1.8l1.6-8.2"/></svg>';
                break;
            case 'custom':
                $icon_html = ! empty( $params['custom_icon'] ) ? $params['custom_icon'] : '';
                break;
            case 'bag1':
            default:
                $icon_html = '<svg class="wsp-cart-svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>';
                break;
        }

        $wrapper_classes = array(
            'wsp-cart-icon-widget-wrap',
            'wsp-align-' . esc_attr( $params['align'] ),
        );
        if ( ! empty( $params['extra_classes'] ) ) {
            $wrapper_classes[] = esc_attr( $params['extra_classes'] );
        }

        $id_attr = ! empty( $params['custom_id'] ) ? 'id="' . esc_attr( $params['custom_id'] ) . '"' : '';

        $badge_hidden = ( 'yes' === $params['hide_badge_empty'] && 0 === $count );

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" <?php echo $id_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <button type="button" class="wsp-cart-trigger-btn wsp-trigger-<?php echo esc_attr( $params['trigger_style'] ); ?>" data-wsp-cart-trigger aria-label="<?php esc_attr_e( 'View shopping cart', 'woo-style-plus' ); ?>">
                <?php if ( 'yes' === $params['show_subtotal'] && 'before' === $params['subtotal_pos'] ) : ?>
                    <span class="wsp-cart-subtotal-wrap wsp-subtotal-pos-before">
                        <?php if ( ! empty( $params['subtotal_label'] ) ) : ?>
                            <span class="wsp-cart-subtotal-label"><?php echo esc_html( $params['subtotal_label'] ); ?></span>
                        <?php endif; ?>
                        <span class="wsp-cart-subtotal-val"><?php echo $subtotal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    </span>
                <?php endif; ?>

                <span class="wsp-cart-icon-inner">
                    <?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php if ( 'yes' === $params['show_badge'] ) : ?>
                        <span class="wsp-cart-count-badge <?php echo $badge_hidden ? 'wsp-badge-hidden' : ''; ?>" data-count="<?php echo esc_attr( $count ); ?>">
                            <?php echo esc_html( $count ); ?>
                        </span>
                    <?php endif; ?>
                </span>

                <?php if ( 'yes' === $params['show_subtotal'] && 'after' === $params['subtotal_pos'] ) : ?>
                    <span class="wsp-cart-subtotal-wrap wsp-subtotal-pos-after">
                        <?php if ( ! empty( $params['subtotal_label'] ) ) : ?>
                            <span class="wsp-cart-subtotal-label"><?php echo esc_html( $params['subtotal_label'] ); ?></span>
                        <?php endif; ?>
                        <span class="wsp-cart-subtotal-val"><?php echo $subtotal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    </span>
                <?php endif; ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode callback [wsp_cart_icon]
     *
     * @param array $atts
     * @return string
     */
    public function render_cart_icon_shortcode( $atts ) {
        $parsed = shortcode_atts( array(
            'icon'          => 'bag1',
            'style'         => 'icon_only',
            'badge'         => 'yes',
            'hide_empty'    => 'no',
            'subtotal'      => 'no',
            'subtotal_pos'  => 'after',
            'align'         => 'left',
            'class'         => '',
            'id'            => '',
        ), $atts, 'wsp_cart_icon' );

        return self::render_cart_icon( array(
            'icon_type'        => $parsed['icon'],
            'trigger_style'    => $parsed['style'],
            'show_badge'       => $parsed['badge'],
            'hide_badge_empty' => $parsed['hide_empty'],
            'show_subtotal'    => $parsed['subtotal'],
            'subtotal_pos'     => $parsed['subtotal_pos'],
            'align'            => $parsed['align'],
            'extra_classes'    => $parsed['class'],
            'custom_id'        => $parsed['id'],
        ) );
    }

    /**
     * Shortcode callback [wsp_cart_drawer]
     *
     * @return string
     */
    public function render_cart_drawer_shortcode() {
        return self::get_drawer_inner_html();
    }
}
