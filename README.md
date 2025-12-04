# Studiofy CRM

![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue)
![License](https://img.shields.io/badge/license-GPLv3-green)
![WordPress](https://img.shields.io/badge/WordPress-%3E%3D5.8-blue)
![Version](https://img.shields.io/badge/version-1.0.0-orange)

**A professional, high-performance Photography Studio CRM for WordPress.**

Studiofy CRM is designed for photographers and creative studios who want to manage their business directly from their own website, without relying on expensive monthly SaaS subscriptions. It prioritizes data privacy, performance, and security.

**[🌐 Official Website & Documentation](https://iangordon.app/studiofy-crm)**

---

## 🚀 Key Features

* **⚡ High Performance:** Built using WordPress Transients and asynchronous background processing to ensure your dashboard never hangs, even when processing API calls.
* **👥 Client Management:** Convert Contact Form 7 leads directly into CRM clients. Track status (Lead, Booked, Invoice Sent) with a clean, native WordPress UI.
* **📅 Google Calendar Sync:** Two-way integration (via OAuth2) to push your studio bookings to your personal Google Calendar automatically.
* **💳 Square Invoices:** Generate and email professional invoices via the Square API. Invoices are generated in the background to prevent admin timeout.
* **✍️ Digital Contracts:** Create legal agreements in the WordPress editor and send links to clients. Captures legally relevant audit data (Signature Image, IP Address, Timestamp).
* **🔒 Privacy by Design:** All API tokens (Square/Google) are encrypted at rest in the database.

---

## 🛠️ Installation

### Option 1: For Developers (GitHub Clone)
Because this repository follows best practices, external libraries are **not** included in the repo. You must run Composer.

1.  Clone this repository into your WordPress plugins folder:
    ```bash
    cd wp-content/plugins/
    git clone [https://github.com/yourusername/studiofy-crm.git](https://github.com/yourusername/studiofy-crm.git)
    cd studiofy-crm
    ```
2.  Install PHP dependencies:
    ```bash
    composer install --no-dev
    ```
    *(This downloads the Google API Client and Square SDK).*
3.  Activate **Studiofy CRM** via the WordPress Admin Plugins page.

### Option 2: Production Zip
If you have a pre-built zip file (with the `vendor` folder included):
1.  Go to **Plugins > Add New > Upload Plugin**.
2.  Upload `studiofy-crm.zip`.
3.  Activate.

---

## ⚙️ Configuration

Studiofy is **agnostic**—it connects to *your* personal Google and Square accounts. You must generate your own API keys.

Navigate to **Studiofy CRM > Settings** in your WordPress dashboard.

### 1. Google Calendar Setup
To sync bookings, you need a Google Cloud Project.

1.  Go to the [Google Cloud Console](https://console.cloud.google.com/).
2.  Create a **New Project**.
3.  Enable the **Google Calendar API** (Library > Search "Google Calendar").
4.  Configure the **OAuth Consent Screen** (User Type: External).
5.  Create **Credentials** -> **OAuth Client ID** (Web Application).
6.  **Critical:** Set the "Authorized Redirect URI" to the URL shown in your Studiofy Settings page (e.g., `https://iangordon.app/wp-admin/admin.php?page=studiofy-settings`).
7.  Copy the **Client ID** and **Client Secret** into Studiofy.

### 2. Square Invoices Setup
1.  Log in to the [Square Developer Dashboard](https://developer.squareup.com/console).
2.  Create a new Application.
3.  Copy your **Access Token** (Credentials) and **Location ID** (Locations).
4.  Paste them into Studiofy Settings.
5.  Set Environment to **Production** (or Sandbox for testing).

---

## 💻 Tech Stack & Architecture

* **Language:** PHP 7.4+
* **Frontend:** jQuery, Signature Pad (JS)
* **Database:** Custom SQL Tables (`wp_studiofy_clients`, `_bookings`, `_invoices`, `_contracts`) managed via `dbDelta`.
* **Dependencies:**
    * `google/apiclient`
    * `square/square`
* **Security:**
    * Nonces for all form actions.
    * `current_user_can` capabilities API.
    * OpenSSL Encryption for stored API tokens.
    * Shortcode Access Tokens for contract viewing.

---

## 🤝 Contributing

1.  Fork the repository.
2.  Create a new branch (`git checkout -b feature/amazing-feature`).
3.  Commit your changes.
4.  Push to the branch.
5.  Open a Pull Request.

Please ensure code adheres to [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

---

## 📝 License

This project is licensed under the GNU GPLv3.

**Author:** [Ian R. Gordon](https://iangordon.app)
