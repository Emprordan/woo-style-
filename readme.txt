=== Woo Style+ ===
Contributors: Excellence
Tags: woocommerce, redesign, product card, shop page, woocommerce styling, checkout, cart
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.3.8
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
  * **Side Cart Drawer & Elementor Widget**: Modern slide-out mini-cart drawer with live quantity steppers, instant item removal, and customizable Elementor cart icon widget.
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

= 1.3.8 =
* Fix: Removed `position: sticky` from single product gallery card to completely eliminate scroll collision and overlapping of product tabs, description, and related products over the image.
* Enhancement: Reinforced relative stacking contexts and clear-both positioning on product tabs (`.woocommerce-tabs`) and related products (`.related.products`).

= 1.3.7 =
* Fix: Targeted and completely suppressed Uncut theme specific single product components (`.tp-breadcrumb-area`, `.tp-hero-cpr-shape`, `.tp-postbox-cpr-left-social`, `.tp-postbox-cpr-comment`, `.tp-blog-area`, `.tp-postbox-details-form`).
* Enhancement: Expanded product container in Uncut theme from restricted 66% width (`.col-lg-8`) to 100% full showcase width and reduced excessive 110px top theme padding.
* Enhancement: Upgraded automatic updater engine to check both GitHub releases and git tags with live cache busting on "Check Again".

= 1.3.6 =
* Feature: Added automatic suppression for extraneous theme components on Single Product pages.
* Enhancement: Completely disabled theme hero sections, author/date headers, and wave shape dividers on product pages.
* Enhancement: Hidden theme author bio box, theme related blog posts ("You may also like!"), and duplicate blog comment forms ("Leave A Comment") outside WooCommerce tabs.
* Enhancement: Added admin setting under Single Product tab to manage theme component suppression.
* Fix: Prevented themes from calling standard blog comments.php on product pages via comments_template filter.

= 1.3.5 =
* Redesign: Completely overhauled Single Product page into a unified, modern, elevated 2-column showcase layout.
* Redesign: Modern single product gallery card with 16px rounded borders, smooth main image framing, and clean inline thumbnail slider (suppressing unstyled theme FlexSlider bullets).
* Redesign: Beautiful product summary card with modern typography, prominent strike-through del / highlighted ins pricing, glowing stock status pill badges, and refined category/vendor metadata pills.
* Redesign: Enhanced variation selection dropdowns, sleek quantity stepper (+ / -), and elevated Add to Cart button with subtle hover elevation and focus rings.
* Redesign: Modern pill/card tabs for Description, Additional Information, and Customer Reviews with clean attributes table and review styling.
* Enhancement: Applied high-specificity selectors across all single product elements to firmly override stubborn theme defaults (Astra, Storefront, OceanWP, etc.).

= 1.3.4 =
* Fix: Enforced bulletproof 2-column layout on mobile screens across all product catalog and archive pages (Shop, Product Categories, Product Tags, Attributes, Brand archives, Gutenberg product templates, and Elementor archive grids).
* Feature: Filtered `loop_shop_columns` and `woocommerce_loop_columns` hooks in PHP to ensure WooCommerce renders 2 columns on mobile devices by default.
* Enhancement: Applied ultra-high specificity CSS selectors (`html body.wsp-active #primary ul.products...`) to firmly override stubborn theme mobile column restrictions (Astra, Storefront, OceanWP, Kadence, Flatsome, Hello Elementor).
* Enhancement: Proportional mobile card sizing with 1:1 square thumbnails, compact 13px titles, 34px action buttons, and aligned sale badges.
* Fix: Suppressed theme pseudo-element clearfix floats (`::before` / `::after`) that could disrupt mobile grid tracks.

= 1.3.3 =
* Fix: Completely redesigned and compacted the item cards on the Cart page for mobile and desktop screens.
* Enhancement: Mobile cart items styled into individual modern native app cards with crisp borders, subtle elevation, and rounded corners.
* Enhancement: Compacted product thumbnail (54px), streamlined quantity stepper (30px), and aligned line subtotal price on mobile cart items.
* Enhancement: Refined desktop cart table row padding (12px 14px) and thumbnail dimensions (52px) with centered alignment.
* Fix: Strictly locked side cart drawer item and thumbnail dimensions with high-specificity overrides to prevent external theme image stretching.

