JAZEERA HOSPITAL MANAGEMENT SYSTEM — XAMPP

REQUIREMENTS
- XAMPP with Apache, PHP 8.0+ and MySQL/MariaDB
- Modern web browser
- Internet access for Bootstrap and Font Awesome CDN assets

INSTALLATION
1. Copy the backend, frontend and jazeera_hospital folders into C:\xampp\htdocs\
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Import backend/jazeera_hospital.sql using phpMyAdmin.
4. Check backend/config.php and set the MySQL host, database, username and password.
5. Open Command Prompt or PowerShell and run:
   C:\xampp\php\php.exe C:\xampp\htdocs\backend\initialize_admin.php
6. Follow the prompts to create the first Super Admin. Use a unique email and a password of at least 12 characters.
   The command also upgrades an existing database and disables known demo-password accounts.
7. Sign in at http://localhost/jazeera_hospital/login.php

SECURITY AND ROLES
- Public visitors may register at register.php only as Demo Viewer. Demo accounts see fictional sample data and cannot access real patient records, staff accounts, or write actions.
- Public registration cannot create staff roles. Create the first Super Admin from the local CLI command above.
- Only the Super Admin can create, edit, deactivate or delete staff accounts.
- Staff accounts have unique email addresses, password hashes, and an active/deactivated state.
- Roles: Super Admin, Admin, Doctor, Nurse, Receptionist, Pharmacist and Demo Viewer.
- Doctors and Nurses can access clinical patient history; Receptionists only see basic patient details.
- Each role is restricted on the server to its permitted pages and actions.
- Sessions use strict mode, HttpOnly/SameSite cookies, session ID rotation, version-based revocation and CSRF checks.
- The backend directory denies direct HTTP access.
- The interface defaults to English; use SO/EN to switch languages.

PROJECT STRUCTURE
- frontend/assets/ contains styles, scripts and the hospital logo.
- backend/ contains database configuration, schema and the CLI-only Super Admin initializer.
- jazeera_hospital/ contains the PHP pages served by Apache.

IMPORTANT
This is a starter system, not a certified clinical or production deployment. Before real patient use,
configure HTTPS, database least-privilege credentials, backups, audit logs, privacy/consent controls,
and clinical workflows. Protect patient data and never use demo credentials in production.
