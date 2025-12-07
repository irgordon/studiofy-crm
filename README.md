# Studiofy CRM 📸
### The Modern Operating System for Photography Studios

![Version](https://img.shields.io/badge/Version-2.2.49-blue.svg) ![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg) ![Elementor](https://img.shields.io/badge/Elementor-Required-E93056.svg)

---

## 🚀 Overview
**Studiofy CRM** is a next-generation business suite designed exclusively for professional photographers. By leveraging the power of **Elementor**, Studiofy transforms your WordPress dashboard into a comprehensive studio management platform without the monthly fees of external SaaS tools.

We simplify the business side of photography so you can focus on the creative side. From capturing leads to getting paid, Studiofy handles the entire client lifecycle with modern, privacy-focused tools.

---

## ✨ Core Features

### 1. 📇 Intelligent Customer Management
* **Centralized Database:** Manage leads and active clients in one secure location.
* **Security First:** Sensitive client data (Phone, Address) is encrypted at rest using AES-256.
* **Address Validation:** Built-in Google Maps API integration for error-free client addresses.

### 2. ⚡ High-Performance Private Galleries
* **Proofing Workflow:** Create password-protected galleries where clients can "Approve" or "Reject" images.
* **Chunked Uploads:** Our custom upload engine bypasses server limits, allowing you to upload large RAW/High-Res files (2MB+ chunks) reliably.
* **Automated Feedback:** When a client submits their selection, Studiofy automatically updates your Project Kanban board with a "Proofs Approved" task.

### 3. 💸 Professional Invoicing
* **Item Library:** Save reusable services (e.g., "Wedding Package", "Print Credit") for rapid invoice creation.
* **PDF Generation:** Generate beautiful, branded PDF invoices with a single click.
* **Flexible Billing:** Support for Hourly, Fixed, and Day rates with dynamic tax calculations.

### 4. 📋 Kanban Project Management
* **Visual Workflow:** Drag-and-drop projects across stages (To Do, In Progress, Future).
* **Task Tracking:** Granular task lists for every project ensure you never miss a shoot detail.
* **Financial Insight:** View budget status and payment tracking directly on the project card.

### 5. ✍️ Digital Contracts
* **Elementor Integration:** Design legally binding contracts using the Elementor editor you already know.
* **eSignatures:** Capture client signatures directly on your website via a dedicated client portal.
* **Templates:** Comes with industry-standard photography contract templates ready to use.

---

## 🛠️ Installation

1.  **Download:** Get the latest `studiofy-crm.zip`.
2.  **Upload:** Go to **Plugins > Add New > Upload** in WordPress.
3.  **Activate:** Activate the plugin. You will be redirected to the **Welcome Screen**.
4.  **Onboarding:** Click "Import Demo Data" to populate your CRM with sample clients, projects, and galleries to see how it works instantly.
5.  **Settings:** Configure your Studio Name, Logo, and API Keys in **Studiofy CRM > Settings**.

---

## ⚙️ Changelog Highlights

### v2.2.49 (Latest)
* **Fix:** Resolved `stdClass` property warning in Project creation.
* **Fix:** Fixed `ltrim()` deprecation warning in Invoice creation logic.

### v2.2.40 - v2.2.48
* **Feature:** Overhauled Gallery Proofing with visual "Approve/Reject" toolbar and Image ID numbering (#0100).
* **Feature:** Added "Item Library" to Invoices for saving reusable products.
* **UI:** Redesigned Invoice Builder to be a distinct editing interface.
* **Fix:** Solved "Access Denied" issues on the Welcome page using CSS hiding strategy.

### v2.2.30 - v2.2.39
* **Feature:** Added SVG Logo branding to Welcome Screen and PDF Invoices.
* **Performance:** Implemented "Chunked Uploads" for Gallery files to fix timeout issues.
* **Architecture:** Renamed CPTs (`studiofy_gal`) to comply with WordPress core limits.

---

**Get back to doing what you love—taking amazing photos.**

&copy; 2025 Studiofy CRM. Built for WordPress.
