# GIMS - Project Overview (Non-Technical)

## The Big Picture

GIMS stands for Grocery Inventory Management System. It is a modern digital tool designed to replace the notebooks, loose paper receipts, and complicated spreadsheets that grocery stores traditionally use to track inventory.

Think of it like this: every time a product is sold (whether it is 5 kg of rice, 2 cans of juice, or 3 packets of noodles), the system automatically deducts that exact amount from the inventory. Every time the shop buys new stock from a supplier, the system adds it in. At any moment, the owner can glance at the dashboard and know exactly what is happening in the shop - no manual stock counts, no guessing.

---

## The Problem It Solves

Running a grocery shop or retail outlet is demanding, and inventory mismanagement is where money gets lost:
- Not knowing a staple product has run out until a customer asks for it
- Accidentally selling items that are already out of stock
- Confusion when products use different metrics (grams, kilograms, cans, packets, bottles)
- Losing track of which supplier provided which batch and at what cost
- Having no clear picture of weekly or monthly profitability
- Inability to hold staff accountable for sales transactions

GIMS gives store owners total visibility, operational control, and an automated audit trail.

---

## Tailored Measurement Units for Groceries

Unlike generic inventory tools that treat everything as an abstract number, GIMS understands grocery items:
- **Rice, Flour, Lentils, Pulses:** Tracked in Kilograms (`kg`)
- **Juices, Sodas, Beverages:** Tracked in Cans (`can`)
- **Noodles, Biscuits, Chips, Spices:** Tracked in Packets (`packet`)
- **Cooking Oils, Sauces, Dairy:** Tracked in Bottles (`bottle`)
- **Stationery, Household Goods:** Tracked in Pieces (`pcs`)

When selling or restocking, the system automatically adapts inputs and table labels to match the item's unit.

---

## How the Owner Experiences It

The owner opens a browser, logs in, and sees a live dashboard:
- Total products active in the catalog
- Warning badges for products that have dropped below their minimum stock threshold
- Completed sales and units sold today
- Real-time revenue figures and sales trend charts
- Quick actions to add products, record restock orders, manage staff accounts, or print reports

---

## How Staff Experience It

Staff members receive individual login credentials:
- Quick point-of-entry to record sales when customers check out
- Ability to record incoming stock deliveries from suppliers
- Live visibility into available quantities and units
- Access restricted from destructive actions (cannot delete products, access financial reports, or alter user permissions)

---

## A Day in the Life Using GIMS

- **Morning:** The owner checks the dashboard. Two items are flagged in red under Low Stock (Basmati Rice and Sunflower Oil). She opens Purchases and logs an incoming order from Himalayan Suppliers to restock them in kg and bottles.
- **Afternoon:** Cashiers record customer purchases seamlessly. When a customer buys 2 cans of juice and 1 packet of biscuits, the system deducts them from stock and updates the cash register totals.
- **Evening:** The owner reviews the daily sales breakdown, verifies profit margins, and confirms end-of-day revenue.
- **Month-End:** The owner opens Reports to view the revenue chart, identify the best-selling products, and print a clean summary for financial records.

---

## Security & Reliability

- **Bcrypt Password Security:** All user passwords are encrypted with strong cryptographic hashing.
- **CSRF Token Protection:** Every action and form submission is protected against cross-site forgery.
- **Double-Check Stock Locks:** Transactions prevent overselling even if two users attempt to sell the last item simultaneously.
- **Danger Zone Confirmation:** Administrative tasks such as resetting transaction history require password re-verification.

---

## What GIMS Is Not

- It is not an e-commerce or online shopping website
- It does not require continuous cloud access or external API keys
- It is not complex ERP accounting software (no VAT filing, payroll, or tax returns)
- It is not a proprietary hardware POS requiring special card terminals

It is a lightweight, dedicated grocery inventory tracking system that runs effortlessly on local computers and local store networks.
