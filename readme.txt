=== Studiofy CRM ===
Contributors: irgordon
Tags: crm, photography, elementor, invoicing, scheduling, kanban
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 2.0.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A comprehensive Elementor Addon and CRM for Photographers. Manage clients, contracts, invoices, and scheduling directly in WordPress.

== Description ==

**Studiofy CRM** transforms your WordPress site into a powerful business operating system for professional photographers. 

**IMPORTANT:** This plugin acts as an **Elementor Addon**. While the backend CRM functions (Invoicing, Client Data, Contracts) work independently, you **must have Elementor installed and active** to use the frontend features:
* Visual Lead Capture Forms
* Booking & Scheduling Calendar
* Proofing Galleries

If you do not have Elementor installed, Studiofy CRM will provide a direct link to download it from the WordPress repository upon activation.

### 🚀 Core Features

* **Customer Management:** Encrypted database for client details.
* **Visual Kanban:** Drag-and-drop project workflow.
* **Invoicing:** Professional invoicing with Square API integration.
* **Digital Contracts:** eSignature capture.
* **Booking System:** Frontend appointment scheduler.

== Installation ==

1.  Upload the `studiofy-crm` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  **Dependency Check:** If Elementor is missing, a notice will appear at the top of your dashboard. Click "Install Elementor" to proceed.
4.  Navigate to **Studiofy CRM > Settings** to configure your studio branding.

== Frequently Asked Questions ==

= What happens if I deactivate Elementor? =
Your backend CRM data (Clients, Invoices, Contracts) remains safe and accessible via the WP Admin dashboard. However, any Studiofy widgets (Forms, Calendars) placed on your pages will stop rendering until Elementor is reactivated.

== Changelog ==

= 2.0.9 =
* Update: Added strict dependency check for Elementor.
* Update: Added "Install/Activate" button for Elementor in admin notices.
