=== Studiofy CRM ===
Contributors: irgordon
Tags: crm, photography, elementor, invoicing, scheduling, kanban, gallery, contracts
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 2.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The ultimate photography business suite. Manage clients, contracts, invoices, and proofing galleries with native Elementor integration.

== Description ==

**Studiofy CRM** is the comprehensive business operating system designed specifically for the modern professional photographer. Stop juggling five different apps to run your studio—Studiofy brings your entire workflow directly into your WordPress dashboard.

Built with a "Privacy & Security First" architecture, Studiofy ensures your client data is encrypted at rest while providing a beautiful, SaaS-like interface to manage your business.

### 🎨 Built for Elementor
We believe you shouldn't need to know code to build an amazing Client Portal. Studiofy integrates natively with **Elementor**, providing drag-and-drop widgets that connect directly to your CRM data.

* **Lead Forms:** Create stunning inquiry forms that auto-populate your CRM.
* **Booking Scheduler:** Drop a calendar on any page for real-time session booking.
* **Proofing Galleries:** Display secure, watermarked image grids for client selection.

### 🚀 Key Features

* **Intelligent Client Management:** A centralized, searchable database for all leads and active clients. Sensitive data (Phone/Address) is AES-256 encrypted.
* **Kanban Project Workflow:** Visualize your shoots. Drag projects from "To Do" to "In Progress" to "Done".
* **Digital Contracts:** Say goodbye to paper. Create legally binding contracts and collect digital e-signatures directly on your site.
* **Square Invoicing:** Integrated directly with the Square API (Sandbox & Production modes). Send professional invoices and get paid faster.
* **Visual Scheduler:** A robust booking calendar that manages your availability and prevents double-booking.
* **Client Galleries:** Create password-protected proofing galleries where clients can "heart" their favorite photos.

### 🔒 Security By Design
Your business depends on trust. Studiofy CRM implements robust data validation, nonces for all actions, and database-level encryption for Personal Identifiable Information (PII).

== Installation ==

1.  Upload the `studiofy-crm` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  **Note:** This plugin adds widgets to Elementor. Please ensure Elementor (Free or Pro) is installed and active for frontend functionality.
4.  Navigate to **Studiofy CRM > Settings** to configure your Business Branding, Logo, and Square API keys.
5.  Create a new page in WordPress, edit with Elementor, and search for "Studiofy" widgets to start building your client portal.

== Frequently Asked Questions ==

= Does this plugin require Elementor? =
The backend CRM (Dashboard, Clients, Invoices, Contracts) works entirely without Elementor. However, to display the Frontend Forms, Booking Calendar, and Galleries to your clients, Elementor is required as we use their widget engine for the best design experience.

= Is the Square integration free? =
Yes, the integration is included in the plugin. You only pay standard transaction fees to Square when you process a payment. You will need your own Square account (Developer Application ID and Access Token).

= How is client data secured? =
We use OpenSSL AES-256-CBC encryption for sensitive fields like Phone Numbers and Addresses before they are stored in your WordPress database. They are decrypted on-the-fly only when you view them in the Admin panel.

= Can I import existing clients? =
Currently, clients must be added manually or via the frontend Lead Form widget. CSV import is planned for a future release.

== Screenshots ==

1.  **Dashboard** - Get a bird's-eye view of your studio's health, revenue, and upcoming tasks.
2.  **Project Kanban** - Visual workflow management to keep track of every shoot.
3.  **Client Management** - A clean, sortable list of all your contacts with status indicators.
4.  **Contract Builder** - Create digital contracts with start/end dates and value tracking.
5.  **Invoice Builder** - Add line items, calculate tax, and generate payment links.
6.  **Appointment Calendar** - Visual grid view of your schedule.
7.  **Gallery Management** - Organize client photos into proofing folders.

== Changelog ==

= 2.0.4 =
* Security: Enhanced sanitization on all API endpoints.
* Performance: Optimized database queries for large client lists.
* Fix: Resolved layout issues in the Booking Widget.

= 2.0.3 =
* UI: Complete overhaul of Admin UI to match modern Dark Theme.
* Feature: Added Tax calculation to Invoice Builder.
* Feature: Added dynamic Line Items to Invoices.
* Security: Implemented AES-256 Encryption for client PII.

= 2.0.2 =
* Dashboard: Added "Quick Actions" and "Revenue Overview" stats cards.
* Database: Updated schema to support detailed contract dates and client companies.

= 2.0.1 =
* Performance: Added transient caching to Gallery Widgets to reduce DB load.
* API: Added dynamic environment switching for Square (Sandbox/Production).

= 2.0.0 =
* Major Release: Full refactor to Elementor Addon architecture.
* Added: Lead Form Widget, Gallery Widget, Scheduler Widget.

= 1.0.0 =
* Initial Release.

== Upgrade Notice ==

= 2.0.4 =
This is a maintenance release improving security and performance. It is recommended for all users.
