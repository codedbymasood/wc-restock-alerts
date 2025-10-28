=== Restock Alerts for WooCommerce ===
Tags: online store, ecommerce, shop, shopping cart, sell online
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Add a `Notify Me` button for out-of-stock items. Store owner gets the list, user gets email when back in stock.

== Description ==

= Why Choose Restock Alert? =

**Recover Lost Sales** – Automatically bring customers back the moment products restock instead of losing them to competitors.
**Build Customer Loyalty** – Keep shoppers engaged and create urgency. Show them you value their interest.
**Understand Customer Demand** – See which products customers want most to make smarter inventory and purchasing decisions.
**Professional Branding** – Fully customize emails, sender details, and templates to match your store's identity.
**Simple Setup** – No complex configuration, just install and go.

= How It Works =

1. **Customer subscribes** – Subscription form appears on out-of-stock product pages
2. **Email verification** – Confirmation email keeps your list clean
3. **Instant notification** – Customer gets alerted when product restocks
4. **Follow-up reminders** – Send up to 3 additional emails if they haven't purchased
5. **Track & optimize** – Monitor which products are most in-demand

= Features =

- **Variable product support** – Works with simple and variable products
- **Automated alerts** when products come back in stock
- **Email verification** to maintain a clean subscriber list
- **Customizable timing** for follow-up emails (e.g., day 3, 7)
- **Unique discount codes** – Automatically generate personalized discount codes for each subscriber
- **User-specific coupons** – Each discount code is tied to the subscriber and can only be used by them
- **Flexible discounts** – Choose fixed amount or percentage-based offers
- **Stock threshold settings** – Control when the subscription form appears (out of stock or low stock)
- **Downloadable reports** – Export subscriber data anytime
- **Follow-up campaigns** – Send up to 3 reminder emails to re-engage customers
- **Custom email templates** – Modify all email templates with your branding
- **Custom messages** – Personalize the subscription form message on product pages
- **Subscriber reports** – See who subscribed and for which products
- **Email delivery logs** – Track successful sends, failures, and full history
- **Scheduled actions dashboard** – Monitor all upcoming email tasks

== Frequently Asked Questions ==

= How do customers sign up for alerts? =

A subscription form automatically appears on product pages based on your stock settings. Customers enter their email and receive a confirmation to verify their subscription.

= Can I customize the email templates? =

Yes. All four email types (verification, back-in-stock, follow-up, and incentive) are fully customizable with your branding, colors, and messaging.

= What happens if customers don't purchase after the first alert? =

You can configure up to 2 follow-up emails with custom timing intervals (e.g., 3 days, 7 days later) to gently remind them.

= How do discount codes work? =

The plugin automatically generates unique discount codes for each subscriber when they receive a restock alert. Each code is bound to that specific user's email address, preventing sharing or unauthorized use. You can set the discount as either a fixed amount or percentage.

= Can discount codes be shared or reused? =

No. Each discount code is tied to the subscriber's email address and can only be used by them. This prevents discount code abuse and ensures your promotions stay controlled.

= What is the stock threshold feature? =

Stock threshold controls when the subscription form appears. Show it only when products are completely out of stock, or display it when stock falls below a number you define (e.g., show when fewer than 5 items remain).

= How do I know which products are most wanted? =

Detailed subscriber reports show exactly which products have the most signups, helping you prioritize restocking and identify popular items.

= Are email addresses verified? =

Yes. A double opt-in confirmation email is sent when customers subscribe, ensuring only valid, interested customers are added to your notification list.

= Can I see my email history? =

Absolutely. View comprehensive logs of all sent emails including delivery status (successful, pending, failed). Download or clear these records anytime.

= Does it work with variable products? =

Yes. The plugin fully supports both simple and variable WooCommerce products, including different sizes, colors, and variations.

= What email services does it support? =

It works with any WordPress email setup including WordPress default mail, SMTP plugins (WP Mail SMTP, Easy WP SMTP).

= Will it slow down my site? =

No. The plugin is lightweight and optimized for performance. Email sending is handled in the background using scheduled tasks.

= Is there a limit to subscribers? =

No subscriber limits. Handle unlimited subscribers and products.

= How long are discount codes valid? =

You can set the expiration period for discount codes in the plugin settings (e.g., 7 days).

= How do I get support? =

Visit our support page or contact us directly. We typically respond within 24 hours on business days.

== Installation ==

= Minimum Requirements =

* PHP 7.4 or greater is required (PHP 8.0 or greater is recommended)
* MySQL 5.5.5 or greater, OR MariaDB version 10.1 or greater, is required
* WordPress 6.7 or greater

= Manual installation =

Manual installation method requires downloading the WooCommerce plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation).

This external service usage is solely for displaying informational content and does not affect the core functionality of the plugin. All communications use secure HTTPS connections.

== Screenshots ==

1. General settings panel where you configure the plugin.
2. Subscription table that tracks user activity: subscribed status, purchased items, emails sent, and product/variation details.
3. Frontend view of how notifications are displayed to customers.
4. Admin email template settings to customize outgoing messages.
5. Email logs with status details showing whether emails were sent or failed.
6. Onboarding process that guides users through initial setup.
7. Debug logs section showing tracked issues, errors, and warnings.

= Updating =

Automatic updates should work smoothly, but we still recommend you back up your site.

If you encounter issues with the pages after an update, flush the permalinks by going to WordPress > Settings > Permalinks and hitting "Save." That should return things to normal.

== Changelog ==

= 1.2.0 2025-10-28 =
* Add - Email queue table added for performance.
* Add - Added email retries and daily check to improve the deliverability.
* Add - Email verification feature added.

= 1.1.0 2025-09-29 =
* Add - Export feature added.
* Add - Follow-up email sequence added.
* Add - Can able to generate and send unique discount on final follow-up email.
* Add - Email templates added for follow-up emails.
* Add - Conditional tags added in email templates.
* Fix - Security issue resolved in notification list table.

= 1.0.0 2025-09-26 =
* Initial Release