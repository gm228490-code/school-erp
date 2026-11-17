# School ERP — Professional School Management System

A complete, database-driven school management system built with **PHP 8.2 (no framework dependencies)**, **MySQL / MariaDB**, **Bootstrap 5**, **Chart.js** and vanilla JavaScript (AJAX). It is designed for Pakistani schools (PKR currency, CNIC / B-Form, Urdu with right-to-left support) and works on a local XAMPP server or any shared/cloud PHP host.

---

## 1. Features

| Area | What is included |
|---|---|
| **Dashboard** | Live counts (students, teachers, staff, classes, subjects), today's attendance (present / absent / leave), today's and monthly fee collection, pending fees, monthly expenses, recent admissions, upcoming exams, announcements, notifications, 12‑month income vs expense chart, attendance trend, students per class. |
| **Students** | Admission with automatic registration number, photo, father / mother / guardian, CNIC / B-Form, previous school, documents, class / section / roll number (auto), transport route. Sibling detection by father CNIC, duplicate prevention, capacity check. Profile with tabs (attendance, fees, results, documents, history), printable profile, ID cards (single / class), transfer, withdrawal, status management, school leaving certificate, promotion / detention / graduation, academic year closing, CSV import & Excel / CSV export, parent and student portal logins. |
| **Teachers & Staff** | Profiles, photo, CNIC, qualification, experience, joining date, department / designation, salary details, documents, assigned classes & subjects, weekly timetable, attendance, leave history, performance reviews, salary history, ID cards, system logins. |
| **Academics** | Academic sessions, classes, sections (capacity, class teacher, room), subjects, subject-teacher assignment per section, periods & breaks, class timetables with teacher-clash detection, teacher timetables, printable timetables. |
| **Attendance** | Daily student attendance by class / section (Present, Absent, Leave, Late) with remarks, teacher & staff attendance with check-in / out, monthly register with percentages, daily class-wise summary, printable sheets, Excel / CSV export, automatic absence alerts to parents. |
| **Fees** | Custom fee categories (admission, tuition, annual, exam, transport, computer, other), class-wise fee structure per session, individual student rates, discounts & scholarships (percent or fixed), monthly fee generation (duplicates skipped), manual invoices, late-payment fine (fixed or per day, with maximum), partial payments, unique invoice & receipt numbers, printable receipts and bank challans (2–3 copies), payment reversal, student fee ledger, daily / monthly / outstanding / class-wise reports, fee-due reminders. |
| **Examinations** | Exams (monthly test, midterm, final), per-class schedule with total & pass marks, date sheet, marks entry (with absent), configurable grading table, pass criteria (overall % and/or every subject), automatic percentage, grade, pass/fail and position, class result / tabulation sheet, report cards (single or whole class), publish to portal, result history. |
| **Finance** | Income & expense categories, income, expenses (with bill attachment), payroll generation (optional absent-day deduction), salary payment & slips, salary sheet, daily cash book, income & expense statement, financial dashboard. |
| **Library** | Books (ISBN, author, publisher, category, rack, copies), issue / return to students, teachers and staff, due dates, late fine (recorded as income), lost books, overdue tracking, stock and issued reports. |
| **Transport** | Vehicles (number, capacity, driver details), routes with stops and monthly fee, student route assignment with capacity warning; transport fee is added to monthly invoices automatically. |
| **Homework** | Homework / assignments per class, section and subject with attachments and deadlines, online submission by students, teacher marks & feedback, submission tracking. |
| **Communication** | Announcements (all / students / parents / teachers / staff / one class; general, exam, holiday, fee, event), in-app notifications with bell & unread count, SMS (any HTTP gateway) and email integration, message log. |
| **Parent & Student Portal** | Child switcher for parents with several children, profile, attendance calendar, published results & report-card download, invoices, challans, receipts & ledger, homework (with online submission), timetable, announcements, notifications. Every query is limited to the user's own children / own record. |
| **Reports** | Admissions, class lists, class strength, attendance, fee collection, outstanding fees, class-wise fee status, results, teacher / staff, salary, income, expense, library, transport — each printable (Save as PDF) and exportable to Excel / CSV. |
| **Settings** | School profile & logo, contact details, working days & timing, date format, default language, currency (PKR default), fee numbering prefixes & late fine, grading criteria, receipt options, ID card colour & text, library rules, notification channels, backup retention. |
| **Users & Security** | 10 built-in roles (Super Admin, School Admin, Principal, Accountant, Teacher, Receptionist, Librarian, Transport Manager, Parent, Student) + custom roles, permission matrix, teachers limited to their assigned sections/subjects. |
| **System** | One-click database backup (pure PHP, no `mysqldump` needed), download, restore (with automatic safety backup), audit log of every change / login, error log viewer, global search. |
| **UI** | Responsive (desktop / tablet / phone), light & dark mode, English & Urdu (RTL), modal AJAX forms, search, filters, sorting and pagination on every list. |

