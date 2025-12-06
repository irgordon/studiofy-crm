=== Studiofy CRM ===
Contributors: irgordon
Tags: crm, photography, elementor, invoicing, scheduling, kanban, gallery, proofing
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 2.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A comprehensive business suite for photographers. Manage clients, contracts, invoices, and high-speed proofing galleries with native Elementor integration.

== Description ==

**Studiofy CRM** is the ultimate operating system for professional photographers. Stop paying monthly fees for external SaaS platforms—Studiofy brings your entire business workflow directly into your WordPress dashboard.

Designed with a **"Performance & Privacy First"** architecture, Studiofy v2.2+ features a rewritten Gallery engine optimized for speed, ensuring your client proofing pages load instantly even with hundreds of images.

### 🎨 Native Elementor Integration
We believe in design freedom. Studiofy adds custom **Widgets** to Elementor, allowing you to build your Client Portal exactly how you want it.

* **Lead Capture Forms:** Entries go straight to your encrypted CRM database.
* **Booking Calendar:** Real-time session scheduling with availability checks.
* **Proofing Galleries:** Beautiful, responsive, and secure image grids.

### 🚀 Core Modules

* **Customer Management:** Securely store client details with AES-256 encryption.
* **Project Kanban:** Visual workflow management (To Do / In Progress / Done).
* **Invoicing:** Square API integration (Sandbox/Production support) with tax calculations.
* **Digital Contracts:** eSignature capture and PDF generation.
* **File Management:** A dedicated "Windows Explorer" style interface for organizing client shoots, separate from the WP Media Library.

### ⚡ High-Speed Performance (v2.2.1)
The latest update introduces advanced optimization techniques:
* **DOM Batching:** Zero-lag rendering for large gallery grids.
* **Smart Caching:** Transient API caching reduces database load.
* **Lazy Loading:** Native browser optimization for image delivery.

== Installation ==

1.  Upload the `studiofy-crm` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  **Requirement:** This plugin requires **Elementor** to display frontend widgets. A notice with an install link will appear if it is missing.
4.  Navigate to **Studiofy CRM > Settings** to configure your Business Branding, Logo, and Square API keys.

== Frequently Asked Questions ==

= Does this replace WooCommerce? =
Studiofy is designed for service-based photography businesses (Invoices, Contracts, Bookings). It does not replace WooCommerce for selling physical products, but handles the service workflow much better.

= Is my client data secure? =
Yes. We use OpenSSL AES-256-CBC encryption for sensitive fields (Phone Numbers, Addresses) in the database.

= Can I upload RAW files? =
Yes! The Gallery module supports RAW file uploads (.CR2, .NEF, .ARW) for storage and delivery, in addition to standard JPG/PNG/GIFs.

== Screenshots ==

1.  **Dashboard** - Real-time business overview with revenue stats.
2.  **Gallery Explorer** - Split-pane folder view for managing client photos.
3.  **Kanban Board** - Visual project tracking.
4.  **Invoice Builder** - Create professional invoices with tax and line items.
5.  **Appointment Calendar** - Monthly/Weekly view of your shoot schedule.
6.  **Customer List** - Sortable, searchable client database.

== Changelog ==

= 2.2.1 =
* **Performance:** Implemented DocumentFragment batching for gallery JS to eliminate render blocking.
* **Optimization:** Added CSS `content-visibility` and `will-change` properties for hardware acceleration.
* **Caching:** Added transient caching for frontend shortcodes.
* **Fix:** Resolved image lazy-loading attributes.

= 2.2.0 =
* **UI:** Updated Empty States for Projects, Contracts, and Invoices to match modern dark-mode aesthetic.
* **Fix:** Resolved JS syntax error in Gallery Admin script.
* **Style:** Refined table layouts to match WP Core standards.

= 2.1.12 =
* **Database:** Fixed schema definition for `wp_page_id` in galleries table.

= 2.1.11 =
* **Feature:** Added Private Gallery workflow (Auto-creates Password Protected Page).
* **Feature:** Added "View Larger" Lightbox to Admin Gallery.
* **Feature:** Added File Type overlays and Metadata editing sidebar.

= 2.0.9 =
* **System:** Added strict dependency check for Elementor with admin notice.

= 2.0.0 =
* **Major Release:** Full refactor to Elementor Addon architecture.

== Upgrade Notice ==

= 2.2.1 =
Critical performance update for Gallery rendering. Recommended for all users.
