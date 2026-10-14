=== Woo Style+ ===
Contributors: Excellence
Tags: woocommerce, redesign, product card, shop page, woocommerce styling, checkout, cart
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform and modernize all your WooCommerce pages: Shop, Product Cards, Single Product details, Cart, and Checkout pages with custom styles and 1-click presets.

== Description ==

**Woo Style+** is the ultimate design and styling engine for WooCommerce. It empowers store owners and designers to redesign every WooCommerce page without touching PHP templates or conflicting with other plugins and themes.

### ✨ Key Features

* **Redesign All WooCommerce Pages**:
  * **Shop & Category Archives**: Responsive CSS Grid layouts with custom columns for Desktop, Tablet, and Mobile.
  * **Product Cards**: Elevated shadows, rounded corners, custom padding, border controls, and fluid hover animations (Lift, Zoom, Scale, Glow).
  * **Shortcodes & Gutenberg Blocks**: Full compatibility with `[products]`, `[featured_products]`, `[sale_products]`, `[recent_products]`, and WooCommerce blocks.
  * **Single Product Details**: Modern gallery layouts, interactive (+ / -) quantity steppers, customizable card/underline tabs, and mobile sticky add-to-cart bar.
  * **Cart & Checkout**: Modern 2-column conversion-optimized layouts, sleek form inputs with focus rings, and elevated order summaries.
  * **My Account Portal**: Sleek authentication cards for Login/Register, 2-column dashboard layout, vertical sidebar navigation cards (or horizontal pills), user greeting avatar banner, and colored order status pill badges.
  * **Notices & Banners**: Modern floating alert toasts for cart notifications and errors.

* **1-Click Design Presets**:
  * **Modern Minimal**: Clean white aesthetic, slate typography, and subtle elevation.
  * **Dark Luxe**: Deep obsidian surfaces with glowing gold highlights and dark cards.
  * **Nordic Clean**: Soft pastels, emerald accents, and organic rounded corners.
  * **Sunset Vibrant**: Energetic coral and violet accents for modern lifestyle brands.
  * **Soft Neomorphic**: Tactile shadows and velvety rounded surfaces.

* **Live Interactive Card Preview**:
  * Real-time preview right inside the WordPress admin dashboard so you can visualize color, border, and shadow adjustments instantly before saving.

* **Rock-Solid Compatibility**:
  * Full WooCommerce High-Performance Order Storage (HPOS) compatibility declared.
  * Clean CSS variable scoping (`--wsp-*`) that firmly overrides stubborn theme styles (Astra, OceanWP, Divi, Elementor, Storefront, Twenty Twenty-Four, etc.) without destructive CSS hacks.
  * Zero PHP namespace or JavaScript collisions.

== Installation ==

1. Upload the `Woocommerce styles` folder to your WordPress `/wp-content/plugins/` directory (or zip and install via Plugins > Add New > Upload Plugin).
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Woo Style+** (or WooCommerce > Woo Style+) in your admin sidebar.
4. Select a 1-click preset or customize colors, grid columns, and card styles to match your brand.
5. Click **Save All Changes** and enjoy your redesigned storefront!

== Frequently Asked Questions ==

= Does Woo Style+ work with existing WooCommerce shortcodes? =
Yes! Woo Style+ targets WooCommerce's loop classes, which means `[products]`, `[sale_products]`, `[featured_products]`, and product category shortcodes automatically adopt the modern card design, shadows, and hover animations.

= Will it conflict with my active WordPress theme? =
No. Woo Style+ uses CSS Custom Properties with high-specificity scoped rules (`body.wsp-active`) that override the theme's default styles cleanly while respecting the theme's core header, footer, and container hierarchy.

= Can I disable the styling without deactivating the plugin? =
Yes. There is a master toggle at the top of the Woo Style+ settings page to activate or deactivate styling at any time.

== Changelog ==

= 1.0.5 =
* Fix: Automatic reactivation of plugin after 1-click update so styles never get deactivated during upgrade.
* Fix: Multi-pass cleaning for preformatted (<pre>) blocks wrapping WooCommerce shortcode content at both early and late rendering priorities.
* Fix: CSS child reset for any preformatted block descendants to guarantee zero monospace font leaks.

= 1.0.4 =
* Feature: Full Page Spacing & Padding Controls in settings for all WooCommerce pages.
* Feature: Global Top Padding, Bottom Padding, and Horizontal side padding controls.
* Feature: Individual page padding overrides for Shop & Archives, Single Product, Cart, Checkout, and My Account pages.

= 1.0.3 =
* Redesign: Modern Empty Cart showcase card with SVG shopping illustration, centered typography, and styled Return to Shop CTA button.
* Fix: Proper container max-width and top/bottom spacing for Cart and Checkout pages on full-width themes.
* Fix: Prevent horizontal inline layout of empty cart notice and shop return button.

= 1.0.2 =
* UI Cleanup: Removed redundant "Check for update" button from the settings page header (update check remains available on the main WordPress Plugins screen).

= 1.0.1 =
* Fix: My Account page layout breakdown caused by accidental preformatted (<pre>) blocks and theme float clearfixes.
* Fix: Automatic filter to clean <pre> wrappers around WooCommerce shortcodes.
* Fix: Scoped selectors for body.woocommerce-account and full-width container widths.
* Enhancement: Modern flexbox 2-column conversion layouts for Cart and Checkout pages.
* Enhancement: Improved 1-click update file replacement safety.

= 1.0.0 =
* Initial release of Woo Style+.
* Modern Shop, Product Cards, Single Product, Cart, and Checkout redesign engines.
* 5 built-in 1-click design presets with live admin card preview.
* Interactive quantity stepper and mobile sticky CTA bar.
* Declared full compatibility with WooCommerce HPOS and modern cart/checkout blocks.
