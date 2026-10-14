<?php
/**
 * Woo Style+ GitHub 1-Click Updater
 *
 * Checks for updates from GitHub releases/tags and integrates natively
 * with WordPress plugin update API (pre_set_site_transient_update_plugins,
 * plugins_api, and upgrader_post_install).
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Updater {

    /**
     * Singleton instance
     *
     * @var WSP_Updater
     */
    protected static $instance = null;

    /**
     * GitHub Repository in :owner/:repo format
     *
     * @var string
     */
    protected $github_repo = 'Emprordan/woo-style-';

    /**
     * GitHub Access Token (optional - configured via WSP_GITHUB_TOKEN or filter)
     *
     * @var string
     */
    protected $access_token = '';

    /**
     * Current plugin slug (directory name)
     *
     * @var string
     */
    protected $slug;

    /**
     * Transient cache key for update checks
     *
     * @var string
     */
    protected $transient_key = 'wsp_github_update_data';

    /**
     * Get singleton instance
     *
     * @return WSP_Updater
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
        $this->slug = dirname( WSP_PLUGIN_BASENAME );
        if ( '.' === $this->slug || empty( $this->slug ) ) {
            $this->slug = 'woo-style-plus';
        }

        if ( defined( 'WSP_GITHUB_TOKEN' ) ) {
            $this->access_token = WSP_GITHUB_TOKEN;
        }
        $this->access_token = apply_filters( 'wsp_github_token', $this->access_token );

        // Hook into WordPress plugin update checks
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_plugin_update' ) );
        add_filter( 'site_transient_update_plugins', array( $this, 'check_for_plugin_update' ) );

        // Hook into plugin information modal ("View version details")
        add_filter( 'plugins_api', array( $this, 'plugin_popup_information' ), 20, 3 );

        // Fix folder renaming after download from GitHub zipball
        add_filter( 'upgrader_post_install', array( $this, 'post_install_cleanup' ), 10, 3 );

        // Admin action links on Plugins screen
        add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta' ), 10, 2 );

        // Manual force check hook on plugins screen
        add_action( 'admin_init', array( $this, 'handle_manual_check_query' ) );

        // AJAX 1-click update checker
        add_action( 'wp_ajax_wsp_check_github_update', array( $this, 'ajax_check_github_update' ) );
    }

    /**
     * Get remote GitHub release or tag information
     *
     * @param bool $force_refresh
     * @return object|false
     */
    public function get_remote_release_info( $force_refresh = false ) {
        if ( ! $force_refresh ) {
            $cached = get_transient( $this->transient_key );
            if ( false !== $cached ) {
                return $cached;
            }
        }

        $url = 'https://api.github.com/repos/' . $this->github_repo . '/releases/latest';

        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/WooStylePlus-Updater',
            ),
        );

        if ( ! empty( $this->access_token ) ) {
            $args['headers']['Authorization'] = 'token ' . $this->access_token;
        }

        $response = wp_remote_get( $url, $args );

        // If no latest release found, fallback to tags
        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $tags_url = 'https://api.github.com/repos/' . $this->github_repo . '/tags';
            $tags_res = wp_remote_get( $tags_url, $args );

            if ( ! is_wp_error( $tags_res ) && 200 === wp_remote_retrieve_response_code( $tags_res ) ) {
                $tags_body = json_decode( wp_remote_retrieve_body( $tags_res ) );
                if ( is_array( $tags_body ) && ! empty( $tags_body ) ) {
                    $latest_tag = $tags_body[0];
                    $version    = ltrim( $latest_tag->name, 'v' );
                    $data = (object) array(
                        'tag_name'     => $latest_tag->name,
                        'version'      => $version,
                        'zipball_url'  => $latest_tag->zipball_url,
                        'html_url'     => 'https://github.com/' . $this->github_repo . '/releases',
                        'body'         => sprintf( __( 'Release %s from GitHub.', 'woo-style-plus' ), $latest_tag->name ),
                        'published_at' => current_time( 'mysql' ),
                    );
                    set_transient( $this->transient_key, $data, 3 * HOUR_IN_SECONDS );
                    return $data;
                }
            }
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );
        if ( ! $body || empty( $body->tag_name ) ) {
            return false;
        }

        $version = ltrim( $body->tag_name, 'v' );
        $package = ! empty( $body->zipball_url ) ? $body->zipball_url : '';

        // Check if there is an uploaded .zip asset attached to the release
        if ( ! empty( $body->assets ) && is_array( $body->assets ) ) {
            foreach ( $body->assets as $asset ) {
                if ( isset( $asset->browser_download_url ) && substr( $asset->browser_download_url, -4 ) === '.zip' ) {
                    $package = $asset->browser_download_url;
                    break;
                }
            }
        }

        $data = (object) array(
            'tag_name'     => $body->tag_name,
            'version'      => $version,
            'zipball_url'  => $package,
            'html_url'     => $body->html_url,
            'body'         => ! empty( $body->body ) ? $body->body : '',
            'published_at' => ! empty( $body->published_at ) ? $body->published_at : current_time( 'mysql' ),
        );

        set_transient( $this->transient_key, $data, 3 * HOUR_IN_SECONDS );
        return $data;
    }

    /**
     * Check GitHub for plugin updates and inject into WordPress transient
     *
     * @param object $transient
     * @return object
     */
    public function check_for_plugin_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = $this->get_remote_release_info( false );
        if ( ! $remote || empty( $remote->version ) ) {
            return $transient;
        }

        // Compare current plugin version with remote version
        if ( version_compare( WSP_VERSION, $remote->version, '<' ) ) {
            $update_item = (object) array(
                'id'            => 'wsp-plugin-update',
                'slug'          => $this->slug,
                'plugin'        => WSP_PLUGIN_BASENAME,
                'new_version'   => $remote->version,
                'url'           => $remote->html_url,
                'package'       => $remote->zipball_url,
                'tested'        => '6.7',
                'requires_php'  => '7.4',
                'icons'         => array(
                    '1x' => WSP_PLUGIN_URL . 'assets/images/icon-128.png',
                    '2x' => WSP_PLUGIN_URL . 'assets/images/icon-256.png',
                ),
            );

            $transient->response[ WSP_PLUGIN_BASENAME ] = $update_item;
        } else {
            $transient->no_update[ WSP_PLUGIN_BASENAME ] = (object) array(
                'id'          => 'wsp-plugin-update',
                'slug'        => $this->slug,
                'plugin'      => WSP_PLUGIN_BASENAME,
                'new_version' => WSP_VERSION,
                'url'         => 'https://github.com/' . $this->github_repo,
            );
        }

        return $transient;
    }

    /**
     * Provide plugin popup details in WP admin
     *
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object
     */
    public function plugin_popup_information( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || ( $args->slug !== $this->slug && $args->slug !== 'woo-style-plus' ) ) {
            return $result;
        }

        $remote = $this->get_remote_release_info( false );
        $version = $remote ? $remote->version : WSP_VERSION;
        $changelog = $remote && ! empty( $remote->body ) ? nl2br( esc_html( $remote->body ) ) : esc_html__( 'Maintenance and performance enhancements.', 'woo-style-plus' );

        $info = (object) array(
            'name'          => 'Woo Style+',
            'slug'          => $this->slug,
            'version'       => $version,
            'author'        => '<a href="https://github.com/' . esc_attr( $this->github_repo ) . '">Excellence</a>',
            'homepage'      => 'https://github.com/' . $this->github_repo,
            'requires'      => '5.8',
            'tested'        => '6.7',
            'requires_php'  => '7.4',
            'download_link' => $remote ? $remote->zipball_url : '',
            'sections'      => array(
                'description' => esc_html__( 'Modern WooCommerce Redesign & Style Overrides for Shop, Cards, Single Product, Cart, Checkout & My Account.', 'woo-style-plus' ),
                'changelog'   => $changelog,
            ),
        );

        return $info;
    }

    /**
     * Fix GitHub zip folder name after installation
     *
     * @param bool  $response
     * @param array $hook_extra
     * @param array $result
     * @return array
     */
    public function post_install_cleanup( $response, $hook_extra, $result ) {
        global $wp_filesystem;

        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== WSP_PLUGIN_BASENAME ) {
            return $result;
        }

        $proper_destination = WP_PLUGIN_DIR . '/' . $this->slug;

        // Move unzipped directory from temporary GitHub folder to proper destination
        if ( isset( $result['destination'] ) && $result['destination'] !== $proper_destination ) {
            $wp_filesystem->move( $result['destination'], $proper_destination );
            $result['destination'] = $proper_destination;
        }

        // Re-activate plugin if it was active
        if ( is_plugin_active( WSP_PLUGIN_BASENAME ) ) {
            activate_plugin( WSP_PLUGIN_BASENAME );
        }

        delete_transient( $this->transient_key );

        return $result;
    }

    /**
     * Add "Check for update" link under the plugin on Plugins screen
     *
     * @param array  $links
     * @param string $file
     * @return array
     */
    public function add_plugin_row_meta( $links, $file ) {
        if ( $file === WSP_PLUGIN_BASENAME ) {
            $check_url = wp_nonce_url(
                admin_url( 'plugins.php?wsp_check_updates=1' ),
                'wsp_check_updates_nonce'
            );
            $links[] = '<a href="' . esc_url( $check_url ) . '" style="font-weight:600; color:#2563eb;"><span class="dashicons dashicons-update" style="font-size:14px; vertical-align:middle; width:14px; height:14px; margin-right:2px;"></span>' . esc_html__( 'Check for update', 'woo-style-plus' ) . '</a>';
        }
        return $links;
    }

    /**
     * Handle manual check trigger from plugins.php link
     */
    public function handle_manual_check_query() {
        if ( ! isset( $_GET['wsp_check_updates'] ) || ! current_user_can( 'update_plugins' ) ) {
            return;
        }

        check_admin_referer( 'wsp_check_updates_nonce' );

        delete_transient( $this->transient_key );
        delete_site_transient( 'update_plugins' );

        $remote = $this->get_remote_release_info( true );

        if ( $remote && version_compare( WSP_VERSION, $remote->version, '<' ) ) {
            wp_safe_redirect( admin_url( 'plugins.php?wsp_update_notice=available&v=' . urlencode( $remote->version ) ) );
        } else {
            wp_safe_redirect( admin_url( 'plugins.php?wsp_update_notice=latest' ) );
        }
        exit;
    }

    /**
     * AJAX 1-Click Update Checker
     */
    public function ajax_check_github_update() {
        check_ajax_referer( 'wsp_admin_nonce', 'security' );

        if ( ! current_user_can( 'update_plugins' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-style-plus' ) ) );
        }

        // Flush transient to force live check on GitHub
        delete_transient( $this->transient_key );
        delete_site_transient( 'update_plugins' );

        $remote = $this->get_remote_release_info( true );

        if ( ! $remote || empty( $remote->version ) ) {
            wp_send_json_success( array(
                'update_available' => false,
                'current_version'  => WSP_VERSION,
                'message'          => sprintf( __( 'You are using the latest version of Woo Style+ (v%s).', 'woo-style-plus' ), WSP_VERSION ),
            ) );
        }

        if ( version_compare( WSP_VERSION, $remote->version, '<' ) ) {
            // Generate standard WordPress 1-click update URL
            $update_url = wp_nonce_url(
                self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . urlencode( WSP_PLUGIN_BASENAME ) ),
                'upgrade-plugin_' . WSP_PLUGIN_BASENAME
            );

            wp_send_json_success( array(
                'update_available' => true,
                'current_version'  => WSP_VERSION,
                'new_version'      => $remote->version,
                'update_url'       => $update_url,
                'release_url'      => $remote->html_url,
                'changelog'        => ! empty( $remote->body ) ? wp_trim_words( $remote->body, 30, '...' ) : '',
                'message'          => sprintf( __( 'A new update (v%s) is available on GitHub!', 'woo-style-plus' ), $remote->version ),
            ) );
        } else {
            wp_send_json_success( array(
                'update_available' => false,
                'current_version'  => WSP_VERSION,
                'message'          => sprintf( __( 'Woo Style+ is up to date (v%s). No updates found.', 'woo-style-plus' ), WSP_VERSION ),
            ) );
        }
    }
}
