<?php
/**
 * Woo Style+ Elementor Integration
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Elementor {

    /**
     * Singleton instance
     *
     * @var WSP_Elementor|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return WSP_Elementor
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
        // Register categories
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_categories' ) );

        // Register widgets (Elementor >= 3.5.0)
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

        // Legacy fallback for older Elementor versions (< 3.5.0)
        add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets_legacy' ) );
    }

    /**
     * Register Woo Style+ category in Elementor widget panel
     *
     * @param \Elementor\Elements_Manager $elements_manager
     */
    public function register_categories( $elements_manager ) {
        $elements_manager->add_category(
            'woo-style-plus',
            array(
                'title' => esc_html__( 'Woo Style+', 'woo-style-plus' ),
                'icon'  => 'eicon-cart',
            )
        );
    }

    /**
     * Register widgets for Elementor 3.5.0+
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
    public function register_widgets( $widgets_manager ) {
        require_once WSP_PLUGIN_DIR . 'includes/elementor/class-wsp-widget-cart-icon.php';
        if ( class_exists( 'WSP_Elementor_Cart_Icon_Widget' ) ) {
            $widgets_manager->register( new \WSP_Elementor_Cart_Icon_Widget() );
        }
    }

    /**
     * Fallback widget registration for older Elementor versions
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
    public function register_widgets_legacy( $widgets_manager ) {
        require_once WSP_PLUGIN_DIR . 'includes/elementor/class-wsp-widget-cart-icon.php';
        if ( class_exists( 'WSP_Elementor_Cart_Icon_Widget' ) ) {
            $widgets_manager->register_widget_type( new \WSP_Elementor_Cart_Icon_Widget() );
        }
    }
}
