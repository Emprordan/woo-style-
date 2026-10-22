<?php
/**
 * Woo Style+ Custom Shop Page Shortcode & AJAX Filtering Engine
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

class WSP_Custom_Shop {

    /**
     * Shortcode instance counter for unique IDs
     *
     * @var int
     */
    protected static $instance_count = 0;

    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode( 'wsp_custom_shop', array( $this, 'render_shortcode' ) );
        add_shortcode( 'woo_style_custom_shop', array( $this, 'render_shortcode' ) );
        add_shortcode( 'woo_style_shop', array( $this, 'render_shortcode' ) );

        // Register AJAX filter endpoints
        add_action( 'wp_ajax_wsp_custom_shop_filter', array( $this, 'ajax_filter_products' ) );
        add_action( 'wp_ajax_nopriv_wsp_custom_shop_filter', array( $this, 'ajax_filter_products' ) );
    }

    /**
     * Render Custom Shop Shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_shortcode( $atts ) {
        if ( ! function_exists( 'WC' ) ) {
            return '';
        }

        self::$instance_count++;
        $instance_id = self::$instance_count;

        $settings = WSP_Core::get_settings();
        $default_cols = ! empty( $settings['columns_desktop'] ) ? $settings['columns_desktop'] : '4';

        $atts = shortcode_atts( array(
            'title'              => '',
            'subtitle'           => '',
            'badge'              => '',
            'show_search'        => 'yes',
            'show_categories'    => 'yes',
            'show_counts'        => 'yes',
            'show_sorting'       => 'yes',
            'columns'            => $default_cols,
            'limit'              => '12',
            'paginate'           => 'yes',
            'orderby'            => 'date',
            'order'              => 'DESC',
            'category'           => '',
            'include_categories' => '',
            'exclude_categories' => '',
            'hide_empty'         => 'no', // Default to 'no' so all categories display prominently
            'header_bg'          => 'gradient', // 'gradient', 'light', 'emerald', 'dark', 'slate', or custom CSS/hex
            'padding'            => '', // Custom wrapper padding e.g. "40px 24px 60px"
            'radius'             => '', // Custom border radius e.g. "20px"
            'max_width'          => '', // Custom container max-width e.g. "1280px"
        ), $atts, 'wsp_custom_shop' );

        // Read URL query params for initial state / bookmarking fallback
        $current_search   = isset( $_GET['wsp_search'] ) ? sanitize_text_field( wp_unslash( $_GET['wsp_search'] ) ) : '';
        $current_category = isset( $_GET['wsp_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['wsp_cat'] ) ) : $atts['category'];
        $current_orderby  = isset( $_GET['wsp_orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['wsp_orderby'] ) ) : $atts['orderby'];
        $current_page     = isset( $_GET['wsp_paged'] ) ? max( 1, intval( $_GET['wsp_paged'] ) ) : 1;

        // Fetch categories if enabled
        $categories = array();
        $total_all_products = 0;
        if ( 'yes' === $atts['show_categories'] ) {
            $cat_args = array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => ( 'yes' === $atts['hide_empty'] ),
                'orderby'    => 'name',
                'order'      => 'ASC',
            );

            if ( ! empty( $atts['include_categories'] ) ) {
                $include_slugs = array_map( 'trim', explode( ',', $atts['include_categories'] ) );
                $cat_args['slug'] = $include_slugs;
            }

            if ( ! empty( $atts['exclude_categories'] ) ) {
                $exclude_slugs = array_map( 'trim', explode( ',', $atts['exclude_categories'] ) );
                $exclude_ids   = array();
                foreach ( $exclude_slugs as $slug ) {
                    $term = get_term_by( 'slug', $slug, 'product_cat' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $exclude_ids[] = $term->term_id;
                    }
                }
                if ( ! empty( $exclude_ids ) ) {
                    $cat_args['exclude'] = $exclude_ids;
                }
            }

            $categories = get_terms( $cat_args );
            if ( is_wp_error( $categories ) ) {
                $categories = array();
            }

            // Count total published products
            $count_obj = wp_count_posts( 'product' );
            $total_all_products = isset( $count_obj->publish ) ? intval( $count_obj->publish ) : 0;
        }

        // Determine header box background style and classes
        $header_classes = array( 'wsp-custom-shop-header-box' );
        $header_inline_style = '';
        $header_bg = trim( $atts['header_bg'] );

        if ( 'emerald' === $header_bg || 'green' === $header_bg ) {
            $header_classes[] = 'wsp-header-bg-emerald';
        } elseif ( 'slate' === $header_bg || 'gray' === $header_bg || 'grey' === $header_bg ) {
            $header_classes[] = 'wsp-header-bg-slate';
        } elseif ( 'dark' === $header_bg ) {
            $header_classes[] = 'wsp-header-bg-dark';
        } elseif ( 'white' === $header_bg ) {
            $header_classes[] = 'wsp-header-bg-white';
        } elseif ( 'gradient' === $header_bg || 'light' === $header_bg || empty( $header_bg ) ) {
            // Default background: visibly colored soft mint/brand surface
            $header_classes[] = 'wsp-header-bg-default';
        } else {
            // Custom CSS color or gradient
            $header_inline_style = 'background: ' . esc_attr( $header_bg ) . ' !important;';
        }

        // Custom wrapper inline styles
        $wrapper_inline_styles = array();
        if ( ! empty( $atts['padding'] ) ) {
            $wrapper_inline_styles[] = 'padding: ' . esc_attr( $atts['padding'] ) . ' !important;';
        }
        if ( ! empty( $atts['max_width'] ) ) {
            $wrapper_inline_styles[] = 'max-width: ' . esc_attr( $atts['max_width'] ) . ' !important;';
        }
        $wrapper_style_attr = ! empty( $wrapper_inline_styles ) ? ' style="' . implode( ' ', $wrapper_inline_styles ) . '"' : '';

        // Custom header border radius if set
        if ( ! empty( $atts['radius'] ) ) {
            $header_inline_style .= ' border-radius: ' . esc_attr( $atts['radius'] ) . ' !important;';
        }
        $header_style_attr = ! empty( $header_inline_style ) ? ' style="' . trim( $header_inline_style ) . '"' : '';

        // Query initial products
        $query_result = $this->query_products( array(
            'search'   => $current_search,
            'category' => $current_category,
            'orderby'  => $current_orderby,
            'order'    => $atts['order'],
            'limit'    => intval( $atts['limit'] ),
            'paged'    => $current_page,
        ) );

        ob_start();
        ?>
        <div id="wsp-custom-shop-<?php echo esc_attr( $instance_id ); ?>" 
             class="wsp-custom-shop-wrapper" 
             data-instance="<?php echo esc_attr( $instance_id ); ?>"
             data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
             data-limit="<?php echo esc_attr( $atts['limit'] ); ?>"
             data-paginate="<?php echo esc_attr( $atts['paginate'] ); ?>"
             data-default-order="<?php echo esc_attr( $atts['order'] ); ?>"<?php echo $wrapper_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

            <!-- COMPACT HEADER CONTAINER: SEARCH & CATEGORIES -->
            <div class="<?php echo esc_attr( implode( ' ', $header_classes ) ); ?>"<?php echo $header_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <?php if ( ! empty( $atts['badge'] ) || ! empty( $atts['title'] ) || ! empty( $atts['subtitle'] ) ) : ?>
                    <div class="wsp-cs-header-titles">
                        <?php if ( ! empty( $atts['badge'] ) ) : ?>
                            <div class="wsp-cs-badge">
                                <span class="wsp-cs-badge-dot" aria-hidden="true"></span>
                                <?php echo esc_html( $atts['badge'] ); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $atts['title'] ) ) : ?>
                            <h2 class="wsp-cs-title"><?php echo esc_html( $atts['title'] ); ?></h2>
                        <?php endif; ?>
                        <?php if ( ! empty( $atts['subtitle'] ) ) : ?>
                            <p class="wsp-cs-subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Row 1: Search Bar -->
                <?php if ( 'yes' === $atts['show_search'] ) : ?>
                    <div class="wsp-cs-search-row">
                        <form class="wsp-cs-search-form" role="search" onsubmit="return false;">
                            <div class="wsp-cs-search-box">
                                <span class="wsp-cs-search-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                </span>
                                <input type="search" 
                                       class="wsp-cs-search-input" 
                                       name="wsp_search" 
                                       value="<?php echo esc_attr( $current_search ); ?>" 
                                       placeholder="<?php esc_attr_e( 'Search products by title, keyword...', 'woo-style-plus' ); ?>" 
                                       autocomplete="off" />
                                <button type="button" class="wsp-cs-search-clear" aria-label="<?php esc_attr_e( 'Clear search query', 'woo-style-plus' ); ?>" style="<?php echo empty( $current_search ) ? 'display: none;' : ''; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                                <button type="submit" class="wsp-cs-search-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    <span><?php esc_html_e( 'Search', 'woo-style-plus' ); ?></span>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Row 2: Compact Controls (Categories Pills on Left + Sorting & Count on Right) -->
                <div class="wsp-cs-controls-row">
                    <?php if ( 'yes' === $atts['show_categories'] && ! empty( $categories ) ) : ?>
                        <div class="wsp-cs-categories-wrap">
                            <div class="wsp-cs-categories-scroll">
                                <div class="wsp-cs-categories-list" role="tablist">
                                    <!-- "All Products" Pill -->
                                    <button type="button" 
                                            class="wsp-cs-cat-btn <?php echo empty( $current_category ) ? 'is-active' : ''; ?>" 
                                            data-category="" 
                                            role="tab" 
                                            aria-selected="<?php echo empty( $current_category ) ? 'true' : 'false'; ?>">
                                        <span class="wsp-cs-cat-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                        </span>
                                        <span class="wsp-cs-cat-name"><?php esc_html_e( 'All Products', 'woo-style-plus' ); ?></span>
                                        <?php if ( 'yes' === $atts['show_counts'] && $total_all_products > 0 ) : ?>
                                            <span class="wsp-cs-cat-count"><?php echo esc_html( $total_all_products ); ?></span>
                                        <?php endif; ?>
                                    </button>

                                    <?php foreach ( $categories as $cat ) : ?>
                                        <button type="button" 
                                                class="wsp-cs-cat-btn <?php echo ( $current_category === $cat->slug ) ? 'is-active' : ''; ?>" 
                                                data-category="<?php echo esc_attr( $cat->slug ); ?>" 
                                                role="tab" 
                                                aria-selected="<?php echo ( $current_category === $cat->slug ) ? 'true' : 'false'; ?>">
                                            <span class="wsp-cs-cat-name"><?php echo esc_html( $cat->name ); ?></span>
                                            <?php if ( 'yes' === $atts['show_counts'] ) : ?>
                                                <span class="wsp-cs-cat-count"><?php echo esc_html( $cat->count ); ?></span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="wsp-cs-controls-right">
                        <div class="wsp-cs-result-count" aria-live="polite">
                            <span class="wsp-cs-count-dot" aria-hidden="true"></span>
                            <span class="wsp-cs-count-label"><?php echo esc_html( $query_result['count_text'] ); ?></span>
                        </div>

                        <?php if ( 'yes' === $atts['show_sorting'] ) : ?>
                            <div class="wsp-cs-sorting-box">
                                <label for="wsp-sort-<?php echo esc_attr( $instance_id ); ?>" class="screen-reader-text"><?php esc_html_e( 'Sort products', 'woo-style-plus' ); ?></label>
                                <div class="wsp-cs-select-wrap">
                                    <select id="wsp-sort-<?php echo esc_attr( $instance_id ); ?>" class="wsp-cs-sort-select">
                                        <option value="date" <?php selected( $current_orderby, 'date' ); ?>><?php esc_html_e( 'Sort by latest', 'woo-style-plus' ); ?></option>
                                        <option value="popularity" <?php selected( $current_orderby, 'popularity' ); ?>><?php esc_html_e( 'Sort by popularity', 'woo-style-plus' ); ?></option>
                                        <option value="rating" <?php selected( $current_orderby, 'rating' ); ?>><?php esc_html_e( 'Sort by average rating', 'woo-style-plus' ); ?></option>
                                        <option value="price" <?php selected( $current_orderby, 'price' ); ?>><?php esc_html_e( 'Sort by price: low to high', 'woo-style-plus' ); ?></option>
                                        <option value="price-desc" <?php selected( $current_orderby, 'price-desc' ); ?>><?php esc_html_e( 'Sort by price: high to low', 'woo-style-plus' ); ?></option>
                                        <option value="title" <?php selected( $current_orderby, 'title' ); ?>><?php esc_html_e( 'Sort by name: A to Z', 'woo-style-plus' ); ?></option>
                                    </select>
                                    <span class="wsp-cs-select-arrow" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PRODUCT CATALOGUE SECTION -->
            <div class="wsp-custom-shop-catalogue">
                <!-- AJAX Loading Spinner Overlay -->
                <div class="wsp-cs-loading-overlay" aria-hidden="true">
                    <div class="wsp-cs-spinner"></div>
                </div>

                <!-- Products Grid Container -->
                <div class="wsp-cs-grid-container">
                    <?php echo $query_result['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>

                <!-- Pagination Container -->
                <?php if ( 'yes' === $atts['paginate'] ) : ?>
                    <div class="wsp-cs-pagination-container">
                        <?php echo $query_result['pagination']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Query WooCommerce products with given filter parameters
     *
     * @param array $args
     * @return array Array containing html, pagination, count_text, found_posts, max_num_pages
     */
    public function query_products( $args ) {
        $search   = ! empty( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
        $category = ! empty( $args['category'] ) ? sanitize_text_field( $args['category'] ) : '';
        $orderby  = ! empty( $args['orderby'] ) ? sanitize_text_field( $args['orderby'] ) : 'date';
        $order    = ! empty( $args['order'] ) ? sanitize_text_field( $args['order'] ) : 'DESC';
        $limit    = ! empty( $args['limit'] ) ? intval( $args['limit'] ) : 12;
        $paged    = ! empty( $args['paged'] ) ? max( 1, intval( $args['paged'] ) ) : 1;
        $columns  = ! empty( $args['columns'] ) ? intval( $args['columns'] ) : 4;

        $query_args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'paged'          => $paged,
        );

        // Keyword search
        if ( ! empty( $search ) ) {
            $query_args['s'] = $search;
        }

        // Category filter
        if ( ! empty( $category ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        // Orderby logic matching WooCommerce standards
        switch ( $orderby ) {
            case 'price':
                $query_args['meta_key'] = '_price';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'ASC';
                break;
            case 'price-desc':
                $query_args['meta_key'] = '_price';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'DESC';
                break;
            case 'popularity':
                $query_args['meta_key'] = 'total_sales';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'DESC';
                break;
            case 'rating':
                $query_args['meta_key'] = '_wc_average_rating';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'DESC';
                break;
            case 'title':
                $query_args['orderby'] = 'title';
                $query_args['order']   = 'ASC';
                break;
            case 'date':
            default:
                $query_args['orderby'] = 'date';
                $query_args['order']   = 'DESC';
                break;
        }

        $query = new WP_Query( $query_args );

        ob_start();
        if ( $query->have_posts() ) {
            echo '<ul class="products columns-' . esc_attr( $columns ) . ' wsp-custom-shop-grid">';
            while ( $query->have_posts() ) {
                $query->the_post();
                /**
                 * Output standard WooCommerce product loop item
                 */
                wc_get_template_part( 'content', 'product' );
            }
            echo '</ul>';
        } else {
            // Modern Empty Catalogue State
            ?>
            <div class="wsp-cs-empty-state">
                <div class="wsp-cs-empty-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </div>
                <h3 class="wsp-cs-empty-title"><?php esc_html_e( 'No products found', 'woo-style-plus' ); ?></h3>
                <p class="wsp-cs-empty-desc">
                    <?php
                    if ( ! empty( $search ) ) {
                        printf(
                            /* translators: %s: search term */
                            esc_html__( 'No products matched "%s". Try searching with a different term or reset your filters.', 'woo-style-plus' ),
                            '<strong>' . esc_html( $search ) . '</strong>'
                        );
                    } else {
                        esc_html_e( 'There are no products matching this category right now. Explore other categories or check back soon.', 'woo-style-plus' );
                    }
                    ?>
                </p>
                <button type="button" class="wsp-cs-reset-filters-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                    <span><?php esc_html_e( 'Reset All Filters', 'woo-style-plus' ); ?></span>
                </button>
            </div>
            <?php
        }
        $html = ob_get_clean();

        // Render Pagination
        $pagination_html = '';
        if ( $query->max_num_pages > 1 ) {
            $pagination_html = $this->render_pagination( $paged, $query->max_num_pages );
        }

        // Count Text
        $total = $query->found_posts;
        if ( 0 === $total ) {
            $count_text = __( 'No products found', 'woo-style-plus' );
        } elseif ( 1 === $total ) {
            $count_text = __( 'Showing the single result', 'woo-style-plus' );
        } else {
            $first = ( $limit * ( $paged - 1 ) ) + 1;
            $last  = min( $total, $limit * $paged );
            /* translators: 1: first result, 2: last result, 3: total results */
            $count_text = sprintf( __( 'Showing %1$d–%2$d of %3$d products', 'woo-style-plus' ), $first, $last, $total );
        }

        wp_reset_postdata();

        return array(
            'html'          => $html,
            'pagination'    => $pagination_html,
            'count_text'    => $count_text,
            'found_posts'   => $total,
            'max_num_pages' => $query->max_num_pages,
        );
    }

    /**
     * Render modern pagination links
     *
     * @param int $current_page
     * @param int $total_pages
     * @return string
     */
    public function render_pagination( $current_page, $total_pages ) {
        $html = '<nav class="wsp-cs-pagination" aria-label="' . esc_attr__( 'Catalogue Pagination', 'woo-style-plus' ) . '">';
        $html .= '<ul class="wsp-cs-page-numbers">';

        // Prev button
        if ( $current_page > 1 ) {
            $html .= '<li><button type="button" class="wsp-cs-page-btn wsp-cs-prev" data-page="' . esc_attr( $current_page - 1 ) . '" aria-label="' . esc_attr__( 'Previous page', 'woo-style-plus' ) . '">&larr; ' . esc_html__( 'Prev', 'woo-style-plus' ) . '</button></li>';
        }

        // Numbered links with ellipsis
        $range = 2;
        $show_first = false;
        $show_last  = false;

        for ( $i = 1; $i <= $total_pages; $i++ ) {
            if ( $i === 1 || $i === $total_pages || ( $i >= $current_page - $range && $i <= $current_page + $range ) ) {
                if ( $i === $current_page ) {
                    $html .= '<li><span class="wsp-cs-page-btn is-current" aria-current="page">' . esc_html( $i ) . '</span></li>';
                } else {
                    $html .= '<li><button type="button" class="wsp-cs-page-btn" data-page="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</button></li>';
                }
            } elseif ( $i < $current_page - $range && ! $show_first ) {
                $html .= '<li><span class="wsp-cs-page-dots">&hellip;</span></li>';
                $show_first = true;
            } elseif ( $i > $current_page + $range && ! $show_last ) {
                $html .= '<li><span class="wsp-cs-page-dots">&hellip;</span></li>';
                $show_last = true;
            }
        }

        // Next button
        if ( $current_page < $total_pages ) {
            $html .= '<li><button type="button" class="wsp-cs-page-btn wsp-cs-next" data-page="' . esc_attr( $current_page + 1 ) . '" aria-label="' . esc_attr__( 'Next page', 'woo-style-plus' ) . '">' . esc_html__( 'Next', 'woo-style-plus' ) . ' &rarr;</button></li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * AJAX endpoint to filter products live
     */
    public function ajax_filter_products() {
        check_ajax_referer( 'wsp_custom_shop_nonce', 'security' );

        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
        $orderby  = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'date';
        $order    = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC';
        $limit    = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 12;
        $columns  = isset( $_POST['columns'] ) ? intval( $_POST['columns'] ) : 4;
        $paged    = isset( $_POST['paged'] ) ? max( 1, intval( $_POST['paged'] ) ) : 1;

        $result = $this->query_products( array(
            'search'   => $search,
            'category' => $category,
            'orderby'  => $orderby,
            'order'    => $order,
            'limit'    => $limit,
            'columns'  => $columns,
            'paged'    => $paged,
        ) );

        wp_send_json_success( $result );
    }
}
