=== Woo Style+ ===
Contributors: Excellence
Tags: woocommerce, redesign, product card, shop page, woocommerce styling, checkout, cart
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.6
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

= 1.1.6 =
* Feature: Shop archive and custom shop page display in 2 columns on mobile screens by default.
* Enhancement: Product card image thumbnails guaranteed perfect square (1:1 aspect ratio) with smooth rounded corners and cover crop.
* Redesign: Pixel-perfect, modern, and compact Cart page with unified coupon group, circular remove action, crisp product thumbnails, styled shipping radio options, and sticky order totals.
* Redesign: High-conversion, elegant Checkout page with compact input controls, borderless order review summary, and styled payment method cards.
* Enhancement: Mobile native app card experience for Cart page with streamlined item summaries.

= 1.1.5 =
* Fix: Multi-row wrapping for category filter pills on mobile screens, completely eliminating horizontal blowout and off-screen overflow.
* Enhancement: Compact category pill dimensions and typography optimized for mobile screens (up to 768px).
* Fix: Expanded mobile responsive breakpoint to 768px for complete tablet and mobile coverage.
* Enhancement: Text truncation with ellipsis for long category labels on mobile devices.

= 1.1.4 =
* Fix: Mobile category overflow containment preventing horizontal viewport blowout and layout shifting.
* Fix: Search input and search button remain strictly on the same row on mobile screens.
* Enhancement: Search submit button displays exclusively the search icon on mobile devices with compact touch target.
* Fix: Wrapped search input inside an isolated relative container to eliminate floating search icon misalignment.

= 1.0.6 =
* Fix: Add `upgrader_source_selection` hook to prevent GitHub folder renaming issues during 1-click update.
* Fix: Retain exact plugin directory name during update so WordPress never flags missing files or deactivates.

= 1.0.5 =
* Fix: Automatic reactivation of plugin after 1-click update so styles never get deactivated during upgrade.
* Fix: Multi-pass cleaning for preformatted (<pre>) blocks wrapping WooCommerce shortcode content at both early and late rendering priorities.
* Fix: CSS child reset for any preformatted block descendants to guarantee zero monospace font leaks.

= 1.1.0 =
* Feature: Custom Shop Page Shortcode [wsp_custom_shop] to embed a full-featured, modern shop experience on any page.
* Feature: Elevated Header Container with interactive Search Bar, debounced live AJAX filtering, and clear action.
* Feature: Modern Category Pills display with "All Products" chip, category counts, and instant filter switching.
* Feature: Interactive Sorting dropdown (Latest, Popularity, Rating, Price low-to-high, Price high-to-low, Title).
* Feature: AJAX Product Catalogue with smooth loading states, pagination, and empty state CTA.
* Feature: Added Custom Shop Shortcode showcase & 1-click copy card in the admin settings dashboard.

= 1.1.3 =
* Fix: Search input and search button now remain on the exact same row on mobile screens.
* Enhancement: Search submit button displays exclusively the search icon on mobile screens with compact 42px touch size.
* Fix: Wrapped search input inside an isolated relative container to eliminate floating search icon misalignment on mobile.
* Enhancement: Clean horizontal scroll for category pills and aligned sorting controls on mobile devices.

= 1.1.2 =
* Enhancement: Compact 2-row layout for Custom Shop header container consolidating categories and sorting controls into a unified row.
* Enhancement: Applied visible, soothing sage-mint background color (#edf5f0) by default to the header container with clean border (#d1e7d8).
* Enhancement: Reduced container padding and search height for a tight, modern, and high-efficiency storefront feel.
* Enhancement: Added "slate", "white", and custom hex color options for header_bg attribute.

= 1.1.1 =
* Enhancement: Redesigned Custom Shop Page shortcode [wsp_custom_shop] with a fancy, luxury styled search & category header.
* Feature: Added distinct tinted gradient background to the header container with support for custom backgrounds via header_bg attribute ("gradient", "emerald", "slate", "dark", or custom hex/CSS).
* Feature: Added generous outer page paddings around the custom shop widget with support for custom padding attribute.
* Feature: Elevated floating white search bar with search button icon, clear button, and focus glow.
* Fix: Changed default hide_empty to 'no' so created categories display prominently even when products have not yet been assigned.
* Feature: Added badge attribute for optional chic header pill badges.

= 1.1.0 =
* Feature: Introduced Custom Shop Page shortcodes [wsp_custom_shop], [woo_style_custom_shop], and [woo_style_shop].
* Feature: Live AJAX debounced search bar filtering WooCommerce catalogue without full page reload.
* Feature: Interactive Category Pills with live counter badges.
* Feature: Integrated sorting dropdown (latest, popularity, rating, price low-to-high, price high-to-low, name A-Z).
* Feature: Responsive product catalogue grid with custom desktop column attributes and pagination.

= 1.0.9 =
* Critical Fix: Removed destructive directory deletion and move logic from post_install_cleanup in the in-app updater.
* Fix: Preserved exact installed directory structure during 1-click update so WordPress never triggers "Plugin file does not exist" or deactivates the plugin.
* Enhancement: Added safe cache cleaning via wp_clean_plugins_cache() and immediate active status validation.

= 1.0.8 =
* Feature: Added modern vector icons to My Account navigation tabs (Dashboard, Orders, Downloads, Address, Account details, Log out, Payment methods, Subscriptions).
* Feature: Vector icons automatically adapt to active and hover color states via currentColor inheritance.
* Feature: Added admin toggle in My Account settings to enable/disable navigation icons.

= 1.0.7 =
* Fix: Address tab card layout squished into narrow vertical stack due to theme/Bootstrap .col-1 and float collisions.
* Enhancement: Modern card layout for single and double addresses in My Account portal.
* Enhancement: Redesigned Address card header with aligned Edit/Add button and cleanly formatted address typography.
* Enhancement: Modern grid layout and styling for Edit Address and Account Details forms.

= 1.0.6 =
* Fix: Plugin deactivation issue during 1-click update by normalizing unzipped GitHub source folder name and auto-reactivating plugin cleanly.

= 1.0.5 =
* Enhancement: Streamlined WordPress in-app updater with GitHub release asset attachment.

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
