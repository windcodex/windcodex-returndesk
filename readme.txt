=== WindCodex ReturnDesk – WooCommerce Returns, Refunds & RMA Management ===
Contributors: windcodex
Tags: woocommerce returns, refund, rma, return management, woocommerce
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 1.0.1
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 10.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automate WooCommerce returns and refunds. Self-service return portal, email notifications, RMA management – no SaaS required.

== Description ==

**WindCodex ReturnDesk** is the free WooCommerce returns and refund plugin that gives your store a complete self-service return management system. Customers submit return requests directly from **My Account → Orders → View** – without emailing support first. You review, approve, or reject requests from a clean WooCommerce admin screen.

No external SaaS, no monthly fee, no complex setup. Configure your return window, eligibility rules, and policy content, then go live in minutes.

**Built for WooCommerce stores that want to automate returns, handle refund requests, and deliver a more professional post-purchase experience.**

= Why Store Owners Choose ReturnDesk =

* **Reduce support volume** – Customers handle return requests themselves instead of contacting you first. Fewer emails, fewer back-and-forth threads.
* **Automate returns processing** – Define eligibility rules once. ReturnDesk enforces your return window, allowed statuses, and sale-item policy automatically – no manual review needed.
* **Enforce your WooCommerce return policy** – Show your policy and return address inline in the request flow so customers always know what to expect.
* **Build customer trust** – A clear, accessible returns process is a key purchase-confidence signal that reduces cart abandonment.
* **Keep operations organized** – All return merchandise authorization (RMA) requests in one place. Review, approve, reject, and track from the WooCommerce admin. No spreadsheets.
* **Professional email notifications** – Fully editable email templates for admin and customer communications. Every status change triggers the right message automatically.

= Key Features =

* **Self-service return portal** – Return request button and form embedded in the My Account order view. Customers do not need to leave your site.
* **Eligibility rules** – Set return window in days, restrict eligible order statuses, and control whether sale items qualify.
* **Return policy & address display** – Show return instructions and the return shipping address directly in the customer request flow to reduce back-and-forth questions.
* **Admin request management** – Full request list with search and filter. Approve or reject requests in one click. View attachments. Delete requests.
* **Email notification system** – Separate email groups for admin (new request) and customer (new request confirmed, approved, rejected, cancelled). Each template has editable subject, heading, body, and additional content.
* **Live email preview & test send** – Preview email templates in desktop and mobile views and send test emails before going live.
* **Return reasons list** – Configure your own list of selectable return reasons for customers to choose from.
* **Custom success message** – Set the message customers see after successfully submitting a return request.
* **Terms & conditions integration** – Link your returns T&C page in the return request flow.
* **Translation ready** – POT file included in the `languages` directory. Works with Loco Translate.

= Free Version Settings =

* **Common settings** – Allowed order statuses, terms & conditions page link.
* **Return address** – Address line 1 and 2, city, state, country, postcode/ZIP, and phone.
* **Return rules** – Enable or disable returns, return window (days), allow or block sale-item returns.
* **Return form** – Return guidelines text and configurable return reasons list.
* **Customer message** – Custom success message shown after request submission.
* **Email settings** – Admin notification address, enable/disable per email group, editable templates: New Request (admin), New Request (customer), Approved (customer), Rejected (customer), Cancelled (customer).
* **Email tools** – Live preview (desktop/mobile) and test email sender.
* **Request management** – Search and filter request list, approve/reject pending requests, delete requests, view attachments.

= Use Cases =

* Enforce a WooCommerce return policy automatically – for example a strict 14 or 30-day return window from order date.
* Accept returns only for completed or delivered orders and block requests on processing or refunded orders.
* Automate returns so customers never need to email support – everything handled through the My Account portal.
* Display the WooCommerce refund plugin request form directly in the My Account order view without redirecting customers.
* Provide clear return instructions and a return shipping address to reduce confusion and back-and-forth emails.
* Collect structured return reason data to identify recurring product issues and reduce future returns.
* Standardize the return process across multiple staff members so every request is handled consistently.

= How It Works =

1. Install and activate ReturnDesk.
2. Go to **WooCommerce → ReturnDesk** and configure return settings.
3. Set eligibility rules (days, allowed order statuses, sale-item policy).
4. Add return policy text and return address details.
5. Customers submit return requests from **My Account → Orders → View** on eligible orders.
6. Review and manage all requests from the WooCommerce admin return management screens.

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 7.0 or higher
* PHP 7.4 or higher

= 🚀 Pro Version =

