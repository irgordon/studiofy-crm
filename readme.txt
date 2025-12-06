=== Studiofy CRM ===
Contributors: irgordon
Tags: crm, photography, elementor, invoicing, scheduling, kanban, gallery, proofing
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 2.2.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A comprehensive business suite for photographers. Manage clients, contracts, invoices, and high-speed proofing galleries with native Elementor integration.

== Description ==

**Studiofy CRM** is the ultimate operating system for professional photographers. Stop paying monthly fees for external SaaS platforms—Studiofy brings your entire business workflow directly into your WordPress dashboard.

Designed with a **"Performance & Privacy First"** architecture, Studiofy v2.2+ features a rewritten Gallery engine optimized for speed, ensuring your client proofing pages load instantly even with hundreds of images.

### 🎨 Native Elementor Integration
We believe in design freedom. Studiofy adds custom **Widgets** to Elementor, allowing you to build your Client Portal exactly how you want it.

* **Lead Capture Forms:** Drag-and-drop forms that auto-populate your CRM database.
* **Booking Calendar:** Real-time session scheduling with availability checks.
* **Proofing Galleries:** Beautiful, responsive, and secure image grids.

### 🚀 Core Modules

* **Customer Management:** Securely store client details with AES-256 encryption.
* **Project Kanban:** Visual workflow management (To Do / In Progress / Done) combined with a detailed list view.
* **Invoicing:** Square API integration (Sandbox/Production support) with tax calculations and dynamic line items.
* **Digital Contracts:** eSignature capture and PDF generation.
* **File Management:** A dedicated "Windows Explorer" style interface for organizing client shoots, separate from the WP Media Library. Supports RAW files.

### ⚡ High-Speed Performance
The latest updates introduce advanced optimization techniques:
* **DOM Batching:** Zero-lag rendering for large gallery grids.
* **Smart Caching:** Transient API caching reduces database load.
* **Lazy Loading:** Native browser optimization for image delivery.

== Installation ==

1.  Upload the `studiofy-crm` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  **Requirement:** This plugin requires **Elementor** to display frontend widgets. A notice with an install link will appear if it is missing.
4.  Navigate to **Studiofy CRM > Settings** to configure your Business Branding, Logo, and Square API keys.
5.  (Optional) Use the "Demo Data" section in Settings to import sample content for testing.

== Frequently Asked Questions ==

= Does this replace WooCommerce? =
Studiofy is designed for service-based photography businesses (Invoices, Contracts, Bookings). It does not replace WooCommerce for selling physical products, but handles the service workflow much better.

= Is my client data secure? =
Yes. We use OpenSSL AES-256-CBC encryption for sensitive fields (Phone Numbers, Addresses) in the database to ensure privacy at rest.

= Can I upload RAW files? =
Yes! The Gallery module supports RAW file uploads (.CR2, .NEF, .ARW) for storage and delivery, in addition to standard JPG/PNG/GIFs.

== Screenshots ==

1.  **Dashboard** - Real-time business overview with revenue stats.
2.  **Gallery Explorer** - Split-pane folder view for managing client photos.
3.  **Kanban Board** - Visual project tracking with List View fallback.
4.  **Invoice Builder** - Create professional invoices with tax and line items.
5.  **Appointment Calendar** - Monthly/Weekly view of your shoot schedule.
6.  **Customer List** - Sortable, searchable client database.

== Changelog ==

= 2.2.10 =
* **Fix:** Resolved PHP 8.1+ deprecation warning in Invoice Controller (`ltrim` on null).
* **Fix:** Fixed "Create Task" button in Project Modal (prevented page reload).
* **Feature:** Added visual strike-through for completed tasks in Kanban view.
* **Update:** Added error logging for API task failures.

= 2.2.9 =
* **Feature:** Overhauled Project Module to display both Kanban Board and Detailed List Table.
* **Feature:** Added "Private Galleries" list table to Gallery Module with Edit/View/Delete actions.
* **Fix:** Corrected redirection logic after creating a Private Gallery Page (now redirects to edit screen).
* **Update:** Added "Payment Status" column to Project List (derived from Invoice status).

= 2.2.8 =
* **Fix:** Fixed fatal error in Project Controller regarding `number_format` types.
* **Fix:** Fixed Gallery Page creation AJAX response to return valid permalink.

= 2.2.7 =
* **Fix:** Corrected XML attribute parsing in Demo Data Manager.
* **Update:** Added automatic Dashboard stats cache clearing after Demo Import/Delete.

= 2.2.6 =
* **Fix:** Refactored Settings page to prevent nested HTML forms (fixed Demo Data upload failure).

= 2.2.5 =
* **Feature:** Refactored Demo Data to use XML File Upload instead of hardcoded data.
* **UI:** Added "Settings Saved" confirmation notice.

= 2.2.4 =
* **Feature:** Added "Import Demo Data" functionality to Settings (Customers, Projects, Invoices, Contracts).
* **Feature:** Added "Delete Demo Data" cleanup tool.

= 2.2.3 =
* **Dashboard:** Fixed real-time counters to count all rows regardless of status.
* **Dashboard:** Added dynamic Revenue calculation based on Paid invoices.
* **Project:** Added "Tax Status" (Taxed/Exempt) toggle and Budget currency formatting.
* **Gallery:** Added "Create Private Gallery Page" button to Folder Explorer.

= 2.2.2 =
* **Fix:** Resolved CSS regressions in Dashboard Grid and Calendar layout.
* **Fix:** Fixed Gallery Explorer flexbox layout.

= 2.2.1 =
* **Performance:** Implemented DocumentFragment batching for gallery JS to eliminate render blocking.
* **Optimization:** Added CSS `content-visibility` and `will-change` properties.
* **Caching:** Added transient caching for frontend shortcodes.

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

= 2.0.8 =
* **Fix:** Resolved JS validation bug preventing "Add Customer" form submission.
* **UI:** Redesigned Contracts and Invoices submenus with Empty States.

= 2.0.7 =
* **Code:** Replaced raw AJAX calls with `wp.apiFetch` for better WordPress compatibility.
* **Security:** Hardened nonce verification on all controllers.

= 2.0.6 =
* **UI:** Fixed Dashboard tile alignment and responsiveness.
* **Fix:** Corrected modal positioning for Appointments.

= 2.0.5 =
* **Refactor:** Renamed "Clients" module to "Customers" globally.
* **Fix:** Resolved "Add Customer" admin post handler issues.

= 2.0.4 =
* **Security:** Enhanced sanitization on all API endpoints.
* **Performance:** Optimized database queries for large client lists.
* **Fix:** Resolved layout issues in the Booking Widget.

= 2.0.3 =
* **UI:** Complete overhaul of Admin UI to match modern Dark Theme.
* **Feature:** Added Tax calculation to Invoice Builder.
* **Feature:** Added dynamic Line Items to Invoices.
* **Security:** Implemented AES-256 Encryption for client PII.

= 2.0.2 =
* **Dashboard:** Added "Quick Actions" and "Revenue Overview" stats cards.
* **Database:** Updated schema to support detailed contract dates and client companies.

= 2.0.1 =
* **Performance:** Added transient caching to Gallery Widgets to reduce DB load.
* **API:** Added dynamic environment switching for Square (Sandbox/Production).

= 2.0.0 =
* **Major Release:** Full refactor to Elementor Addon architecture.
* **Added:** Lead Form Widget, Gallery Widget, Scheduler Widget.

== Upgrade Notice ==

= 2.2.10 =
Maintenance release fixing PHP 8.1 warnings and Project Task interactions. Recommended update.
