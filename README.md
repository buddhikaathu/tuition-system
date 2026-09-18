# Tuition Class Management System

A simple PHP + MySQL web app to manage students, subject enrollments (Math /
Science / English), and monthly payments — with a QR code printed on each
student's ID card for fast payment lookup.

## Features

- **3 teacher logins** (Math/Admin, Science, English) — each teacher only
  sees and manages students enrolled in their own subject. The Math teacher
  account is also the **Admin**, who can see everything and manage teacher
  accounts.
- **Student records**: name, grade (4–11), parent/guardian, contact number,
  address, enrollment date.
- **Subject enrollment per student**: Math is offered grade 4–11. Science and
  English are offered grade 10–11 only (enforced automatically in the form).
  Each subject enrollment has its own monthly fee and teacher.
- **Printable student ID card** with a QR code that encodes the student's
  unique code (e.g. `TUT-0001`).
- **Scan & Pay**: open the "Scan & Pay" page on a phone or laptop with a
  camera, scan the student's card, and instantly see their profile and this
  month's payment status per subject — then mark it paid in one click. A
  student code can also be typed in manually if there's no camera.
- **Payment history & printable receipts.**
- **Dashboard** with this month's paid/pending counts and totals.

## 1. Requirements

- PHP 8+ with the `pdo_mysql` extension (standard on almost all hosting and
  in XAMPP/WAMP/MAMP).
- MySQL or MariaDB.
- A camera-equipped phone or laptop for scanning (the QR scanner runs in the
  browser — no app needed).

## 2. Local setup (XAMPP / WAMP / MAMP)

1. Copy the whole `tuition-system` folder into your server's web root, e.g.
   `C:\xampp\htdocs\tuition-system`.
2. Start Apache and MySQL from the XAMPP control panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), click **Import**,
   and import `schema.sql`. This creates the `tuition_system` database, its
   tables, and 3 starter teacher accounts.
4. Open `config.php` and check the DB settings match your setup (XAMPP
   defaults — `localhost` / `root` / empty password — usually work as-is).
5. Visit `http://localhost/tuition-system/login.php` in your browser.

## 3. Default login accounts

All three accounts start with the password **`changeme123`** — please log
in and change these (ask the admin to reset them from **Teachers → Edit**)
before real use.

| Username  | Subject | Role  |
|-----------|---------|-------|
| `admin`   | Math    | Admin (sees everything) |
| `science` | Science | Teacher |
| `english` | English | Teacher |

## 4. Everyday workflow

1. **Add a student** (Students → Add Student). Pick their grade — eligible
   subjects appear automatically. Choose the teacher and monthly fee for
   each subject they're joining.
2. **Print their ID card** (the QR icon next to their name, or the button on
   the "Add Student" success page). Print it, laminate it if you like, and
   hand it to the student.
3. **Collect payment**: open **Scan & Pay**, point the camera at the card
   (or type the student code), see which subjects are paid/pending for the
   current month, and click **Mark Paid** for the relevant subject. A
   printable receipt is generated automatically.
4. Each teacher only sees/manages their own subject; the Math/Admin account
   can see and manage all three.

## 5. Deploying to live/shared hosting

1. Upload all files via FTP or your hosting file manager (most Sri Lankan
   shared hosts — e.g. cPanel-based — support PHP + MySQL out of the box).
2. Create a MySQL database and user from your hosting control panel, then
   import `schema.sql` through phpMyAdmin.
3. Update `config.php` with the DB name/user/password your host gave you,
   and set `BASE_URL` to your site's folder path if it's not at the domain
   root (e.g. `/tuition-system`).
4. Make sure the site is served over **HTTPS** — most mobile browsers only
   allow camera access (needed for QR scanning) on secure (https://) pages.
   Free hosting/cPanel usually includes a free SSL certificate (Let's
   Encrypt/AutoSSL) — enable it from your hosting panel.

## 6. How the QR code works (no extra libraries needed)

The QR code on the ID card is generated **in the browser** using the
`qrcode.js` library (loaded from a CDN) — it simply encodes the student's
short code (e.g. `TUT-0001`) as text. The Scan & Pay page uses the
`html5-qrcode` library (also from a CDN) to read the camera feed and decode
the code, then looks the student up over AJAX. Nothing is installed on the
server — this keeps the app lightweight and works on ordinary shared
hosting without Composer or extra PHP extensions like GD.

## 7. Notes & things you may want to customize

- Currency is shown as **Rs.** (Sri Lankan Rupees) — change `format_money()`
  in `includes/functions.php` if needed.
- Timezone is set to `Asia/Colombo` in `config.php`.
- To add a 4th subject or teacher, you'd need to widen the `ENUM` columns in
  `schema.sql` (`subject` on `teachers` and `enrollments`) — ask if you'd
  like help with that.
- This app doesn't send SMS/WhatsApp payment reminders — that would need a
  paid SMS gateway API and is a natural next step if you want it later.
