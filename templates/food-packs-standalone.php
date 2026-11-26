<?php
/**
 * Standalone Food Packs Ordering Page Template
 *
 * Clean HTML shell without theme header, footer, sidebar, or theme styles.
 * Preserves wp_head() and wp_footer() so WooCommerce payment gateway scripts work seamlessly.
 *
 * @package WooStylePlus
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wsp-fp-standalone-body' ); ?>>
<?php
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
}

while ( have_posts() ) :
	the_post();
	the_content();
endwhile;

wp_footer();
?>
</body>
</html>
