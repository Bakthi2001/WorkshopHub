# SLPI Workshop Hub — MySQL + PHP Backend

## Project Structure

```
slpi_db/
├── schema.sql                  ← Run this first to create all tables + seed data
├── db_config.php               ← Database credentials (shared by all APIs)
├── README.md                   ← This file
│
├── api/
│   ├── public/
│   │   └── data.php            ← Public read-only data (workshops, trainers, etc.)
│   │
│   ├── user/
│   │   ├── auth.php            ← Signup / login / profile (public users)
│   │   └── enroll.php          ← Workshop enrollment + notifications
│   │
│   └── admin/
│       ├── auth.php            ← Admin login / logout / session check
│       ├── workshops.php       ← Workshop CRUD (admin1, admin2, super)
│       ├── enrollments.php     ← Enrollment management + CSV export
│       └── email_log.php       ← Rejection email log viewer
```

---

## Database Tables

| Table                | Used By Page(s)                             |
|----------------------|---------------------------------------------|
| `admins`             | admin1-login, admin2-login, super-login      |
| `admin_sessions`     | All admin panels (session management)        |
| `users`              | login.html, signup.html, profile.html        |
| `trainers`           | trainers.html, workshops (FK)                |
| `workshops`          | index.html, admin1, admin2, super-admin      |
| `enrollments`        | admin1, admin2, super-admin, profile.html    |
| `attendance`         | profile.html stats                           |
| `email_log`          | admin1, admin2, super-admin email log        |
| `user_notifications` | profile.html notifications tab               |
| `testimonials`       | index.html feedback section                  |
| `resources`          | resources.html                               |
| `upcoming_events`    | upcoming.html                                |

---

## Setup Instructions

### 1. Create the database

```bash
mysql -u root -p < schema.sql
```

Or in MySQL Workbench / phpMyAdmin, paste and run `schema.sql`.

### 2. Create a MySQL user (recommended)

```sql
CREATE USER 'slpi_user'@'localhost' IDENTIFIED BY 'yourpassword';
GRANT ALL PRIVILEGES ON slpi_db.* TO 'slpi_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Edit db_config.php

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'slpi_user');
define('DB_PASS', 'yourpassword');
define('DB_NAME', 'slpi_db');
```

### 4. Hash the admin passwords

Run this once to generate bcrypt hashes, then update the `admins` table:

```php
<?php
echo password_hash('Admin1@2025', PASSWORD_BCRYPT, ['cost' => 12]);  // admin1
echo password_hash('Admin2@2025', PASSWORD_BCRYPT, ['cost' => 12]);  // admin2
echo password_hash('Super@2025',  PASSWORD_BCRYPT, ['cost' => 12]);  // super
echo password_hash('Demo1234',    PASSWORD_BCRYPT, ['cost' => 12]);  // demo user
```

Then:

```sql
UPDATE admins SET password='<hash>' WHERE username='admin1';
UPDATE admins SET password='<hash>' WHERE username='admin2';
UPDATE admins SET password='<hash>' WHERE username='super';
UPDATE users  SET password='<hash>' WHERE email='demo@example.com';
```

### 5. Place files on your server

Put the project folder on a PHP server (XAMPP, WAMP, Apache, Nginx + php-fpm).
Your HTML files stay as-is; they call the APIs via `fetch()`.

---

## API Reference

### Public APIs (`api/public/data.php`)

| Method | URL | Description |
|--------|-----|-------------|
| GET | `?resource=workshops` | All active workshops |
| GET | `?resource=workshops&category=Digital` | Filter by category |
| GET | `?resource=workshops&search=ethics` | Search workshops |
| GET | `?resource=workshop&slug=digital-journalism` | Single workshop |
| GET | `?resource=trainers` | All trainers |
| GET | `?resource=resources` | All resources |
| GET | `?resource=upcoming` | All upcoming events |
| GET | `?resource=testimonials` | Approved testimonials |
| POST | `?resource=testimonials` | Submit feedback (body: `{name, role, message}`) |

