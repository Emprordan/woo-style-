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

// Get total products count
$total_products = ( $current_cat && isset( $current_cat->count ) ) ? intval( $current_cat->count ) : 0;

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
        <?php if ( have_posts() ) : ?>

            <div class="wsp-archive-toolbar">
                <div class="wsp-archive-result-count">
                    <span class="wsp-pulse-dot"></span>
                    <?php woocommerce_result_count(); ?>
                </div>

                <div class="wsp-archive-sorting-wrap">
                    <?php woocommerce_catalog_ordering(); ?>
                </div>
            </div>

            <?php
            woocommerce_product_loop_start();

            while ( have_posts() ) {
                the_post();
                do_action( 'woocommerce_shop_loop' );
                wc_get_template_part( 'content', 'product' );
            }

            woocommerce_product_loop_end();

            woocommerce_pagination();
            ?>

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
