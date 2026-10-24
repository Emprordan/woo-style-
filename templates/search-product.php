<?php
/**
 * Custom WooCommerce Search Results Page Template
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$search_query = get_search_query();
$paged        = max( 1, get_query_var( 'paged' ) );
$orderby      = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'date';
$selected_cat = isset( $_GET['product_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) : '';

$per_page = apply_filters( 'loop_shop_per_page', 12 );

// Build WP_Query for products
$query_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    's'              => $search_query,
    'paged'          => $paged,
    'posts_per_page' => $per_page,
);

if ( ! empty( $selected_cat ) ) {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $selected_cat,
        ),
    );
}

// Orderby sorting logic
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

$search_query_obj = new WP_Query( $query_args );
$total_products   = $search_query_obj->found_posts;
$max_pages        = $search_query_obj->max_num_pages;

// Fetch product categories for quick filter chips
$all_categories = get_terms( array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'number'     => 12,
) );
if ( is_wp_error( $all_categories ) ) {
    $all_categories = array();
}
?>

<div class="wsp-archive-page-wrap wsp-search-page-wrap">
    <div class="container container-1230 woocommerce">

        <!-- SEARCH HERO BANNER -->
        <div class="wsp-archive-hero-banner wsp-search-hero-banner">
            <div class="wsp-archive-hero-inner">
                
                <div class="wsp-search-hero-top">
                    <div class="wsp-search-hero-meta">
                        <span class="wsp-archive-pill-tag">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <span><?php esc_html_e( 'Catalogue Search', 'woo-style-plus' ); ?></span>
                        </span>
                        <h1 class="wsp-archive-hero-title">
                            <?php if ( ! empty( $search_query ) ) : ?>
                                <?php printf( esc_html__( 'Results for: "%s"', 'woo-style-plus' ), esc_html( $search_query ) ); ?>
                            <?php else : ?>
                                <?php esc_html_e( 'Search Products', 'woo-style-plus' ); ?>
                            <?php endif; ?>
                        </h1>
                    </div>

                    <div class="wsp-archive-count-badge">
                        <span class="wsp-count-num"><?php echo esc_html( $total_products ); ?></span>
                        <span class="wsp-count-label"><?php echo esc_html( _n( 'Found', 'Found', $total_products, 'woo-style-plus' ) ); ?></span>
                    </div>
                </div>

                <!-- LIVE SEARCH BAR -->
                <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="wsp-search-page-form">
                    <div class="wsp-search-input-wrap">
                        <span class="wsp-search-input-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input type="search" 
                               name="s" 
                               class="wsp-search-page-input" 
                               value="<?php echo esc_attr( $search_query ); ?>" 
                               placeholder="<?php esc_attr_e( 'Search again by product name or keyword...', 'woo-style-plus' ); ?>" 
                               autocomplete="off">
                        <input type="hidden" name="post_type" value="product">
                        <?php if ( ! empty( $selected_cat ) ) : ?>
                            <input type="hidden" name="product_cat" value="<?php echo esc_attr( $selected_cat ); ?>">
                        <?php endif; ?>
                        <button type="submit" class="wsp-search-page-submit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <span><?php esc_html_e( 'Search', 'woo-style-plus' ); ?></span>
                        </button>
                    </div>
                </form>

                <!-- QUICK CATEGORY FILTER CHIPS -->
                <?php if ( ! empty( $all_categories ) ) : ?>
                    <div class="wsp-search-cat-chips-bar">
                        <span class="wsp-search-filter-label"><?php esc_html_e( 'Filter by:', 'woo-style-plus' ); ?></span>
                        <a href="<?php echo esc_url( add_query_arg( array( 's' => $search_query, 'post_type' => 'product' ), home_url( '/' ) ) ); ?>" 
                           class="wsp-archive-cat-pill <?php echo empty( $selected_cat ) ? 'is-active' : ''; ?>">
                            <span><?php esc_html_e( 'All', 'woo-style-plus' ); ?></span>
                        </a>
                        <?php foreach ( $all_categories as $cat ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( array( 's' => $search_query, 'post_type' => 'product', 'product_cat' => $cat->slug ), home_url( '/' ) ) ); ?>" 
                               class="wsp-archive-cat-pill <?php echo ( $selected_cat === $cat->slug ) ? 'is-active' : ''; ?>">
                                <span><?php echo esc_html( $cat->name ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- SEARCH RESULTS BODY -->
        <?php if ( $search_query_obj->have_posts() ) : ?>

            <div class="wsp-archive-toolbar">
                <div class="wsp-archive-result-count">
                    <span class="wsp-pulse-dot"></span>
                    <span>
                        <?php
                        $first = ( $per_page * ( $paged - 1 ) ) + 1;
                        $last  = min( $total_products, $per_page * $paged );
                        printf( esc_html__( 'Showing %1$d–%2$d of %3$d products', 'woo-style-plus' ), $first, $last, $total_products );
                        ?>
                    </span>
                </div>

                <div class="wsp-archive-sorting-wrap">
                    <form method="get" class="wsp-archive-sort-form">
                        <input type="hidden" name="s" value="<?php echo esc_attr( $search_query ); ?>">
                        <input type="hidden" name="post_type" value="product">
                        <?php if ( ! empty( $selected_cat ) ) : ?>
                            <input type="hidden" name="product_cat" value="<?php echo esc_attr( $selected_cat ); ?>">
                        <?php endif; ?>
                        <div class="wsp-sort-select-box">
                            <select name="orderby" class="wsp-archive-sort-select" onchange="this.form.submit()">
                                <option value="date" <?php selected( $orderby, 'date' ); ?>><?php esc_html_e( 'Sort by latest', 'woo-style-plus' ); ?></option>
                                <option value="popularity" <?php selected( $orderby, 'popularity' ); ?>><?php esc_html_e( 'Sort by popularity', 'woo-style-plus' ); ?></option>
                                <option value="rating" <?php selected( $orderby, 'rating' ); ?>><?php esc_html_e( 'Sort by average rating', 'woo-style-plus' ); ?></option>
                                <option value="price" <?php selected( $orderby, 'price' ); ?>><?php esc_html_e( 'Sort by price: low to high', 'woo-style-plus' ); ?></option>
                                <option value="price-desc" <?php selected( $orderby, 'price-desc' ); ?>><?php esc_html_e( 'Sort by price: high to low', 'woo-style-plus' ); ?></option>
                                <option value="title" <?php selected( $orderby, 'title' ); ?>><?php esc_html_e( 'Sort by name: A to Z', 'woo-style-plus' ); ?></option>
                            </select>
                            <span class="wsp-sort-arrow" aria-hidden="true">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PRODUCT GRID -->
            <ul class="products columns-4">
                <?php
                while ( $search_query_obj->have_posts() ) {
                    $search_query_obj->the_post();
                    wc_get_template_part( 'content', 'product' );
                }
                wp_reset_postdata();
                ?>
            </ul>

            <!-- PAGINATION -->
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

            <!-- ZERO RESULTS EMPTY STATE -->
            <div class="wsp-archive-empty-state">
                <div class="wsp-empty-icon-wrap">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </div>
                <h2 class="wsp-empty-title"><?php esc_html_e( 'No products found', 'woo-style-plus' ); ?></h2>
                <p class="wsp-empty-subtitle">
                    <?php if ( ! empty( $search_query ) ) : ?>
                        <?php printf( esc_html__( 'We couldn\'t find any products matching "%s". Try checking for spelling errors, using simpler keywords, or explore our popular categories below.', 'woo-style-plus' ), '<strong>' . esc_html( $search_query ) . '</strong>' ); ?>
                    <?php else : ?>
                        <?php esc_html_e( 'Try typing a keyword above to find products in our store.', 'woo-style-plus' ); ?>
                    <?php endif; ?>
                </p>

                <?php if ( ! empty( $all_categories ) ) : ?>
                    <div class="wsp-empty-cat-links">
                        <span class="wsp-empty-cat-hint"><?php esc_html_e( 'Popular categories:', 'woo-style-plus' ); ?></span>
                        <div class="wsp-empty-pills">
                            <?php foreach ( $all_categories as $c ) : ?>
                                <a href="<?php echo esc_url( get_term_link( $c ) ); ?>" class="wsp-empty-cat-pill">
                                    <?php echo esc_html( $c->name ); ?> (<?php echo esc_html( $c->count ); ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

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
