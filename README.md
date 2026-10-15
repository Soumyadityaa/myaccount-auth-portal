# Secure User Authentication and Personalized Dashboard System

A complete, responsive full-stack web application built with HTML5, CSS3, JavaScript, PHP (PDO), and MySQL. This project allows users to register accounts, sign in securely using their username or email address, and view a personalized dashboard displaying their private account information.

---

## Project Overview

This system demonstrates how to build a multi-user authentication workflow without relying on frontend-only checks. All credential verification, session handling, and access control rules are enforced on the server using PHP and MySQL.

When different users sign in, they access the same `dashboard.php` file, but the application dynamically queries and displays only the data belonging to the currently authenticated user's session.

---

## Key Features

- **User Registration:** Validates full name, username, email format, and password strength on both the client and server. Ensures usernames and email addresses are unique before creating an account.
- **Flexible Login:** Allows users to log in using either their registered username or email address.
- **Personalized Dashboard:** Displays user-specific profile details (Account ID, Full Name, Username, Email Address, and Registration Date) strictly bound to the active server session.
- **Secure Logout:** Terminates sessions through a CSRF-protected POST request, clears session cookies, and prevents unauthorized back-button access.
- **Responsive User Interface:** Adapts cleanly across mobile phones, tablets, and desktop screens with accessible labels, focus states, and a password visibility toggle.

---

## Security Practices Implemented

- **Password Hashing:** Uses PHP's `password_hash()` (bcrypt) for storage and `password_verify()` for authentication. Plain-text passwords are never stored.
- **SQL Injection Prevention:** Uses PHP Data Objects (PDO) with native prepared statements (`ATTR_EMULATE_PREPARES => false`).
- **Session Hardening:** Enforces `HttpOnly` and `SameSite=Strict` cookies, strict session mode, and regenerates session IDs (`session_regenerate_id(true)`) upon login to prevent session fixation.
- **Cross-Site Request Forgery (CSRF) Protection:** Validates cryptographic CSRF tokens on all POST forms (registration, login, and logout).
- **Brute-Force Rate Limiting:** Locks login attempts temporarily for 15 minutes after 5 consecutive failed sign-in attempts.
- **Cross-Site Scripting (XSS) Prevention:** Escapes all dynamic outputs using `htmlspecialchars()`.
- **Access Control:** Restricts dashboard data strictly to `$_SESSION['user_id']` rather than URL query parameters or hidden form fields.

---

## Technology Stack

- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Backend:** PHP 8.x (PDO Extension)
- **Database:** MySQL (InnoDB Storage Engine)
- **Local Server Environment:** XAMPP (Apache + MySQL + phpMyAdmin)

---

## Project File Structure

```text
myaccount/
├── index.php       # Entry router redirecting users based on authentication status
├── db.php          # Database connection, session configuration, and security helpers
├── register.php    # Account registration form and server-side validation logic
├── login.php       # User login controller with rate limiting and session creation
├── dashboard.php   # Protected, session-driven personalized user dashboard
├── logout.php      # POST-only session termination handler
├── style.css       # Responsive stylesheet for mobile, tablet, and desktop views
├── script.js       # Client-side validation and password show/hide toggle
└── README.md       # Project documentation
```

---

## Database Schema

The application uses a MySQL database named `user_system` containing a single `users` table:

| Column | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Unique identifier for each user |
| `name` | `VARCHAR(100)` | `NOT NULL` | User's full name |
| `username` | `VARCHAR(50)` | `NOT NULL`, `UNIQUE` | Unique account username |
| `email` | `VARCHAR(150)` | `NOT NULL`, `UNIQUE` | Unique email address |
| `password` | `VARCHAR(255)` | `NOT NULL` | Bcrypt password hash |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | Date and time of registration |

---

## Local Installation and Setup Guide (XAMPP)

### 1. Prerequisites
Download and install [XAMPP](https://www.apachefriends.org/) on your computer.

### 2. Start Apache and MySQL
Open the **XAMPP Control Panel** and click **Start** next to both **Apache** and **MySQL**. Wait until both status indicators turn green.

### 3. Clone or Copy the Project
Place the project folder inside your XAMPP `htdocs` directory:
- **Windows:** `C:\xampp\htdocs\myaccount\`
- **macOS:** `/Applications/XAMPP/htdocs/myaccount/`

Or clone the repository directly into `htdocs`:
```bash
cd C:/xampp/htdocs
git clone [https://github.com/your-username/php-mysql-auth-dashboard.git](https://github.com/your-username/php-mysql-auth-dashboard.git) myaccount
```

### 4. Create the MySQL Database
1. Open your browser and go to `http://localhost/phpmyadmin/`.
2. Click the **SQL** tab at the top of the page.
3. Paste the following SQL script and click **Go**:

```sql
CREATE DATABASE IF NOT EXISTS user_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE user_system;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. Configure Database Credentials
Open `db.php` and verify the database settings match your local MySQL configuration (default XAMPP credentials are pre-configured):

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'user_system');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 6. Run the Application
Open your web browser and navigate to:
```text
http://localhost/myaccount/
```

---

## Verified Test Scenarios

All 14 core functional and security test cases have been verified:

1. **Valid Registration:** Creates a new user record and redirects to the login page with a confirmation message.
2. **Duplicate Username Prevention:** Blocks registration if the chosen username already exists.
3. **Duplicate Email Prevention:** Blocks registration if the email address is already registered.
4. **Input Validation:** Rejects invalid email formats and mismatched password confirmations.
5. **Password Strength Enforcement:** Requires passwords to be at least 8 characters long.
6. **Username Login:** Authenticates valid username and password combinations.
7. **Email Login:** Authenticates valid email and password combinations.
8. **Invalid Login & Rate Limiting:** Displays a generic error message on failure and locks login for 15 minutes after 5 failed attempts.
9. **User A Dashboard Isolation:** Displays only User A's profile details when User A is logged in.
10. **User B Dashboard Isolation:** Displays only User B's profile details when User B is logged in, ignoring any manual URL parameters.
11. **Unauthenticated Route Protection:** Redirects unauthenticated visitors from `dashboard.php` to `login.php`.
12. **Secure Logout:** Destroys the session via POST and prevents access to the dashboard until the user signs in again.
13. **Password Hash Storage:** Confirms all passwords in `users.password` are stored as `$2y$` bcrypt hashes.
14. **Responsive Layout:** Renders cleanly across mobile (`375px`) and desktop (`1280px+`) viewports.

---

## License

This project is open-source and available for educational and portfolio use.
