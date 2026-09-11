<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gims');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db_connection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn     = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            auto_migrate_db($pdo);
        } catch (PDOException $e) {
            die('Database connection failed. Please make sure XAMPP MySQL is running.');
        }
    }
    return $pdo;
}

function auto_migrate_db(PDO $pdo): void {
    try {
        $saleCols = $pdo->query("SHOW COLUMNS FROM sales")->fetchAll(PDO::FETCH_COLUMN);
        if ($saleCols && !in_array('voided_at', $saleCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN voided_at TIMESTAMP NULL DEFAULT NULL");
        }
        if ($saleCols && !in_array('voided_by', $saleCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN voided_by INT DEFAULT NULL");
        }
        if ($saleCols && !in_array('discount', $saleCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
        if ($saleCols && !in_array('discount_type', $saleCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN discount_type ENUM('flat','percent') NOT NULL DEFAULT 'flat'");
        }
        if ($saleCols && !in_array('vat_amount', $saleCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN vat_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }

        $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if ($userCols) {
            if (!in_array('is_locked', $userCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0");
            }
            if (!in_array('phone', $userCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(30) DEFAULT NULL");
            }
            if (!in_array('designation', $userCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN designation VARCHAR(100) DEFAULT NULL");
            }
            if (!in_array('bio', $userCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL");
            }
        }

        $prodCols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if ($prodCols) {
            if (!in_array('sku', $prodCols)) {
                $pdo->exec("ALTER TABLE products ADD COLUMN sku VARCHAR(50) DEFAULT NULL");
            }
            if (!in_array('cost_price', $prodCols)) {
                $pdo->exec("ALTER TABLE products ADD COLUMN cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
            }
            if (!in_array('min_quantity', $prodCols)) {
                $pdo->exec("ALTER TABLE products ADD COLUMN min_quantity INT NOT NULL DEFAULT 5");
            }
            if (!in_array('unit', $prodCols)) {
                $pdo->exec("ALTER TABLE products ADD COLUMN unit VARCHAR(20) NOT NULL DEFAULT 'pcs'");
                $pdo->exec("UPDATE products SET unit = 'kg' WHERE name LIKE '%Rice%' OR name LIKE '%Dal%' OR name LIKE '%Chana%' OR name LIKE '%Flour%' OR name LIKE '%Sugar%'");
                $pdo->exec("UPDATE products SET unit = 'can' WHERE name LIKE '%Juice%' OR name LIKE '%Coke%' OR name LIKE '%Soda%' OR name LIKE '%Beverage%'");
                $pdo->exec("UPDATE products SET unit = 'packet' WHERE name LIKE '%Noodles%' OR name LIKE '%Biscuit%' OR name LIKE '%Chips%'");
            }
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS stock_adjustments (
                id          INT           NOT NULL AUTO_INCREMENT,
                product_id  INT           DEFAULT NULL,
                quantity    INT           NOT NULL,
                reason      ENUM('damaged','expired','correction','other') NOT NULL DEFAULT 'other',
                notes       VARCHAR(255)  DEFAULT NULL,
                adjusted_by INT           DEFAULT NULL,
                adjusted_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (Exception $e) {
        error_log('GIMS DB migration error: ' . $e->getMessage());
    }
}

function get_product_units(): array {
    return [
        'kg'      => 'Kilogram (kg) — Rice, Dal, Grains, Sugar',
        'can'     => 'Can (can) — Beverages, Soda, Canned items',
        'pcs'     => 'Pieces (pcs) — Stationery, Soap, General items',
        'bottle'  => 'Bottle (bottle) — Oil, Drinks, Sauces',
        'packet'  => 'Packet (pkt) — Noodles, Biscuits, Snacks',
        'box'     => 'Box (box) — Matches, Packed sets',
        'ltr'     => 'Liter (ltr) — Milk, Liquid, Oil',
        'gm'      => 'Gram (gm) — Spices, Tea, Coffee',
        'bag'     => 'Bag (bag) — Sacks, Large packs',
        'carton'  => 'Carton (carton) — Cases, Crates',
        'jar'     => 'Jar (jar) — Honey, Jam, Pickles',
        'tin'     => 'Tin (tin) — Ghee, Powdered milk',
    ];
}

function format_quantity($qty, ?string $unit = 'pcs'): string {
    $qtyVal = (float)$qty;
    $formattedQty = (floor($qtyVal) == $qtyVal) ? (int)$qtyVal : number_format($qtyVal, 2);
    $unit = trim(strtolower((string)$unit));
    if ($unit === '') {
        $unit = 'pcs';
    }

    $unitMap = [
        'kg'      => ['kg', 'kg'],
        'g'       => ['g', 'g'],
        'gm'      => ['gm', 'gm'],
        'can'     => ['can', 'cans'],
        'bottle'  => ['bottle', 'bottles'],
        'pcs'     => ['pc', 'pcs'],
        'pc'      => ['pc', 'pcs'],
        'piece'   => ['piece', 'pieces'],
        'packet'  => ['packet', 'packets'],
        'pkt'     => ['pkt', 'pkts'],
        'box'     => ['box', 'boxes'],
        'ltr'     => ['ltr', 'ltr'],
        'liter'   => ['liter', 'liters'],
        'litre'   => ['litre', 'litres'],
        'bag'     => ['bag', 'bags'],
        'sack'    => ['sack', 'sacks'],
        'pack'    => ['pack', 'packs'],
        'carton'  => ['carton', 'cartons'],
        'doz'     => ['doz', 'doz'],
        'dozen'   => ['dozen', 'dozens'],
        'roll'    => ['roll', 'rolls'],
        'jar'     => ['jar', 'jars'],
        'tin'     => ['tin', 'tins'],
    ];

    if (isset($unitMap[$unit])) {
        $unitLabel = ($qtyVal == 1) ? $unitMap[$unit][0] : $unitMap[$unit][1];
    } else {
        $unitLabel = ($qtyVal == 1 || str_ends_with($unit, 's')) ? $unit : $unit . 's';
    }

    return $formattedQty . ' ' . $unitLabel;
}

