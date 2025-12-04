# studiofy-crm
A professional CRM for Photographers using Wordpress. Manages clients, bookings, invoices (Square), and contracts (Digital Signature).

=== Studiofy CRM ===
Contributors: irgordon
Donate link: https://iangordon.app/studiofycrm/donate
Tags: crm, photography, scheduling, invoice, digital signature, square, google calendar
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A professional Photography Studio CRM. Manage clients, sync Google Calendar, send Square invoices, and collect digital signatures.

== Description ==

Studiofy CRM is a lightweight, high-performance Customer Relationship Management tool built specifically for photographers and creative studios. Unlike bloated SaaS subscriptions, Studiofy lives inside your WordPress dashboard, giving you full control over your data.

Designed with **Privacy & Security by Design**, all sensitive tokens are encrypted at rest. The plugin focuses on speed, using transient caching to ensure your dashboard loads instantly, even with thousands of clients.

**Core Features**

* **Client Management:** Convert leads from Contact Form 7 directly into CRM clients. Robust sorting, filtering, and status tracking (Lead, Booked, Invoice Sent).
* **Google Calendar Integration:** Automatically push your studio bookings to your Google Calendar to prevent double-bookings.
* **Square Payments:** Generate and email professional invoices directly via the Square API. Track payment status in real-time.
* **Digital Contracts:** Create legal agreements using the native WordPress editor. Send links to clients to sign digitally. Captures signature image, IP address, and timestamp for audit trails.
* **Performance Dashboard:** Get an at-a-glance view of unpaid invoices, pending contracts, and upcoming shoots without slowing down your admin area.

== Installation ==

1. Upload the `studiofy-crm` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Studiofy CRM > Settings** to configure your API Integrations.

---

### **Configuration: Google Calendar Integration**

To sync bookings with your personal calendar, you must create a Google Cloud Project.

1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a **New Project** (e.g., named "Studiofy CRM").
3. Go to **APIs & Services > Library**, search for **"Google Calendar API"**, and enable it.
4. Go to **APIs & Services > OAuth consent screen**. select **External**, and fill in the required contact info. Add `calendar.events` to the Scopes.
5. Go to **Credentials > Create Credentials > OAuth client ID**.
6. Select **Web application**.
7. **IMPORTANT:** Under "Authorized redirect URIs", paste the URL found on your **Studiofy Settings** page (usually `https://your-site.com/wp-admin/admin.php?page=studiofy-settings`).
8. Copy the **Client ID** and **Client Secret** and paste them into the Studiofy Settings page.
9. Click **Save Settings**, then click the **"Connect Google Calendar"** button that appears.

### **Configuration: Square Invoices**

To send invoices, you need credentials from Square.

1. Log in to the [Square Developer Dashboard](https://developer.squareup.com/console).
2. Click **+** to create a new application.
3. Open the application settings.
4. On the left sidebar, click **Credentials**. Copy your **Access Token**.
5. On the left sidebar, click **Locations**. Copy the **Location ID** for your business.
6. Paste these into the Studiofy Settings page under the "Square Payments API" section.
7. Select "Production" (Live) or "Sandbox" (Testing) environment.

== Frequently Asked Questions ==

= Do I need to pay for the Google or Square APIs? =
Generally, no. The Google Calendar API has a very generous free tier that covers typical studio usage. Square creates invoices for free, but you (the merchant) pay standard credit card processing fees when a client pays an invoice.

= Why do I need to create my own Google App? =
To ensure data privacy and security, Studiofy is designed as a self-hosted application. By creating your own Google Cloud Project, you ensure that you—and only you—have access to your calendar data. No third-party servers are involved.

= I get a "redirect_uri_mismatch" error when connecting Google. =
This means the URL you entered in the Google Cloud Console does not match your WordPress site exactly. Check for `http` vs `https` and `www` vs non-www. Copy the URI exactly as shown on the Studiofy Settings page.

= Where is client data stored? =
All data is stored in your local WordPress database in custom tables (`wp_studiofy_clients`, `wp_studiofy_bookings`, etc.) to keep the core `wp_posts` table clean and performant.

= Is the digital signature legally binding? =
Studiofy CRM captures the signature image, the signer's IP address, and a timestamp. While this meets the basic requirements for many e-signature laws (like ESIGN in the US), you should always consult with a legal professional regarding contracts in your specific jurisdiction.

== Screenshots ==

1. **Dashboard Overview** - High-performance widget showing leads, unpaid invoices, and upcoming shoots.
2. **Client List** - Filterable list of clients with status badges.
3. **Settings Page** - Centralized API configuration for Google and Square.
4. **Digital Contract** - The frontend interface for clients to sign agreements.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added Client Management with Contact Form 7 integration.
* Added Google Calendar API integration.
* Added Square Invoices API integration.
* Added Digital Signatures with IP audit trail.

== Upgrade Notice ==

= 1.0.0 =
This is the first version of Studiofy CRM. Welcome aboard!
