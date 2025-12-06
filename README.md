# Studiofy CRM 📸
### The Ultimate Business Suite for Professional Photographers

![Version](https://img.shields.io/badge/Version-2.2.1-blue.svg) ![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg) ![Elementor](https://img.shields.io/badge/Elementor-Required-E93056.svg)

---

## 🚀 Overview
**Studiofy CRM** is a high-performance, privacy-focused business operating system designed specifically for modern photographers. It transforms your WordPress dashboard into a command center for managing Clients, Projects, Invoices, and Proofing Galleries.

**New in v2.2.1:** The Gallery module has been re-engineered for **High-Speed Performance**, utilizing DOM fragmentation, lazy loading, and transient caching to ensure your client proofing pages load instantly, even with hundreds of high-res images.

---

## 🎨 Built for Elementor
Studiofy integrates natively with **Elementor**, allowing you to build stunning Client Portals without writing code.

* **✨ Lead Forms:** Drag-and-drop inquiry forms that auto-populate your CRM database.
* **📅 Visual Scheduler:** Frontend booking calendar with availability checks.
* **🖼️ High-Speed Galleries:** Secure, watermarked proofing grids where clients can "heart" favorites.

---

## 🔥 Key Features

### 1. 📇 Intelligent Customer Management
* **Centralized Database:** Track leads and active clients.
* **Encryption:** AES-256 encryption for sensitive PII (Phone/Address).
* **Quick Actions:** Clone, Edit, and Manage clients via a streamlined UI.

### 2. ⚡ High-Performance Galleries
* **Windows-Explorer Style UI:** Manage folders and files with a split-pane interface.
* **RAW Support:** Upload RAW files for storage; auto-generate previews for JPG/PNG.
* **Client Proofing:** Create password-protected pages where clients select images.
* **Zero-Lag:** Optimized DOM manipulation and CSS content-visibility for large galleries.

### 3. 💸 Smart Invoicing & Payments
* **Square API Integration:** Collect payments directly on your site.
* **Dynamic Line Items:** Add services, products, and calculate tax percentages on the fly.
* **Status Tracking:** Draft, Sent, Paid statuses with iCal export for due dates.

### 4. ✍️ Digital Contracts
* **eSignatures:** Collect legally binding signatures from clients.
* **Templates:** Pre-filled fields for Event Date, Venue, and Fees.

### 5. 📋 Kanban Project Workflow
* **Visual Board:** Drag and drop projects between "To Do", "In Progress", and "Future".
* **Billing Status:** Automatically tracks if a project is Unbilled, Partial, or Paid.

---

## 🛠️ Installation

1.  **Upload:** Upload the `studiofy-crm` folder to `/wp-content/plugins/`.
2.  **Activate:** Activate via the WordPress Plugins menu.
3.  **Dependency:** Ensure **Elementor** is installed (the plugin will prompt you if missing).
4.  **Setup:** Go to **Studiofy CRM > Settings** to configure branding and API keys.

---

## ⚙️ Recent Changelog

### v2.2.1 (Performance Update)
* **Speed:** Implemented `DocumentFragment` batching for Gallery grid rendering (eliminated reflow/repaint lag).
* **CSS:** Added `content-visibility: auto` for browser rendering optimization.
* **Caching:** Added Transient caching for Gallery Shortcode output (1-hour cache).
* **Lazy Load:** Enforced `loading="lazy"` and `decoding="async"` on frontend images.

### v2.2.0
* **UI:** Updated Empty States for Projects, Contracts, and Invoices to match modern UI.
* **Fix:** Resolved JS syntax errors in Gallery Admin.

### v2.1.x
* **Features:** Added Private Gallery workflow (Auto-page creation + Password protection).
* **UI:** Reverted Admin Theme to standard WordPress colors for better consistency.
* **Fix:** Corrected `dbDelta` schema issues for `wp_page_id`.

---

**Ready to transform your workflow?**
*Get back to doing what you love—taking amazing photos.*

&copy; 2025 Ian R. Gordon. All Rights Reserved.
