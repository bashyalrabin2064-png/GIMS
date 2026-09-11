# GIMS - Code Architecture & Technical Reference

---

## Framework & Technology Decisions

GIMS uses no framework intentionally. It is plain PHP with PDO, Vanilla JavaScript, and Bootstrap 5 for styling. This keeps the codebase beginner-friendly, fast to load, and easy to host on any basic XAMPP/LAMP setup with zero build steps.

| Layer | Technology | Why |
|---|---|---|
| Backend | PHP 8+ | Runs on any shared hosting, no setup needed |
| Database | MySQL via PDO | Prepared statements prevent SQL injection |
| Frontend | Bootstrap 5 + Vanilla JS | No bundler, no build step, just open the browser |
| Charts | Chart.js (CDN) | Lightweight, beautiful, zero config |
| Icons | Bootstrap Icons (CDN) | Consistent icon set, free |

---

## Folder Architecture

```text
project/
|
+-- php/                   All user-facing PHP pages
|   +-- index.php          Login page + first-run admin setup
|   +-- dashboard.php      Home screen with stats and charts
|   +-- products.php       Product list, add, edit, delete + unit assignment
|   +-- categories.php     Category management (admin only)
|   +-- suppliers.php      Supplier management
|   +-- purchases.php      Record stock-in (buying inventory with units)
|   +-- sales.php          Record stock-out (selling with units) + voiding
|   +-- reports.php        Revenue reports and charts
|   +-- users.php          User account management (admin only)
|   +-- profile.php        Edit profile + admin danger zone
|   +-- logout.php         Destroys session and redirects to login
|   |
|   +-- db.php             Database connection + auto-migration + format_quantity()
|   +-- auth_check.php     Session guard + CSRF helpers
|   +-- header.php         HTML head, navbar, flash messages
|   +-- footer.php         Closing HTML, Bootstrap JS, app.js
|   +-- setup.php          Runs SQL schema from the sql/ folder
|
+-- api/
|   +-- stock.php          JSON endpoint: returns live stock qty + unit of measurement
|
+-- js/
|   +-- app.js             All client-side logic (forms, charts, search, unit labels)
|
+-- css/
|   +-- style.css          Custom modern styles on top of Bootstrap
|
+-- sql/
|   +-- schema.sql         Table definitions (CREATE TABLE statements)
|   +-- seed.sql           Sample data for testing with realistic units
|
+-- readme/                Documentation (English, Nepali, Architecture)
```

---

## Request Lifecycle (How a Page Loads)

```text
Browser visits /php/sales.php
        |
        v
auth_check.php checks $_SESSION['user_id']
        |
        +-- Not logged in --> redirect to index.php
        |
        +-- Logged in --> continue
        |
        v
db.php creates PDO connection (singleton)
auto_migrate_db() checks and adds any missing columns (sku, cost_price, min_quantity, unit, etc.)
        |
        v
sales.php handles POST (if form submitted)
  - verify_csrf() checks CSRF token
  - validates input (product, quantity, price)
  - runs SQL inside a database transaction
  - sets $formSuccess or $formError
        |
        v
sales.php runs SELECT queries to build page data
        |
        v
header.php outputs: DOCTYPE, CSS links, navbar, flash alerts
        |
        v
sales.php outputs: page HTML (stats, table with units, modals)
        |
        v
footer.php outputs: Bootstrap JS, app.js script tag
        |
        v
Browser renders the page
app.js runs: attaches event listeners, unit labels, live stock check, init charts
```

---

## Database Schema

