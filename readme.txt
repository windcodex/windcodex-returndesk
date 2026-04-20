=== WindCodex ReturnDesk - Return for WooCommerce ===
Contributors: windcodex
Tags: woocommerce, returns, rma, refund, return management
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 10.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create a complete self-service returns workflow for WooCommerce customers and store admins.

== Description ==

**Return for WooCommerce** helps you run a clear, customer-friendly return process directly inside WooCommerce.

Customers can submit return requests from **My Account -> Order details**, while store admins review and manage requests from the WooCommerce admin area.

No external SaaS required. No complex setup. Configure your return window, eligibility rules, and policy text, then go live.

**Built for WooCommerce stores that want fewer support tickets, faster return handling, and a cleaner post-purchase experience.**

= Why store owners choose ReturnDesk =

* **Reduce support overhead** - Let customers submit return requests themselves instead of contacting support first.
* **Enforce clear return policy rules** - Control which orders qualify, for how long, and whether sale items are eligible.
* **Improve customer trust** - Show return policy and return address directly in the request flow.
* **Keep operations organized** - Review, approve, reject, and manage requests in one admin workflow.
* **Improve communication** - Use built-in email notifications for both admin and customer updates.

= Key Features =

* **Self-service return portal** - Return button and request page in My Account order view.
* **Eligibility controls** - Configure return window days and allowed order statuses.
* **Sale item policy control** - Choose whether sale items are return-eligible.
* **Policy + address content** - Display return instructions and shipping return address in customer flow.
* **Admin request management** - Manage requests from WooCommerce admin with status actions.
* **Email notifications** - Notify store admin and customers about new requests and updates.
* **Template/test support** - Configure email templates and send test emails.
* **Translation ready** - POT file included for localization.

= Settings Included In Free Version =

* **Common settings** - Allowed order statuses, terms & conditions page.
* **Return address settings** - Address line 1/2, city, state, country, postcode/ZIP, phone.
* **Return rules settings** - Enable/disable returns, return window (days), allow/block sale-item returns.
* **Return form settings** - Return guidelines and return reasons list.
* **Customer message setting** - Success message after request submission.
* **Email settings** - Admin notification email, enable/disable email groups, editable subject/heading/content/items title/additional content for templates: New request (admin), New request (customer), Approved (customer), Rejected (customer), Cancelled (customer).
* **Email tools** - Live preview (desktop/mobile) and send test email.
* **Request management** - Search/filter request list, approve/reject pending requests, delete request, view attachments.

= Common Use Cases =

* Accept returns only for completed or delivered orders.
* Set a strict return window (for example 7, 14, or 30 days).
* Provide clear return instructions and return address to reduce back-and-forth.
* Standardize return operations for multi-staff WooCommerce stores.

= How It Works =

1. Install and activate ReturnDesk.
2. Go to **WooCommerce -> ReturnDesk** and configure return settings.
3. Set eligibility rules (days, order statuses, sale-item policy).
4. Add return policy and return address content.
5. Customers submit requests from **My Account -> Orders -> View**.
6. Admin manages requests from WooCommerce return management screens.

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 7.0 or higher
* PHP 7.4 or higher

= Privacy =

ReturnDesk stores return request data in your WordPress database for return workflow operations. By default, the plugin does not send customer request data to third-party services.

== Installation ==

**From your WordPress dashboard:**

1. Go to **Plugins -> Add New -> Upload Plugin**.
2. Upload the ReturnDesk plugin ZIP file.
3. Click **Install Now**, then **Activate Plugin**.

**Manual installation:**

1. Upload the `windcodex-returndesk` folder to `/wp-content/plugins/`.
2. Activate through the **Plugins** screen in WordPress.

**After activation:**

1. Open **WooCommerce -> ReturnDesk**.
2. Configure return window, allowed statuses, and sale-item policy.
3. Add return policy and return address content.
4. Place a test order and verify request flow from My Account.

== Frequently Asked Questions ==

= Does ReturnDesk require WooCommerce? =

Yes. ReturnDesk works only with WooCommerce.

= Where do customers submit return requests? =

Customers submit requests from **My Account -> Orders -> View** on eligible orders.

= Can I control which orders are eligible? =

Yes. You can configure return window days and allowed order statuses.

= Can I allow or block sale-item returns? =

Yes. Sale item eligibility is configurable in plugin settings.

= Does the free version include exchanges? =

No. This plugin is returns-only.

= Is this plugin translation-ready? =

Yes. A POT file is included in the `languages` directory.

== Screenshots ==

1. ReturnDesk settings page (common + return configuration).
2. Email template and test-email settings.
3. WooCommerce admin request management screen.
4. Customer return request page in My Account order view.

== Changelog ==

= 1.0.0 =
* Initial free release.
* Customer-facing return request flow in My Account.
* Eligibility rules (window/status/sale-item policy).
* Admin return request management.
* Return policy/address content and email notifications.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
