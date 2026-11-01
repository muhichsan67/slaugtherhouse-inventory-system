-- =====================================================================
-- Slaughterhouse Inventory & Simple Ledger  |  MySQL 8.0.16+  |  17 tables
-- Part 1: schema   Part 2: seed data   Part 3: verification queries
-- Design rule: the database holds structure only (tables, foreign keys, unique identifiers).
-- No triggers, generated columns or CHECK constraints: business rules (no negative stock,
-- one receipt per PO, ...) are enforced in Laravel code.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS rph_inventory
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rph_inventory;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS stock_balances, stock_movements,
  delivery_lines, deliveries, sales_order_lines, sales_orders,
  receipt_lines, receipts, purchase_order_lines, purchase_orders,
  partners, warehouses, items, units, categories, users, roles;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- PART 1: SCHEMA
-- =====================================================================

-- ---------- Access ----------
CREATE TABLE roles (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(50)  NOT NULL,
  description VARCHAR(255) NULL,
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB;

CREATE TABLE users (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id           BIGINT UNSIGNED NOT NULL,
  name              VARCHAR(255) NOT NULL,
  email             VARCHAR(255) NOT NULL,
  email_verified_at TIMESTAMP NULL,
  password          VARCHAR(255) NOT NULL,
  remember_token    VARCHAR(100) NULL,
  is_active         TINYINT(1) NOT NULL DEFAULT 1,
  created_at        TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB;

-- ---------- Master data ----------
CREATE TABLE categories (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE units (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code       VARCHAR(10)  NOT NULL,
  name       VARCHAR(50)  NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_units_code (code)
) ENGINE=InnoDB;

CREATE TABLE items (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id BIGINT UNSIGNED NOT NULL,
  unit_id     BIGINT UNSIGNED NOT NULL,
  sku         VARCHAR(30)  NOT NULL,
  name        VARCHAR(150) NOT NULL,
  item_type   ENUM('live_animal','carcass','cut','by_product','supply') NOT NULL,
  sale_price  DECIMAL(15,2) NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_items_sku (sku),
  KEY idx_items_category (category_id),
  CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories (id),
  CONSTRAINT fk_items_unit     FOREIGN KEY (unit_id)     REFERENCES units (id)
) ENGINE=InnoDB;

CREATE TABLE warehouses (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code       VARCHAR(20)  NOT NULL,
  name       VARCHAR(100) NOT NULL,
  address    VARCHAR(255) NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_warehouses_code (code)
) ENGINE=InnoDB;

CREATE TABLE partners (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  type       ENUM('supplier','customer') NOT NULL,
  name       VARCHAR(150) NOT NULL,
  phone      VARCHAR(30)  NULL,
  address    VARCHAR(255) NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_partners_type (type)
) ENGINE=InnoDB;

-- ---------- Purchase ----------
CREATE TABLE purchase_orders (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  number      VARCHAR(30) NOT NULL,
  partner_id  BIGINT UNSIGNED NOT NULL,
  user_id     BIGINT UNSIGNED NOT NULL,
  order_date  DATE NOT NULL,
  status      ENUM('draft','approved','received','cancelled') NOT NULL DEFAULT 'draft',
  total       DECIMAL(15,2) NOT NULL DEFAULT 0,
  notes       VARCHAR(255) NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_po_number (number),
  KEY idx_po_status_date (status, order_date),
  CONSTRAINT fk_po_partner  FOREIGN KEY (partner_id)  REFERENCES partners (id),
  CONSTRAINT fk_po_user     FOREIGN KEY (user_id)     REFERENCES users (id),
  CONSTRAINT fk_po_approver FOREIGN KEY (approved_by) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE purchase_order_lines (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  purchase_order_id BIGINT UNSIGNED NOT NULL,
  item_id           BIGINT UNSIGNED NOT NULL,
  qty               DECIMAL(15,3) NOT NULL,
  unit_price        DECIMAL(15,2) NOT NULL,
  subtotal          DECIMAL(15,2) NOT NULL,
  created_at        TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_pol_po (purchase_order_id),
  CONSTRAINT fk_pol_po   FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_pol_item FOREIGN KEY (item_id)           REFERENCES items (id)
) ENGINE=InnoDB;

CREATE TABLE receipts (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  number            VARCHAR(30) NOT NULL,
  purchase_order_id BIGINT UNSIGNED NOT NULL,
  warehouse_id      BIGINT UNSIGNED NOT NULL,
  user_id           BIGINT UNSIGNED NOT NULL,
  received_at       DATETIME NOT NULL,
  status            ENUM('posted','void') NOT NULL DEFAULT 'posted',
  notes             VARCHAR(255) NULL,
  created_at        TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_receipts_number (number),
  KEY idx_receipts_po (purchase_order_id),
  CONSTRAINT fk_rcv_po        FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id),
  CONSTRAINT fk_rcv_warehouse FOREIGN KEY (warehouse_id)      REFERENCES warehouses (id),
  CONSTRAINT fk_rcv_user      FOREIGN KEY (user_id)           REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE receipt_lines (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  receipt_id   BIGINT UNSIGNED NOT NULL,
  item_id      BIGINT UNSIGNED NOT NULL,
  qty_received DECIMAL(15,3) NOT NULL,
  unit_cost    DECIMAL(15,2) NOT NULL,
  subtotal     DECIMAL(15,2) NOT NULL,
  created_at   TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_rcvl_receipt (receipt_id),
  CONSTRAINT fk_rcvl_receipt FOREIGN KEY (receipt_id) REFERENCES receipts (id) ON DELETE CASCADE,
  CONSTRAINT fk_rcvl_item    FOREIGN KEY (item_id)    REFERENCES items (id)
) ENGINE=InnoDB;

-- ---------- Sales ----------
CREATE TABLE sales_orders (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  number       VARCHAR(30) NOT NULL,
  partner_id   BIGINT UNSIGNED NOT NULL,
  user_id      BIGINT UNSIGNED NOT NULL,
  order_date   DATE NOT NULL,
  status       ENUM('draft','confirmed','delivered','cancelled') NOT NULL DEFAULT 'draft',
  total        DECIMAL(15,2) NOT NULL DEFAULT 0,
  notes        VARCHAR(255) NULL,
  confirmed_at DATETIME NULL,
  created_at   TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_so_number (number),
  KEY idx_so_status_date (status, order_date),
  CONSTRAINT fk_so_partner FOREIGN KEY (partner_id) REFERENCES partners (id),
  CONSTRAINT fk_so_user    FOREIGN KEY (user_id)    REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE sales_order_lines (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sales_order_id BIGINT UNSIGNED NOT NULL,
  item_id        BIGINT UNSIGNED NOT NULL,
  qty            DECIMAL(15,3) NOT NULL,
  unit_price     DECIMAL(15,2) NOT NULL,
  subtotal       DECIMAL(15,2) NOT NULL,
  created_at     TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_sol_so (sales_order_id),
  CONSTRAINT fk_sol_so   FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_sol_item FOREIGN KEY (item_id)        REFERENCES items (id)
) ENGINE=InnoDB;

CREATE TABLE deliveries (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  number         VARCHAR(30) NOT NULL,
  sales_order_id BIGINT UNSIGNED NOT NULL,
  warehouse_id   BIGINT UNSIGNED NOT NULL,
  user_id        BIGINT UNSIGNED NOT NULL,
  delivered_at   DATETIME NOT NULL,
  status         ENUM('posted','void') NOT NULL DEFAULT 'posted',
  notes          VARCHAR(255) NULL,
  created_at     TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_deliveries_number (number),
  KEY idx_deliveries_so (sales_order_id),
  CONSTRAINT fk_dlv_so        FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id),
  CONSTRAINT fk_dlv_warehouse FOREIGN KEY (warehouse_id)   REFERENCES warehouses (id),
  CONSTRAINT fk_dlv_user      FOREIGN KEY (user_id)        REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE delivery_lines (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  delivery_id BIGINT UNSIGNED NOT NULL,
  item_id     BIGINT UNSIGNED NOT NULL,
  qty         DECIMAL(15,3) NOT NULL,
  unit_cost   DECIMAL(15,2) NOT NULL,              -- average cost at posting time
  subtotal    DECIMAL(15,2) NOT NULL,              -- qty * unit_cost (COGS)
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_dlvl_delivery (delivery_id),
  CONSTRAINT fk_dlvl_delivery FOREIGN KEY (delivery_id) REFERENCES deliveries (id) ON DELETE CASCADE,
  CONSTRAINT fk_dlvl_item     FOREIGN KEY (item_id)     REFERENCES items (id)
) ENGINE=InnoDB;

-- ---------- Stock ----------
CREATE TABLE stock_movements (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id      BIGINT UNSIGNED NOT NULL,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  qty          DECIMAL(15,3) NOT NULL,             -- signed: + in, - out
  unit_cost    DECIMAL(15,2) NOT NULL DEFAULT 0,
  type         ENUM('receipt','delivery','opname_adj','manual') NOT NULL, -- legacy types remain for existing movement history
  ref_type     VARCHAR(30) NULL,                   -- receipts, deliveries; legacy stock_opnames references may remain
  ref_id       BIGINT UNSIGNED NULL,
  occurred_at  DATETIME NOT NULL,
  user_id      BIGINT UNSIGNED NULL,
  notes        VARCHAR(255) NULL,
  created_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_mv_item_wh_time (item_id, warehouse_id, occurred_at),
  KEY idx_mv_ref (ref_type, ref_id),
  CONSTRAINT fk_mv_item      FOREIGN KEY (item_id)      REFERENCES items (id),
  CONSTRAINT fk_mv_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses (id),
  CONSTRAINT fk_mv_user      FOREIGN KEY (user_id)      REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE stock_balances (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id      BIGINT UNSIGNED NOT NULL,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  qty          DECIMAL(15,3) NOT NULL DEFAULT 0,
  avg_cost     DECIMAL(15,2) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_balance_item_wh (item_id, warehouse_id),
  CONSTRAINT fk_bal_item      FOREIGN KEY (item_id)      REFERENCES items (id),
  CONSTRAINT fk_bal_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)
) ENGINE=InnoDB;

-- =====================================================================
-- PART 2: SEED DATA
-- Demo login password for all users: "password" (replace with Hash::make in Laravel seeders)
-- =====================================================================

INSERT INTO roles (id, name, description, created_at, updated_at) VALUES
 (1,'admin','Full access, user management','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (2,'purchasing','Suppliers and purchase orders','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (3,'warehouse','Receipts, deliveries, and stock','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (4,'sales','Customers and sales orders','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (5,'finance','Reports','2026-10-19 08:00:00','2026-10-19 08:00:00');

INSERT INTO users (id, role_id, name, email, email_verified_at, password, is_active, created_at, updated_at) VALUES
 (1,1,'Admin RPH','admin@rph.test','2026-10-19 08:00:00','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (2,2,'Budi Purchasing','purchasing@rph.test','2026-10-19 08:00:00','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (3,3,'Wawan Warehouse','warehouse@rph.test','2026-10-19 08:00:00','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (4,4,'Sari Sales','sales@rph.test','2026-10-19 08:00:00','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (5,5,'Fina Finance','finance@rph.test','2026-10-19 08:00:00','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',1,'2026-10-19 08:00:00','2026-10-19 08:00:00');

INSERT INTO categories (id, name, description, created_at, updated_at) VALUES
 (1,'Live Animals','Cattle, goats and other live stock','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (2,'Carcass','Whole or half carcass','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (3,'Meat Cuts','Primal and retail cuts','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (4,'By-products','Offal, bones, hides','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (5,'Supplies','Packaging and consumables','2026-10-19 08:00:00','2026-10-19 08:00:00');

INSERT INTO units (id, code, name, created_at, updated_at) VALUES
 (1,'KG','Kilogram','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (2,'HEAD','Head (per animal)','2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (3,'PCS','Piece','2026-10-19 08:00:00','2026-10-19 08:00:00');

INSERT INTO items (id, category_id, unit_id, sku, name, item_type, sale_price, is_active, created_at, updated_at) VALUES
 (1,1,2,'LIV-CAT','Live Cattle','live_animal',0,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (2,1,2,'LIV-GOT','Live Goat','live_animal',0,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (3,2,1,'CAR-BEF','Beef Carcass','carcass',110000,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (4,2,1,'CAR-GOT','Goat Carcass','carcass',125000,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (5,3,1,'CUT-BEF-TEN','Beef Tenderloin','cut',230000,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (6,3,1,'CUT-BEF-SIR','Beef Sirloin','cut',170000,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (7,3,1,'CUT-BEF-RIB','Beef Short Ribs','cut',120000,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (8,3,1,'CUT-BEF-MIN','Minced Beef','cut',115000,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (9,4,1,'BYP-BEF-LIV','Beef Liver','by_product',60000,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (10,4,1,'BYP-BEF-TRP','Beef Tripe','by_product',45000,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (11,4,1,'BYP-BEF-BON','Beef Bones','by_product',15000,1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (12,5,3,'SUP-VAC-BAG','Vacuum Bag','supply',500,1,'2026-10-19 08:00:00','2026-10-19 08:00:00');

INSERT INTO warehouses (id, code, name, address, is_active, created_at, updated_at) VALUES
 (1,'WH-01','Cold Storage','Main plant, building A',1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (2,'WH-02','Dry Storage','Main plant, building B',1,'2026-10-19 08:00:00','2026-10-19 08:00:00');

INSERT INTO partners (id, type, name, phone, address, is_active, created_at, updated_at) VALUES
 (1,'supplier','PT Sumber Ternak Jaya','021-5550101','Jl. Peternakan No. 12, Bogor',1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (2,'supplier','CV Berkah Daging Nusantara','021-5550102','Jl. Raya Cibinong No. 45, Bogor',1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (3,'customer','Toko Daging Segar Pasar Minggu','021-5550201','Pasar Minggu, Jakarta Selatan',1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (4,'customer','Restoran Sate Senayan','021-5550202','Senayan, Jakarta Pusat',1,'2026-10-19 08:00:00','2026-10-19 08:00:00'),
 (5,'customer','Catering Dapur Sehat','021-5550203','Tebet, Jakarta Selatan',1,'2026-10-19 08:00:00','2026-10-19 08:00:00');

-- ---------- Demo transactions (internally consistent) ----------

-- Purchase orders: #1 received, #2 approved (waiting for receipt), #3 draft
INSERT INTO purchase_orders (id, number, partner_id, user_id, order_date, status, total, notes, approved_by, approved_at, created_at, updated_at) VALUES
 (1,'PO-2026-0001',1,2,'2026-10-20','received',26500000,'Carcass restock',2,'2026-10-20 10:00:00','2026-10-20 09:30:00','2026-10-21 08:30:00'),
 (2,'PO-2026-0002',2,2,'2026-10-22','approved',11900000,'Premium cuts',2,'2026-10-22 11:00:00','2026-10-22 10:30:00','2026-10-22 11:00:00'),
 (3,'PO-2026-0003',1,2,'2026-10-26','draft',800000,'Bones for stock',NULL,NULL,'2026-10-26 09:00:00','2026-10-26 09:00:00');
INSERT INTO purchase_order_lines (purchase_order_id, item_id, qty, unit_price, subtotal, created_at, updated_at) VALUES
 (1,3,200,85000,17000000,'2026-10-20 09:30:00','2026-10-20 09:30:00'),
 (1,4,100,95000,9500000,'2026-10-20 09:30:00','2026-10-20 09:30:00'),
 (2,5,30,180000,5400000,'2026-10-22 10:30:00','2026-10-22 10:30:00'),
 (2,6,50,130000,6500000,'2026-10-22 10:30:00','2026-10-22 10:30:00'),
 (3,11,100,8000,800000,'2026-10-26 09:00:00','2026-10-26 09:00:00');

-- Receipt for PO #1: stock in and balance
INSERT INTO receipts (id, number, purchase_order_id, warehouse_id, user_id, received_at, status, notes, created_at, updated_at) VALUES
 (1,'RCV-2026-0001',1,1,3,'2026-10-21 08:30:00','posted',NULL,'2026-10-21 08:30:00','2026-10-21 08:30:00');
INSERT INTO receipt_lines (receipt_id, item_id, qty_received, unit_cost, subtotal, created_at, updated_at) VALUES
 (1,3,200,85000,17000000,'2026-10-21 08:30:00','2026-10-21 08:30:00'),
 (1,4,100,95000,9500000,'2026-10-21 08:30:00','2026-10-21 08:30:00');
INSERT INTO stock_movements (id, item_id, warehouse_id, qty, unit_cost, type, ref_type, ref_id, occurred_at, user_id, created_at) VALUES
 (1,3,1,200,85000,'receipt','receipts',1,'2026-10-21 08:30:00',3,'2026-10-21 08:30:00'),
 (2,4,1,100,95000,'receipt','receipts',1,'2026-10-21 08:30:00',3,'2026-10-21 08:30:00');
-- Sales orders: #1 delivered, #2 confirmed (waiting for delivery)
INSERT INTO sales_orders (id, number, partner_id, user_id, order_date, status, total, notes, confirmed_at, created_at, updated_at) VALUES
 (1,'SO-2026-0001',3,4,'2026-10-23','delivered',13800000,NULL,'2026-10-23 11:00:00','2026-10-23 10:30:00','2026-10-24 09:00:00'),
 (2,'SO-2026-0002',4,4,'2026-10-26','confirmed',5500000,NULL,'2026-10-26 14:00:00','2026-10-26 13:30:00','2026-10-26 14:00:00');
INSERT INTO sales_order_lines (sales_order_id, item_id, qty, unit_price, subtotal, created_at, updated_at) VALUES
 (1,3,80,110000,8800000,'2026-10-23 10:30:00','2026-10-23 10:30:00'),
 (1,4,40,125000,5000000,'2026-10-23 10:30:00','2026-10-23 10:30:00'),
 (2,3,50,110000,5500000,'2026-10-26 13:30:00','2026-10-26 13:30:00');

-- Delivery for SO #1: stock out at average cost
INSERT INTO deliveries (id, number, sales_order_id, warehouse_id, user_id, delivered_at, status, notes, created_at, updated_at) VALUES
 (1,'DLV-2026-0001',1,1,3,'2026-10-24 09:00:00','posted',NULL,'2026-10-24 09:00:00','2026-10-24 09:00:00');
INSERT INTO delivery_lines (delivery_id, item_id, qty, unit_cost, subtotal, created_at, updated_at) VALUES
 (1,3,80,85000,6800000,'2026-10-24 09:00:00','2026-10-24 09:00:00'),
 (1,4,40,95000,3800000,'2026-10-24 09:00:00','2026-10-24 09:00:00');
INSERT INTO stock_movements (id, item_id, warehouse_id, qty, unit_cost, type, ref_type, ref_id, occurred_at, user_id, created_at) VALUES
 (3,3,1,-80,85000,'delivery','deliveries',1,'2026-10-24 09:00:00',3,'2026-10-24 09:00:00'),
 (4,4,1,-40,95000,'delivery','deliveries',1,'2026-10-24 09:00:00',3,'2026-10-24 09:00:00');
-- Stock balances after all movements above
INSERT INTO stock_balances (item_id, warehouse_id, qty, avg_cost, created_at, updated_at) VALUES
 (3,1,120,85000,'2026-10-21 08:30:00','2026-10-24 09:00:00'),
 (4,1,60,95000,'2026-10-21 08:30:00','2026-10-24 09:00:00');

-- Profit & Loss report totals for October 2026:
--   Purchase expense 26,500,000 (received PO total) | Sales revenue 13,800,000 (delivered SO total)

-- =====================================================================
-- PART 3: VERIFICATION AND REPORT QUERIES (use bound parameters in Laravel)
-- =====================================================================

-- 3.1 Cached balance must equal the sum of movements (should return 0 rows)
SELECT b.item_id, b.warehouse_id, b.qty AS cached, COALESCE(SUM(m.qty),0) AS ledger
FROM stock_balances b
LEFT JOIN stock_movements m ON m.item_id = b.item_id AND m.warehouse_id = b.warehouse_id
GROUP BY b.id, b.item_id, b.warehouse_id, b.qty
HAVING b.qty <> COALESCE(SUM(m.qty),0);

-- 3.2 Profit and loss for an order-date range: received purchases are expenses;
--     delivered sales are revenue. All other activity and order statuses are excluded.
SELECT 'purchase' AS code, 'Purchase Orders' AS name, 'expense' AS type,
       COALESCE(SUM(total), 0) AS amount
FROM purchase_orders
WHERE status = 'received' AND order_date BETWEEN '2026-10-01' AND '2026-10-31'
UNION ALL
SELECT 'sales' AS code, 'Sales Orders' AS name, 'revenue' AS type,
       COALESCE(SUM(total), 0) AS amount
FROM sales_orders
WHERE status = 'delivered' AND order_date BETWEEN '2026-10-01' AND '2026-10-31';

-- 3.3 Stock card for one item and warehouse (item 3, warehouse 1)
SELECT occurred_at, type, qty, unit_cost,
       SUM(qty) OVER (ORDER BY occurred_at, id) AS running_qty
FROM stock_movements WHERE item_id = 3 AND warehouse_id = 1 ORDER BY occurred_at, id;
