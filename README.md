# PrusaLink Fetcher & Dashboard

## Overview
This project consists of three main components to monitor and display the live status of multiple Prusa MK4S printers:

1. **Python Fetcher (`fetch_prusa_status.py`)**  
   - Queries each printer’s `/api/v1/status` endpoint using HTTP Digest authentication.  
   - Posts the JSON payload to a PHP endpoint for storage in a MySQL table.  
2. **PHP Endpoint (`add3dprinter_status.php`)**  
   - Receives the JSON from the Python fetcher.  
   - Inserts or updates the record in the `3dprinter_status` table, with a UTC timestamp.  
3. **WordPress Shortcode (`printer_cards`)**  
   - Fetches rows from `3dprinter_status`.  
   - Renders a responsive grid of status “cards” on any page/post via `[printer_cards]`.  
   - Stale cards (>10 min since last update) are dimmed at 50% opacity.

---

## 1. Python Fetcher Script

Save `fetchandpost.py` to a computer locally on your network that always is on.
Schedule the script to run via cron for periodic updates.

---

## 2. PHP Endpoint

Save as `add3dprinter_status.php` alongside `constants.php`:

---

## 3. MySQL Schema

Use `setupdb.SQL` to create the table in your MySQL database.

---

## 4. WordPress Shortcode

Add the functions from `functions.php` to your theme’s `functions.php` on the WordPress website to create a shortcode in the WordPress environment.

Use shortcode in any page/post:

```text
[printer_cards]
```

---