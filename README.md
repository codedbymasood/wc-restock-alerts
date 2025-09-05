# Plugin Name

A lightweight WooCommerce addon that allows customers to subscribe for notifications when an out-of-stock product is restocked. Includes intelligent follow-up emails and include dynamic discount codes to recover more sales.

---

## Features

- Show **"Notify Me"** button on out-of-stock product pages
- Store user email and product info in a custom table
- Send **automatic email** when the product is back in stock
- **Track purchases** to prevent unnecessary emails

---

## Use Case

1. Customer visits an out-of-stock product and subscribes.
2. When stock is updated, the plugin sends an email.

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

## License

GPLv2 or later

---
