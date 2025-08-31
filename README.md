# Restock Alerts for WooCommerce

A lightweight WooCommerce addon that allows customers to subscribe for notifications when an out-of-stock product is restocked. Includes intelligent follow-up emails and include dynamic discount codes to recover more sales.

---

## Features

- Show **"Notify Me"** button on out-of-stock product pages
- Store user email and product info in a custom table
- Send **automatic email** when the product is back in stock
- **Track purchases** to prevent unnecessary emails
- Send **follow-up emails** (2 days later) only if the product wasn’t purchased
- Generate **unique, time-limited coupon codes** for follow-up

---

## Use Case

1. Customer visits an out-of-stock product and subscribes.
2. When stock is updated, the plugin sends an email.
3. If the customer hasn’t purchased within 2 days, a follow-up email with a discount coupon is sent.
4. After purchase, the customer status changed to completed to prevent unnecessary follow-up.

---

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin from the WordPress dashboard
3. Rest is automated!

---

## Cron Info

- Uses **`wp_schedule_single_event()`** to send follow-ups per subscriber
- Make sure your WP-Cron is running correctly (or set a real cron job for reliability)

---

## Support

This plugin is developer-focused. For issues or contributions, feel free to open an issue or pull request.

---

## License

GPLv2 or later

---

## Author

Made with care by [Store Boost Kit](https://github.com/codedbymasood)
Follow me on [Twitter](https://x.com/masoodmohamed90)
Connect me on [Linkedin](https://www.linkedin.com/in/masoodmohamed/)
