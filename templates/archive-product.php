<?php
/**
 * Custom WooCommerce Shop Page Template (Archive: All Products)
 *
 * Replaces the default theme shop/archive-product template with the same
 * modern compact 2-row hero banner design used on category archive pages.
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

// 1. Pagination & Ordering parameters
$paged         = max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );
$orderby_param = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';

// 2. Query all published products directly (bypass corrupted global $wp_query)
$shop_query_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => apply_filters( 'loop_shop_per_page', 12 ),
    'paged'          => $paged,
);

// Sorting order
switch ( $orderby_param ) {
    case 'price':
        $shop_query_args['meta_key'] = '_price';
        $shop_query_args['orderby']  = 'meta_value_num';
        $shop_query_args['order']    = 'ASC';
        break;
    case 'price-desc':
        $shop_query_args['meta_key'] = '_price';
        $shop_query_args['orderby']  = 'meta_value_num';
        $shop_query_args['order']    = 'DESC';
        break;
    case 'popularity':
        $shop_query_args['meta_key'] = 'total_sales';
        $shop_query_args['orderby']  = 'meta_value_num';
        $shop_query_args['order']    = 'DESC';
        break;
    case 'rating':
        $shop_query_args['meta_key'] = '_wc_average_rating';
        $shop_query_args['orderby']  = 'meta_value_num';
        $shop_query_args['order']    = 'DESC';
        break;
    case 'date':
        $shop_query_args['orderby'] = 'date';
        $shop_query_args['order']   = 'DESC';
        break;
    case 'title':
        $shop_query_args['orderby'] = 'title';
        $shop_query_args['order']   = 'ASC';
        break;
    default:
        $shop_query_args['orderby'] = 'menu_order title';
        $shop_query_args['order']   = 'ASC';
        break;
}

$shop_query     = new WP_Query( $shop_query_args );
$total_products = $shop_query->found_posts;
$max_pages      = $shop_query->max_num_pages;

// 3. Fetch all product categories for the category switcher pills
$all_categories = get_terms( array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
) );
if ( is_wp_error( $all_categories ) ) {
    $all_categories = array();
}

// 4. Shop page description (from WooCommerce page content)
$shop_page    = wc_get_page_id( 'shop' ) ? get_post( wc_get_page_id( 'shop' ) ) : null;
$shop_page_desc = ( $shop_page && ! empty( $shop_page->post_content ) ) ? $shop_page->post_content : '';
?>

<div class="wsp-archive-page-wrap wsp-shop-page">
    <div class="container container-1230 woocommerce">

        <!-- COMPACT SHOP HERO HEADER BANNER -->
        <div class="wsp-archive-hero-banner wsp-shop-hero-banner wsp-hero-compact">
            <div class="wsp-archive-hero-inner">

                <!-- Row 1: Breadcrumbs on Left, Product Count Badge on Right -->
                <div class="wsp-archive-hero-top-row">
                    <nav class="wsp-archive-breadcrumbs" aria-label="Breadcrumb">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'woo-style-plus' ); ?></a>
                        <span class="wsp-sep">/</span>
                        <span class="wsp-current"><?php esc_html_e( 'Shop', 'woo-style-plus' ); ?></span>
                    </nav>

                    <div class="wsp-archive-count-pill">
                        <span class="wsp-count-dot"></span>
                        <span><?php printf( esc_html( _n( '%s Product', '%s Products', $total_products, 'woo-style-plus' ) ), number_format_i18n( $total_products ) ); ?></span>
                    </div>
                </div>

                <!-- Row 2: Shop Title on Left, Category Switcher Pills on Right -->
                <div class="wsp-archive-hero-bottom-row">
                    <div class="wsp-archive-title-group">
                        <h1 class="wsp-archive-hero-title"><?php esc_html_e( 'All Products', 'woo-style-plus' ); ?></h1>
                    </div>

                    <?php if ( ! empty( $all_categories ) ) : ?>
                        <div class="wsp-archive-cat-pills-bar">
                            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wsp-archive-cat-pill is-active">
                                <span class="wsp-cat-pill-icon">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                </span>
                                <span><?php esc_html_e( 'All', 'woo-style-plus' ); ?></span>
                            </a>
                            <?php foreach ( $all_categories as $cat ) : ?>
                                <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
                                   class="wsp-archive-cat-pill">
                                    <span><?php echo esc_html( $cat->name ); ?></span>
                                    <span class="wsp-cat-pill-count"><?php echo esc_html( $cat->count ); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $shop_page_desc ) ) : ?>
                    <div class="wsp-archive-hero-desc">
                        <?php echo wp_kses_post( wpautop( $shop_page_desc ) ); ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- PRODUCT LOOP & CONTROLS -->
        <?php if ( $shop_query->have_posts() ) : ?>

            <div class="wsp-archive-toolbar">
                <div class="wsp-archive-result-count">
                    <span class="wsp-pulse-dot"></span>
                    <span>
                        <?php
                        $per_page = $shop_query_args['posts_per_page'];
                        $first    = ( $per_page * ( $paged - 1 ) ) + 1;
                        $last     = min( $total_products, $per_page * $paged );
                        if ( $total_products <= 1 ) {
                            printf( esc_html__( 'Showing %d result', 'woo-style-plus' ), $total_products );
                        } elseif ( $max_pages > 1 ) {
                            printf( esc_html__( 'Showing %1$d–%2$d of %3$d products', 'woo-style-plus' ), $first, $last, $total_products );
                        } else {
                            printf( esc_html__( 'Showing all %d results', 'woo-style-plus' ), $total_products );
                        }
                        ?>
                    </span>
                </div>

                <div class="wsp-archive-sorting-wrap">
                    <form method="get" class="wsp-archive-sort-form">
                        <div class="wsp-sort-select-box">
                            <select name="orderby" class="wsp-archive-sort-select" onchange="this.form.submit()">
                                <option value="menu_order" <?php selected( $orderby_param, 'menu_order' ); ?>><?php esc_html_e( 'Default sorting', 'woo-style-plus' ); ?></option>
                                <option value="popularity" <?php selected( $orderby_param, 'popularity' ); ?>><?php esc_html_e( 'Sort by popularity', 'woo-style-plus' ); ?></option>
                                <option value="rating"     <?php selected( $orderby_param, 'rating' ); ?>><?php esc_html_e( 'Sort by average rating', 'woo-style-plus' ); ?></option>
                                <option value="date"       <?php selected( $orderby_param, 'date' ); ?>><?php esc_html_e( 'Sort by latest', 'woo-style-plus' ); ?></option>
                                <option value="price"      <?php selected( $orderby_param, 'price' ); ?>><?php esc_html_e( 'Sort by price: low to high', 'woo-style-plus' ); ?></option>
                                <option value="price-desc" <?php selected( $orderby_param, 'price-desc' ); ?>><?php esc_html_e( 'Sort by price: high to low', 'woo-style-plus' ); ?></option>
                                <option value="title"      <?php selected( $orderby_param, 'title' ); ?>><?php esc_html_e( 'Sort by name: A to Z', 'woo-style-plus' ); ?></option>
                            </select>
                            <span class="wsp-sort-arrow" aria-hidden="true">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <ul class="products columns-4">
                <?php
                while ( $shop_query->have_posts() ) {
                    $shop_query->the_post();
                    wc_get_template_part( 'content', 'product' );
                }
                wp_reset_postdata();
                ?>
            </ul>

            <?php if ( $max_pages > 1 ) : ?>
                <nav class="woocommerce-pagination wsp-archive-pagination">
                    <?php
                    echo paginate_links( array(
                        'base'      => esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) ),
                        'format'    => '',
                        'add_args'  => false,
                        'current'   => max( 1, $paged ),
                        'total'     => $max_pages,
                        'prev_text' => '&larr;',
                        'next_text' => '&rarr;',
                        'type'      => 'list',
                        'end_size'  => 3,
                        'mid_size'  => 3,
                    ) );
                    ?>
                </nav>
            <?php endif; ?>

        <?php else : ?>

            <div class="wsp-archive-empty-state">
                <div class="wsp-empty-icon-wrap">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <h2 class="wsp-empty-title"><?php esc_html_e( 'No products found', 'woo-style-plus' ); ?></h2>
                <p class="wsp-empty-subtitle"><?php esc_html_e( 'We are adding new products regularly. Please check back soon!', 'woo-style-plus' ); ?></p>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php
get_footer( 'shop' );
