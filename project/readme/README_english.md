# GIMS - Grocery Inventory Management System

## What is this?

GIMS is a web-based inventory management system built specifically for grocery stores and small retail businesses. It helps store owners and staff track items with realistic units of measurement (e.g. kg, can, packet, bottle, pcs), manage live stock levels, record daily sales and purchases, prevent overselling, and generate financial reports - all from any browser without installing extra software.

---

## Why was it built?

Most small grocery shops in Nepal track inventory using notebooks or Excel sheets. This creates problems:
- Paper records get lost, stained, or damaged
- Calculating totals manually takes time and causes calculation mistakes
- There is no automated warning when essentials (like rice, oil, or sugar) run low
- Generic quantity systems don't differentiate between bulk goods (like 25 kg rice) and unit items (like 2 cans of juice)
- There is no audit trail - you cannot track which staff member performed a sale or purchase

GIMS solves these problems by giving store owners a fast, secure, unit-aware digital system that runs locally.

---

## Key Features

- **Unit of Measurement System:** Assign specific units per product (kg, can, packet, bottle, pcs, box, litre, gram).
- **F-Key Fullscreen Mode:** Press F anywhere (when not focused on a form field) to enter fullscreen. Fullscreen is maintained while navigating between pages. Press Esc to exit.
- **Dashboard Charts:** Revenue line chart (last 14 days) and daily sales transaction bar chart rendered directly on the dashboard for at-a-glance insights.
- **Localized Revenue Period:** Scoped revenue period selection (Today, This Week, This Month, All Time) directly attached inside the Revenue card.
- **Live Stock Alerts:** Real-time badge in the navbar and dashboard alerts for products below minimum threshold.
- **Atomic Stock Checks:** Transactions ensure stock can never drop below zero or suffer from race conditions.
- **Sale Reversal / Voiding:** Admins can safely void faulty sales with automatic inventory restoration.
- **Supplier and Purchase Management:** Track stock-in batches, unit costs, supplier contact details, and purchase history.
- **Interactive Reports and Analytics:** Chart.js revenue summaries, top-selling items, low-stock lists, and print-ready reports.
- **Role-Based Access Control:** Strict separation between Admin and Staff permissions with CSRF and session security.
- **Admin Danger Zone:** Protected data resets and table truncations with password re-authentication.

---

## Who is it for?

- **Store Owners (Admin):** Full control - manage products, categories, suppliers, user accounts, financial reports, and system resets.
- **Staff Members:** Operational access - record customer sales, restock purchases, view stock levels, and update own profile.
- **Small Grocery Shops, Mini-marts, and Retailers:** Anyone needing clear, local inventory management.

---

## How does it work?

1. **Initial Setup:** On first launch, visiting the login page displays the initial admin registration screen.
2. **Catalog Configuration:** Admin sets up categories, suppliers, and products with appropriate units.
3. **Staff Onboarding:** Admin creates accounts for staff members.
4. **Daily Operations:** Staff records sales (which decrement stock) and purchases (which increment stock).
5. **Real-time Monitoring:** The dashboard continuously reports stock health, daily revenue charts, and low-inventory warnings.
6. **Reporting:** Admins inspect monthly trends, top performers, and export printable summaries.

---

## Keyboard Shortcuts

| Key | Action |
|---|---|
| F | Enter fullscreen (when not focused on an input or textarea) |
| Esc | Exit fullscreen |

---

## Where does data go?

All data is stored in a local MySQL database named gims. It runs locally via XAMPP on the shop computer or local network. No external internet connection or cloud service is required.

---

## When should I use what?

| Situation | What to use |
|---|---|
| New stock arrived from a supplier | Purchases page - Record Purchase |
| Customer bought goods | Sales page - Record Sale |
| Check which items need urgent restocking | Dashboard - Low Stock Alerts |
| View revenue, top products, or print reports | Reports page |
| Add new items or edit prices/units | Products page |
| Add, lock, or manage staff logins | Users page (Admin only) |
| Reverse an incorrect sale transaction | Sales page - Void button (Admin only) |
| Start a fresh financial year / clear history | Profile - Danger Zone (Admin only) |

---

## Project Structure

project/
  php/     - All PHP pages (login, dashboard, products, sales, purchases, etc.)
  api/     - JSON API endpoints for live stock and unit queries
  css/     - Custom stylesheet (style.css)
  js/      - JavaScript frontend logic (app.js)
  sql/     - SQL schema and seed data
  readme/  - Documentation (English, Nepali, Architecture)

---

## Tech Stack

- **Backend:** PHP 8+ with PDO (vanilla, no framework overhead)
- **Database:** MySQL (InnoDB with foreign keys and transactions)
- **Frontend:** HTML5, CSS3, Vanilla JavaScript, Bootstrap 5
- **Charts:** Chart.js (CDN)
- **Icons:** Bootstrap Icons (CDN)

---

## How to run it

1. Install XAMPP (with PHP 8+ and MySQL).
2. Place the project/ folder inside your XAMPP htdocs/ directory (c:\xampp\htdocs\project).
3. Start Apache and MySQL from the XAMPP Control Panel.
4. Open phpMyAdmin and create a database named gims (or run http://localhost/project/php/setup.php).
5. Open your browser and navigate to: http://localhost/project/php/index.php.
6. Register the first admin account and start managing your inventory!
