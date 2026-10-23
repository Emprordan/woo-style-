<?php
/**
 * Plugin Name: Woo Style+
 * Plugin URI: https://wordpress.org/plugins/woo-style-plus/
 * Description: Ultimate WooCommerce redesign suite. Transform your Shop page, Product cards, Single product, Cart, and Checkout pages with modern presets, responsive grids, and full styling controls. Fully compatible with all themes and shortcodes.
 * Version: 1.3.8
 * Author: Excellence
 * Author URI: https://woostyleplus.local
 * Text Domain: woo-style-plus
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.3
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

// Plugin Constants
define( 'WSP_VERSION', '1.3.8' );
define( 'WSP_PLUGIN_FILE', __FILE__ );
define( 'WSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Declare WooCommerce HPOS (High-Performance Order Storage) and Cart/Checkout Blocks compatibility
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

/**
 * Check if WooCommerce is installed and active
 */
function wsp_check_woocommerce_dependency() {
    if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', 'wsp_woocommerce_missing_notice' );
        return false;
    }
    return true;
}

/**
 * Notice if WooCommerce is not active
 */
function wsp_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p>
            <strong><?php esc_html_e( 'Woo Style+', 'woo-style-plus' ); ?></strong>: 
            <?php esc_html_e( 'WooCommerce is required for this plugin to work. Please install and activate WooCommerce.', 'woo-style-plus' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Helper to check network active plugins for multisite
 */
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Initialize Plugin Core
 */
function wsp_init() {
    if ( ! wsp_check_woocommerce_dependency() ) {
        return;
    }

    // Require classes
    require_once WSP_PLUGIN_DIR . 'includes/class-wsp-presets.php';
    require_once WSP_PLUGIN_DIR . 'includes/class-wsp-core.php';
    require_once WSP_PLUGIN_DIR . 'includes/class-wsp-admin.php';
    require_once WSP_PLUGIN_DIR . 'includes/class-wsp-frontend.php';
    require_once WSP_PLUGIN_DIR . 'includes/class-wsp-templates.php';
    require_once WSP_PLUGIN_DIR . 'includes/class-wsp-custom-shop.php';
    require_once WSP_PLUGIN_DIR . 'includes/class-wsp-side-cart.php';
    require_once WSP_PLUGIN_DIR . 'includes/class-wsp-updater.php';

    // Start Core & Updater instances
    WSP_Core::instance();
    WSP_Side_Cart::instance();
    WSP_Updater::instance();

    // Elementor Widget Integration
    if ( did_action( 'elementor/loaded' ) ) {
        require_once WSP_PLUGIN_DIR . 'includes/elementor/class-wsp-elementor.php';
        WSP_Elementor::instance();
    } else {
        add_action( 'elementor/init', function() {
            require_once WSP_PLUGIN_DIR . 'includes/elementor/class-wsp-elementor.php';
            WSP_Elementor::instance();
        } );
    }
}
add_action( 'plugins_loaded', 'wsp_init' );

/**
 * Plugin activation hook: Setup initial default settings
 */
register_activation_hook( __FILE__, function() {
    require_once WSP_PLUGIN_DIR . 'includes/class-wsp-presets.php';
    if ( false === get_option( 'wsp_settings' ) ) {
        update_option( 'wsp_settings', WSP_Presets::get_default_settings() );
    }
} );