= 1.3.2 =
* Fix: Completely redesigned Side Cart drawer items to be compact, tight, and unified without vertical gaps.
* Fix: Removed unnatural `justify-content: space-between` on item details that was stretching the title away from the quantity stepper and price.
* Enhancement: Compact 58px product thumbnail with block image anchor wrapping.
* Enhancement: Streamlined quantity stepper to compact 28px height with refined buttons.
* Enhancement: Reset variation and vendor metadata margins to prevent layout expansion.
* Enhancement: Balanced drawer header (15px 20px) and footer padding (16px 20px) for an elegant slide-out cart experience.

= 1.3.1 =
* Fix: Redesigned Cart item layout on mobile screens into modern native app cards with zero horizontal clipping.
* Fix: Suppressed theme/WooCommerce default `data-title::before` pseudo-element text labels (`Product:`, `Quantity:`, `Subtotal:`) in mobile cart items and cart totals.
* Fix: Restored circular remove (X) button at top-right of mobile cart item cards and properly contained product thumbnail and title text.
* Fix: Balanced 2nd row layout in mobile cart item placing quantity stepper on bottom-left and line subtotal on bottom-right.
* Fix: Prevented mobile coupon input and "Apply coupon" button from clipping by applying flex shrinking and zero-overflow boundaries.
* Enhancement: Added theme wrapper padding and margin resets on Cart page for `#primary`, `.site-content`, `.entry-content`, and `.entry-header`.

= 1.3.0 =
* Cleanup: Removed Free Shipping Goal Progress Bar and associated threshold settings from the Side Cart drawer, Elementor widget, and plugin settings.
* Enhancement: Streamlined Side Cart drawer layout with drawer header directly followed by cart items list.

= 1.2.9 =
* Feature: Elementor Widget "Cart Icon & Side Panel" with comprehensive Content and Style controls for custom icons, trigger layouts, count badge, subtotal, and drawer appearance.
* Feature: Interactive slide-out Side Cart Drawer with blurred backdrop, smooth spring animation, live item quantity steppers (+ / -), and instant AJAX item removal without leaving the page.
* Feature: Free Shipping Goal Progress Bar dynamically calculating amount remaining to unlock free delivery.
* Feature: Auto-open side cart on Add to Cart with live count badge pop animations and WooCommerce fragment synchronization.
* Feature: Shortcode `[wsp_cart_icon]` for placing the interactive trigger icon anywhere in theme headers, navigation menus, or widget zones.

= 1.2.8 =
* Fix: Strictly ensured zero shipping options are selected by default on Cart and Checkout pages until explicitly chosen by the customer.
* Enhancement: Session tracking for explicit user shipping selection, preventing WooCommerce from auto-populating or defaulting to the first shipping rate.
* Enhancement: Shipping fee displays as ₦0 / unselected and dropdown displays "Select delivery zone..." placeholder by default on initial page visit.
* Enhancement: Seamless AJAX prefiltering to ensure manual shipping selection is reliably synced to the server and retained throughout checkout.