```text
+------------------+       +------------------+
|     users        |       |   categories     |
+------------------+       +------------------+
| id (PK)          |       | id (PK)          |
| full_name        |       | name             |
| email            |       | created_at       |
| phone            |       +--------+---------+
| designation      |                |
| bio              |                | FK: category_id
| password (bcrypt)|                |
| role             |       +--------v---------+
| is_locked        |       |     products     |
| created_at       |       +------------------+
+--------+---------+       | id (PK)          |
         |                 | name             |
         | FK: sold_by     | sku              |
         | FK: purchased_by| category_id (FK) |
         |                 | supplier_id (FK) |
+--------v---------+       | unit             |
|      sales       |       | cost_price       |
+------------------+       | sell_price       |
| id (PK)          |       | quantity         |
| product_id (FK)  |       | min_quantity     |
| quantity         |       | created_at       |
| unit_price       |       +--------+---------+
| sold_by (FK)     |                |
| sale_date        |       +--------v---------+
| voided_at        |       +------------------+
| voided_by (FK)   |       |    suppliers     |
+------------------+       +------------------+
                           | id (PK)          |
+------------------+       | name             |
|    purchases     |       | phone            |
+------------------+       | email            |
| id (PK)          |       | address          |
| product_id (FK)  +-------+ created_at       |
| supplier_id (FK) |       +------------------+
| quantity         |
| unit_cost        |       +------------------+
| purchased_by(FK) |       | stock_adjustments|
| purchase_date    |       +------------------+
+------------------+       | id (PK)          |
                           | product_id (FK)  |
                           | quantity         |
                           | reason (ENUM)    |
                           | notes            |
                           | adjusted_by (FK) |
                           | adjusted_at      |
                           +------------------+
```

---

## Unit of Measurement System

Products are defined with explicit measurement units (`unit` column in `products` table):
- `kg`: Grains, pulses, rice, flour, sugar, bulk produce
- `can`: Beverages, sodas, juices, canned food
- `packet`: Noodles, biscuits, snacks, spice powders
- `bottle`: Cooking oil, sauces, milk, beverages
- `pcs`: Stationery, household items, individual tools
- `box`: Multi-item boxed sets, crates
- `litre`: Liquids, dairy
- `gram`: Spices, specialty items

The helper function `format_quantity($qty, $unit)` in `db.php` standardizes display across all tables, cards, alerts, and modal dialogs (e.g. `10 kg`, `1 can`, `2 cans`, `1 packet`, `5 packets`).

---

## File-by-File Breakdown

### db.php
- Defines DB credentials as constants
- `get_db_connection()` returns a singleton PDO instance - called once per request
- `auto_migrate_db()` runs ALTER TABLE checks on startup so the DB schema self-updates when new columns are needed (`sku`, `cost_price`, `min_quantity`, `unit`, etc.) without requiring manual SQL migrations
- `format_quantity($qty, $unit)` formats quantities with appropriate singular/plural unit suffix

### auth_check.php
- Must be included at the top of every protected page
- Starts the session, checks `$_SESSION['user_id']`
- If `$required_role = 'admin'` is set before including it, non-admins get blocked
- `csrf_field()` outputs a hidden input with a random token
- `verify_csrf()` compares the submitted token to the session token using `hash_equals()` (timing-safe)

### header.php
- Gets the low-stock count to show in the navbar badge
- Outputs the full HTML head section and top navbar
- Reads `$page_title` and `$active_page` variables set by the calling page
- Outputs flash messages from `$_SESSION['flash_success']` and `$_SESSION['flash_error']` then clears them

### footer.php
- Closes the main container tag
- Loads Bootstrap JS bundle
- Conditionally loads Chart.js if `$load_chartjs = true` was set
- Loads `app.js`

### index.php (Login)
- Detects if no admin exists yet (`SELECT COUNT(*) FROM users WHERE role = 'admin'`)
- If no admin: shows a registration form instead of a login form
- Login uses `password_verify()` against bcrypt hash
- On success: regenerates session ID (prevents session fixation), sets session vars, redirects to dashboard

### products.php
- Handles POST with `_method` field to simulate PUT and DELETE over HTML forms
- Add (POST): inserts a new product row with unit, cost price, sell price, min quantity
- Edit (PUT): updates name, SKU, unit, prices, category, supplier, min_quantity
- Delete (DELETE): removes the product row
- Reads GET params `search` and `category_id` for filtered listing with pagination

### sales.php
- Two POST actions: `record` (new sale) and `void` (admin reverses a sale)
- Stock check before insert: `SELECT quantity FROM products WHERE id = ?`
- Uses a DB transaction: INSERT into sales + UPDATE products quantity in one atomic operation
- If the UPDATE affects 0 rows (race condition / stock changed mid-request), rolls back and returns error
- Void: sets `voided_at` timestamp, records `voided_by`, and adds stock back atomically