### User APIs (`api/user/`)

| Method | URL | Description |
|--------|-----|-------------|
| POST | `auth.php?action=signup` | Create account |
| POST | `auth.php?action=login` | Sign in |
| GET | `auth.php?action=profile` | Get profile + stats + enrollments |
| PUT | `auth.php?action=profile` | Update profile / password |
| POST | `auth.php?action=logout` | Sign out |
| POST | `enroll.php?action=enroll` | Enroll in workshop (body: `{workshop_id}`) |
| GET | `enroll.php?action=my_enrollments` | My enrollments |
| POST | `enroll.php?action=mark_read&notif_id=X` | Mark notification read |
| POST | `enroll.php?action=mark_all_read` | Mark all notifications read |

### Admin APIs (`api/admin/`)

#### auth.php
| Method | URL | Description |
|--------|-----|-------------|
| POST | `auth.php?action=login` | Admin login (body: `{username, password}`) |
| POST | `auth.php?action=logout` | Admin logout |
| GET | `auth.php?action=check` | Check session |

#### workshops.php
| Method | URL | Description |
|--------|-----|-------------|
| GET | `workshops.php?action=list` | List own workshops |
| GET | `workshops.php?action=list&owner=admin1` | Super: filter by owner |
| POST | `workshops.php?action=create` | Create workshop |
| PUT | `workshops.php?action=update&id=X` | Edit workshop |
| DELETE | `workshops.php?action=delete&id=X` | Delete workshop |

#### enrollments.php
| Method | URL | Description |
|--------|-----|-------------|
| GET | `enrollments.php?action=list` | List enrollments |
| GET | `enrollments.php?action=list&workshop_id=X` | Filter by workshop |
| GET | `enrollments.php?action=list&search=Amal` | Search enrollments |
| POST | `enrollments.php?action=toggle_select&id=X` | Select / deselect |
| DELETE | `enrollments.php?action=remove&id=X` | Remove + send rejection email |
| GET | `enrollments.php?action=export` | Download CSV |
| GET | `enrollments.php?action=export&selected_only=1` | Selected CSV |
| GET | `enrollments.php?action=export&workshop_id=X` | Per-workshop CSV |

#### email_log.php
| Method | URL | Description |
|--------|-----|-------------|
| GET | `email_log.php?action=list` | All rejection emails |
| GET | `email_log.php?action=get&id=X` | Single email with body |

---

## How to Connect Your HTML Pages

Replace `localStorage` calls in `admin.js` and `main.js` with `fetch()` calls.

### Example — Admin Login (`admin1-login.html`)

```javascript
document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const res = await fetch('api/admin/auth.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      username: document.getElementById('username').value,
      password: document.getElementById('password').value,
    }),
    credentials: 'include',
  });
  const data = await res.json();
  if (data.success) {
    window.location.href = 'admin1.html';
  } else {
    // show error
  }
});
```

### Example — Load Workshops (`index.html`)

```javascript
async function loadWorkshops(category = 'All', search = '') {
  const url = `api/public/data.php?resource=workshops&category=${category}&search=${encodeURIComponent(search)}`;
  const res  = await fetch(url);
  const data = await res.json();
  renderWorkshopsGrid(data.workshops);
}
```

### Example — Enroll (`login.html` after sign-in)

```javascript
async function enrollInWorkshop(workshopId) {
  const res = await fetch('api/user/enroll.php?action=enroll', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ workshop_id: workshopId }),
    credentials: 'include',
  });
  const data = await res.json();
  if (data.success) alert('Enrolled successfully!');
}
```

---

## Security Notes

- All passwords stored as **bcrypt** hashes (cost 12).
- Admin sessions stored server-side with `HttpOnly` cookies.
- All user input bound with **prepared statements** — no SQL injection.
- Public user sessions use PHP `$_SESSION`.
- For production: enable HTTPS and set `'secure' => true` on cookies.
