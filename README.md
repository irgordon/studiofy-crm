# Studiofy CRM 📸
### The Ultimate Business Suite for Professional Photographers

![Version](https://img.shields.io/badge/Version-2.2.27-blue.svg) ![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg) ![Elementor](https://img.shields.io/badge/Elementor-Required-E93056.svg)

---

## 🚀 Overview
**Studiofy CRM** is a high-performance, privacy-focused business operating system designed specifically for modern photographers. It transforms your WordPress dashboard into a command center for managing Clients, Projects, Invoices, and Proofing Galleries.

**New in v2.2.27:** The Gallery module now features a complete **Proofing Workflow**, allowing clients to approve/reject images directly on the frontend. We've also resolved critical upload permissions (403 errors) for the chunked uploader.

---

## 🎨 Built for Elementor
Studiofy integrates natively with **Elementor**, allowing you to build stunning Client Portals without writing code.

* **✨ Lead Forms:** Drag-and-drop inquiry forms that auto-populate your CRM database.
* **📅 Visual Scheduler:** Frontend booking calendar with availability checks.
* **🖼️ Proofing Galleries:** Secure image grids where clients can select favorites.

---

## 🔥 Key Features

### 1. 📇 Intelligent Customer Management
* **Centralized Database:** Track leads and active clients.
* **Encryption:** AES-256 encryption for sensitive PII (Phone/Address).
* **Google Maps API:** Auto-complete address validation for US locations.

### 2. ⚡ High-Performance Galleries
* **Chunked Uploads:** Bypass server limits by streaming large files (RAW/JPG) in 2MB chunks.
* **Proofing Workflow:** Clients can "Approve" or "Reject" images.
* **Kanban Integration:** Submitting selections automatically creates a "Proofing Review" task on your project board.

### 3. 💸 Smart Invoicing & Payments
* **Square API Integration:** Collect payments directly on your site.
* **Dynamic Line Items:** Add services, products, and calculate tax percentages on the fly.
* **Status Tracking:** Draft, Sent, Paid statuses with PDF print support.

### 4. ✍️ Digital Contracts
* **Elementor-Powered:** Design contract templates using the full power of Elementor.
* **eSignatures:** Collect legally binding signatures from clients.
* **Linked Projects:** Associate contracts directly with CRM projects.

### 5. 📋 Kanban Project Workflow
* **Visual Board:** Drag and drop projects between "To Do", "In Progress", and "Future".
* **Task Management:** Create sub-tasks, set priorities, and mark complete with visual strike-throughs.
* **Financial Overview:** See budget and payment status directly on the project card.

---

## 🛠️ Installation

1.  **Upload:** Upload the `studiofy-crm` folder to `/wp-content/plugins/`.
2.  **Activate:** Activate via the WordPress Plugins menu.
3.  **Dependency:** Ensure **Elementor** is installed (the plugin will prompt you if missing).
4.  **Setup:** Go to **Studiofy CRM > Settings** to configure branding and API keys.
5.  **(Optional):** Use the "Demo Data" import tool in Settings to populate test content.

---

## ⚙️ Recent Changelog

### v2.2.27
* **Fix:** Resolved 403 Forbidden error during chunked uploads by implementing a dedicated `upload_nonce`.
* **Feature:** Added Frontend Proofing UI (Approve/Reject buttons) to Gallery Shortcode.
* **Automation:** Submitting gallery proofing now auto-creates a "Review" task in the Project Kanban.

### v2.2.26
* **UI:** Overhauled Kanban Board to enforce horizontal scrolling layout.
* **UI:** Redesigned Project Cards to include Task Count and Budget metadata.

### v2.2.23 - v2.2.25
* **Performance:** Implemented Chunked File Uploads (2MB slices) for handling large RAW files.
* **Feature:** Added visual progress bar for uploads.
* **Data:** Updated Demo Data XML with 60 randomized high-res images and sub-tasks.

### v2.2.20 - v2.2.22
* **Feature:** Added "Contract Section" Elementor Widget.
* **Architecture:** Registered `studiofy_doc` CPT to bridge Contracts with Elementor Editor.
* **Cleanup:** Added `uninstall.php` for complete data removal on deletion.

---

**Ready to transform your workflow?**
*Get back to doing what you love—taking amazing photos.*

&copy; 2025 Ian R. Gordon. All Rights Reserved.
