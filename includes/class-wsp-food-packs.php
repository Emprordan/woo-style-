<?php
/**
 * Woo Style+ Food Packs Ordering Module
 *
 * Standalone one-page food pack ordering interface with admin pack manager
 * (image, title, rich text description, pricing) and WooCommerce payment gateway checkout.
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Food_Packs {

	/**
	 * Custom post type slug
	 */
	const POST_TYPE = 'wsp_food_pack';

	/**
	 * Page settings option name
	 */
	const SETTINGS_OPTION = 'wsp_food_packs_settings';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );

		// Admin hooks
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_pack_meta' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_notices', array( $this, 'render_admin_list_banner' ) );
			add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'set_custom_columns' ) );
			add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
			add_action( 'wp_ajax_wsp_fp_create_order_page', array( $this, 'ajax_create_order_page' ) );
			add_action( 'wp_ajax_wsp_fp_save_settings', array( $this, 'ajax_save_settings' ) );
		}

		// Frontend & Standalone isolation hooks
		add_action( 'wp_head', array( $this, 'output_social_meta_tags' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'isolate_standalone_styles' ), 9999 );
		add_filter( 'template_include', array( $this, 'maybe_load_standalone_template' ), 999 );
		add_filter( 'body_class', array( $this, 'filter_body_classes' ), 999 );

		// AJAX checkout handler (logged-in & guest users)
		add_action( 'wp_ajax_wsp_fp_submit_order', array( $this, 'ajax_submit_order' ) );
		add_action( 'wp_ajax_nopriv_wsp_fp_submit_order', array( $this, 'ajax_submit_order' ) );
	}

	/**
	 * Register Food Pack custom post type under Woo Style+ menu
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Food Packs', 'woo-style-plus' ),
			'singular_name'      => __( 'Food Pack', 'woo-style-plus' ),
			'menu_name'          => __( 'Food Packs', 'woo-style-plus' ),
			'add_new'            => __( 'Add New Pack', 'woo-style-plus' ),
			'add_new_item'       => __( 'Add New Food Pack', 'woo-style-plus' ),
			'edit_item'          => __( 'Edit Food Pack', 'woo-style-plus' ),
			'new_item'           => __( 'New Food Pack', 'woo-style-plus' ),
			'view_item'          => __( 'View Food Pack', 'woo-style-plus' ),
			'search_items'       => __( 'Search Food Packs', 'woo-style-plus' ),
			'not_found'          => __( 'No food packs found. Click "Add New Pack" to create your first pack.', 'woo-style-plus' ),
			'not_found_in_trash' => __( 'No food packs found in Trash.', 'woo-style-plus' ),
			'all_items'          => __( 'Food Packs', 'woo-style-plus' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'woo-style-plus',
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'show_in_rest'        => false, // Ensures classic meta boxes & wp_editor() rich text editor
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register ordering shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode( 'wsp_food_packs', array( $this, 'render_shortcode' ) );
		add_shortcode( 'food_packs_order', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Add custom columns to Food Packs admin list table
	 */
	public function set_custom_columns( $columns ) {
		$new_columns = array(
			'cb'             => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
			'wsp_fp_thumb'   => __( 'Image', 'woo-style-plus' ),
			'title'          => __( 'Pack Title', 'woo-style-plus' ),
			'wsp_fp_price'   => __( 'Price', 'woo-style-plus' ),
			'wsp_fp_status'  => __( 'Availability', 'woo-style-plus' ),
			'wsp_fp_order'   => __( 'Sort Order', 'woo-style-plus' ),
			'date'           => isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'woo-style-plus' ),
		);
		return $new_columns;
	}

	/**
	 * Render custom column values
	 */
	public function render_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'wsp_fp_thumb':
				$image_id = absint( get_post_meta( $post_id, '_wsp_fp_image_id', true ) );
				if ( $image_id ) {
					echo wp_get_attachment_image( $image_id, array( 52, 52 ), false, array( 'style' => 'border-radius:8px;object-fit:cover;width:52px;height:52px;border:1px solid #e2e8f0;' ) );
				} else {
					echo '<span style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:8px;background:#f1f5f9;color:#94a3b8;font-size:20px;" class="dashicons dashicons-format-image"></span>';
				}
				break;

			case 'wsp_fp_price':
				$price         = get_post_meta( $post_id, '_wsp_fp_price', true );
				$regular_price = get_post_meta( $post_id, '_wsp_fp_regular_price', true );
				if ( '' !== $price && is_numeric( $price ) ) {
					echo '<strong>' . wp_kses_post( wc_price( (float) $price ) ) . '</strong>';
					if ( '' !== $regular_price && is_numeric( $regular_price ) && (float) $regular_price > (float) $price ) {
						echo ' <del style="color:#94a3b8;font-size:12px;">' . wp_kses_post( wc_price( (float) $regular_price ) ) . '</del>';
					}
				} else {
					echo '<span style="color:#ef4444;">' . esc_html__( 'Not set', 'woo-style-plus' ) . '</span>';
				}
				break;

			case 'wsp_fp_status':
				$available = get_post_meta( $post_id, '_wsp_fp_available', true );
				if ( 'no' === $available ) {
					echo '<span style="display:inline-block;padding:3px 9px;border-radius:999px;background:#fee2e2;color:#991b1b;font-size:12px;font-weight:600;">' . esc_html__( 'Unavailable', 'woo-style-plus' ) . '</span>';
				} else {
					echo '<span style="display:inline-block;padding:3px 9px;border-radius:999px;background:#dcfce7;color:#166534;font-size:12px;font-weight:600;">' . esc_html__( 'Available', 'woo-style-plus' ) . '</span>';
				}
				break;

			case 'wsp_fp_order':
				$order = get_post_meta( $post_id, '_wsp_fp_sort_order', true );
				echo esc_html( '' !== $order ? (int) $order : 0 );
				break;
		}
	}

	/**
	 * Render helpful Shortcode & Quick Setup banner on Food Packs list screen
	 */
	public function render_admin_list_banner() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-' . self::POST_TYPE !== $screen->id ) {
			return;
		}

		$existing_page_id = $this->find_existing_order_page_id();
		$nonce            = wp_create_nonce( 'wsp_fp_admin_nonce' );
		$settings         = $this->get_page_settings();
		?>
		<div class="notice wsp-fp-admin-banner" style="border-left-color:#16a34a;padding:16px 20px;margin:16px 0 8px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
			<div>
				<h2 style="margin:0 0 6px;font-size:16px;color:#0f172a;">
					<?php esc_html_e( 'Food Packs One-Page Ordering Interface', 'woo-style-plus' ); ?>
				</h2>
				<p style="margin:0;color:#475569;font-size:13px;">
					<?php esc_html_e( 'Paste this shortcode into any WordPress Page to launch a standalone, theme-isolated ordering page powered by your WooCommerce payment gateways:', 'woo-style-plus' ); ?>
				</p>
			</div>
			<div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
				<code id="wsp-fp-shortcode-code" style="padding:7px 12px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;font-weight:600;color:#0f172a;">[wsp_food_packs]</code>
				<button type="button" class="button" id="wsp-fp-copy-shortcode" data-shortcode="[wsp_food_packs]">
					<?php esc_html_e( 'Copy Shortcode', 'woo-style-plus' ); ?>
				</button>
				<?php if ( $existing_page_id ) : ?>
					<a href="<?php echo esc_url( get_permalink( $existing_page_id ) ); ?>" target="_blank" class="button button-primary" style="background:#16a34a;border-color:#15803d;">
						<?php esc_html_e( 'View Ordering Page', 'woo-style-plus' ); ?>
					</a>
				<?php else : ?>
					<button type="button" class="button button-primary" id="wsp-fp-create-page" data-nonce="<?php echo esc_attr( $nonce ); ?>" style="background:#16a34a;border-color:#15803d;">
						<?php esc_html_e( 'Create Standalone Ordering Page', 'woo-style-plus' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>

		<div class="notice wsp-fp-admin-settings-card" style="border-left-color:#0f172a;padding:16px 20px;margin:8px 0 12px;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
			<form id="wsp-fp-settings-form" style="display:flex;flex-direction:column;gap:12px;">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
				<div style="display:grid;grid-template-columns:1fr 1.6fr auto auto;gap:12px;align-items:end;">
					<div>
						<label for="wsp_fp_hero_title" style="display:block;font-weight:600;font-size:12px;color:#334155;margin-bottom:4px;">
							<?php esc_html_e( 'Ordering Page Heading', 'woo-style-plus' ); ?>
						</label>
						<input type="text" id="wsp_fp_hero_title" name="hero_title" value="<?php echo esc_attr( $settings['hero_title'] ); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e( 'Order Fresh Food Packs', 'woo-style-plus' ); ?>" />
					</div>
					<div>
						<label for="wsp_fp_hero_subtitle" style="display:block;font-weight:600;font-size:12px;color:#334155;margin-bottom:4px;">
							<?php esc_html_e( 'Ordering Page Subtitle', 'woo-style-plus' ); ?>
						</label>
						<input type="text" id="wsp_fp_hero_subtitle" name="hero_subtitle" value="<?php echo esc_attr( $settings['hero_subtitle'] ); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e( 'Select your preferred food pack(s), enter your delivery details, and pay securely.', 'woo-style-plus' ); ?>" />
					</div>
					<div>
						<label for="wsp_fp_accent_color" style="display:block;font-weight:600;font-size:12px;color:#334155;margin-bottom:4px;">
							<?php esc_html_e( 'Accent Color', 'woo-style-plus' ); ?>
						</label>
						<input type="color" id="wsp_fp_accent_color" name="accent_color" value="<?php echo esc_attr( $settings['accent_color'] ); ?>" style="height:32px;width:54px;padding:2px;border-radius:6px;border:1px solid #cbd5e1;cursor:pointer;" />
					</div>
					<div style="display:flex;flex-direction:column;justify-content:flex-end;gap:6px;padding-bottom:6px;">
						<label style="display:flex;align-items:center;gap:7px;font-weight:600;font-size:12px;color:#334155;cursor:pointer;white-space:nowrap;">
							<input type="checkbox" id="wsp_fp_christmas" name="christmas_enabled" value="yes" <?php checked( $settings['christmas_enabled'], 'yes' ); ?> style="width:16px;height:16px;accent-color:#c0392b;cursor:pointer;" />
							🎄 Christmas Mode
						</label>
					</div>
				</div>
				<div style="display:grid;grid-template-columns:1fr auto auto auto;gap:12px;align-items:end;padding-top:10px;border-top:1px solid #f1f5f9;">
					<div>
						<label for="wsp_fp_hero_notice_text" style="display:block;font-weight:600;font-size:12px;color:#334155;margin-bottom:4px;">
							<?php esc_html_e( 'Hero Info / Notice Text (Optional)', 'woo-style-plus' ); ?>
						</label>
						<input type="text" id="wsp_fp_hero_notice_text" name="hero_notice_text" value="<?php echo esc_attr( $settings['hero_notice_text'] ); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. Free delivery on orders above ₦50,000! Pre-order closes Dec 20th.', 'woo-style-plus' ); ?>" />
					</div>
					<div>
						<label for="wsp_fp_hero_notice_bg" style="display:block;font-weight:600;font-size:12px;color:#334155;margin-bottom:4px;">
							<?php esc_html_e( 'Notice Box Bg', 'woo-style-plus' ); ?>
						</label>
						<input type="color" id="wsp_fp_hero_notice_bg" name="hero_notice_bg" value="<?php echo esc_attr( $settings['hero_notice_bg'] ); ?>" style="height:32px;width:54px;padding:2px;border-radius:6px;border:1px solid #cbd5e1;cursor:pointer;" />
					</div>
					<div style="display:flex;flex-direction:column;justify-content:flex-end;gap:6px;padding-bottom:6px;">
						<label style="display:flex;align-items:center;gap:7px;font-weight:600;font-size:12px;color:#334155;cursor:pointer;white-space:nowrap;">
							<input type="checkbox" id="wsp_fp_hero_notice_marquee" name="hero_notice_marquee" value="yes" <?php checked( $settings['hero_notice_marquee'], 'yes' ); ?> style="width:16px;height:16px;accent-color:#0f172a;cursor:pointer;" />
							<?php esc_html_e( 'Scrolling Marquee', 'woo-style-plus' ); ?>
						</label>
					</div>
					<div style="display:flex;align-items:center;gap:10px;">
						<button type="submit" class="button button-primary" id="wsp-fp-save-settings-btn">
							<?php esc_html_e( 'Save Page Text', 'woo-style-plus' ); ?>
						</button>
						<span id="wsp-fp-settings-status" style="font-size:12px;font-weight:600;color:#16a34a;display:none;"></span>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Get Food Packs page settings with defaults
	 *
	 * @return array
	 */
	public function get_page_settings() {
		$defaults = array(
			'hero_title'          => __( 'Order Fresh Food Packs', 'woo-style-plus' ),
			'hero_subtitle'       => __( 'Select your preferred food pack(s), enter your delivery details, and pay securely.', 'woo-style-plus' ),
			'accent_color'        => '#16a34a',
			'christmas_enabled'   => 'no',
			'hero_notice_text'    => '',
			'hero_notice_bg'      => '#f5c842',
			'hero_notice_marquee' => 'no',
		);
		$saved = get_option( self::SETTINGS_OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * AJAX: Save Food Packs ordering page heading, subtitle, and accent color
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'wsp_fp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-style-plus' ) ) );
		}

		$hero_title          = isset( $_POST['hero_title'] ) ? sanitize_text_field( wp_unslash( $_POST['hero_title'] ) ) : '';
		$hero_subtitle       = isset( $_POST['hero_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['hero_subtitle'] ) ) : '';
		$accent_color        = isset( $_POST['accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) ) : '#16a34a';
		$christmas_enabled   = ( isset( $_POST['christmas_enabled'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['christmas_enabled'] ) ) ) ? 'yes' : 'no';
		$hero_notice_text    = isset( $_POST['hero_notice_text'] ) ? sanitize_text_field( wp_unslash( $_POST['hero_notice_text'] ) ) : '';
		$hero_notice_bg      = isset( $_POST['hero_notice_bg'] ) ? sanitize_hex_color( wp_unslash( $_POST['hero_notice_bg'] ) ) : '#f5c842';
		$hero_notice_marquee = ( isset( $_POST['hero_notice_marquee'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['hero_notice_marquee'] ) ) ) ? 'yes' : 'no';

		if ( '' === $hero_title ) {
			$hero_title = __( 'Order Fresh Food Packs', 'woo-style-plus' );
		}
		if ( ! $accent_color ) {
			$accent_color = '#16a34a';
		}
		if ( ! $hero_notice_bg ) {
			$hero_notice_bg = '#f5c842';
		}

		update_option(
			self::SETTINGS_OPTION,
			array(
				'hero_title'          => $hero_title,
				'hero_subtitle'       => $hero_subtitle,
				'accent_color'        => $accent_color,
				'christmas_enabled'   => $christmas_enabled,
				'hero_notice_text'    => $hero_notice_text,
				'hero_notice_bg'      => $hero_notice_bg,
				'hero_notice_marquee' => $hero_notice_marquee,
			)
		);

		wp_send_json_success( array( 'message' => __( 'Saved!', 'woo-style-plus' ) ) );
	}

	/**
	 * Find an existing published page containing [wsp_food_packs]
	 *
	 * @return int
	 */
	private function find_existing_order_page_id() {
		global $wpdb;
		$page_id = $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'page'
			   AND post_status IN ('publish', 'draft', 'private')
			   AND (post_content LIKE '%[wsp_food_packs%' OR post_content LIKE '%[food_packs_order%')
			 LIMIT 1"
		);
		return absint( $page_id );
	}

	/**
	 * AJAX: 1-click create a standalone ordering page
	 */
	public function ajax_create_order_page() {
		check_ajax_referer( 'wsp_fp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'publish_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-style-plus' ) ) );
		}

		$existing = $this->find_existing_order_page_id();
		if ( $existing ) {
			wp_send_json_success( array(
				'page_id' => $existing,
				'url'     => get_permalink( $existing ),
			) );
		}

		$page_id = wp_insert_post( array(
			'post_title'   => __( 'Order Food Packs', 'woo-style-plus' ),
			'post_content' => '[wsp_food_packs]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		) );

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not create page.', 'woo-style-plus' ) ) );
		}

		wp_send_json_success( array(
			'page_id' => $page_id,
			'url'     => get_permalink( $page_id ),
		) );
	}

	/**
	 * Register meta boxes for Food Pack editor
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'wsp_fp_description_box',
			__( 'Pack Description (Rich Editor)', 'woo-style-plus' ),
			array( $this, 'render_description_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'wsp_fp_image_box',
			__( 'Pack Image', 'woo-style-plus' ),
			array( $this, 'render_image_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);

		add_meta_box(
			'wsp_fp_pricing_box',
			__( 'Pricing & Display Options', 'woo-style-plus' ),
			array( $this, 'render_pricing_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'wsp_fp_shortcode_box',
			__( 'Ordering Interface Shortcode', 'woo-style-plus' ),
			array( $this, 'render_shortcode_meta_box' ),
			self::POST_TYPE,
			'side',
			'low'
		);
	}

	/**
	 * Render Rich Text Editor meta box for Pack Description
	 */
	public function render_description_meta_box( $post ) {
		wp_nonce_field( 'wsp_fp_save_meta', 'wsp_fp_meta_nonce' );

		$description = get_post_meta( $post->ID, '_wsp_fp_description', true );
		if ( '' === $description && ! empty( $post->post_content ) ) {
			$description = $post->post_content;
		}

		echo '<p class="description" style="margin-bottom:10px;">' . esc_html__( 'Describe what is included in this food pack (items, weight, portions, serving notes). Supports rich formatting, lists, and colors.', 'woo-style-plus' ) . '</p>';

		wp_editor(
			$description,
			'wsp_pack_description',
			array(
				'textarea_name' => 'wsp_pack_description',
				'media_buttons' => false,
				'textarea_rows' => 10,
				'teeny'         => false,
				'quicktags'     => true,
				'tinymce'       => array(
					'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,forecolor,removeformat',
				),
			)
		);
	}

	/**
	 * Render Pack Image meta box with WP Media Uploader
	 */
	public function render_image_meta_box( $post ) {
		$image_id  = absint( get_post_meta( $post->ID, '_wsp_fp_image_id', true ) );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<div class="wsp-fp-image-uploader">
			<input type="hidden" id="wsp_fp_image_id" name="wsp_fp_image_id" value="<?php echo esc_attr( $image_id ); ?>" />
			<div id="wsp-fp-image-preview" style="margin-bottom:12px;border:2px dashed #cbd5e1;border-radius:10px;background:#f8fafc;min-height:170px;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:6px;">
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width:100%;max-height:220px;border-radius:8px;display:block;" />
				<?php else : ?>
					<span style="color:#64748b;font-size:13px;text-align:center;padding:16px;">
						<?php esc_html_e( 'No pack image selected yet', 'woo-style-plus' ); ?>
					</span>
				<?php endif; ?>
			</div>
			<div style="display:flex;gap:8px;">
				<button type="button" class="button button-primary" id="wsp-fp-upload-image" style="flex:1;">
					<?php echo $image_id ? esc_html__( 'Change Image', 'woo-style-plus' ) : esc_html__( 'Select Image', 'woo-style-plus' ); ?>
				</button>
				<button type="button" class="button" id="wsp-fp-remove-image" style="<?php echo $image_id ? '' : 'display:none;'; ?>">
					<?php esc_html_e( 'Remove', 'woo-style-plus' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Pricing & Display Options meta box
	 */
	public function render_pricing_meta_box( $post ) {
		$price         = get_post_meta( $post->ID, '_wsp_fp_price', true );
		$regular_price = get_post_meta( $post->ID, '_wsp_fp_regular_price', true );
		$badge         = get_post_meta( $post->ID, '_wsp_fp_badge', true );
		$sort_order    = get_post_meta( $post->ID, '_wsp_fp_sort_order', true );
		$available     = get_post_meta( $post->ID, '_wsp_fp_available', true );
		if ( '' === $available ) {
			$available = 'yes';
		}
		$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
		?>
		<p>
			<label for="wsp_fp_price" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php
				/* translators: %s: currency symbol */
				printf( esc_html__( 'Pack Price (%s) *', 'woo-style-plus' ), esc_html( $currency ) );
				?>
			</label>
			<input type="number" step="0.01" min="0" id="wsp_fp_price" name="wsp_fp_price" value="<?php echo esc_attr( $price ); ?>" class="widefat" placeholder="e.g. 15000" required />
		</p>
		<p>
			<label for="wsp_fp_regular_price" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php
				/* translators: %s: currency symbol */
				printf( esc_html__( 'Original / Compare-at Price (%s)', 'woo-style-plus' ), esc_html( $currency ) );
				?>
			</label>
			<input type="number" step="0.01" min="0" id="wsp_fp_regular_price" name="wsp_fp_regular_price" value="<?php echo esc_attr( $regular_price ); ?>" class="widefat" placeholder="Optional strikethrough price" />
		</p>
		<p>
			<label for="wsp_fp_badge" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php esc_html_e( 'Highlight Badge (Optional)', 'woo-style-plus' ); ?>
			</label>
			<input type="text" id="wsp_fp_badge" name="wsp_fp_badge" value="<?php echo esc_attr( $badge ); ?>" class="widefat" placeholder="e.g. Best Seller, Family Size" />
		</p>
		<p>
			<label for="wsp_fp_sort_order" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php esc_html_e( 'Display Order', 'woo-style-plus' ); ?>
			</label>
			<input type="number" step="1" id="wsp_fp_sort_order" name="wsp_fp_sort_order" value="<?php echo esc_attr( '' !== $sort_order ? $sort_order : 0 ); ?>" class="widefat" />
			<span class="description"><?php esc_html_e( 'Lower numbers appear first.', 'woo-style-plus' ); ?></span>
		</p>
		<p style="margin-bottom:0;">
			<label style="display:flex;align-items:center;gap:8px;font-weight:600;">
				<input type="checkbox" name="wsp_fp_available" value="yes" <?php checked( $available, 'yes' ); ?> />
				<?php esc_html_e( 'Available for ordering', 'woo-style-plus' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Render Shortcode side meta box
	 */
	public function render_shortcode_meta_box() {
		?>
		<p style="margin-top:0;color:#475569;font-size:12px;">
			<?php esc_html_e( 'Use this shortcode on any page to display the standalone ordering interface:', 'woo-style-plus' ); ?>
		</p>
		<code style="display:block;padding:8px 10px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:6px;text-align:center;font-weight:600;margin-bottom:8px;">[wsp_food_packs]</code>
		<button type="button" class="button widefat" id="wsp-fp-copy-shortcode" data-shortcode="[wsp_food_packs]">
			<?php esc_html_e( 'Copy Shortcode', 'woo-style-plus' ); ?>
		</button>
		<?php
	}

	/**
	 * Save Food Pack meta data
	 */
	public function save_pack_meta( $post_id, $post ) {
		if ( ! isset( $_POST['wsp_fp_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsp_fp_meta_nonce'] ) ), 'wsp_fp_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['wsp_fp_image_id'] ) ) {
			update_post_meta( $post_id, '_wsp_fp_image_id', absint( $_POST['wsp_fp_image_id'] ) );
		}

		if ( isset( $_POST['wsp_pack_description'] ) ) {
			$description = wp_kses_post( wp_unslash( $_POST['wsp_pack_description'] ) );
			update_post_meta( $post_id, '_wsp_fp_description', $description );
		}

		if ( isset( $_POST['wsp_fp_price'] ) ) {
			$price = wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['wsp_fp_price'] ) ) );
			update_post_meta( $post_id, '_wsp_fp_price', $price );
		}

		if ( isset( $_POST['wsp_fp_regular_price'] ) ) {
			$raw_regular   = sanitize_text_field( wp_unslash( $_POST['wsp_fp_regular_price'] ) );
			$regular_price = '' !== $raw_regular ? wc_format_decimal( $raw_regular ) : '';
			update_post_meta( $post_id, '_wsp_fp_regular_price', $regular_price );
		}

		if ( isset( $_POST['wsp_fp_badge'] ) ) {
			update_post_meta( $post_id, '_wsp_fp_badge', sanitize_text_field( wp_unslash( $_POST['wsp_fp_badge'] ) ) );
		}

		if ( isset( $_POST['wsp_fp_sort_order'] ) ) {
			update_post_meta( $post_id, '_wsp_fp_sort_order', intval( $_POST['wsp_fp_sort_order'] ) );
		}

		$available = isset( $_POST['wsp_fp_available'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wsp_fp_available', $available );
	}

	/**
	 * Enqueue admin scripts (WP Media Uploader + copy/page helpers)
	 */
	public function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_script(
			'wsp-food-packs-admin',
			WSP_PLUGIN_URL . 'assets/js/wsp-food-packs-admin.js',
			array( 'jquery' ),
			WSP_VERSION,
			true
		);
	}

	/**
	 * Check whether the current request is a standalone Food Packs page
	 *
	 * @return bool
	 */
	public function is_standalone_food_packs_page() {
		if ( is_admin() || ! is_singular() ) {
			return false;
		}

		$post = get_queried_object();
		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}

		if ( ! has_shortcode( $post->post_content, 'wsp_food_packs' ) && ! has_shortcode( $post->post_content, 'food_packs_order' ) ) {
			return false;
		}

		// Allow opting out via standalone="no"
		if ( preg_match( '/\[(?:wsp_food_packs|food_packs_order)[^\]]*standalone=["\']?no["\']?/i', $post->post_content ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if current page contains the food packs shortcode (standalone or embedded)
	 *
	 * @return bool
	 */
	private function page_has_food_packs_shortcode() {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_queried_object();
		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}
		return has_shortcode( $post->post_content, 'wsp_food_packs' ) || has_shortcode( $post->post_content, 'food_packs_order' );
	}

	/**
	 * Enqueue frontend CSS and JS when shortcode is present
	 */
	public function enqueue_frontend_assets() {
		if ( ! $this->page_has_food_packs_shortcode() ) {
			return;
		}

		wp_enqueue_style(
			'wsp-food-packs',
			WSP_PLUGIN_URL . 'assets/css/wsp-food-packs.css',
			array(),
			WSP_VERSION
		);

		wp_enqueue_script(
			'wsp-food-packs',
			WSP_PLUGIN_URL . 'assets/js/wsp-food-packs.js',
			array( 'jquery' ),
			WSP_VERSION,
			true
		);

		wp_localize_script(
			'wsp-food-packs',
			'wspFoodPacks',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'wsp_fp_checkout_nonce' ),
				'currencySymbol'  => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '₦',
				'currencyPos'     => get_option( 'woocommerce_currency_pos', 'left' ),
				'thousandSep'     => wc_get_price_thousand_separator(),
				'decimalSep'      => wc_get_price_decimal_separator(),
				'decimals'        => wc_get_price_decimals(),
				'i18n'            => array(
					'selectAtLeastOne' => __( 'Please select at least one food pack before placing your order.', 'woo-style-plus' ),
					'fillRequired'     => __( 'Please fill in all required delivery details.', 'woo-style-plus' ),
					'selectGateway'    => __( 'Please choose a payment method.', 'woo-style-plus' ),
					'processing'       => __( 'Processing your order...', 'woo-style-plus' ),
					'genericError'     => __( 'Something went wrong while processing your order. Please try again.', 'woo-style-plus' ),
					'completeOrder'    => __( 'Complete Order', 'woo-style-plus' ),
					'selected'         => __( 'Selected', 'woo-style-plus' ),
					'selectPack'       => __( 'Select Pack', 'woo-style-plus' ),
					'noPacksSelected'  => __( 'No packs selected yet. Choose a pack on the left to begin.', 'woo-style-plus' ),
				),
			)
		);
	}

	/**
	 * Dequeue active theme & conflicting stylesheets on standalone Food Packs pages
	 * so the theme cannot affect the ordering page styles, while keeping WC payment gateway scripts intact.
	 */
	public function isolate_standalone_styles() {
		if ( ! $this->is_standalone_food_packs_page() ) {
			return;
		}

		global $wp_styles;
		if ( ! ( $wp_styles instanceof WP_Styles ) ) {
			return;
		}

		$theme_uri       = trailingslashit( get_template_directory_uri() );
		$child_theme_uri = trailingslashit( get_stylesheet_directory_uri() );

		$blocked_handles = array(
			'wsp-frontend',
			'wsp-side-cart',
			'woocommerce-layout',
			'woocommerce-smallscreen',
			'woocommerce-general',
			'elementor-frontend',
			'elementor-post',
			'elementor-global',
			'wp-block-library',
			'global-styles',
			'classic-theme-styles',
		);

		foreach ( $wp_styles->queue as $handle ) {
			if ( 'wsp-food-packs' === $handle || 'admin-bar' === $handle ) {
				continue;
			}

			if ( in_array( $handle, $blocked_handles, true ) ) {
				wp_dequeue_style( $handle );
				continue;
			}

			if ( isset( $wp_styles->registered[ $handle ] ) && ! empty( $wp_styles->registered[ $handle ]->src ) ) {
				$src = $wp_styles->registered[ $handle ]->src;
				if (
					false !== strpos( $src, $theme_uri ) ||
					false !== strpos( $src, $child_theme_uri ) ||
					false !== strpos( $src, '/themes/' ) ||
					false !== strpos( $src, '/elementor/' )
				) {
					wp_dequeue_style( $handle );
				}
			}
		}
	}

	/**
	 * Load clean standalone HTML template when page contains [wsp_food_packs]
	 */
	public function maybe_load_standalone_template( $template ) {
		if ( $this->is_standalone_food_packs_page() ) {
			$standalone = WSP_PLUGIN_DIR . 'templates/food-packs-standalone.php';
			if ( file_exists( $standalone ) ) {
				return $standalone;
			}
		}
		return $template;
	}

	/**
	 * Clean body classes on standalone page so theme / WSP WooCommerce redesign styles don't apply
	 */
	public function filter_body_classes( $classes ) {
		if ( $this->is_standalone_food_packs_page() ) {
			$classes = array_diff( $classes, array( 'wsp-active', 'wsp-single-redesign', 'wsp-shop-redesign' ) );
			$classes[] = 'wsp-fp-standalone-page';
		}
		return $classes;
	}

	/**
	 * Output Open Graph and Twitter Card meta tags on Food Packs pages
	 * so social media platforms display the page's featured image, title, and description.
	 */
	public function output_social_meta_tags() {
		if ( ! $this->page_has_food_packs_shortcode() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$settings    = $this->get_page_settings();
		$title       = get_the_title( $post );
		if ( '' === $title ) {
			$title = $settings['hero_title'];
		}

		$description = has_excerpt( $post ) ? wp_strip_all_tags( get_the_excerpt( $post ) ) : '';
		if ( '' === $description ) {
			$description = $settings['hero_subtitle'];
		}

		$url       = get_permalink( $post );
		$site_name = get_bloginfo( 'name' );

		$image_url    = '';
		$image_width  = '';
		$image_height = '';

		$thumb_id = get_post_thumbnail_id( $post->ID );
		if ( $thumb_id ) {
			$img_data = wp_get_attachment_image_src( $thumb_id, 'full' );
			if ( ! empty( $img_data[0] ) ) {
				$image_url    = $img_data[0];
				$image_width  = ! empty( $img_data[1] ) ? (int) $img_data[1] : '';
				$image_height = ! empty( $img_data[2] ) ? (int) $img_data[2] : '';
			}
		}

		echo "\n<!-- Woo Style+ Food Packs Social Meta -->\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		if ( '' !== $description ) {
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		}
		if ( $url ) {
			echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
		}
		if ( '' !== $image_url ) {
			echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
			if ( 0 === strpos( $image_url, 'https://' ) ) {
				echo '<meta property="og:image:secure_url" content="' . esc_url( $image_url ) . '" />' . "\n";
			}
			if ( $image_width && $image_height ) {
				echo '<meta property="og:image:width" content="' . esc_attr( $image_width ) . '" />' . "\n";
				echo '<meta property="og:image:height" content="' . esc_attr( $image_height ) . '" />' . "\n";
			}
		}

		echo '<meta name="twitter:card" content="' . ( '' !== $image_url ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
		if ( '' !== $description ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
		}
		if ( '' !== $image_url ) {
			echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '" />' . "\n";
		}
		echo "<!-- /Woo Style+ Food Packs Social Meta -->\n";
	}

	/**
	 * Fetch all available food packs ordered by sort order
	 *
	 * @return array
	 */
	public function get_available_packs() {
		$query = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_wsp_fp_sort_order',
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'date'           => 'DESC',
			),
		) );

		$packs = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$available = get_post_meta( $post->ID, '_wsp_fp_available', true );
				if ( 'no' === $available ) {
					continue;
				}

				$price = get_post_meta( $post->ID, '_wsp_fp_price', true );
				if ( '' === $price || ! is_numeric( $price ) ) {
					continue;
				}

				$image_id      = absint( get_post_meta( $post->ID, '_wsp_fp_image_id', true ) );
				$image_url     = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
				$regular_price = get_post_meta( $post->ID, '_wsp_fp_regular_price', true );
				$description   = get_post_meta( $post->ID, '_wsp_fp_description', true );
				$badge         = get_post_meta( $post->ID, '_wsp_fp_badge', true );

				$packs[] = array(
					'id'            => $post->ID,
					'title'         => $post->post_title,
					'description'   => wpautop( wp_kses_post( $description ) ),
					'price'         => (float) $price,
					'regular_price' => ( '' !== $regular_price && is_numeric( $regular_price ) ) ? (float) $regular_price : 0,
					'image_url'     => $image_url,
					'badge'         => $badge,
				);
			}
		}

		return $packs;
	}

	/**
	 * Render [wsp_food_packs] one-page ordering interface
	 */
	public function render_shortcode( $atts ) {
		// Ensure assets are enqueued even if shortcode is rendered dynamically
		wp_enqueue_style( 'wsp-food-packs', WSP_PLUGIN_URL . 'assets/css/wsp-food-packs.css', array(), WSP_VERSION );
		wp_enqueue_script( 'wsp-food-packs', WSP_PLUGIN_URL . 'assets/js/wsp-food-packs.js', array( 'jquery' ), WSP_VERSION, true );

		$saved_settings = $this->get_page_settings();
		$atts = shortcode_atts(
			array(
				'title'      => $saved_settings['hero_title'],
				'subtitle'   => $saved_settings['hero_subtitle'],
				'standalone' => 'yes',
				'accent'     => $saved_settings['accent_color'],
			),
			$atts,
			'wsp_food_packs'
		);

		$packs    = $this->get_available_packs();
		$gateways = function_exists( 'WC' ) && WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : array();

		// Pre-fill logged-in user details
		$current_user = wp_get_current_user();
		$prefill_name = '';
		$prefill_email = '';
		$prefill_phone = '';
		$prefill_address = '';
		$prefill_city = '';
		$prefill_state = '';

		if ( $current_user && $current_user->ID ) {
			$prefill_name    = trim( $current_user->first_name . ' ' . $current_user->last_name );
			if ( '' === $prefill_name ) {
				$prefill_name = $current_user->display_name;
			}
			$prefill_email   = $current_user->user_email;
			$prefill_phone   = get_user_meta( $current_user->ID, 'billing_phone', true );
			$prefill_address = get_user_meta( $current_user->ID, 'billing_address_1', true );
			$prefill_city    = get_user_meta( $current_user->ID, 'billing_city', true );
			$prefill_state   = get_user_meta( $current_user->ID, 'billing_state', true );
		}

		$site_name = get_bloginfo( 'name' );
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		$logo_url = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'medium' ) : '';
		$accent_color   = sanitize_hex_color( $atts['accent'] ) ? sanitize_hex_color( $atts['accent'] ) : '#16a34a';
		$xmas_class     = ( 'yes' === $saved_settings['christmas_enabled'] ) ? ' wsp-fp-xmas' : '';
		$notice_text    = trim( (string) $saved_settings['hero_notice_text'] );
		$notice_bg      = sanitize_hex_color( $saved_settings['hero_notice_bg'] ) ? sanitize_hex_color( $saved_settings['hero_notice_bg'] ) : '#f5c842';
		$notice_marquee = ( 'yes' === $saved_settings['hero_notice_marquee'] );

		// Auto-select contrasting text color for notice background.
		$hex_clean = ltrim( $notice_bg, '#' );
		if ( 3 === strlen( $hex_clean ) ) {
			$hex_clean = $hex_clean[0] . $hex_clean[0] . $hex_clean[1] . $hex_clean[1] . $hex_clean[2] . $hex_clean[2];
		}
		$r_lum        = hexdec( substr( $hex_clean, 0, 2 ) );
		$g_lum        = hexdec( substr( $hex_clean, 2, 2 ) );
		$b_lum        = hexdec( substr( $hex_clean, 4, 2 ) );
		$luminance    = ( 0.299 * $r_lum + 0.587 * $g_lum + 0.114 * $b_lum ) / 255;
		$notice_color = ( $luminance > 0.58 ) ? '#0f172a' : '#ffffff';

		ob_start();
		?>
		<div id="wsp-fp-root" class="wsp-fp-root<?php echo esc_attr( $xmas_class ); ?>" style="--wsp-fp-accent: <?php echo esc_attr( $accent_color ); ?>;">
			<?php if ( 'yes' === $saved_settings['christmas_enabled'] ) : ?>
			<div class="wsp-fp-xmas-snowflakes" aria-hidden="true">
				<?php for ( $i = 1; $i <= 15; $i++ ) : ?>
				<span class="wsp-fp-snowflake" style="--sf-delay:<?php echo esc_attr( $i * 0.4 ); ?>s;--sf-left:<?php echo esc_attr( wp_rand( 2, 98 ) ); ?>%;--sf-size:<?php echo esc_attr( wp_rand( 18, 30 ) ); ?>px;--sf-dur:<?php echo esc_attr( wp_rand( 6, 14 ) ); ?>s;--sf-color:<?php $cols=array('rgba(255,255,255,0.88)','rgba(192,57,43,0.90)','rgba(26,71,49,0.90)');echo esc_attr($cols[$i%3]); ?>;"><?php echo '&#10052;'; ?></span>
				<?php endfor; ?>
			</div>
			<?php endif; ?>
			<!-- Hero Title -->
			<section class="wsp-fp-hero">
				<div class="wsp-fp-container">
					<h1 class="wsp-fp-hero-title"><?php echo esc_html( $atts['title'] ); ?></h1>
					<?php if ( ! empty( $atts['subtitle'] ) ) : ?>
						<p class="wsp-fp-hero-subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $notice_text ) : ?>
						<div class="wsp-fp-hero-notice<?php echo $notice_marquee ? ' is-marquee' : ''; ?>" style="background-color: <?php echo esc_attr( $notice_bg ); ?>; color: <?php echo esc_attr( $notice_color ); ?>;">
							<?php if ( $notice_marquee ) : ?>
								<div class="wsp-fp-hero-notice-track">
									<span class="wsp-fp-hero-notice-item"><?php echo esc_html( $notice_text ); ?></span>
									<span class="wsp-fp-hero-notice-item" aria-hidden="true"><?php echo esc_html( $notice_text ); ?></span>
									<span class="wsp-fp-hero-notice-item" aria-hidden="true"><?php echo esc_html( $notice_text ); ?></span>
									<span class="wsp-fp-hero-notice-item" aria-hidden="true"><?php echo esc_html( $notice_text ); ?></span>
								</div>
							<?php else : ?>
								<span class="wsp-fp-hero-notice-static"><?php echo esc_html( $notice_text ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<!-- Main Full-Width Packs Selection -->
			<main class="wsp-fp-main">
				<div class="wsp-fp-container">
					<?php if ( empty( $packs ) ) : ?>
						<div class="wsp-fp-empty-state">
							<h3><?php esc_html_e( 'No Food Packs Available Yet', 'woo-style-plus' ); ?></h3>
							<p><?php esc_html_e( 'Please check back shortly or contact the store administrator to publish food packs.', 'woo-style-plus' ); ?></p>
						</div>
					<?php else : ?>
						<div class="wsp-fp-packs-section">
							<div class="wsp-fp-packs-grid">
								<?php foreach ( $packs as $pack ) : ?>
									<article class="wsp-fp-card"
										data-pack-id="<?php echo esc_attr( $pack['id'] ); ?>"
										data-pack-title="<?php echo esc_attr( $pack['title'] ); ?>"
										data-pack-price="<?php echo esc_attr( $pack['price'] ); ?>">

										<?php if ( ! empty( $pack['badge'] ) ) : ?>
											<span class="wsp-fp-card-badge"><?php echo esc_html( $pack['badge'] ); ?></span>
										<?php endif; ?>

										<div class="wsp-fp-card-media">
											<?php if ( ! empty( $pack['image_url'] ) ) : ?>
												<img src="<?php echo esc_url( $pack['image_url'] ); ?>" alt="<?php echo esc_attr( $pack['title'] ); ?>" loading="lazy" />
											<?php else : ?>
												<div class="wsp-fp-card-placeholder">
													<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
														<rect x="3" y="3" width="18" height="18" rx="3"></rect>
														<circle cx="8.5" cy="8.5" r="1.5"></circle>
														<path d="M21 15l-5-5L5 21"></path>
													</svg>
												</div>
											<?php endif; ?>
										</div>

										<div class="wsp-fp-card-body">
											<div class="wsp-fp-card-top">
												<h3 class="wsp-fp-card-title"><?php echo esc_html( $pack['title'] ); ?></h3>
												<div class="wsp-fp-card-pricing">
													<span class="wsp-fp-price"><?php echo wp_kses_post( wc_price( $pack['price'] ) ); ?></span>
													<?php if ( $pack['regular_price'] > $pack['price'] ) : ?>
														<span class="wsp-fp-regular-price"><?php echo wp_kses_post( wc_price( $pack['regular_price'] ) ); ?></span>
													<?php endif; ?>
												</div>
											</div>

											<?php if ( ! empty( $pack['description'] ) ) : ?>
												<div class="wsp-fp-card-desc">
													<?php echo wp_kses_post( $pack['description'] ); ?>
												</div>
											<?php endif; ?>

											<div class="wsp-fp-card-actions">
												<button type="button" class="wsp-fp-select-btn">
													<span class="wsp-fp-select-label"><?php esc_html_e( 'Select Pack', 'woo-style-plus' ); ?></span>
												</button>

												<div class="wsp-fp-qty-wrap" hidden>
													<button type="button" class="wsp-fp-qty-btn wsp-fp-qty-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'woo-style-plus' ); ?>">&minus;</button>
													<input type="number" class="wsp-fp-qty-input" value="0" min="0" max="999" inputmode="numeric" aria-label="<?php esc_attr_e( 'Quantity', 'woo-style-plus' ); ?>" />
													<button type="button" class="wsp-fp-qty-btn wsp-fp-qty-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'woo-style-plus' ); ?>">+</button>
												</div>
											</div>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
						</div>

						<!-- Center-Bottom Floating Order Widget -->
						<div id="wsp-fp-floating-bar" class="wsp-fp-floating-bar" hidden aria-live="polite">
							<div class="wsp-fp-floating-info">
								<div id="wsp-fp-floating-summary" class="wsp-fp-floating-summary"></div>
								<div id="wsp-fp-floating-total" class="wsp-fp-floating-total"><?php echo wp_kses_post( wc_price( 0 ) ); ?></div>
							</div>
							<button type="button" id="wsp-fp-open-modal-btn" class="wsp-fp-floating-btn">
								<?php esc_html_e( 'Order Now', 'woo-style-plus' ); ?>
							</button>
						</div>

						<!-- Checkout Modal Popup (Full screen on mobile) -->
						<div id="wsp-fp-checkout-modal" class="wsp-fp-modal" hidden role="dialog" aria-modal="true" aria-labelledby="wsp-fp-modal-title">
							<div class="wsp-fp-modal-backdrop" data-wsp-fp-close-modal></div>
							<div class="wsp-fp-modal-dialog">
								<div class="wsp-fp-modal-header">
									<h2 id="wsp-fp-modal-title" class="wsp-fp-modal-title"><?php esc_html_e( 'Complete Your Order', 'woo-style-plus' ); ?></h2>
									<button type="button" class="wsp-fp-modal-close" data-wsp-fp-close-modal aria-label="<?php esc_attr_e( 'Close checkout', 'woo-style-plus' ); ?>">&times;</button>
								</div>

								<div class="wsp-fp-modal-body">
									<?php if ( '' !== $notice_text ) : ?>
										<div class="wsp-fp-modal-notice" style="background-color: <?php echo esc_attr( $notice_bg ); ?>; color: <?php echo esc_attr( $notice_color ); ?>;">
											<?php echo esc_html( $notice_text ); ?>
										</div>
									<?php endif; ?>
									<form id="wsp-fp-order-form" class="wsp-fp-modal-form" novalidate>
										<!-- Selected Order Summary -->
										<div class="wsp-fp-summary-box">
											<div class="wsp-fp-section-head wsp-fp-section-head--compact">
												<span class="wsp-fp-step-badge">1</span>
												<div>
													<h3 class="wsp-fp-section-title"><?php esc_html_e( 'Selected Order', 'woo-style-plus' ); ?></h3>
												</div>
											</div>
											<div id="wsp-fp-summary-items" class="wsp-fp-summary-items">
												<p class="wsp-fp-summary-empty"><?php esc_html_e( 'No packs selected yet. Choose a pack on the left to begin.', 'woo-style-plus' ); ?></p>
											</div>
											<div class="wsp-fp-summary-total-row">
												<span><?php esc_html_e( 'Total', 'woo-style-plus' ); ?></span>
												<strong id="wsp-fp-grand-total"><?php echo wp_kses_post( wc_price( 0 ) ); ?></strong>
											</div>
										</div>

										<hr class="wsp-fp-divider" />

										<!-- Delivery Details -->
										<div class="wsp-fp-details-box">
											<div class="wsp-fp-section-head wsp-fp-section-head--compact">
												<span class="wsp-fp-step-badge">2</span>
												<div>
													<h3 class="wsp-fp-section-title"><?php esc_html_e( 'Delivery Details', 'woo-style-plus' ); ?></h3>
												</div>
											</div>

											<div class="wsp-fp-form-grid">
												<div class="wsp-fp-field wsp-fp-field--full">
													<label for="wsp_fp_full_name"><?php esc_html_e( 'Full Name', 'woo-style-plus' ); ?> <span class="wsp-fp-req">*</span></label>
													<input type="text" id="wsp_fp_full_name" name="full_name" value="<?php echo esc_attr( $prefill_name ); ?>" placeholder="<?php esc_attr_e( 'e.g. Adebayo Ogunleye', 'woo-style-plus' ); ?>" required />
												</div>

												<div class="wsp-fp-field">
													<label for="wsp_fp_email"><?php esc_html_e( 'Email Address', 'woo-style-plus' ); ?> <span class="wsp-fp-req">*</span></label>
													<input type="email" id="wsp_fp_email" name="email" value="<?php echo esc_attr( $prefill_email ); ?>" placeholder="you@example.com" required />
												</div>

												<div class="wsp-fp-field">
													<label for="wsp_fp_phone"><?php esc_html_e( 'Phone Number', 'woo-style-plus' ); ?> <span class="wsp-fp-req">*</span></label>
													<input type="tel" id="wsp_fp_phone" name="phone" value="<?php echo esc_attr( $prefill_phone ); ?>" placeholder="0801 234 5678" required />
												</div>

												<div class="wsp-fp-field wsp-fp-field--full">
													<label for="wsp_fp_address"><?php esc_html_e( 'Delivery Address', 'woo-style-plus' ); ?> <span class="wsp-fp-req">*</span></label>
													<input type="text" id="wsp_fp_address" name="address" value="<?php echo esc_attr( $prefill_address ); ?>" placeholder="<?php esc_attr_e( 'House number, street name, landmark', 'woo-style-plus' ); ?>" required />
												</div>

												<div class="wsp-fp-field">
													<label for="wsp_fp_city"><?php esc_html_e( 'City / Area', 'woo-style-plus' ); ?></label>
													<input type="text" id="wsp_fp_city" name="city" value="<?php echo esc_attr( $prefill_city ); ?>" placeholder="<?php esc_attr_e( 'e.g. Ikeja', 'woo-style-plus' ); ?>" />
												</div>

												<div class="wsp-fp-field">
													<label for="wsp_fp_state"><?php esc_html_e( 'State', 'woo-style-plus' ); ?></label>
													<input type="text" id="wsp_fp_state" name="state" value="<?php echo esc_attr( $prefill_state ); ?>" placeholder="<?php esc_attr_e( 'e.g. Lagos', 'woo-style-plus' ); ?>" />
												</div>

												<div class="wsp-fp-field wsp-fp-field--full">
													<label for="wsp_fp_notes"><?php esc_html_e( 'Delivery Notes (Optional)', 'woo-style-plus' ); ?></label>
													<textarea id="wsp_fp_notes" name="notes" rows="2" placeholder="<?php esc_attr_e( 'Any special instructions for your order or delivery...', 'woo-style-plus' ); ?>"></textarea>
												</div>
											</div>
										</div>

										<hr class="wsp-fp-divider" />

										<!-- WooCommerce Integrated Payment Gateways -->
										<div class="wsp-fp-payment-box">
											<div class="wsp-fp-section-head wsp-fp-section-head--compact">
												<span class="wsp-fp-step-badge">3</span>
												<div>
													<h3 class="wsp-fp-section-title"><?php esc_html_e( 'Payment Method', 'woo-style-plus' ); ?></h3>
												</div>
											</div>

											<?php if ( empty( $gateways ) ) : ?>
												<div class="wsp-fp-alert wsp-fp-alert--error">
													<?php esc_html_e( 'No WooCommerce payment gateways are currently enabled. Please enable a payment gateway in WooCommerce > Settings > Payments.', 'woo-style-plus' ); ?>
												</div>
											<?php else : ?>
												<ul class="wsp-fp-gateways-list">
													<?php
													$first = true;
													foreach ( $gateways as $gateway_id => $gateway ) :
														?>
														<li class="wsp-fp-gateway-item <?php echo $first ? 'is-active' : ''; ?>">
															<label class="wsp-fp-gateway-label" for="wsp_fp_gw_<?php echo esc_attr( $gateway_id ); ?>">
																<input type="radio"
																	id="wsp_fp_gw_<?php echo esc_attr( $gateway_id ); ?>"
																	name="payment_method"
																	value="<?php echo esc_attr( $gateway_id ); ?>"
																	<?php checked( $first, true ); ?> />
																<span class="wsp-fp-gateway-title"><?php echo esc_html( $gateway->get_title() ); ?></span>
																<?php if ( $gateway->get_icon() ) : ?>
																	<span class="wsp-fp-gateway-icon"><?php echo wp_kses_post( $gateway->get_icon() ); ?></span>
																<?php endif; ?>
															</label>

															<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
																<div class="wsp-fp-gateway-fields" <?php echo $first ? '' : 'hidden'; ?>>
																	<?php
																	ob_start();
																	$gateway->payment_fields();
																	$fields_html = ob_get_clean();
																	echo $fields_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
																	?>
																</div>
															<?php endif; ?>
														</li>
														<?php
														$first = false;
													endforeach;
													?>
												</ul>
											<?php endif; ?>
										</div>

										<div id="wsp-fp-feedback" class="wsp-fp-alert" hidden></div>

										<button type="submit" id="wsp-fp-submit-btn" class="wsp-fp-submit-btn" <?php echo empty( $gateways ) ? 'disabled' : ''; ?>>
											<span id="wsp-fp-submit-text"><?php esc_html_e( 'Complete Order', 'woo-style-plus' ); ?></span>
										</button>
									</form>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</main>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX: Create WooCommerce order and invoke selected WooCommerce payment gateway
	 */
	public function ajax_submit_order() {
		check_ajax_referer( 'wsp_fp_checkout_nonce', 'nonce' );

		if ( ! function_exists( 'wc_create_order' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'woo-style-plus' ) ) );
		}

		// Parse selected packs
		$raw_items = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();
		if ( is_string( $raw_items ) ) {
			$raw_items = json_decode( $raw_items, true );
		}

		if ( empty( $raw_items ) || ! is_array( $raw_items ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select at least one food pack.', 'woo-style-plus' ) ) );
		}

		// Validate customer fields
		$full_name      = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone          = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$address        = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
		$city           = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
		$state          = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';
		$notes          = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
		$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';

		if ( '' === $full_name || ! is_email( $email ) || '' === $phone || '' === $address ) {
			wp_send_json_error( array( 'message' => __( 'Please enter your valid full name, email, phone number, and delivery address.', 'woo-style-plus' ) ) );
		}

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		if ( empty( $payment_method ) || ! isset( $available_gateways[ $payment_method ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Selected payment method is unavailable.', 'woo-style-plus' ) ) );
		}

		$gateway = $available_gateways[ $payment_method ];

		// Validate packs against database prices
		$validated_items = array();
		foreach ( $raw_items as $pack_id => $qty ) {
			$pack_id = absint( $pack_id );
			$qty     = absint( $qty );
			if ( ! $pack_id || $qty < 1 ) {
				continue;
			}

			$pack_post = get_post( $pack_id );
			if ( ! $pack_post || self::POST_TYPE !== $pack_post->post_type || 'publish' !== $pack_post->post_status ) {
				continue;
			}

			if ( 'no' === get_post_meta( $pack_id, '_wsp_fp_available', true ) ) {
				continue;
			}

			$price = get_post_meta( $pack_id, '_wsp_fp_price', true );
			if ( '' === $price || ! is_numeric( $price ) ) {
				continue;
			}

			$validated_items[] = array(
				'id'    => $pack_id,
				'title' => $pack_post->post_title,
				'price' => (float) $price,
				'qty'   => $qty,
			);
		}

		if ( empty( $validated_items ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid food packs were selected.', 'woo-style-plus' ) ) );
		}

		// Ensure WC session exists for gateways that rely on it
		if ( null === WC()->session && method_exists( WC(), 'initialize_session' ) ) {
			WC()->initialize_session();
		}

		try {
			$order = wc_create_order( array(
				'customer_id' => get_current_user_id(),
				'status'      => 'pending',
			) );

			if ( is_wp_error( $order ) ) {
				wp_send_json_error( array( 'message' => $order->get_error_message() ) );
			}

			foreach ( $validated_items as $pack_item ) {
				$line_total = $pack_item['price'] * $pack_item['qty'];

				$item = new WC_Order_Item_Product();
				$item->set_name( $pack_item['title'] );
				$item->set_quantity( $pack_item['qty'] );
				$item->set_subtotal( $line_total );
				$item->set_total( $line_total );
				$item->add_meta_data( '_wsp_food_pack_id', $pack_item['id'], true );
				$order->add_item( $item );
			}

			// Split full name into first & last name
			$name_parts = preg_split( '/\s+/', trim( $full_name ), 2 );
			$first_name = isset( $name_parts[0] ) ? $name_parts[0] : $full_name;
			$last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';
			$country    = WC()->countries ? WC()->countries->get_base_country() : 'NG';

			$address_data = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'email'      => $email,
				'phone'      => $phone,
				'address_1'  => $address,
				'city'       => $city,
				'state'      => $state,
				'country'    => $country,
			);

			$order->set_address( $address_data, 'billing' );
			$order->set_address( $address_data, 'shipping' );

			if ( '' !== $notes ) {
				$order->set_customer_note( $notes );
			}

			$order->set_payment_method( $gateway );
			$order->add_order_note( __( 'Order placed via Woo Style+ Food Packs module.', 'woo-style-plus' ) );
			$order->calculate_totals();
			$order->save();

			// Allow gateways that read $_POST fields to validate/process cleanly
			if ( method_exists( $gateway, 'validate_fields' ) ) {
				$valid = $gateway->validate_fields();
				if ( false === $valid ) {
					$error_msg = $this->collect_wc_error_notices( __( 'Payment validation failed. Please check your payment details.', 'woo-style-plus' ) );
					wp_send_json_error( array( 'message' => $error_msg ) );
				}
			}

			$result = $gateway->process_payment( $order->get_id() );

			if ( is_array( $result ) && isset( $result['result'] ) && 'success' === $result['result'] ) {
				$redirect = ! empty( $result['redirect'] ) ? $result['redirect'] : $order->get_checkout_order_received_url();
				wp_send_json_success( array(
					'order_id' => $order->get_id(),
					'redirect' => $redirect,
				) );
			}

			$error_msg = $this->collect_wc_error_notices( __( 'Payment gateway could not process this order. Please try another payment method.', 'woo-style-plus' ) );
			wp_send_json_error( array( 'message' => $error_msg ) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Collect WooCommerce error notices into a single readable string and clear them
	 *
	 * @param string $fallback
	 * @return string
	 */
	private function collect_wc_error_notices( $fallback ) {
		if ( ! function_exists( 'wc_get_notices' ) ) {
			return $fallback;
		}
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();

		if ( empty( $notices ) ) {
			return $fallback;
		}

		$messages = array();
		foreach ( $notices as $notice ) {
			if ( is_array( $notice ) && isset( $notice['notice'] ) ) {
				$messages[] = wp_strip_all_tags( $notice['notice'] );
			} elseif ( is_string( $notice ) ) {
				$messages[] = wp_strip_all_tags( $notice );
			}
		}

		return ! empty( $messages ) ? implode( ' ', $messages ) : $fallback;
	}
}
