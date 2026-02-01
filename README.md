# Event Registration Module for Drupal 10

A custom Drupal 10 module that allows users to register for events via a custom form, stores registrations in a database, and sends email notifications.

## Features

- **Event Configuration**: Admin form to create and manage events with registration periods
- **Event Registration Form**: User-facing form with AJAX-powered dropdowns
- **Email Notifications**: Automatic confirmation emails to users and admin notifications
- **Admin Dashboard**: View registrations with filtering and CSV export
- **Validation**: Duplicate prevention, email validation, special character filtering
- **Access Control**: Custom permissions for different user roles

## Requirements

- Drupal 10.x
- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+

## Installation

### Step 1: Copy Module Files

Copy the `event_registration` folder to your Drupal installation:

```
web/modules/custom/event_registration/
```

### Step 2: Enable the Module

Using Drush:
```bash
drush en event_registration -y
```

Or via the Drupal admin UI:
1. Navigate to **Extend** (`/admin/modules`)
2. Find "Event Registration" in the list
3. Check the checkbox and click "Install"

### Step 3: Configure Module Settings

1. Go to **Configuration > System > Event Registration Settings** (`/admin/config/event-registration/settings`)
2. Enter the admin notification email address
3. Enable/disable email notifications as needed
4. Save configuration

### Step 4: Set Up Permissions

Navigate to **People > Permissions** (`/admin/people/permissions`) and assign:

| Permission | Description |
|------------|-------------|
| `Administer Event Registration` | Configure settings and manage events |
| `View Event Registrations` | View admin listing and export CSV |
| `Register for Events` | Allow users to register for events |

### Step 5: Create Events

1. Go to **Configuration > System > Event Configuration** (`/admin/config/event-registration/events`)
2. Fill in event details:
   - Event Name
   - Category (Online Workshop, Hackathon, Conference, One-day Workshop)
   - Event Date
   - Registration Start Date
   - Registration End Date
3. Click "Save Event"

## URLs and Routes

### Public URLs

| URL | Description |
|-----|-------------|
| `/event/register` | Event registration form (accessible during registration period) |

### Admin URLs

| URL | Description |
|-----|-------------|
| `/admin/config/event-registration/settings` | Module settings configuration |
| `/admin/config/event-registration/events` | Event configuration (add/view events) |
| `/admin/event-registration/list` | View all registrations with filters |
| `/admin/event-registration/export-csv` | Export registrations to CSV |

## Database Tables

### event_registration_events

Stores event configuration data.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK) | Unique event identifier |
| `registration_start_date` | VARCHAR(10) | Registration period start (YYYY-MM-DD) |
| `registration_end_date` | VARCHAR(10) | Registration period end (YYYY-MM-DD) |
| `event_date` | VARCHAR(10) | Event date (YYYY-MM-DD) |
| `event_name` | VARCHAR(255) | Name of the event |
| `category` | VARCHAR(100) | Event category |
| `created` | INT | Unix timestamp of creation |

### event_registration_registrations

Stores user registration data.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK) | Unique registration identifier |
| `full_name` | VARCHAR(255) | Registrant's full name |
| `email` | VARCHAR(255) | Registrant's email address |
| `college_name` | VARCHAR(255) | Registrant's college name |
| `department` | VARCHAR(255) | Registrant's department |
| `category` | VARCHAR(100) | Event category |
| `event_id` | INT (FK) | Foreign key to events table |
| `created` | INT | Unix timestamp of registration |

## Validation Logic

### Form Validation

1. **Required Fields**: All fields are mandatory
2. **Email Format**: Validated using Drupal's email validator
3. **Special Characters**: Text fields only allow:
   - Letters (including Unicode characters)
   - Numbers
   - Spaces
   - Hyphens (-)
   - Periods (.)
   - Underscores (_) (for event names)

### Duplicate Prevention

Registrations are checked for duplicates using:
- **Email Address** + **Event Date** combination

If a user has already registered for an event on the same date, they cannot register again.

## Email Logic

### User Confirmation Email

Sent automatically when:
- `enable_user_notification` is enabled in settings
- Registration is successful

Contains:
- Event name, date, and category
- Registrant's details (name, email, college, department)

### Admin Notification Email

Sent automatically when:
- `enable_admin_notification` is enabled in settings
- `admin_email` is configured
- Registration is successful

Contains:
- All registrant details
- Event information

## AJAX Functionality

### Registration Form

1. **Category Selection**: When a category is selected, the Event Date dropdown is populated with dates that have events in that category within the registration period.

2. **Event Date Selection**: When an event date is selected, the Event Name dropdown is populated with events matching the selected category and date.

### Admin Listing

1. **Date Filter**: Selecting a date populates the Event Name dropdown with events on that date.

2. **Filter Button**: Clicking "Filter" loads registrations for the selected event and updates the participant count.

## File Structure

```
event_registration/
├── config/
│   ├── install/
│   │   └── event_registration.settings.yml
│   └── schema/
│       └── event_registration.schema.yml
├── css/
│   └── admin.css
├── js/
│   └── admin.js
├── src/
│   ├── Controller/
│   │   ├── AdminController.php
│   │   └── AjaxController.php
│   ├── Form/
│   │   ├── EventConfigForm.php
│   │   ├── EventRegistrationForm.php
│   │   └── SettingsForm.php
│   └── Service/
│       ├── EventMailService.php
│       └── EventRegistrationService.php
├── event_registration.info.yml
├── event_registration.install
├── event_registration.libraries.yml
├── event_registration.links.menu.yml
├── event_registration.module
├── event_registration.permissions.yml
├── event_registration.routing.yml
└── event_registration.services.yml
```

## Development Notes

### Coding Standards

- Follows Drupal coding standards
- PSR-4 autoloading
- Dependency Injection (no `\Drupal::service()` in business logic)
- Uses Config API for settings

### Services

| Service ID | Class | Purpose |
|------------|-------|---------|
| `event_registration.registration_service` | EventRegistrationService | Database operations |
| `event_registration.mail_service` | EventMailService | Email handling |

### Hooks Implemented

- `hook_help()` - Module help text
- `hook_mail()` - Email templates
- `hook_theme()` - Theme definitions
- `hook_schema()` - Database schema
- `hook_install()` - Installation message
- `hook_uninstall()` - Cleanup on uninstall

## Troubleshooting

### Emails Not Sending

1. Verify email settings at `/admin/config/event-registration/settings`
2. Check Drupal's mail system configuration
3. Review recent log messages at `/admin/reports/dblog`

### AJAX Not Working

1. Clear Drupal caches: `drush cr`
2. Check browser console for JavaScript errors
3. Verify jQuery is loaded

### Permission Denied

Ensure the user has the appropriate permissions:
- `administer event registration` for admin pages
- `view event registrations` for viewing/exporting
- `register for events` for the registration form

## License

This module is provided as-is for educational purposes.

## Author

Custom Drupal 10 Module Development