### purchases.php
- INSERT into purchases + UPDATE products quantity in a transaction
- Stock only goes up (stock-in operation)
- Reflects unit of measurement on input and in history tables

### profile.php
- All logged-in users can view/edit their own profile
- Admin can view/edit any user's profile via `?id=X` parameter
- `reset_system_records` action: requires admin password re-confirmation, then truncates tables based on chosen scope

### api/stock.php
- Called by JavaScript via `fetch()` when a product is selected in sales or purchase modal
- Returns live JSON: `{"id": 1, "name": "Rice (Basmati)", "quantity": 60, "unit": "kg", "sell_price": 70.00}`
- Used to show real-time stock and dynamic unit labels before submitting

### app.js
- All JS lives here - clean and without inline scripts in PHP files
- `openAddProductForm()` / `openEditProductForm()` - populate the shared product modal for add vs edit
- Same pattern for categories, suppliers, users
- Sale modal: on product select, fetches live stock and unit from `api/stock.php`, updates unit labels, disables submit if stock is 0
- Quantity input listener: validates in real-time, shows error if quantity exceeds available stock
- Form submit guard: final client-side verification before submitting to server
- Chart.js initialisation: reads data dynamically from data attributes on the page
- Table search: live filtering across table rows by text content

---

## Security Model

```text
+----------------------+
|  Every page load     |
|  auth_check.php      |  <-- checks session, redirects if not logged in
+----------+-----------+
           |
           v
+----------+-----------+
|  Every POST request  |
|  verify_csrf()       |  <-- compares token to session, dies on mismatch
+----------+-----------+
           |
           v
+----------+-----------+
|  All DB queries      |
|  PDO prepared stmts  |  <-- no string interpolation, no SQL injection
+----------+-----------+
           |
           v
+----------+-----------+
|  User passwords      |
|  password_hash()     |  <-- bcrypt, never stored plain
|  password_verify()   |
+----------+-----------+
           |
           v
+----------+-----------+
|  Output to browser   |
|  htmlspecialchars()  |  <-- prevents XSS on all user data
+----------------------+
```

---

## Role Permission Matrix

| Feature | Staff | Admin |
|---|---|---|
| View dashboard | Yes | Yes |
| Record a sale | Yes | Yes |
| Record a purchase | Yes | Yes |
| View products | Yes | Yes |
| Add/edit/delete products | No | Yes |
| Manage categories | No | Yes |
| Manage suppliers | No | Yes |
| View reports | No | Yes |
| Manage user accounts | No | Yes |
| Void a sale | No | Yes |
| System reset (Danger Zone) | No | Yes |

---

## Auto-Migration Pattern

Instead of requiring manual SQL updates when the codebase changes, `auto_migrate_db()` runs on every connection. It checks if a column exists before adding it:

```text
SHOW COLUMNS FROM sales
  --> if 'voided_at' not in list --> ALTER TABLE sales ADD COLUMN voided_at TIMESTAMP NULL
  --> if 'voided_by' not in list --> ALTER TABLE sales ADD COLUMN voided_by INT DEFAULT NULL

SHOW COLUMNS FROM users
  --> check is_locked, phone, designation, bio --> add if missing

SHOW COLUMNS FROM products
  --> check sku, cost_price, min_quantity, unit --> add if missing

CREATE TABLE IF NOT EXISTS stock_adjustments (...)
  --> safe to run every time - does nothing if table already exists
```

This means pulling new code and refreshing the browser is enough to upgrade the DB schema automatically.

---

## Stock Safety (No Negative Stock)

Sales use a double-check pattern to prevent overselling even under concurrent requests:

```text
1. SELECT quantity FROM products WHERE id = ?
   --> if quantity <= 0: show error, stop

2. BEGIN TRANSACTION
   INSERT INTO sales (...)
   UPDATE products SET quantity = quantity - X
     WHERE id = ? AND quantity >= X   <-- atomic condition check

3. IF rowCount() == 0
   --> ROLLBACK (stock changed between check and update)
   --> Show error: "Stock error, please try again"
   ELSE
   --> COMMIT
```

This ensures stock can never go below 0 even if concurrent requests hit at the same time.