Need auto-approvals, exchanges, store credit, partial returns, or WhatsApp notifications?
[ReturnDesk Pro is available at windcodex.com](https://windcodex.com/product/woocommerce-returns-plugin/)

= Privacy =

ReturnDesk stores return request data – including customer-provided reason and request details – in your WordPress database for return workflow operations. By default, this data is not sent to any third-party service. You are responsible for documenting this data storage in your own privacy policy.

== Installation ==

**From your WordPress dashboard:**

1. Go to **Plugins → Add New**.
2. Search for **WindCodex ReturnDesk**.
3. Click **Install Now**, then **Activate Plugin**.

**Manual installation:**

1. Download the plugin ZIP file.
2. Upload the `windcodex-returndesk` folder to `/wp-content/plugins/`.
3. Activate through the **Plugins** screen in WordPress.

**After activation:**

1. Open **WooCommerce → ReturnDesk**.
2. Configure return window, allowed statuses, and sale-item policy.
3. Add return policy content and your return shipping address.
4. Place a test order, mark it complete, and verify the return request flow from My Account.

== Frequently Asked Questions ==

= Does ReturnDesk require WooCommerce? =

Yes. ReturnDesk is a WooCommerce-specific plugin and requires WooCommerce to be installed and active.

= Where do customers submit return requests? =

Customers see a "Request Return" button on eligible orders under **My Account → Orders → View**. Clicking it opens the return request form directly within your site.

= How do I control which orders are eligible for returns? =

In ReturnDesk settings, you can set the return window in days (counted from the order date), and specify which order statuses qualify – for example, only "Completed" orders.

= Can I block sale items from being returned? =

Yes. Sale item eligibility is a dedicated toggle in the return rules settings. You can allow or block sale-item returns globally.

= How do I approve or reject a return request? =

Go to **WooCommerce → ReturnDesk → Requests** in your admin. Each request has one-click Approve and Reject buttons. Approving or rejecting sends the corresponding email notification to the customer automatically.

= Can customers attach photos or files to their return request? =

Yes. Customers can attach files (such as photos of damaged items) when submitting a request. Attachments are visible to admins in the request detail view.

= Does the free version support exchanges or store credit? =

No. The free version handles return requests only. Exchanges and store credit options are available in ReturnDesk Pro.

= Can I edit the email templates? =

Yes. Every email template – New Request (admin), New Request (customer), Approved, Rejected, Cancelled – has editable subject, heading, body, and additional content fields. You can also preview templates in desktop/mobile view and send test emails before going live.

= Is there a way to test the return flow without a real customer? =

Yes. Place a test order in your own store, set its status to Completed (or whatever status your settings allow), then visit the order in My Account and use the return request form to test the complete flow end to end.

= Does ReturnDesk support multiple languages? =

Yes. A POT file is included in the `languages` directory and the plugin is fully translation-ready. Use Loco Translate or any standard WordPress translation tool.

= Can customers request exchanges instead of refunds? =

Exchanges are a Pro feature. The free version handles return requests only. You can use the return reason and custom success message to guide customers who need an exchange toward contacting you directly.

= Does ReturnDesk support partial returns on multi-item orders? =

Partial returns are available in ReturnDesk Pro. In the free version, return requests apply to the full order.

= Can I set different return windows for different products? =

The free version applies one global return window to all products. Per-product return windows are available in ReturnDesk Pro.

= Does ReturnDesk work with WooCommerce Subscriptions? =

ReturnDesk works on any order type that WooCommerce creates. Return requests on subscription-related orders are supported as long as the order status matches your eligibility settings.

= Can I send WhatsApp notifications for return updates? =

WhatsApp notifications are a Pro feature. The free version includes email notifications for all return status changes (new, approved, rejected, cancelled).

= How do I give customers store credit instead of a refund? =

Store credit is a Pro feature. In the free version, refunds and credit decisions are handled manually by the store admin after approving a return request through the WooCommerce admin.

= Can I automatically approve return requests? =

Auto-approval rules are available in ReturnDesk Pro. In the free version, each request requires manual review and one-click approval or rejection from the admin request list.

= How do I customise the return request email templates? =

Go to **WooCommerce → ReturnDesk → Email Settings**. Each email template (New Request for admin, New Request for customer, Approved, Rejected, Cancelled) has editable subject, heading, body, and additional content fields. Use the live preview tool to check desktop and mobile rendering before saving.

= Is there a Pro version? =

Yes. ReturnDesk Pro adds advanced features including auto-approvals, exchanges, store credit, partial returns, per-product return windows, WhatsApp notifications, and extended reporting. Visit [windcodex.com](https://windcodex.com/product/woocommerce-returns-plugin/) for details.

= Does this plugin work with WooCommerce HPOS (High-Performance Order Storage)? =

Yes. ReturnDesk is compatible with WooCommerce High-Performance Order Storage (HPOS/custom order tables).

== Screenshots ==

1. **Settings page** – Return rules, return window, and policy content configuration.
2. **Email template settings** – Editable templates with live desktop/mobile preview and test-send tool.
3. **Admin request management** – Full request list with search, filter, approve, and reject actions.
4. **Customer return request form** – Return button and request form in the My Account order view.

== Changelog ==

= 1.0.1 =
* Improved: Settings page UI for better usability.
* Added: Admin review request notice.
* Added: Pro features notice.

= 1.0.0 =
* Initial free release.
* Customer-facing return request flow embedded in My Account order view.
* Eligibility rules: return window in days, allowed order statuses, sale-item policy toggle.
* Return policy text and return address display in customer request flow.
* Admin return request management with approve, reject, and delete actions.
* Five email notification templates with editable subject, heading, body, and additional content.
* Live email preview (desktop/mobile) and test-send tool.
* Configurable return reasons list and custom success message.
* POT file included for full translation support.

== Upgrade Notice ==

= 1.0.1 =
Adds inline Pro upsell banner, review request notice, and help button in the settings header. No database changes – safe to update.

= 1.0.0 =
Initial release – no upgrade steps required.
