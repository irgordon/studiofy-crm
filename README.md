# Studiofy CRM

![Version](https://img.shields.io/badge/version-2.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-%3E%3D6.4-blue)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-777bb4)
![License](https://img.shields.io/badge/license-GPLv3-green)

**Stop renting your business process and start owning it.**

Studiofy CRM shatters the cycle of "subscription fatigue" by turning your WordPress site into a complete studio command center. Ditch the juggle of five different apps and manage your entire client lifecycle—from lead capture and automated Google Calendar scheduling to signed contracts, paid Square invoices, and delivered image galleries—all within one lightning-fast, native dashboard. With zero monthly fees, zero data bloat, and total privacy by design, Studiofy gives you the power of a corporate SaaS with the freedom of WordPress.

**[🌐 Official Website & Documentation](https://iangordon.app/studiofy-crm)**

---

## 🚀 What's New in v2.0 (Native Edition)

Version 2.0 represents a complete architectural rewrite. We have removed all heavy external dependencies (Composer, Google SDK, Square SDK) in favor of **Native WordPress APIs**.

* **⚡ Lightweight:** Plugin size reduced from ~50MB to **<500KB**.
* **🏗️ Modular Architecture:** Features are split into isolated modules (Projects, Contracts, Galleries).
* **🧩 Custom Post Types:** Projects, Leads, Invoices, and Sessions are now CPTs, allowing for better compatibility with WordPress export tools and permalinks.
* **🛠️ Form Engine:** A built-in drag-and-drop form builder for client intake questionnaires and lead forms.
* **🖼️ Client Galleries:** Deliver photo collections securely using the native WordPress Media Library.

---

## 🌟 Core Features

### 1. Project Command Center
Manage workflows from Inquiry to Delivery.
* Link Clients to Projects via relationships.
* Track Status (New, In Progress, On Hold, Complete).
* Define Workflow Phases (Shoot, Editing, Proofing).

### 2. Financials (Square API)
* **Native Integration:** Connects directly to Square API using `wp_remote_post`.
* **Invoicing:** Generate invoices linked to specific projects.
* **Async Processing:** API calls are handled in the background to prevent admin dashboard slowdowns.

### 3. Legal & Contracts
* **Digital Signatures:** Capture legally binding e-signatures using HTML5 Canvas.
* **Audit Trail:** Records Signer IP and Timestamp.
* **Print Views:** Auto-generates printer-friendly versions of contracts without PDF bloat.

### 4. Scheduling
* **Google Calendar Sync:** Two-way integration via OAuth2.
* **Session Management:** Create session records linked to specific dates and client forms.

### 5. Client Galleries
* **Secure Delivery:** Create password-protected gallery pages.
* **Native Media:** Select images directly from your existing WordPress Media Library.
* **Download Options:** Clients can download individual high-res images.

---

## 🛠️ Installation

**Note:** Unlike previous versions, v2.0 does **not** require Composer. It is ready to run out of the box.

1.  Download the `studiofy-crm.zip` file from the **Releases** page.
2.  Log in to your WordPress Admin.
3.  Go to **Plugins > Add New > Upload Plugin**.
4.  Upload the zip file and click **Activate**.

---

## ⚙️ Configuration

Studiofy is platform-agnostic. You connect it to *your* own API accounts.

Navigate to **Studiofy > Settings** to configure:

### 1. Square Payments
1.  Log in to the [Square Developer Dashboard](https://developer.squareup.com/console).
2.  Create an Application.
3.  Copy your **Access Token** and **Location ID**.
4.  Paste them into Studiofy Settings.

### 2. Google Calendar
1.  Log in to [Google Cloud Console](https://console.cloud.google.com/).
2.  Create a Project and enable **Google Calendar API**.
3.  Create **OAuth Credentials** (Web Application).
4.  **Important:** Set the "Authorized Redirect URI" to the URL displayed in your Studiofy Settings page.
5.  Copy the **Client ID** and **Client Secret** into Studiofy.

---

## 💻 Developer Notes

This plugin follows strict **WordPress Coding Standards (WPCS)**.

### Directory Structure
```text
includes/
├── class-studiofy-cpt-registrar.php  # Registers CPTs (Projects, Leads, etc.)
├── modules/
│   ├── class-studiofy-forms.php      # JSON Schema Form Engine
│   ├── class-studiofy-contracts.php  # Signature & Print Logic
│   └── class-studiofy-gallery.php    # Frontend Gallery Grid
admin/
├── class-studiofy-metaboxes.php      # Admin UI & Data Saving logic
├── list-tables/                      # Native WP_List_Table extensions
