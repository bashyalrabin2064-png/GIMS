
CREATE DATABASE IF NOT EXISTS gims
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE gims;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS stock_adjustments;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id          INT          NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    phone       VARCHAR(30)  DEFAULT NULL,
    designation VARCHAR(100) DEFAULT NULL,
    bio         TEXT         DEFAULT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    is_locked   TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id         INT          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE suppliers (
    id         INT          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(150) NOT NULL,
    phone      VARCHAR(20)  DEFAULT NULL,
    email      VARCHAR(150) DEFAULT NULL,
    address    VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id           INT            NOT NULL AUTO_INCREMENT,
    name         VARCHAR(150)   NOT NULL,
    sku          VARCHAR(50)    DEFAULT NULL,
    category_id  INT            DEFAULT NULL,
    supplier_id  INT            DEFAULT NULL,
    unit         VARCHAR(20)    NOT NULL DEFAULT 'pcs',
    cost_price   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    sell_price   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    quantity     INT            NOT NULL DEFAULT 0,
    min_quantity INT            NOT NULL DEFAULT 5,
    created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_sku (sku),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchases (
    id            INT           NOT NULL AUTO_INCREMENT,
    product_id    INT           DEFAULT NULL,
    supplier_id   INT           DEFAULT NULL,
    quantity      INT           NOT NULL,
    unit_cost     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    purchased_by  INT           DEFAULT NULL,
    purchase_date TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (product_id)   REFERENCES products(id)  ON DELETE SET NULL,
    FOREIGN KEY (supplier_id)  REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (purchased_by) REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sales (
    id            INT           NOT NULL AUTO_INCREMENT,
    product_id    INT           DEFAULT NULL,
    quantity      INT           NOT NULL,
    unit_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_type ENUM('flat','percent') NOT NULL DEFAULT 'flat',
    vat_amount    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sold_by       INT           DEFAULT NULL,
    sale_date     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    voided_at     TIMESTAMP     NULL DEFAULT NULL,
    voided_by     INT           DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (sold_by)    REFERENCES users(id)    ON DELETE SET NULL,
    FOREIGN KEY (voided_by)  REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_adjustments (
    id          INT           NOT NULL AUTO_INCREMENT,
    product_id  INT           DEFAULT NULL,
    quantity    INT           NOT NULL,
    reason      ENUM('damaged','expired','correction','other') NOT NULL DEFAULT 'other',
    notes       VARCHAR(255)  DEFAULT NULL,
    adjusted_by INT           DEFAULT NULL,
    adjusted_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (product_id)  REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (adjusted_by) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
