<?php
/**
 * Woo Style+ Core Singleton Class
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Core {

    /**
     * Singleton instance
     *
     * @var WSP_Core
     */
    protected static $instance = null;

    /**
     * Admin handler instance
     *
     * @var WSP_Admin
     */
    public $admin;

    /**
     * Frontend handler instance
     *
     * @var WSP_Frontend
     */
    public $frontend;

    /**
     * Templates handler instance
     *
     * @var WSP_Templates
     */
    public $templates;

    /**
     * Get singleton instance
     *
     * @return WSP_Core
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
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialize core hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    /**
     * Load localization
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'woo-style-plus',
            false,
            dirname( WSP_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Initialize components
     */
    private function init_components() {
        if ( is_admin() ) {
            $this->admin = new WSP_Admin();
        }

        $this->frontend  = new WSP_Frontend();
        $this->templates = new WSP_Templates();
    }

    /**
     * Helper to get all settings with defaults
     *
     * @return array
     */
    public static function get_settings() {
        $saved_settings = get_option( 'wsp_settings', array() );
        $default_settings = WSP_Presets::get_default_settings();

        if ( ! is_array( $saved_settings ) ) {
            $saved_settings = array();
        }

        return wp_parse_args( $saved_settings, $default_settings );
    }

    /**
     * Helper to get a single setting with fallback
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function get_setting( $key, $default = '' ) {
        $settings = self::get_settings();
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }
}
