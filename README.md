# Event Registration Drupal 10 Module

[![Drupal 10+](https://img.shields.io/badge/Drupal-10%2B-blue.svg)](https://www.drupal.org/)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-purple.svg)](https://www.php.net/)
[![License: GPLv2](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

A professional, high-performance Drupal 10 module designed for seamless event management and registrant tracking. This module emphasizes a reactive UX, strict data integrity, and enterprise-grade code architecture.

---

## 📌 Table of Contents
1. [Key Features](#-key-features)
2. [Installation & Setup](#-installation--setup)
3. [Administrative Dashboard](#-administrative-dashboard)
4. [Public Experience](#-public-experience)
5. [Developer Documentation](#-developer-documentation)
    - [Architecture](#architecture)
    - [Database Access](#database-access)
    - [Email Testing](#email-testing)
6. [Standards](#-standards)

---

## ✨ Key Features

*   **Reactive AJAX Flows**: Deep cascading logic (**Category → Date → Event**) ensures a frictionless registration process.
*   **Time-Locked Windows**: Automatic locking of registration based on customizable start/end windows.
*   **Live Analytics**: Real-time participant counting and multi-criteria filtering in the admin portal.
*   **Security Focused**: 
    *   Strict duplicate prevention (one registration per person per event date).
    *   Robust input sanitization via regex to prevent XSS and SQL injection.
*   **Decoupled Architecture**: 100% Dependency Injection; zero static service calls.

---

## ⚙️ Installation & Setup

1.  Clone/Place the `event_registration` folder into `modules/custom/`.
2.  Enable via Drush:
    ```bash
    drush en event_registration
    ```
3.  The module will automatically provision the required database schema.

---

## 🛠️ Administrative Dashboard

### Configuration
Manage your events and global notification settings.

*   **Event Setup**: `/admin/config/services/event-registration`
    > [!TIP]
    > You can configure multiple events. Registration forms will only enable when the current time is within the defined window.
    ![Event Config](web/modules/custom/event_registration/images/event_config_form_real.png)

*   **Global Alerts**: `/admin/config/services/event-registration/global`
    ![Global Settings](web/modules/custom/event_registration/images/global_settings_real.png)

### Registrations & Export
Monitor attendees and export data for external use.
*   **Path**: `/admin/config/services/event-registration/registrations`
    ![Admin Dashboard](web/modules/custom/event_registration/images/admin_dashboard_real.png)

---

## 🌐 Public Experience

Users interact with a reactive form located at:  
👉 **`/event/register`**

![Registration Form](web/modules/custom/event_registration/images/registration_form_real.png)

---

## 💻 Developer Documentation

### Architecture
- **Form API**: Custom cascading AJAX callbacks for dynamic field updates.
- **Mail API**: Integrated `hook_mail` for transactional user and admin notifications.
- **Database**: Custom schema implementation via `hook_schema`.

### Database Access
To inspect the underlying relational data in your local environment:
```bash
# Launch phpMyAdmin
ddev phpmyadmin
```
**Relational View:**
![DB Config](web/modules/custom/event_registration/images/db_configuration_table.png)
![DB Registration](web/modules/custom/event_registration/images/db_registration_table.png)

### Email Testing
We use **Mailpit** to capture all outgoing registrations.
```bash
# Browse Captured Mail
ddev mailpit
```
**Mailpit Inbox View:**
![Mailpit Preview](web/modules/custom/event_registration/images/mailpit_debug_real.png)

---

## 🏆 Standards
*   **PSR-4 Autoloading**
*   **Drupal 10.x/11.x Core Compatibility**
*   **PSR-12 PHP Coding Standards**
*   **Strict Dependency Injection Pattern**
