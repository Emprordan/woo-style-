<?php
/**
 * Woo Style+ Elementor Cart Icon & Side Panel Widget
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

class WSP_Elementor_Cart_Icon_Widget extends Widget_Base {

    /**
     * Get widget name
     *
     * @return string
     */
    public function get_name() {
        return 'wsp_cart_icon';
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return esc_html__( 'Cart Icon & Side Panel', 'woo-style-plus' );
    }

    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-cart';
    }

    /**
     * Get widget categories
     *
     * @return array
     */
    public function get_categories() {
        return array( 'woo-style-plus', 'woocommerce-elements' );
    }

    /**
     * Get widget keywords
     *
     * @return array
     */
    public function get_keywords() {
        return array( 'cart', 'icon', 'side cart', 'drawer', 'mini cart', 'bag', 'basket', 'woo style' );
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {

        // ==========================================
        // CONTENT TAB: Trigger Button & Cart Icon
        // ==========================================
        $this->start_controls_section(
            'section_trigger_content',
            array(
                'label' => esc_html__( 'Cart Icon & Trigger', 'woo-style-plus' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'icon_type',
            array(
                'label'   => esc_html__( 'Icon Style', 'woo-style-plus' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'bag1',
                'options' => array(
                    'bag1'   => esc_html__( 'Shopping Bag 1 (Minimal)', 'woo-style-plus' ),
                    'bag2'   => esc_html__( 'Shopping Bag 2 (Modern Curved)', 'woo-style-plus' ),
                    'cart'   => esc_html__( 'Shopping Cart (Supermarket)', 'woo-style-plus' ),
                    'basket' => esc_html__( 'Shopping Basket (Hand Basket)', 'woo-style-plus' ),
                    'custom' => esc_html__( 'Custom Icon Picker', 'woo-style-plus' ),
                ),
            )
        );

        $this->add_control(
            'custom_icon',
            array(
                'label'     => esc_html__( 'Custom Icon', 'woo-style-plus' ),
                'type'      => Controls_Manager::ICONS,
                'default'   => array(
                    'value'   => 'fas fa-shopping-bag',
                    'library' => 'fa-solid',
                ),
                'condition' => array(
                    'icon_type' => 'custom',
                ),
            )
        );

        $this->add_control(
            'trigger_style',
            array(
                'label'   => esc_html__( 'Trigger Style', 'woo-style-plus' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'icon_only',
                'options' => array(
                    'icon_only' => esc_html__( 'Icon Only', 'woo-style-plus' ),
                    'boxed'     => esc_html__( 'Rounded Box', 'woo-style-plus' ),
                    'pill'      => esc_html__( 'Pill Badge', 'woo-style-plus' ),
                ),
            )
        );

        $this->add_responsive_control(
            'align',
            array(
                'label'     => esc_html__( 'Alignment', 'woo-style-plus' ),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => array(
                    'left'   => array(
                        'title' => esc_html__( 'Left', 'woo-style-plus' ),
                        'icon'  => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => esc_html__( 'Center', 'woo-style-plus' ),
                        'icon'  => 'eicon-text-align-center',
                    ),
                    'right'  => array(
                        'title' => esc_html__( 'Right', 'woo-style-plus' ),
                        'icon'  => 'eicon-text-align-right',
                    ),
                ),
                'default'   => 'left',
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-icon-widget-wrap' => 'text-align: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'show_badge',
            array(
                'label'        => esc_html__( 'Show Count Badge', 'woo-style-plus' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'woo-style-plus' ),
                'label_off'    => esc_html__( 'No', 'woo-style-plus' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'hide_badge_empty',
            array(
                'label'        => esc_html__( 'Hide Badge When Empty', 'woo-style-plus' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'woo-style-plus' ),
                'label_off'    => esc_html__( 'No', 'woo-style-plus' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'condition'    => array(
                    'show_badge' => 'yes',
                ),
            )
        );

        $this->add_control(
            'show_subtotal',
            array(
                'label'        => esc_html__( 'Show Cart Subtotal', 'woo-style-plus' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'woo-style-plus' ),
                'label_off'    => esc_html__( 'No', 'woo-style-plus' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'separator'    => 'before',
            )
        );

        $this->add_control(
            'subtotal_position',
            array(
                'label'     => esc_html__( 'Subtotal Position', 'woo-style-plus' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'after',
                'options'   => array(
                    'after'  => esc_html__( 'After Icon (Right)', 'woo-style-plus' ),
                    'before' => esc_html__( 'Before Icon (Left)', 'woo-style-plus' ),
                ),
                'condition' => array(
                    'show_subtotal' => 'yes',
                ),
            )
        );

        $this->add_control(
            'subtotal_label',
            array(
                'label'     => esc_html__( 'Subtotal Prefix Text', 'woo-style-plus' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => '',
                'placeholder' => esc_html__( 'e.g. Cart:', 'woo-style-plus' ),
                'condition' => array(
                    'show_subtotal' => 'yes',
                ),
            )
        );

        $this->end_controls_section();

        // ==========================================
        // CONTENT TAB: Side Panel Drawer Settings
        // ==========================================
        $this->start_controls_section(
            'section_drawer_content',
            array(
                'label' => esc_html__( 'Side Panel Drawer', 'woo-style-plus' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'drawer_position',
            array(
                'label'   => esc_html__( 'Slide Position', 'woo-style-plus' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'right',
                'options' => array(
                    'right' => esc_html__( 'Slide from Right', 'woo-style-plus' ),
                    'left'  => esc_html__( 'Slide from Left', 'woo-style-plus' ),
                ),
            )
        );

        $this->add_control(
            'auto_open',
            array(
                'label'        => esc_html__( 'Auto-Open on Add to Cart', 'woo-style-plus' ),
                'description'  => esc_html__( 'Automatically open side panel when a customer clicks Add to Cart anywhere on your site.', 'woo-style-plus' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'woo-style-plus' ),
                'label_off'    => esc_html__( 'No', 'woo-style-plus' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'drawer_title',
            array(
                'label'   => esc_html__( 'Drawer Title', 'woo-style-plus' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Shopping Cart', 'woo-style-plus' ),
            )
        );

        $this->add_control(
            'show_free_shipping',
            array(
                'label'        => esc_html__( 'Show Free Shipping Goal Bar', 'woo-style-plus' ),
                'description'  => esc_html__( 'Displays an interactive progress bar showing how much more to spend for free shipping.', 'woo-style-plus' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'woo-style-plus' ),
                'label_off'    => esc_html__( 'No', 'woo-style-plus' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'separator'    => 'before',
            )
        );

        $this->add_control(
            'free_shipping_threshold',
            array(
                'label'     => esc_html__( 'Free Shipping Threshold Amount', 'woo-style-plus' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 50000,
                'min'       => 0,
                'step'      => 100,
                'condition' => array(
                    'show_free_shipping' => 'yes',
                ),
            )
        );

        $this->end_controls_section();


        // ==========================================
        // STYLE TAB: Trigger Button & Icon
        // ==========================================
        $this->start_controls_section(
            'section_trigger_style',
            array(
                'label' => esc_html__( 'Cart Icon & Trigger', 'woo-style-plus' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'icon_size',
            array(
                'label'      => esc_html__( 'Icon Size', 'woo-style-plus' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', 'em', 'rem' ),
                'range'      => array(
                    'px' => array(
                        'min' => 12,
                        'max' => 64,
                    ),
                ),
                'default'    => array(
                    'size' => 24,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .wsp-cart-trigger-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .wsp-cart-trigger-btn i'   => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->start_controls_tabs( 'tabs_trigger_style' );

        // Normal State
        $this->start_controls_tab(
            'tab_trigger_normal',
            array(
                'label' => esc_html__( 'Normal', 'woo-style-plus' ),
            )
        );

        $this->add_control(
            'icon_color',
            array(
                'label'     => esc_html__( 'Icon Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-trigger-btn svg' => 'stroke: {{VALUE}}; color: {{VALUE}};',
                    '{{WRAPPER}} .wsp-cart-trigger-btn i'   => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'trigger_bg_color',
            array(
                'label'     => esc_html__( 'Background Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-trigger-btn' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'trigger_border_color',
            array(
                'label'     => esc_html__( 'Border Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-trigger-btn' => 'border-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'trigger_box_shadow',
                'selector' => '{{WRAPPER}} .wsp-cart-trigger-btn',
            )
        );

        $this->end_controls_tab();

        // Hover State
        $this->start_controls_tab(
            'tab_trigger_hover',
            array(
                'label' => esc_html__( 'Hover', 'woo-style-plus' ),
            )
        );

        $this->add_control(
            'icon_hover_color',
            array(
                'label'     => esc_html__( 'Icon Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#059669',
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-trigger-btn:hover svg' => 'stroke: {{VALUE}}; color: {{VALUE}};',
                    '{{WRAPPER}} .wsp-cart-trigger-btn:hover i'   => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'trigger_hover_bg',
            array(
                'label'     => esc_html__( 'Background Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-trigger-btn:hover' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'trigger_hover_border_color',
            array(
                'label'     => esc_html__( 'Border Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-trigger-btn:hover' => 'border-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'trigger_hover_box_shadow',
                'selector' => '{{WRAPPER}} .wsp-cart-trigger-btn:hover',
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'trigger_padding',
            array(
                'label'      => esc_html__( 'Padding', 'woo-style-plus' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'separator'  => 'before',
                'selectors'  => array(
                    '{{WRAPPER}} .wsp-cart-trigger-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'trigger_border_radius',
            array(
                'label'      => esc_html__( 'Border Radius', 'woo-style-plus' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%', 'em' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wsp-cart-trigger-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'trigger_border',
                'selector' => '{{WRAPPER}} .wsp-cart-trigger-btn',
            )
        );

        $this->end_controls_section();


        // ==========================================
        // STYLE TAB: Count Badge
        // ==========================================
        $this->start_controls_section(
            'section_badge_style',
            array(
                'label'     => esc_html__( 'Count Badge', 'woo-style-plus' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_badge' => 'yes',
                ),
            )
        );

        $this->add_control(
            'badge_bg_color',
            array(
                'label'     => esc_html__( 'Background Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#059669',
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-count-badge' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'badge_text_color',
            array(
                'label'     => esc_html__( 'Text Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-count-badge' => 'color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'badge_typography',
                'selector' => '{{WRAPPER}} .wsp-cart-count-badge',
            )
        );

        $this->add_responsive_control(
            'badge_size',
            array(
                'label'      => esc_html__( 'Badge Size', 'woo-style-plus' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => 14,
                        'max' => 32,
                    ),
                ),
                'default'    => array(
                    'size' => 19,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .wsp-cart-count-badge' => 'min-width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'badge_offset_x',
            array(
                'label'      => esc_html__( 'Offset X', 'woo-style-plus' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => -30,
                        'max' => 30,
                    ),
                ),
                'default'    => array(
                    'size' => 8,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .wsp-cart-count-badge' => 'right: calc(-1 * {{SIZE}}{{UNIT}});',
                ),
            )
        );

        $this->add_responsive_control(
            'badge_offset_y',
            array(
                'label'      => esc_html__( 'Offset Y', 'woo-style-plus' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => -30,
                        'max' => 30,
                    ),
                ),
                'default'    => array(
                    'size' => 8,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .wsp-cart-count-badge' => 'top: calc(-1 * {{SIZE}}{{UNIT}});',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'badge_border',
                'selector' => '{{WRAPPER}} .wsp-cart-count-badge',
            )
        );

        $this->end_controls_section();


        // ==========================================
        // STYLE TAB: Subtotal Display
        // ==========================================
        $this->start_controls_section(
            'section_subtotal_style',
            array(
                'label'     => esc_html__( 'Subtotal Display', 'woo-style-plus' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_subtotal' => 'yes',
                ),
            )
        );

        $this->add_control(
            'subtotal_color',
            array(
                'label'     => esc_html__( 'Text Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => array(
                    '{{WRAPPER}} .wsp-cart-subtotal-wrap' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'subtotal_typography',
                'selector' => '{{WRAPPER}} .wsp-cart-subtotal-wrap',
            )
        );

        $this->add_responsive_control(
            'subtotal_spacing',
            array(
                'label'      => esc_html__( 'Spacing from Icon', 'woo-style-plus' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => 2,
                        'max' => 30,
                    ),
                ),
                'default'    => array(
                    'size' => 8,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .wsp-cart-subtotal-pos-after'  => 'margin-left: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .wsp-cart-subtotal-pos-before' => 'margin-right: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();


        // ==========================================
        // STYLE TAB: Side Panel Drawer Shell
        // ==========================================
        $this->start_controls_section(
            'section_drawer_shell_style',
            array(
                'label' => esc_html__( 'Side Panel Drawer Shell', 'woo-style-plus' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'drawer_width',
            array(
                'label'      => esc_html__( 'Drawer Width', 'woo-style-plus' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', 'vw' ),
                'range'      => array(
                    'px' => array(
                        'min' => 280,
                        'max' => 650,
                    ),
                    'vw' => array(
                        'min' => 30,
                        'max' => 100,
                    ),
                ),
                'default'    => array(
                    'size' => 420,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '#wsp-side-cart-drawer .wsp-sc-panel' => 'width: {{SIZE}}{{UNIT}} !important;',
                ),
            )
        );

        $this->add_control(
            'drawer_bg',
            array(
                'label'     => esc_html__( 'Drawer Background', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-panel' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'drawer_overlay_color',
            array(
                'label'     => esc_html__( 'Backdrop Overlay Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(15, 23, 42, 0.55)',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-backdrop' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'drawer_overlay_blur',
            array(
                'label'      => esc_html__( 'Backdrop Blur', 'woo-style-plus' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 20,
                    ),
                ),
                'default'    => array(
                    'size' => 4,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '#wsp-side-cart-drawer .wsp-sc-backdrop' => 'backdrop-filter: blur({{SIZE}}{{UNIT}}) !important; -webkit-backdrop-filter: blur({{SIZE}}{{UNIT}}) !important;',
                ),
            )
        );

        $this->end_controls_section();


        // ==========================================
        // STYLE TAB: Drawer Header
        // ==========================================
        $this->start_controls_section(
            'section_drawer_header_style',
            array(
                'label' => esc_html__( 'Drawer Header', 'woo-style-plus' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'header_bg',
            array(
                'label'     => esc_html__( 'Header Background', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-header' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'header_title_color',
            array(
                'label'     => esc_html__( 'Title Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-header-title' => 'color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'header_title_typography',
                'selector' => '#wsp-side-cart-drawer .wsp-sc-header-title',
            )
        );

        $this->add_control(
            'close_icon_color',
            array(
                'label'     => esc_html__( 'Close Button Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#64748b',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-close-btn' => 'color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'close_icon_hover_color',
            array(
                'label'     => esc_html__( 'Close Button Hover Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-close-btn:hover' => 'color: {{VALUE}} !important; background: rgba(15, 23, 42, 0.06) !important;',
                ),
            )
        );

        $this->end_controls_section();


        // ==========================================
        // STYLE TAB: Free Shipping Goal Bar
        // ==========================================
        $this->start_controls_section(
            'section_free_shipping_style',
            array(
                'label'     => esc_html__( 'Free Shipping Goal Bar', 'woo-style-plus' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_free_shipping' => 'yes',
                ),
            )
        );

        $this->add_control(
            'fs_bar_fill_color',
            array(
                'label'     => esc_html__( 'Progress Bar Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#10b981',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-fs-progress-bar' => 'background: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'fs_bar_bg_color',
            array(
                'label'     => esc_html__( 'Progress Bar Track', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f1f5f9',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-fs-progress-bg' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'fs_text_color',
            array(
                'label'     => esc_html__( 'Message Text Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#334155',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-fs-message' => 'color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'fs_typography',
                'selector' => '#wsp-side-cart-drawer .wsp-sc-fs-message',
            )
        );

        $this->end_controls_section();


        // ==========================================
        // STYLE TAB: Action Buttons
        // ==========================================
        $this->start_controls_section(
            'section_buttons_style',
            array(
                'label' => esc_html__( 'Action Buttons (Checkout & Cart)', 'woo-style-plus' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'checkout_btn_heading',
            array(
                'label'     => esc_html__( 'Checkout Button (Primary)', 'woo-style-plus' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'checkout_btn_bg',
            array(
                'label'     => esc_html__( 'Background Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#059669',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-btn-checkout' => 'background: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'checkout_btn_hover_bg',
            array(
                'label'     => esc_html__( 'Hover Background', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#047857',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-btn-checkout:hover' => 'background: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'checkout_btn_color',
            array(
                'label'     => esc_html__( 'Text Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-btn-checkout' => 'color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'cart_btn_heading',
            array(
                'label'     => esc_html__( 'View Cart Button (Secondary)', 'woo-style-plus' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'cart_btn_bg',
            array(
                'label'     => esc_html__( 'Background Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f8fafc',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-btn-cart' => 'background: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'cart_btn_color',
            array(
                'label'     => esc_html__( 'Text Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-btn-cart' => 'color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'cart_btn_border_color',
            array(
                'label'     => esc_html__( 'Border Color', 'woo-style-plus' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e2e8f0',
                'selectors' => array(
                    '#wsp-side-cart-drawer .wsp-sc-btn-cart' => 'border-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_responsive_control(
            'btn_border_radius',
            array(
                'label'      => esc_html__( 'Button Border Radius', 'woo-style-plus' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'separator'  => 'before',
                'selectors'  => array(
                    '#wsp-side-cart-drawer .wsp-sc-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $custom_icon_html = '';
        if ( 'custom' === $settings['icon_type'] && ! empty( $settings['custom_icon']['value'] ) ) {
            ob_start();
            \Elementor\Icons_Manager::render_icon( $settings['custom_icon'], array( 'aria-hidden' => 'true' ) );
            $custom_icon_html = ob_get_clean();
        }

        $args = array(
            'icon_type'        => $settings['icon_type'],
            'custom_icon'      => $custom_icon_html,
            'trigger_style'    => $settings['trigger_style'],
            'show_badge'       => $settings['show_badge'],
            'hide_badge_empty' => $settings['hide_badge_empty'],
            'show_subtotal'    => $settings['show_subtotal'],
            'subtotal_pos'     => $settings['subtotal_position'],
            'subtotal_label'   => $settings['subtotal_label'],
            'align'            => $settings['align'],
        );

        if ( class_exists( 'WSP_Side_Cart' ) ) {
            echo WSP_Side_Cart::render_cart_icon( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
}
