
USE gims;
INSERT INTO categories (name) VALUES
    ('Grains & Pulses'),
    ('Snacks'),
    ('Stationery'),
    ('Household'),
    ('Beverages');

INSERT INTO suppliers (name, phone, email, address) VALUES
    ('Sharma Traders',    '9801234567', 'sharma.traders@gmail.com',  'New Road, Kathmandu'),
    ('Himalayan Suppliers', '9857890123', 'himalayan.sup@gmail.com', 'Ason, Kathmandu');

INSERT INTO products (name, category_id, supplier_id, unit, cost_price, sell_price, quantity, min_quantity) VALUES
    ('Dal (Masoor)',       1, 1, 'kg',     60.00,  75.00, 80, 20),
    ('Rice (Basmati)',     1, 1, 'kg',     55.00,  70.00, 60, 20),
    ('Chana (Chickpea)',   1, 1, 'kg',     85.00, 100.00, 45, 15),
    ('Wai Wai Noodles',   2, 2, 'packet', 18.00,  25.00, 120, 30),
    ('Real Fruit Juice',  5, 2, 'can',    45.00,  60.00, 40, 10),
    ('Pencil (HB)',        3, 2, 'pcs',     5.00,  10.00,  3, 10),   
    ('Pen (Blue Ink)',     3, 2, 'pcs',    10.00,  20.00,  4, 10);   
INSERT INTO purchases (product_id, supplier_id, quantity, unit_cost, purchased_by, purchase_date) VALUES
    (1, 1, 100, 60.00, 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
    (2, 1,  80, 55.00, 1, DATE_SUB(NOW(), INTERVAL 8  DAY)),
    (3, 1,  50, 85.00, 1, DATE_SUB(NOW(), INTERVAL 5  DAY)),
    (4, 2, 150, 18.00, 1, DATE_SUB(NOW(), INTERVAL 3  DAY)),
    (5, 2,  50, 45.00, 1, DATE_SUB(NOW(), INTERVAL 2  DAY));

INSERT INTO sales (product_id, quantity, unit_price, sold_by, sale_date) VALUES
    (1, 20, 75.00, 2, DATE_SUB(NOW(), INTERVAL 7 DAY)),
    (2, 20, 70.00, 2, DATE_SUB(NOW(), INTERVAL 6 DAY)),
    (3,  5,100.00, 2, DATE_SUB(NOW(), INTERVAL 4 DAY)),
    (4, 30, 25.00, 2, DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (5, 10, 60.00, 2, DATE_SUB(NOW(), INTERVAL 1 DAY));