## 2. Security

- Passwords hashed with `password_hash()` (bcrypt) and automatically re-hashed when needed.
- CSRF token required on every POST (forms and AJAX).
- 100 % prepared statements (PDO, emulation disabled) — SQL-injection safe; identifiers are whitelisted.
- All output escaped (`htmlspecialchars`) — XSS safe; strict security headers and a Content-Security-Policy.
- Hardened sessions: HttpOnly + SameSite cookies, Secure flag on HTTPS, session ID regeneration on login and password change, user-agent binding, 2-hour idle timeout, secure logout (POST + cookie destruction).
- Brute-force protection: 5 failed logins (per username or IP) → 15-minute lock.
- Role-based access control on every action + record-level checks (teacher sections, parent's own children).
- File uploads: size limit, real MIME detection (`finfo`), image verification, random file names, stored outside direct web access (`uploads/` is blocked by `.htaccess`, files are served through an access-checked controller).
- Spreadsheet-formula injection protection on exports.
- Audit log (who / what / when / IP) and error log (`storage/logs/error.log`); errors never expose details to users unless `debug` is enabled.

## 3. Requirements

- PHP **8.1+** (8.2 recommended) with extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `json`, `openssl` (and `curl` for SMS).
- MySQL **5.7+** or MariaDB **10.3+**.
- Apache with `mod_rewrite` / `mod_headers` (recommended) or Nginx.
- A modern browser. No Composer, Node or internet connection is needed — Bootstrap, icons and Chart.js are bundled in `assets/vendor`.

## 4. Installation

### A. Local computer (XAMPP — Windows)

1. Copy the project folder into `C:\xampp\htdocs\` and rename it, e.g. `C:\xampp\htdocs\school`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open **http://localhost/school/install/** in your browser.
4. The installer checks the server, then asks for:
   - Database: host `localhost`, port `3306`, a database name (created automatically, e.g. `school_erp`), user `root`, password (blank on default XAMPP).
   - School name, phone, address and timezone (`Asia/Karachi`).
   - **Super Admin** full name, username, email and a strong password (8+ characters with upper-case, lower-case and a number).
   - Optional *Load sample data* — tick only for a demo / training copy.
5. Click **Install now**, then **Open the application** and sign in with the Super Admin account.

### B. Cloud / shared hosting (cPanel, Plesk, VPS)

1. Create a MySQL database and user in your hosting panel and give the user **all privileges** on that database.
2. Upload all files to `public_html/` (or a sub-folder / sub-domain) and keep the folder structure.
3. Make these folders writable by PHP (usually `755`, or `775` if required): `config/`, `uploads/`, `storage/`, `storage/logs/`, `storage/backups/`.
4. Visit `https://your-domain/install/` and complete the wizard with the database details from step 1.
5. After installation:
   - Enable **HTTPS** (Let's Encrypt / AutoSSL). The session cookie is marked *Secure* automatically on HTTPS.
   - Delete or rename the `install/` folder (it is already locked once `config/config.php` exists).
   - Confirm that `https://your-domain/config/config.php`, `/storage/` and `/uploads/` return **403 Forbidden**.

**Nginx:** use `index.php` as the index and deny the internal folders:

```nginx
location ~ ^/(app|config|storage|database|uploads)/ { deny all; }
location ~ /\.(?!well-known) { deny all; }
location / { try_files $uri $uri/ /index.php?$query_string; }
```

### C. Manual database setup (optional)

The installer runs everything automatically. If your host does not allow PHP to create databases, create the empty database in the hosting panel first (or import `database/schema.sql` with phpMyAdmin), then run the installer with that database name. It skips existing tables, seeds roles, permissions and settings, and creates the Super Admin. `config/config.example.php` shows the format of the generated `config/config.php`.

To load the sample data on an already-installed copy that has **no students yet**:

```bash
php database/demo_seed.php
```

## 5. First-time setup checklist

1. **Settings → School Profile**: logo, address, phone, principal name.
2. **Academics → Sessions**: verify the current session (created automatically, April–March).
3. **Academics → Classes** (set *Order*, used for promotion) → **Sections** → **Subjects** → **Assign Subjects** (teacher per subject & section).
4. **Human Resource → Teachers / Staff** (tick *Create login account* to give them access).
5. **Fees → Fee Categories** (built-in ones exist) → **Fee Structure** (amounts per class) → **Discounts** as needed.
6. **Academics → Periods** → **Timetable**.
7. **Students → New Admission** (or **Import** from Excel/CSV).
8. **Fees → Generate Fees** every month → **Collect Fee**.
9. **Settings → Notifications**: enable SMS/email if you have a gateway.
10. **Administration → Backup**: create a backup regularly and download it to a safe place.

### Sample data logins (only if *Load sample data* was ticked)

| Role | Username | Password |
|---|---|---|
| Teacher | `teacher1` … `teacher8` | `Teacher@123` |
| Accountant / Receptionist / Librarian | `accountant` / `receptionist` / `librarian` | `Staff@123` |
| Parent portal | `parent` | `Parent@123` |
| Student portal | `student` | `Student@123` |

Change or delete these accounts before using the system with real data.

## 6. PDF & Excel

- Every printable page (receipts, challans, report cards, result sheets, ID cards, registers, reports, salary slips, certificates) opens in a print layout with **Print / Save as PDF** — choose *Save as PDF* in the browser's print dialog. This produces exact, selectable-text PDFs without any server library.
- Lists and reports offer **Excel** (`.xls`, opens directly in Microsoft Excel / LibreOffice) and **CSV** (UTF-8, Urdu-safe) downloads.

## 7. SMS and email

- **Email:** enable in *Settings → Notifications*. Uses PHP `mail()`; on cPanel this works out of the box. On Windows/XAMPP configure `sendmail` / SMTP in `php.ini`.
- **SMS:** enter your gateway's HTTP URL with `{to}` and `{message}` placeholders, e.g. `https://api.yourprovider.pk/send?key=XXXX&to={to}&msg={message}`. Use *Send test* to verify. Every message is recorded in *Communication → Message Log*.

## 8. Backups

- *Administration → Backup & Restore → Create Backup Now* writes a full SQL dump to `storage/backups/` (the newest *N* are kept — see Settings). Download copies off-site.
- Restore (Super Admin only) from a saved file or an uploaded `.sql`; a safety backup is taken automatically first.
- Also back up the `uploads/` folder (photos and documents) with your hosting file backup.
- Optional automatic daily backup via cron: `0 2 * * * php /path/to/school/database/backup_cli.php`.

## 9. Project structure

```
index.php               Front controller (routes: index.php?r=module/action)
install/                Installation wizard (locks itself after install)
app/bootstrap.php       Config, secure session, headers, autoload
app/core/               DB (PDO), Auth/RBAC, helpers, validation, CRUD engine, form builder, services (fees, results, notices, accounts)
app/controllers/        One controller per module
app/resources/          Definitions for list/form modules (classes, books, expenses, users …)
app/views/              Layouts, pages and print templates
app/permissions.php     Permission catalog and default role grants
app/menu.php            Sidebar navigation
database/schema.sql     Full normalized schema (50+ tables, FKs, indexes)
database/demo_seed.php  Optional sample data
lang/ur.php             Urdu translations
assets/                 CSS, JS, bundled Bootstrap / icons / Chart.js
uploads/                Uploaded files (not web-accessible)
storage/logs, backups   Error log and database backups (not web-accessible)
config/config.php       Generated by the installer (database credentials)
```

## 10. Troubleshooting

| Problem | Fix |
|---|---|
| Installer says a folder is not writable | Give PHP write permission to `config/`, `uploads/`, `storage/`. |
| "A database error occurred" | Check `storage/logs/error.log` (or *Administration → Error Log*). |
| Blank page / need details while developing | Set `'debug' => true` in `config/config.php` (turn off in production). |
| Locked out after failed logins | Wait 15 minutes; or delete rows in the `login_attempts` table. |
| Forgot Super Admin password | In phpMyAdmin run: `UPDATE users SET password='$2y$10$…' WHERE username='admin';` using a hash produced by `php -r "echo password_hash('NewPass@123', PASSWORD_DEFAULT);"`. |
| Uploaded logo/photo not showing | Ensure `uploads/` is writable and the `fileinfo` extension is enabled. |
