=== Studiofy CRM ===
Contributors: irgordon
Tags: crm, photography, elementor, invoicing, scheduling, kanban, gallery, proofing
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 2.2.49
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A comprehensive business suite for photographers. Manage clients, contracts, invoices, and high-speed proofing galleries with native Elementor integration.

== Description ==

**Studiofy CRM** is the modern operating system for professional photography studios. Stop paying monthly fees for external platforms—Studiofy brings your entire workflow inside WordPress.

Designed to leverage the power of **Elementor**, Studiofy allows you to build custom Client Portals, Booking Forms, and Contract pages without touching code.

### 🚀 Key Modules

* **Customer CRM:** Securely store client details with AES-256 encryption.
* **Project Kanban:** Visual workflow management (To Do / In Progress / Done) with drag-and-drop cards.
* **Invoicing:** Professional PDF invoices with an "Items Library" for saving your standard packages and rates.
* **Digital Contracts:** Legal agreements with eSignature capture, fully editable via Elementor.
* **Proofing Galleries:** Private, password-protected galleries where clients can select their favorite images.

### ⚡ Modern Features

* **High-Speed Uploads:** Our custom Chunked Uploader handles large RAW files that normally crash WordPress.
* **Automated Workflow:** Client proof selections automatically create tasks in your Project Manager.
* **One-Click Setup:** Includes a robust "Demo Data" importer to get you started in seconds.

== Installation ==

1.  Upload `studiofy-crm` to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  **Elementor is Required:** You will be prompted to install it if missing.
4.  Follow the **Welcome Screen** prompts to import demo data.
5.  Navigate to **Studiofy CRM > Settings** to configure your branding.

== Frequently Asked Questions ==

= Can I use this for non-photography businesses? =
While optimized for photographers (Galleries, Shoot Projects), the Invoicing and Contract modules are powerful enough for any creative freelancer.

= Is my client data secure? =
Yes. We use OpenSSL AES-256-CBC encryption for sensitive fields (Phone Numbers, Addresses) in the database to ensure privacy at rest.

= How do clients sign contracts? =
You create a page with the `[studiofy_contract_portal]` shortcode. Clients receive a link where they can view the contract and sign digitally using a touchscreen or mouse.

== Screenshots ==

1.  **Dashboard** - Real-time business overview with revenue stats.
2.  **Kanban Board** - Visual project tracking with financial data.
3.  **Invoice Builder** - Modern interface for creating and managing invoices.
4.  **Gallery Proofing** - Frontend view where clients approve/reject images.
5.  **Contract Editor** - Manage legal terms directly in WordPress.

== Changelog ==

= 2.2.49 =
* **Fix:** Resolved `stdClass` property warning in Project creation logic.
* **Fix:** Fixed `ltrim()` deprecation warning in Invoice creation logic by enforcing string casting.

= 2.2.48 =
* **UI:** Redesigned Invoice Builder to be a clear editing form.
* **Feature:** Added "Image ID" (#0100) and Toolbar to Gallery Proofing view.
* **Fix:** Solved "Access Denied" error on Welcome Page.

= 2.2.47 =
* **Fix:** Hydrated Invoice Builder object to prevent undefined variable errors.
* **Visual:** Highlighted "Proofs Approved" tasks in Red on the Kanban board.

= 2.2.46 =
* **Fix:** Changed Welcome Page registration strategy to use CSS hiding, fixing permissions.

= 2.2.45 =
* **Fix:** Resolved Fatal Errors in Invoice Builder for new invoices.
* **Update:** Improved Demo Data Import permission checks.

= 2.2.44 =
* **Fix:** Renamed Gallery CPT to `studiofy_gal` (12 chars) to comply with WordPress limits.
* **Fix:** Updated Gallery Metadata JS to prevent undefined property errors.

= 2.2.43 =
* **Fix:** Renamed Contract CPT to `studiofy_doc`.
* **Fix:** Implemented `get_safe_option` to prevent `str_replace` on null errors in Settings.

= 2.2.42 =
* **Feature:** New PDF Invoice Template with SVG Logo support.
* **Feature:** Registered `studiofy_gallery_page` (later renamed) to hide galleries from site menus.

= 2.2.41 =
* **Fix:** Gallery Proofing now clears Dashboard Cache to update counts immediately.
* **Logic:** Proofing submission now finds *any* active project if "In Progress" is missing.

= 2.2.40 =
* **Fix:** Aggressive type casting in `Menu.php` to stop `strpos` deprecation warnings.

= 2.2.30 - 2.2.39 =
* **Feature:** Added Studiofy SVG Logo to Welcome Screen.
* **Feature:** Added "Items Library" to Invoices module.
* **Data:** Updated Demo Data XML with 10 random customers and 60+ images.

= 2.2.20 - 2.2.29 =
* **Feature:** Added "Contract Section" Elementor Widget.
* **Feature:** Added Frontend Contract Signing Portal.
* **Fix:** Database schema updates for `signed_at` column.

= 2.2.0 - 2.2.19 =
* **Performance:** Implemented Chunked File Uploads.
* **Feature:** Added Google Maps Address Validation.
* **Feature:** Kanban Board overhaul.
* **Security:** Added AES-256 encryption for client data.

== Upgrade Notice ==

= 2.2.49 =
Maintenance release fixing PHP warnings in Project and Invoice modules. Recommended for all users.
