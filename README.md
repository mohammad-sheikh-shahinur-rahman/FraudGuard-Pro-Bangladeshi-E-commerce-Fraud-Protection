# bKash Tokenized Checkout DevHub 🚀

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D8.x-red.svg)](https://laravel.com/)

A professional-grade, interactive playground and developer guide for **bKash Tokenized Checkout (v1.2.0-beta)** integration. This project provides a complete, drop-in solution for both **Laravel** and **Raw PHP**, featuring a modern dashboard with real-time analytics and transaction management.

---
![ , ](image.png)
![alt text](image-1.png) ![ ](image-2.png) ![alt text](image-3.png) ![alt text](image-4.png) ![alt text](image-5.png)
## 👨‍💻 Developed By
**Mohammad Sheikh Shahinur RAHMAN**  
*Software Engineer | CTO | DevOps Architect | Independent Researcher | Writer & Poet*  
🔗 [LinkedIn Profile](https://www.linkedin.com/in/mohammad-sheikh-shahinur-rahman/)

---

## ✨ Key Features

### 📊 Interactive Dashboard
- **Real-time Analytics**: Monitor Total Volume, Success Payments, and Refund statistics at a glance.
- **Transaction Trends**: Visualized data using **Chart.js** to track payment performance over time.
- **Live Search**: Instant filtering of local transaction history by Invoice, Payer, or Payment ID.
- **CSV Export**: Download your entire transaction log for accounting and reporting.

### 🛠️ bKash Integration Playground
- **Step-by-Step Flow**: Visual progress nodes tracking the Token -> Create -> Execute lifecycle.
- **Live API Console**: View real-time `cURL` traces, request payloads, and bKash API responses.
- **Credential Manager**: Easily switch between Sandbox and Production credentials via the UI.
- **Refund Management**: Initiate and track refunds directly from the dashboard.
- **IPN Simulator**: Test your Webhook (IPN) listeners with a built-in simulation tool.

### 🚀 Integration Packages
- **Laravel Guide**: Clean, service-oriented architecture with **Service Provider** and **Facade** support for easy integration.
- **Raw PHP Driver**: A robust, session-aware class (`BkashTokenized.php`) for projects without frameworks.
- **SQLite Database**: Self-contained transaction logging out of the box.

---

## 📂 Project Structure

```text
├── index.php                # Main DevHub Dashboard (Playground UI)
├── style.css                # Modern Dark Theme Styles
├── packages/                # Unified Package Directory
│   ├── laravel/             # Drop-in files for Laravel projects
│   │   ├── app/Services/    # BkashService.php (Core Logic)
│   │   ├── app/Http/Controllers/# BkashController.php
│   │   └── config/bkash.php # Laravel Configuration
│   └── php-sdk/             # Raw PHP implementation (SDK)
│       ├── BkashTokenized.php # Core API Driver
│       ├── db.php           # SQLite Database Manager
│       ├── ipn.php          # Webhook Listener
│       └── callback.php     # Redirect Handler
```

---

## 🚀 Quick Start

1. **Environment Setup**:
   Ensure you have a PHP environment (XAMPP/WAMP/Laragon) running.
   
2. **Configuration**:
   Add your merchant credentials in `packages/php-sdk/config.php` or through the **Gateway Credentials** card in the dashboard sidebar.

3. **Database**:
   The project uses SQLite. The `database.sqlite` will be automatically initialized on first run.

4. **Testing**:
   Use the **Sandbox Wallet Numbers** provided in the dashboard (e.g., `01770618575`) with OTP `123456` and PIN `12121`.

---

## 📦 Installation via Composer

You can include this project as a dependency in your PHP project:

```bash
composer require shahinur/bkash-tokenized-checkout-devhub
```

---

## 🏗️ Laravel Integration Guide

This project contains a complete, drop-in integration package for **bKash Tokenized Checkout (v1.2.0-beta)** in Laravel located in the `packages/laravel` folder.

### 1. Copy Files to Project
Copy the corresponding directories/files to your Laravel application:
- Copy `config/bkash.php` to your `config/` folder.
- Copy `app/Services/BkashService.php` to your `app/Services/` folder.
- Copy `app/Http/Controllers/BkashController.php` to your `app/Http/Controllers/` folder.
- Append the routes in `routes/web.php` to your `routes/web.php` file.
- Copy `resources/views/bkash-payment.blade.php` to your `resources/views/` folder.

### 2. Configure Environment `.env`
Add your merchant credentials to your `.env` file:
```env
BKASH_SANDBOX=true
BKASH_APP_KEY=your_app_key
BKASH_APP_SECRET=your_app_secret
BKASH_USERNAME=your_username
BKASH_PASSWORD=your_password
```

### 3. Usage & Payment Redirect
To initiate checkout, redirect the user using a `POST` request to `route('bkash.pay')` specifying the `amount` input.
In `BkashController::callback()`, add your business logic under `// SUCCESS` to record transaction details and flag the order as completed.

---

## 📜 Professional Mandate
This integration is built with **Security**, **Performance**, and **Scalability** in mind. It follows the official bKash Tokenized API documentation v1.2.0-beta, ensuring your payment gateway is future-proof and robust.

---

## 🤝 Contributing
Contributions, issues, and feature requests are welcome! Feel free to check the [issues page](https://github.com/mohammad-sheikh-shahinur-rahman/bKash-Tokenized-Checkout-DevHub/issues).

## ⭐️ Show your support
Give a ⭐️ if this project helped you!

## 📜 License
Copyright © 2026 [Mohammad Sheikh Shahinur RAHMAN](https://www.linkedin.com/in/mohammad-sheikh-shahinur-rahman/).  
This project is [MIT](https://opensource.org/licenses/MIT) licensed.

---

© 2026 **Mohammad Sheikh Shahinur RAHMAN**. All rights reserved.
