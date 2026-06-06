# ClinicDesk — Clinic Management Dashboard

A private, login-protected clinic management system built with PHP and MySQL.

## Project Info

- **Course:** SDEV 2106 / WDMM 2010 / MOBC 2102
- **Semester:** 2, 2025–2026
- **Instructor:** Eng. Mohammed Zuqlam

## Tech Stack

- PHP 8.x (OOP, MVC pattern)
- MySQL (InnoDB, Foreign Keys)
- AdminLTE 3 (UI Template)
- No external frameworks

## Features

- Role-based access: Admin, Doctor, Patient
- Session-based authentication with CSRF protection
- Appointment booking with conflict detection
- Prescription management with secure PDF upload
- Admin reports with CSV export
- Pagination, search, and filtering

## Roles & Credentials (Default)

| Role  | Email                  | Password |
| ----- | ---------------------- | -------- |
| Admin | admin@clinic.local | Admin@1234 |

> Admin creates Doctor and Patient accounts from the Users panel.

## Installation

1. Clone the repository
2. Create MySQL database: `clinicdesk_db`
3. Import: `database/clinicdesk_db.sql`
4. Copy config: `cp config/database.example.php config/database.php`
5. Edit `config/database.php` with your DB credentials
6. Edit `config/config.php` — set `BASE_URL`
7. Download AdminLTE 3.x → extract to `public/assets/adminlte/`
8. Visit: `http://localhost/clinicdesk`

## Project Structure
