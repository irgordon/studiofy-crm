# Studiofy CRM

![Version](https://img.shields.io/badge/version-5.1.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-%3E%3D6.4-blue)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-777bb4)
![License](https://img.shields.io/badge/license-GPLv3-green)

# The Operating System for Creative Entrepreneurs.

**Stop renting your business process. Start owning it.**

Studiofy CRM is a declaration of independence from "subscription fatigue." It transforms your WordPress installation into a **full-stack enterprise command center**, giving photographers and small business owners the robust business logic usually reserved for expensive SaaS platforms—**for free.**

We believe that powerful tools shouldn't come with a monthly mortgage. Studiofy replaces the juggle of five different apps (CRMs, Signing services, Gallery hosts, Scheduling tools) with one lightning-fast, native dashboard that you control 100%.

**[🌐 Official Website & Documentation](https://iangordon.app/studiofy-crm)**

---

## 🚀 Why Studiofy?

Most WordPress CRMs are just glorified contact lists. **Studiofy is a Logic Engine.**

It manages the entire lifecycle of your client relationships through intelligent, interconnected modules. Data flows seamlessly from **Lead Capture** → **Project Workflow** → **Legal Contract** → **Invoicing** → **Asset Delivery**, ensuring nothing falls through the cracks.

### ⚡ Powered by Native Performance
We stripped out the bloat. By utilizing the native WordPress HTTP API and custom database structures, Studiofy runs lean (<600KB) on even the most modest hosting environments while handling complex logic.

---

## 🌟 The Business Suite

### 1. 🏗️ Project Command Center (Kanban)
Visualize your production pipeline.
* **Drag-and-Drop Kanban Board:** Move projects from Pre-Production to Delivery effortlessly.
* **Visual Alerts:** The system flags "stalled" projects that haven't moved in days.
* **Workflow Phases:** Track granular status (Shooting, Editing, Proofing, Delivered).

### 2. 💸 Enterprise Financials
Get paid faster with zero friction.
* **Square API Integration:** Native, secure connection to Square Payments.
* **Smart Invoicing:** Generate professional invoices linked directly to specific projects and clients.
* **Async Processing:** Background job runners ensure invoice generation never slows down your dashboard.

### 3. ⚖️ Ironclad Legal Tools
Protect your business without the paperwork.
* **Digital Signatures:** Capture legally binding e-signatures via HTML5 canvas (Mobile/Touch friendly).
* **Smart Contracts:** Create reusable legal templates with dynamic data insertion.
* **Audit Trails:** Automatically records signer IP addresses and timestamps for compliance.
* **Print-Ready:** One-click PDF generation for physical archiving.

### 4. 📸 Client Asset Delivery
Ditch the third-party gallery fees.
* **Native Galleries:** Deliver high-resolution image collections using your existing WordPress Media Library.
* **Secure Access:** Client-specific gallery pages.
* **Download Management:** Allow clients to download assets directly.

### 5. 🧲 Lead Intelligence
* **Intake Form Engine:** Build custom questionnaires and lead forms with a drag-and-drop builder.
* **Automated Onboarding:** Leads are automatically converted into Client profiles upon acceptance.
* **Data Seeder:** One-click "Demo Data" installation to test your workflows instantly.

---

## 🛠️ Installation

1.  Download the `studiofy-crm` folder or zip file.
2.  Upload to your `/wp-content/plugins/` directory.
3.  Activate via WordPress Admin.
4.  Navigate to **Studiofy > Settings** to configure your API integrations.

---

## ⚙️ Configuration

Studiofy is **platform-agnostic**. You own your data, and you connect your own keys.

### 🟦 Square Payments
1.  Log in to the [Square Developer Dashboard](https://developer.squareup.com/console).
2.  Create an Application.
3.  Copy your **Access Token** and **Location ID**.
4.  Paste them into **Studiofy > Settings**.

### 📅 Google Calendar
1.  Log in to [Google Cloud Console](https://console.cloud.google.com/).
2.  Enable the **Google Calendar API**.
3.  Create **OAuth Credentials** (Web Application).
4.  **Important:** Set the "Authorized Redirect URI" to the URL shown in your Studiofy Settings.
5.  Copy the **Client ID** and **Client Secret** into Studiofy.

---

## 🤝 Contributing

We welcome contributions from the community to help keep professional business tools accessible to everyone.

1.  Fork the repository.
2.  Create a new branch (`git checkout -b feature/amazing-feature`).
3.  Commit your changes.
4.  Push to the branch.
5.  Open a Pull Request.

---

## 📝 License

This project is licensed under the GNU GPLv3. You are free to use, modify, and distribute it.

**Author:** [Ian R. Gordon](https://iangordon.app)
