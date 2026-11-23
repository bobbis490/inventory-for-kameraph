-- ============================================================================
-- KameraPH Warehouse Inventory System Database Schema
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB, and Azure Database for MySQL
-- ============================================================================

-- 1. Warehouses Table
CREATE TABLE IF NOT EXISTS `warehouse` (
    `WarehouseID` INT AUTO_INCREMENT PRIMARY KEY,
    `WarehouseCode` VARCHAR(50) NOT NULL UNIQUE,
    `WarehouseName` VARCHAR(150) NOT NULL,
    `Address` VARCHAR(255) NOT NULL,
    `City` VARCHAR(100) NOT NULL,
    `Province` VARCHAR(100) DEFAULT NULL,
    `PostalCode` VARCHAR(20) DEFAULT NULL,
    `ContactPerson` VARCHAR(100) DEFAULT NULL,
    `ContactPhone` VARCHAR(50) DEFAULT NULL,
    `ContactEmail` VARCHAR(100) DEFAULT NULL,
    `StorageCapacity` INT DEFAULT 5000 COMMENT 'Maximum unit capacity of warehouse',
    `Status` ENUM('Active', 'Inactive', 'Under Maintenance') DEFAULT 'Active',
    `Notes` TEXT DEFAULT NULL,
    `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `UpdatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Warehouse Product Inventory Table (Tracks stock per warehouse and bin location)
CREATE TABLE IF NOT EXISTS `warehouse_inventory` (
    `InventoryID` INT AUTO_INCREMENT PRIMARY KEY,
    `WarehouseID` INT NOT NULL,
    `ListingID` INT NOT NULL,
    `QuantityOnHand` INT NOT NULL DEFAULT 0 COMMENT 'Total physical units in warehouse',
    `QuantityReserved` INT NOT NULL DEFAULT 0 COMMENT 'Units reserved for orders/customers',
    `QuantityAvailable` INT NOT NULL DEFAULT 0 COMMENT 'QuantityOnHand - QuantityReserved',
    `ReorderLevel` INT NOT NULL DEFAULT 5 COMMENT 'Minimum threshold before low stock alert',
    `ReorderQuantity` INT NOT NULL DEFAULT 15 COMMENT 'Suggested quantity to restock',
    `Aisle` VARCHAR(30) DEFAULT 'A1',
    `Rack` VARCHAR(30) DEFAULT 'R1',
    `Shelf` VARCHAR(30) DEFAULT 'S1',
    `Bin` VARCHAR(30) DEFAULT 'B1',
    `BatchNumber` VARCHAR(60) DEFAULT NULL,
    `ConditionNotes` VARCHAR(255) DEFAULT NULL,
    `LastAuditedAt` DATETIME DEFAULT NULL,
    `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `UpdatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_wh_inv_warehouse` (`WarehouseID`),
    KEY `idx_wh_inv_listing` (`ListingID`),
    KEY `idx_wh_inv_qty` (`QuantityAvailable`, `ReorderLevel`),
    CONSTRAINT `fk_wh_inv_warehouse` FOREIGN KEY (`WarehouseID`) REFERENCES `warehouse` (`WarehouseID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Stock Movement / Inventory Transaction Log
CREATE TABLE IF NOT EXISTS `stock_movement` (
    `MovementID` INT AUTO_INCREMENT PRIMARY KEY,
    `MovementType` ENUM('Stock In', 'Stock Out', 'Transfer', 'Adjustment', 'Reservation Lock', 'Reservation Release', 'Damage/Loss', 'Return') NOT NULL,
    `ReferenceNumber` VARCHAR(100) NOT NULL,
    `ListingID` INT NOT NULL,
    `FromWarehouseID` INT DEFAULT NULL,
    `ToWarehouseID` INT DEFAULT NULL,
    `Quantity` INT NOT NULL,
    `UnitCost` DECIMAL(12,2) DEFAULT NULL,
    `Reason` VARCHAR(255) DEFAULT NULL,
    `Notes` TEXT DEFAULT NULL,
    `PerformedBy` VARCHAR(100) DEFAULT 'Admin',
    `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sm_listing` (`ListingID`),
    KEY `idx_sm_from_wh` (`FromWarehouseID`),
    KEY `idx_sm_to_wh` (`ToWarehouseID`),
    KEY `idx_sm_created` (`CreatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Physical Inventory Audit / Cycle Count Sessions
CREATE TABLE IF NOT EXISTS `inventory_audit` (
    `AuditID` INT AUTO_INCREMENT PRIMARY KEY,
    `AuditCode` VARCHAR(50) NOT NULL UNIQUE,
    `WarehouseID` INT NOT NULL,
    `AuditedBy` VARCHAR(100) NOT NULL,
    `Status` ENUM('Draft', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'In Progress',
    `TotalItemsCounted` INT DEFAULT 0,
    `TotalDiscrepancy` INT DEFAULT 0,
    `Notes` TEXT DEFAULT NULL,
    `AuditedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `CompletedAt` DATETIME DEFAULT NULL,
    KEY `idx_audit_wh` (`WarehouseID`),
    CONSTRAINT `fk_audit_wh` FOREIGN KEY (`WarehouseID`) REFERENCES `warehouse` (`WarehouseID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Inventory Audit Items Table
CREATE TABLE IF NOT EXISTS `inventory_audit_item` (
    `AuditItemID` INT AUTO_INCREMENT PRIMARY KEY,
    `AuditID` INT NOT NULL,
    `InventoryID` INT NOT NULL,
    `SystemQuantity` INT NOT NULL,
    `PhysicalQuantity` INT NOT NULL,
    `Discrepancy` INT NOT NULL DEFAULT 0,
    `DiscrepancyReason` VARCHAR(255) DEFAULT NULL,
    `Notes` TEXT DEFAULT NULL,
    KEY `idx_audit_item_audit` (`AuditID`),
    KEY `idx_audit_item_inv` (`InventoryID`),
    CONSTRAINT `fk_audit_item_audit` FOREIGN KEY (`AuditID`) REFERENCES `inventory_audit` (`AuditID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Insert Default Warehouses (if not already existing)
INSERT IGNORE INTO `warehouse` (`WarehouseID`, `WarehouseCode`, `WarehouseName`, `Address`, `City`, `Province`, `PostalCode`, `ContactPerson`, `ContactPhone`, `ContactEmail`, `StorageCapacity`, `Status`, `Notes`) VALUES
(1, 'WH-MNL-01', 'Manila Central Fulfillment Hub', '128 Chino Roces Ave, Makati Logistics Park', 'Makati City', 'Metro Manila', '1230', 'Carlos Mendoza', '+63 917 123 4567', 'manila.warehouse@kameraph.com', 12000, 'Active', 'Primary temperature-controlled warehouse for premium DSLR and mirrorless camera gear.'),
(2, 'WH-CEB-01', 'Cebu Regional Distribution Hub', 'Bldg 4, Mandaue Industrial Estate, Subangdaku', 'Mandaue City', 'Cebu', '6014', 'Rowena Santos', '+63 928 234 5678', 'cebu.warehouse@kameraph.com', 6500, 'Active', 'Central Visayas distribution hub for lenses, accessories, and camera bodies.'),
(3, 'WH-DVO-01', 'Davao Southern Hub', 'Km 7, Lanang Business Park, Lanang', 'Davao City', 'Davao del Sur', '8000', 'Arnel Fernandez', '+63 939 345 6789', 'davao.warehouse@kameraph.com', 5000, 'Active', 'Mindanao fulfillment depot for certified used camera gears and lighting kits.');
