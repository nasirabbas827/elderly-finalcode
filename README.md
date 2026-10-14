# Elderly Care Home Management System

elderly-finalcode is an elderly-care management application.

![PHP](https://img.shields.io/badge/PHP-2563EB?style=flat-square) ![MySQL](https://img.shields.io/badge/MySQL-0F172A?style=flat-square)

## Overview

The data model covers `admins`, `appointments`, `daily_activities`, `donations`, `elderly_members`, `emergency_reports`, `medical_records`, `staff`, `users`.

## Application areas

| Area | Pages in the repository |
| --- | --- |
| Admin | [`admin/add_user.php`](admin/add_user.php), [`admin/admin_home.php`](admin/admin_home.php), [`admin/admin_login.php`](admin/admin_login.php), [`admin/admin_navbar.php`](admin/admin_navbar.php), [`admin/approve_user.php`](admin/approve_user.php) |
| Donor | [`donor/donor_dashboard.php`](donor/donor_dashboard.php), [`donor/download_receipt.php`](donor/download_receipt.php), [`donor/manage_donations.php`](donor/manage_donations.php), [`donor/update_profile.php`](donor/update_profile.php) |
| Family | [`family/appointments.php`](family/appointments.php), [`family/family_dashboard.php`](family/family_dashboard.php), [`family/manage_elderly_members.php`](family/manage_elderly_members.php), [`family/member_report.php`](family/member_report.php), [`family/update_profile.php`](family/update_profile.php) |
| Root Pages | [`login.php`](login.php), [`register.php`](register.php) |
| Staff | [`staff/emergency_reports.php`](staff/emergency_reports.php), [`staff/manage_daily_activities.php`](staff/manage_daily_activities.php), [`staff/manage_elderly_members.php`](staff/manage_elderly_members.php), [`staff/manage_medical_records.php`](staff/manage_medical_records.php), [`staff/staff_dashboard.php`](staff/staff_dashboard.php) |

## Getting started

### 1. Get the project

```bash
git clone https://github.com/nasirabbas827/elderly-finalcode.git
cd elderly-finalcode
```

### 2. Configure PHP and the database

1. Use a local PHP server with MySQL or MariaDB, such as XAMPP.
2. Import [`Database/eldry_db.sql`](Database/eldry_db.sql) into your local database server.
3. Set your local database connection details in [`admin/config.php`](admin/config.php), [`config.php`](config.php), [`donor/config.php`](donor/config.php), [`family/config.php`](family/config.php), [`staff/config.php`](staff/config.php).
4. Keep the existing asset and upload directory paths intact.

### 3. Start the application

With the database running, start a local development server from the repository root:

```bash
php -S 127.0.0.1:8000
```

Open http://127.0.0.1:8000/index.php. If using Apache/XAMPP instead, place the project in its document root and open the matching localhost folder.

## Repository guide

| Path | Purpose |
| --- | --- |
| [`Database`](Database) | Database scripts |
| [`config.php`](config.php) | Application/database configuration |
| [`css`](css) | Stylesheets |
| [`images`](images) | Image assets |
| [`index.php`](index.php) | Web entry page |

## Development notes

These setup instructions are based on the repository source. The application has not been executed as part of this documentation update; verify dependencies and configuration in your local environment.
