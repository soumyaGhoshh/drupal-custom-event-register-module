# Event Registration Drupal 10 Module

## Overview
This module provides a robust framework for managing event registrations. It allows administrators to configure multiple events with specific registration windows and categories, while providing a seamless, AJAX-powered registration experience for end-users.

## Key Features
- **Sophisticated AJAX Cascading**: The public registration form features a deep cascading logic: **Category -> Event Date -> Event Name**, ensuring users only see relevant and open events.
- **Dynamic Access Control**: Forms are automatically enabled/disabled based on the registration window (start/end dates) defined by the administrator.
- **Advanced Admin Dashboard**: A specialized listing page with AJAX-driven filtering by date and event name, including a real-time participant counter and CSV export functionality.
- **Security & Validation**: 
    - Duplicate registration prevention (Email + Event Date).
    - Regex-based input sanitization to prevent special character exploits.
    - Full PSR-4 and Drupal 10.x compliance.
- **100% Dependency Injection**: No static `\Drupal::service()` calls in business logic, ensuring testability and high-quality architecture.

## Installation & Setup
1. Place the `event_registration` folder in your Drupal site's `modules/custom/` directory.
2. Enable the module using Drush:
   ```bash
   drush en event_registration
   ```
3. The custom database tables (`event_configuration` and `event_registration`) are automatically created upon installation.

## URLs & Access
- **Event Configuration**: `/admin/config/services/event-registration`
  - Purpose: Add new events, set registration windows, and define categories.
- **Global Settings**: `/admin/config/services/event-registration/global`
  - Purpose: Configure admin notification email and enable/disable alerts.
- **Admin Dashboard**: `/admin/config/services/event-registration/registrations`
  - Purpose: Filter registrations, view participant counts, and export CSVs.
- **Public Registration Form**: `/event/register`
  - Purpose: User-facing form for event signup.

## Database Architecture
The module utilizes two relational tables:
1. **`event_configuration`**: Stores event metadata.
   - `id`: Primary Key.
   - `event_registration_start`: Timestamp for opening registration.
   - `event_registration_end`: Timestamp for closing registration.
   - `event_date`: Stored as a string for consistent display.
   - `event_name` & `category`: Textual descriptors.
2. **`event_registration`**: Stores registrant data.
   - `event_id`: Foreign Key linking to `event_configuration`.
   - `name`, `email`, `college`, `department`: User inputs.
   - `created`: Submission timestamp.

## Implementation Logic
- **Email Logic**: Utilizes the Drupal Mail API via `hook_mail` and `MailManagerInterface`. Notifications are triggered upon successful submission, sending personalized details to both the registrant and the administrator (if enabled).
- **Validation Logic**: A multi-layered validation approach. First, we check for existing database entries to prevent duplicates. Second, we verify email syntax. Third, we use regular expressions to enforce character restrictions on text fields.
- **AJAX Logic**: Uses Drupal's `AjaxResponse` and `ReplaceCommand` to provide a reactive UI, updating filtered lists and counters without page refreshes.

## Development Standards
- **PSR-4 Autoloading**
- **Dependency Injection Pattern**
- **Drupal Coding Standards (Coder/PHPCS)**
- **Strict adherence to Drupal 10 API**

## Email Testing
To verify email delivery during development (localhost/DDEV), you can check the captured emails in one of two ways:
1. **DDEV Mailpit**: If you are running the site via DDEV, run `ddev launch -m` to open the Mailpit interface where all outgoing emails are captured.
2. **Maillog Module**: If you have the `maillog` contrib module enabled for debugging, you can view sent emails at `/admin/reports/maillog`.