= 1.2.7 =
* Fix: Cart totals table column overflow where subtotal and grand total prices were pushed outside the right boundary of the Cart Totals card.
* Enhancement: Converted Cart Totals summary table to a block and flexbox layout model with zero-overflow guarantee and proper left/right item alignment.
* Enhancement: Reduced top padding on Cart page by 50% (from 40px to 20px) and reset theme wrapper spacing (#primary, .site-content, .entry-content, .entry-header) for a compact and consistent layout across both Cart and Checkout.
* Enhancement: Added overflow-x containment on the Cart Totals card container.

= 1.2.6 =
* Enhancement: Reduced top padding on Checkout page by 50% (from 40px to 20px) for a more compact and elegant layout.
* Fix: Reset theme wrapper padding and margins (#primary, .site-content, .entry-content, .entry-header) on checkout page to remove excessive white space between the site header and checkout cards.

= 1.2.5 =
* Fix: Checkout review order table column overflow where product totals, subtotals, and total prices spilled outside the card boundary.
* Enhancement: Converted checkout review order table to a robust block and flexbox layout model with zero-overflow guarantee and perfect left/right spacing.
* Fix: Resolved left column customer details squishing by converting customer details into a full-width card with balanced 2-column field grid for name/address fields.
* Enhancement: Consolidated orphaned shipping fields and order notes into the customer details card for a seamless, unified, pixel-perfect checkout aesthetic.
* Enhancement: Added overflow protection and sticky boundary containment on the checkout order review card.

= 1.2.4 =
* Fix: Prevented shipping dropdown container overflow in Cart totals card by enforcing box-sizing, min-width: 0, and ellipsis truncation on long delivery zone names.
* Enhancement: Expanded Cart Totals sidebar width to 420px to match checkout order summary card.
* Feature: Prevented auto-selecting the first shipping method by default, displaying a clean "Select delivery zone..." placeholder until chosen.
* Feature: Enforced required delivery zone / shipping method validation on both Cart ("Proceed to checkout") and Checkout ("Place order") with red alert highlight and warning shake animation.

= 1.2.3 =
* Feature: Embedded modern Checkout Coupon widget directly inside the right-hand "Your order" review container.
* Enhancement: Sleek collapsible toggle card with emerald promo tag icon, rotating chevron, and focus rings.
* Enhancement: Inline real-time coupon feedback badges with immediate error/success messaging without jumping to top of page.
* Enhancement: Suppressed the default top standalone coupon notice banner for a streamlined, clutter-free checkout layout.
* Enhancement: Styled applied discount rows in order review table with emerald badges and soft red remove actions.
* Feature: Added admin setting to toggle embedded coupon widget on Cart & Checkout tab.

= 1.2.2 =
* Fix: Converted Checkout layout from flexbox wrapping to bulletproof 2-column CSS Grid (`minmax(0, 1fr) 420px`).
* Fix: Explicit grid item placement for `#customer_details` (Column 1) and `#order_review` (Column 2) preventing order summary wrap onto row 2.
* Fix: Suppressed loose `#order_review_heading` from disturbing checkout columns and integrated clean card header inside `#order_review`.
* Enhancement: Responsive single-column grid stacking for tablet and mobile devices under 992px.

= 1.2.1 =
* Feature: Automatic cart recalculation on quantity changes with debounced AJAX synchronization.
* Enhancement: Removed the manual "Update cart" button for a clean, modern, seamless shopping cart experience.
* Feature: Admin toggle in Cart & Checkout tab to enable or disable automatic cart updates.

= 1.2.0 =
* Fix: Checkout 2-column layout shift where Order Review card dropped underneath customer details.
* Fix: Prevented loose #order_review_heading from acting as an unnested flex row item in form.checkout.
* Enhancement: Nested #order_review_heading into the sticky #order_review right column card for aligned, cohesive presentation.
* Enhancement: Clean 2-column grid layout for customer details (billing fields and shipping/additional info) on desktop.
* Enhancement: Full-width stacked shipment row in Checkout review order table for the fancy custom shipping dropdown.

= 1.1.9 =
* Redesign: Fancy custom dropdown UI for Cart & Checkout shipping / delivery methods with separate zone titles and highlighted emerald price badges.
* Enhancement: Elevated floating option menu with checkmark indicators, smooth hover transitions, and clean rounded card borders.
* Enhancement: Stacked shipment row layout in Cart Totals table giving the dropdown 100% full card width to prevent text truncation.
* Feature: Outside click and Escape key dismissal for custom floating shipping dropdown.

= 1.1.8 =
* Feature: Compact Dropdown Select for Cart & Checkout shipping / delivery methods, replacing stacked radio lists with a sleek single-line select menu and custom chevron icon.
* Enhancement: Real-time bidirectional sync between custom shipping dropdown and WooCommerce AJAX recalculation triggers.
* Feature: Admin setting in Cart & Checkout tab to switch between "Compact Dropdown Select" and "Stacked Radio Cards".
* Enhancement: Responsive mobile optimization for shipping selection with native OS bottom sheet/picker support.

= 1.1.7 =
* Fix: Full-width layout containment for WooCommerce notices and alerts preventing 2-column cart layout shift upon cart updates.
* Fix: Quantity controller numeric value visibility, contrast, and alignment across all themes.

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
