# LUXE STORE — PHP/MySQL Online Store

A full-stack dynamic e-commerce website built with PHP (backend), MySQL (database), featuring user authentication, a customer-facing shop, and a full admin panel.

---

## Tech Stack

| Layer      | Technology                          |
|------------|-------------------------------------|
| Backend    | PHP 8.1+ (PDO, OOP, Sessions)       |
| Database   | MySQL 8.0+ / MariaDB 10.6+          |
| Frontend   | HTML5, CSS3 (custom), Vanilla JS    |
| Security   | CSRF tokens, bcrypt, prepared stmts |
| Web Server | Apache (with mod_rewrite)           |

---

## Project Structure

```
store/
├── index.php                  # Homepage / Shop
├── database.sql               # Full DB schema + seed data
├── .htaccess                  # Apache security rules
│
├── includes/
│   ├── config.php             # App config, session init
│   ├── db.php                 # PDO singleton connection
│   ├── auth.php               # Auth helpers (register/login/CSRF)
│   ├── header.php             # Public site header/nav
│   └── footer.php             # Public site footer
│
├── pages/
│   ├── login.php              # Login form
│   ├── register.php           # Registration form
│   ├── logout.php             # Logout handler
│   └── account.php            # Customer account dashboard
│
├── admin/
│   ├── index.php              # Admin dashboard (stats)
│   ├── users.php              # User management (CRUD)
│   ├── products.php           # Product management (CRUD)
│   ├── categories.php         # Category management (CRUD)
│   ├── orders.php             # Order management + status updates
│   ├── header.php             # Admin header/sidebar
│   └── footer.php             # Admin footer
│
└── assets/
    ├── css/
    │   ├── style.css          # Main stylesheet
    │   └── admin.css          # Admin-specific styles
    └── js/
        └── main.js            # Client-side interactions
```

---

## Database Schema

### Tables

**users** — Registered accounts (admin & customer roles)  
**categories** — Product categories  
**products** → FK → categories (cascade delete)  
**orders** → FK → users (cascade delete)  
**order_items** → FK → orders + products

### Relationships
- `products.category_id` → `categories.id`
- `orders.user_id` → `users.id`
- `order_items.order_id` → `orders.id`
- `order_items.product_id` → `products.id`

---

## Setup Instructions

### 1. Prerequisites
- PHP 8.1+
- MySQL 8.0+ or MariaDB 10.6+
- Apache with `mod_rewrite` enabled
- A local server like XAMPP, WAMP, MAMP, or Laragon

### 2. Place Files
Copy the `store/` folder into your web server root:
- XAMPP: `C:/xampp/htdocs/store/`
- MAMP:  `/Applications/MAMP/htdocs/store/`
- Linux: `/var/www/html/store/`

### 3. Create the Database
Open phpMyAdmin or your MySQL client and run:
```sql
SOURCE /path/to/store/database.sql;
```
Or copy-paste the contents of `database.sql` into your SQL client.

### 4. Configure Database Connection
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'luxe_store');
define('DB_USER', 'root');       // Your MySQL username
define('DB_PASS', '');           // Your MySQL password
define('SITE_URL', 'http://localhost/store');  // Adjust if needed
```

### 5. Visit the Site
- **Store:** http://localhost/store/
- **Admin:** http://localhost/store/admin/

---

## Demo Credentials

| Role     | Username | Password  |
|----------|----------|-----------|
| Admin    | admin    | Admin@123 |
| Customer | johndoe  | User@123  |

---

## Features

### Public Store
- Browse all products with category filtering
- Full-text product search
- Responsive dark luxury design

### Authentication System
- Secure registration with validation
- Password hashing (bcrypt, cost 12)
- Login with username or email
- CSRF protection on all forms
- Session regeneration on login
- Role-based access control (admin/customer)

### Customer Account
- Edit profile (name, email)
- Change password with strength indicator
- View order history

### Admin Panel
**Dashboard**
- Live stats: users, products, orders, revenue
- Recent orders & user lists

**User Management**
- View, search, and filter all users
- Add new users with any role
- Toggle active/inactive status
- Change user roles (customer ↔ admin)
- Delete users

**Product Management**
- Add, edit, delete products
- Assign to categories
- Set price, stock, featured flag
- Toggle active/hidden visibility

**Category Management**
- Add, edit, delete categories
- Auto-generated URL slugs
- Delete protection when category has products

**Order Management**
- View all orders with filters
- Update order status (pending → processing → shipped → delivered → cancelled)
- View order details & line items
- Delete orders

---

## Security Features

| Feature            | Implementation                          |
|--------------------|-----------------------------------------|
| SQL Injection      | PDO prepared statements throughout      |
| XSS               | `htmlspecialchars()` on all output      |
| CSRF              | Token per session, verified on POST     |
| Password Storage  | bcrypt with cost 12                     |
| Session Fixation  | `session_regenerate_id(true)` on login  |
| Directory Listing | `Options -Indexes` in .htaccess         |
| Sensitive Files   | .htaccess blocks direct PHP access      |
| Admin Protection  | `require_admin()` on every admin page   |

---

## Password Requirements
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 number

---

## Extending the Project

**Add cart & checkout:**
Create a `cart` session array and a `checkout.php` page that creates orders.

**Add image uploads:**
Use `move_uploaded_file()` and store paths in `products.image`.

**Add email notifications:**
Use PHPMailer (`composer require phpmailer/phpmailer`) for order confirmations.

**Add pagination:**
Use `LIMIT ? OFFSET ?` in SQL queries and pass page numbers via GET.

**Production hardening:**
- Set `secure: true` in session cookie params (requires HTTPS)
- Move `config.php` above webroot
- Use environment variables for credentials
- Add rate limiting on login attempts
