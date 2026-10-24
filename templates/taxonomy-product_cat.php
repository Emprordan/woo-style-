<?php
/**
 * Custom WooCommerce Category Archive Page Template
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$current_cat = get_queried_object();
$cat_id      = ( $current_cat && isset( $current_cat->term_id ) ) ? $current_cat->term_id : 0;
$cat_name    = ( $current_cat && isset( $current_cat->name ) ) ? $current_cat->name : __( 'Category', 'woo-style-plus' );
$cat_desc    = ( $current_cat && isset( $current_cat->description ) ) ? $current_cat->description : '';

// 1. Pagination & Ordering parameters
$paged         = max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );
$orderby_param = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';

// 2. Query products for this category explicitly to eliminate theme/header query collisions
$cat_query_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => apply_filters( 'loop_shop_per_page', 12 ),
    'paged'          => $paged,
    'tax_query'      => array(
        array(
            'taxonomy'         => 'product_cat',
            'field'            => 'term_id',
            'terms'            => $cat_id,
            'include_children' => true,
        ),
    ),
);

// Sorting order
switch ( $orderby_param ) {
    case 'price':
        $cat_query_args['meta_key'] = '_price';
        $cat_query_args['orderby']  = 'meta_value_num';
        $cat_query_args['order']    = 'ASC';
        break;
    case 'price-desc':
        $cat_query_args['meta_key'] = '_price';
        $cat_query_args['orderby']  = 'meta_value_num';
        $cat_query_args['order']    = 'DESC';
        break;
    case 'popularity':
        $cat_query_args['meta_key'] = 'total_sales';
        $cat_query_args['orderby']  = 'meta_value_num';
        $cat_query_args['order']    = 'DESC';
        break;
    case 'rating':
        $cat_query_args['meta_key'] = '_wc_average_rating';
        $cat_query_args['orderby']  = 'meta_value_num';
        $cat_query_args['order']    = 'DESC';
        break;
    case 'date':
        $cat_query_args['orderby']  = 'date';
        $cat_query_args['order']    = 'DESC';
        break;
    case 'title':
        $cat_query_args['orderby']  = 'title';
        $cat_query_args['order']    = 'ASC';
        break;
    default:
        $cat_query_args['orderby']  = 'menu_order title';
        $cat_query_args['order']    = 'ASC';
        break;
}

$cat_query      = new WP_Query( $cat_query_args );
$total_products = $cat_query->found_posts;
$max_pages      = $cat_query->max_num_pages;

// Fetch all product categories for the top interactive switcher pills
$all_categories = get_terms( array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
) );
if ( is_wp_error( $all_categories ) ) {
    $all_categories = array();
}

// Breadcrumb ancestors
$ancestors = $cat_id ? get_ancestors( $cat_id, 'product_cat' ) : array();
$ancestors = array_reverse( $ancestors );
?>

<div class="wsp-archive-page-wrap wsp-category-archive-page">
    <div class="container container-1230 woocommerce">

        <!-- CATEGORY HERO HEADER BANNER -->
        <div class="wsp-archive-hero-banner wsp-category-hero-banner">
            <div class="wsp-archive-hero-inner">

                <!-- Breadcrumbs -->
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
                        <div class="wsp-archive-title-wrap">
                            <h1 class="wsp-archive-hero-title"><?php echo esc_html( $cat_name ); ?></h1>
                            <span class="wsp-archive-inline-badge"><?php printf( esc_html( _n( '%s Product', '%s Products', $total_products, 'woo-style-plus' ) ), number_format_i18n( $total_products ) ); ?></span>
                        </div>
                    </div>

                    <div class="wsp-archive-count-badge wsp-desktop-count-badge">
                        <span class="wsp-count-num"><?php echo esc_html( $total_products ); ?></span>
                        <span class="wsp-count-label"><?php echo esc_html( _n( 'Product', 'Products', $total_products, 'woo-style-plus' ) ); ?></span>
                    </div>
                </div>

                <?php if ( ! empty( $cat_desc ) ) : ?>
                    <div class="wsp-archive-hero-desc">
                        <?php echo wp_kses_post( wpautop( $cat_desc ) ); ?>
                    </div>
                <?php endif; ?>

                <!-- CATEGORY NAVIGATION PILLS -->
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

        <!-- PRODUCT LOOP & CONTROLS -->
        <?php if ( $cat_query->have_posts() ) : ?>

            <div class="wsp-archive-toolbar">
                <div class="wsp-archive-result-count">
                    <span class="wsp-pulse-dot"></span>
                    <span>
                        <?php
                        $first = ( $cat_query_args['posts_per_page'] * ( $paged - 1 ) ) + 1;
                        $last  = min( $total_products, $cat_query_args['posts_per_page'] * $paged );
                        if ( $total_products <= 1 ) {
                            printf( esc_html__( 'Showing %d result', 'woo-style-plus' ), $total_products );
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
                                <option value="rating" <?php selected( $orderby_param, 'rating' ); ?>><?php esc_html_e( 'Sort by average rating', 'woo-style-plus' ); ?></option>
                                <option value="date" <?php selected( $orderby_param, 'date' ); ?>><?php esc_html_e( 'Sort by latest', 'woo-style-plus' ); ?></option>
                                <option value="price" <?php selected( $orderby_param, 'price' ); ?>><?php esc_html_e( 'Sort by price: low to high', 'woo-style-plus' ); ?></option>
                                <option value="price-desc" <?php selected( $orderby_param, 'price-desc' ); ?>><?php esc_html_e( 'Sort by price: high to low', 'woo-style-plus' ); ?></option>
                                <option value="title" <?php selected( $orderby_param, 'title' ); ?>><?php esc_html_e( 'Sort by name: A to Z', 'woo-style-plus' ); ?></option>
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
                while ( $cat_query->have_posts() ) {
                    $cat_query->the_post();
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
                <h2 class="wsp-empty-title"><?php esc_html_e( 'No products found in this category', 'woo-style-plus' ); ?></h2>
                <p class="wsp-empty-subtitle"><?php esc_html_e( 'We are updating our catalogue regularly. Explore our other popular categories below.', 'woo-style-plus' ); ?></p>
                <div class="wsp-empty-cta-wrap">
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button wsp-empty-btn">
                        <span><?php esc_html_e( 'Explore All Products', 'woo-style-plus' ); ?></span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php
get_footer( 'shop' );
