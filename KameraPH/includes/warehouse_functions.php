<?php
/**
 * KameraPH Warehouse Inventory Helper Functions
 * Provides stock tracking, multi-warehouse location management,
 * movement logging, and automatic listing quantity synchronization.
 */

if (!function_exists('ensure_warehouse_tables_exist')) {
    function ensure_warehouse_tables_exist($conn) {
        static $checked = false;
        if ($checked || !$conn) {
            return;
        }

        // 1. Create warehouse table
        $conn->query("
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
                `StorageCapacity` INT DEFAULT 5000,
                `Status` ENUM('Active', 'Inactive', 'Under Maintenance') DEFAULT 'Active',
                `Notes` TEXT DEFAULT NULL,
                `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `UpdatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 2. Create warehouse_inventory table
        $conn->query("
            CREATE TABLE IF NOT EXISTS `warehouse_inventory` (
                `InventoryID` INT AUTO_INCREMENT PRIMARY KEY,
                `WarehouseID` INT NOT NULL,
                `ListingID` INT NOT NULL,
                `QuantityOnHand` INT NOT NULL DEFAULT 0,
                `QuantityReserved` INT NOT NULL DEFAULT 0,
                `QuantityAvailable` INT NOT NULL DEFAULT 0,
                `ReorderLevel` INT NOT NULL DEFAULT 5,
                `ReorderQuantity` INT NOT NULL DEFAULT 15,
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
                KEY `idx_wh_inv_qty` (`QuantityAvailable`, `ReorderLevel`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 3. Create stock_movement table
        $conn->query("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 4. Create inventory_audit table
        $conn->query("
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
                KEY `idx_audit_wh` (`WarehouseID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 5. Create inventory_audit_item table
        $conn->query("
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
                KEY `idx_audit_item_inv` (`InventoryID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 6. Seed initial warehouses if table is empty
        $whCountRes = $conn->query("SELECT COUNT(*) AS total FROM warehouse");
        if ($whCountRes) {
            $whCount = (int)($whCountRes->fetch_assoc()['total'] ?? 0);
            if ($whCount === 0) {
                $conn->query("
                    INSERT INTO `warehouse` (`WarehouseID`, `WarehouseCode`, `WarehouseName`, `Address`, `City`, `Province`, `PostalCode`, `ContactPerson`, `ContactPhone`, `ContactEmail`, `StorageCapacity`, `Status`, `Notes`) VALUES
                    (1, 'WH-MNL-01', 'Manila Central Fulfillment Hub', '128 Chino Roces Ave, Makati Logistics Park', 'Makati City', 'Metro Manila', '1230', 'Carlos Mendoza', '+63 917 123 4567', 'manila.warehouse@kameraph.com', 12000, 'Active', 'Primary temperature-controlled warehouse for premium DSLR and mirrorless camera gear.'),
                    (2, 'WH-CEB-01', 'Cebu Regional Distribution Hub', 'Bldg 4, Mandaue Industrial Estate, Subangdaku', 'Mandaue City', 'Cebu', '6014', 'Rowena Santos', '+63 928 234 5678', 'cebu.warehouse@kameraph.com', 6500, 'Active', 'Central Visayas distribution hub for lenses, accessories, and camera bodies.'),
                    (3, 'WH-DVO-01', 'Davao Southern Hub', 'Km 7, Lanang Business Park, Lanang', 'Davao City', 'Davao del Sur', '8000', 'Arnel Fernandez', '+63 939 345 6789', 'davao.warehouse@kameraph.com', 5000, 'Active', 'Mindanao fulfillment depot for certified used camera gears and lighting kits.')
                ");
            }
        }

        // 7. Seed warehouse inventory for existing product listings if they don't have warehouse entries yet
        $unlinkedListings = $conn->query("
            SELECT pl.ListingID, pl.Quantity, pl.Price
            FROM product_listing pl
            LEFT JOIN warehouse_inventory wi ON pl.ListingID = wi.ListingID
            WHERE wi.InventoryID IS NULL
        ");

        if ($unlinkedListings && $unlinkedListings->num_rows > 0) {
            $bins = [
                ['Aisle' => 'A1', 'Rack' => 'R1', 'Shelf' => 'S1', 'Bin' => 'B1'],
                ['Aisle' => 'A1', 'Rack' => 'R2', 'Shelf' => 'S2', 'Bin' => 'B2'],
                ['Aisle' => 'A2', 'Rack' => 'R1', 'Shelf' => 'S1', 'Bin' => 'B3'],
                ['Aisle' => 'B1', 'Rack' => 'R3', 'Shelf' => 'S2', 'Bin' => 'B4'],
                ['Aisle' => 'B2', 'Rack' => 'R2', 'Shelf' => 'S3', 'Bin' => 'B5']
            ];
            $binIdx = 0;

            while ($item = $unlinkedListings->fetch_assoc()) {
                $lid = (int)$item['ListingID'];
                $initialQty = max(1, (int)$item['Quantity']);
                $loc = $bins[$binIdx % count($bins)];
                $binIdx++;

                // Allocate stock to Manila Main Hub (WarehouseID 1)
                $conn->query("
                    INSERT INTO warehouse_inventory
                    (WarehouseID, ListingID, QuantityOnHand, QuantityReserved, QuantityAvailable, ReorderLevel, ReorderQuantity, Aisle, Rack, Shelf, Bin, BatchNumber)
                    VALUES
                    (1, {$lid}, {$initialQty}, 0, {$initialQty}, 3, 10, '{$loc['Aisle']}', '{$loc['Rack']}', '{$loc['Shelf']}', '{$loc['Bin']}', 'BATCH-INIT-01')
                ");

                // Log initial stock-in movement
                $refNo = 'INIT-' . str_pad((string)$lid, 5, '0', STR_PAD_LEFT);
                $unitCost = (float)$item['Price'] * 0.75;
                $conn->query("
                    INSERT INTO stock_movement
                    (MovementType, ReferenceNumber, ListingID, ToWarehouseID, Quantity, UnitCost, Reason, Notes, PerformedBy)
                    VALUES
                    ('Stock In', '{$refNo}', {$lid}, 1, {$initialQty}, {$unitCost}, 'Initial Warehouse Ingestion', 'Auto-migrated from existing catalog listing', 'System Migration')
                ");
            }
        }

        $checked = true;
    }
}

/**
 * Get Global Warehouse KPI Statistics
 */
if (!function_exists('get_warehouse_stats')) {
    function get_warehouse_stats($conn) {
        ensure_warehouse_tables_exist($conn);

        $stats = [
            'total_warehouses' => 0,
            'active_warehouses' => 0,
            'total_items_in_stock' => 0,
            'total_stock_reserved' => 0,
            'total_stock_available' => 0,
            'low_stock_count' => 0,
            'out_of_stock_count' => 0,
            'total_movements' => 0,
            'stock_valuation' => 0.0
        ];

        // Warehouse counts
        $res = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN Status = 'Active' THEN 1 ELSE 0 END) AS active_cnt FROM warehouse");
        if ($res && $row = $res->fetch_assoc()) {
            $stats['total_warehouses'] = (int)($row['total'] ?? 0);
            $stats['active_warehouses'] = (int)($row['active_cnt'] ?? 0);
        }

        // Inventory totals
        $res = $conn->query("
            SELECT 
                SUM(QuantityOnHand) AS total_on_hand,
                SUM(QuantityReserved) AS total_reserved,
                SUM(QuantityAvailable) AS total_available,
                SUM(CASE WHEN QuantityAvailable > 0 AND QuantityAvailable <= ReorderLevel THEN 1 ELSE 0 END) AS low_count,
                SUM(CASE WHEN QuantityAvailable <= 0 THEN 1 ELSE 0 END) AS out_count
            FROM warehouse_inventory
        ");
        if ($res && $row = $res->fetch_assoc()) {
            $stats['total_items_in_stock'] = (int)($row['total_on_hand'] ?? 0);
            $stats['total_stock_reserved'] = (int)($row['total_reserved'] ?? 0);
            $stats['total_stock_available'] = (int)($row['total_available'] ?? 0);
            $stats['low_stock_count'] = (int)($row['low_count'] ?? 0);
            $stats['out_of_stock_count'] = (int)($row['out_count'] ?? 0);
        }

        // Valuation (QuantityOnHand * Listing Price)
        $res = $conn->query("
            SELECT SUM(wi.QuantityOnHand * pl.Price) AS val
            FROM warehouse_inventory wi
            INNER JOIN product_listing pl ON wi.ListingID = pl.ListingID
        ");
        if ($res && $row = $res->fetch_assoc()) {
            $stats['stock_valuation'] = (float)($row['val'] ?? 0.0);
        }

        // Total movements
        $res = $conn->query("SELECT COUNT(*) AS total FROM stock_movement");
        if ($res && $row = $res->fetch_assoc()) {
            $stats['total_movements'] = (int)($row['total'] ?? 0);
        }

        return $stats;
    }
}

/**
 * Get All Warehouses with live utilization
 */
if (!function_exists('get_warehouses')) {
    function get_warehouses($conn, $activeOnly = false) {
        ensure_warehouse_tables_exist($conn);

        $where = $activeOnly ? "WHERE w.Status = 'Active'" : "";
        $sql = "
            SELECT w.*,
                   COALESCE(SUM(wi.QuantityOnHand), 0) AS CurrentUnits,
                   COUNT(DISTINCT wi.ListingID) AS DistinctProductsCount
            FROM warehouse w
            LEFT JOIN warehouse_inventory wi ON w.WarehouseID = wi.WarehouseID
            {$where}
            GROUP BY w.WarehouseID
            ORDER BY w.Status ASC, w.WarehouseName ASC
        ";

        $res = $conn->query($sql);
        $warehouses = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $cap = max(1, (int)$row['StorageCapacity']);
                $units = (int)$row['CurrentUnits'];
                $row['UtilizationRate'] = min(100, round(($units / $cap) * 100, 1));
                $warehouses[] = $row;
            }
        }
        return $warehouses;
    }
}

/**
 * Get Single Warehouse by ID
 */
if (!function_exists('get_warehouse_by_id')) {
    function get_warehouse_by_id($conn, $warehouseId) {
        ensure_warehouse_tables_exist($conn);

        $stmt = $conn->prepare("
            SELECT w.*,
                   COALESCE(SUM(wi.QuantityOnHand), 0) AS CurrentUnits,
                   COUNT(DISTINCT wi.ListingID) AS DistinctProductsCount
            FROM warehouse w
            LEFT JOIN warehouse_inventory wi ON w.WarehouseID = wi.WarehouseID
            WHERE w.WarehouseID = ?
            GROUP BY w.WarehouseID
        ");
        $stmt->bind_param("i", $warehouseId);
        $stmt->execute();
        $wh = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($wh) {
            $cap = max(1, (int)$wh['StorageCapacity']);
            $units = (int)$wh['CurrentUnits'];
            $wh['UtilizationRate'] = min(100, round(($units / $cap) * 100, 1));
        }
        return $wh;
    }
}

/**
 * Synchronize Product Listing Quantity from Warehouse Available Stock
 */
if (!function_exists('sync_listing_quantity')) {
    function sync_listing_quantity($conn, $listingId) {
        $listingId = (int)$listingId;
        if ($listingId <= 0) return 0;

        $res = $conn->query("
            SELECT COALESCE(SUM(QuantityAvailable), 0) AS TotalAvail
            FROM warehouse_inventory
            WHERE ListingID = {$listingId}
        ");
        $avail = 0;
        if ($res && $row = $res->fetch_assoc()) {
            $avail = max(0, (int)$row['TotalAvail']);
        }

        $stmt = $conn->prepare("UPDATE product_listing SET Quantity = ? WHERE ListingID = ?");
        $stmt->bind_param("ii", $avail, $listingId);
        $stmt->execute();
        $stmt->close();

        return $avail;
    }
}

/**
 * Record a Stock Movement and update inventory counts
 */
if (!function_exists('record_stock_movement')) {
    function record_stock_movement($conn, $data) {
        ensure_warehouse_tables_exist($conn);

        $type = $data['MovementType'] ?? 'Stock In';
        $ref = trim($data['ReferenceNumber'] ?? ('MOV-' . date('YmdHis')));
        $listingId = (int)($data['ListingID'] ?? 0);
        $fromWh = !empty($data['FromWarehouseID']) ? (int)$data['FromWarehouseID'] : null;
        $toWh = !empty($data['ToWarehouseID']) ? (int)$data['ToWarehouseID'] : null;
        $qty = (int)($data['Quantity'] ?? 0);
        $unitCost = isset($data['UnitCost']) ? (float)$data['UnitCost'] : null;
        $reason = trim($data['Reason'] ?? '');
        $notes = trim($data['Notes'] ?? '');
        $performedBy = trim($data['PerformedBy'] ?? 'Admin');

        if ($listingId <= 0 || $qty <= 0) {
            throw new InvalidArgumentException("Listing ID and positive Quantity are required.");
        }

        $targetWh = $toWh ?: $fromWh;
        if (!$targetWh) {
            throw new InvalidArgumentException("At least one warehouse must be specified.");
        }

        // Find or create inventory row
        $invStmt = $conn->prepare("SELECT * FROM warehouse_inventory WHERE WarehouseID = ? AND ListingID = ? LIMIT 1");
        $invStmt->bind_param("ii", $targetWh, $listingId);
        $invStmt->execute();
        $inv = $invStmt->get_result()->fetch_assoc();
        $invStmt->close();

        if (!$inv) {
            $insInv = $conn->prepare("
                INSERT INTO warehouse_inventory
                (WarehouseID, ListingID, QuantityOnHand, QuantityReserved, QuantityAvailable, ReorderLevel, ReorderQuantity, Aisle, Rack, Shelf, Bin)
                VALUES (?, ?, 0, 0, 0, 5, 15, 'A1', 'R1', 'S1', 'B1')
            ");
            $insInv->bind_param("ii", $targetWh, $listingId);
            $insInv->execute();
            $invId = $insInv->insert_id;
            $insInv->close();

            $inv = [
                'InventoryID' => $invId,
                'QuantityOnHand' => 0,
                'QuantityReserved' => 0,
                'QuantityAvailable' => 0
            ];
        }

        $onHand = (int)$inv['QuantityOnHand'];
        $reserved = (int)$inv['QuantityReserved'];

        if ($type === 'Stock In' || $type === 'Return') {
            $onHand += $qty;
        } elseif ($type === 'Stock Out' || $type === 'Damage/Loss') {
            if ($onHand < $qty) {
                throw new RuntimeException("Insufficient stock in warehouse. Currently on hand: {$onHand}, requested: {$qty}.");
            }
            $onHand -= $qty;
        } elseif ($type === 'Adjustment') {
            // In Adjustment, qty can be new total or delta depending on mode. Here caller passes target new QuantityOnHand
            if (isset($data['NewOnHand'])) {
                $onHand = max(0, (int)$data['NewOnHand']);
            } else {
                $onHand += $qty; // delta
            }
        } elseif ($type === 'Reservation Lock') {
            $reserved += $qty;
        } elseif ($type === 'Reservation Release') {
            $reserved = max(0, $reserved - $qty);
        }

        $avail = max(0, $onHand - $reserved);

        // Update inventory record
        $upd = $conn->prepare("UPDATE warehouse_inventory SET QuantityOnHand = ?, QuantityReserved = ?, QuantityAvailable = ?, UpdatedAt = NOW() WHERE InventoryID = ?");
        $upd->bind_param("iiii", $onHand, $reserved, $avail, $inv['InventoryID']);
        $upd->execute();
        $upd->close();

        // Record movement in stock_movement log
        $insMov = $conn->prepare("
            INSERT INTO stock_movement
            (MovementType, ReferenceNumber, ListingID, FromWarehouseID, ToWarehouseID, Quantity, UnitCost, Reason, Notes, PerformedBy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insMov->bind_param("ssiiidssss", $type, $ref, $listingId, $fromWh, $toWh, $qty, $unitCost, $reason, $notes, $performedBy);
        $insMov->execute();
        $movementId = $insMov->insert_id;
        $insMov->close();

        // Synchronize product_listing.Quantity
        sync_listing_quantity($conn, $listingId);

        return $movementId;
    }
}

/**
 * Transfer Stock Between Two Warehouses
 */
if (!function_exists('transfer_warehouse_stock')) {
    function transfer_warehouse_stock($conn, $listingId, $fromWh, $toWh, $qty, $ref = '', $notes = '', $performedBy = 'Admin') {
        ensure_warehouse_tables_exist($conn);

        $listingId = (int)$listingId;
        $fromWh = (int)$fromWh;
        $toWh = (int)$toWh;
        $qty = (int)$qty;

        if ($listingId <= 0 || $fromWh <= 0 || $toWh <= 0 || $qty <= 0) {
            throw new InvalidArgumentException("Valid listing, source, destination warehouse, and quantity are required.");
        }
        if ($fromWh === $toWh) {
            throw new InvalidArgumentException("Source and Destination warehouse cannot be the same.");
        }

        // Check source stock
        $stmt = $conn->prepare("SELECT * FROM warehouse_inventory WHERE WarehouseID = ? AND ListingID = ?");
        $stmt->bind_param("ii", $fromWh, $listingId);
        $stmt->execute();
        $srcInv = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$srcInv || (int)$srcInv['QuantityAvailable'] < $qty) {
            $currAvail = $srcInv ? (int)$srcInv['QuantityAvailable'] : 0;
            throw new RuntimeException("Insufficient available stock at source warehouse. Available: {$currAvail}, requested transfer: {$qty}.");
        }

        if (empty($ref)) {
            $ref = 'TR-' . date('Ymd-His');
        }

        // Begin transaction
        $conn->begin_transaction();
        try {
            // 1. Deduct from source warehouse
            $srcOnHand = (int)$srcInv['QuantityOnHand'] - $qty;
            $srcRes = (int)$srcInv['QuantityReserved'];
            $srcAvail = max(0, $srcOnHand - $srcRes);

            $updSrc = $conn->prepare("UPDATE warehouse_inventory SET QuantityOnHand = ?, QuantityAvailable = ? WHERE InventoryID = ?");
            $updSrc->bind_param("iii", $srcOnHand, $srcAvail, $srcInv['InventoryID']);
            $updSrc->execute();
            $updSrc->close();

            // 2. Add to destination warehouse (find or create row)
            $destStmt = $conn->prepare("SELECT * FROM warehouse_inventory WHERE WarehouseID = ? AND ListingID = ?");
            $destStmt->bind_param("ii", $toWh, $listingId);
            $destStmt->execute();
            $destInv = $destStmt->get_result()->fetch_assoc();
            $destStmt->close();

            if ($destInv) {
                $destOnHand = (int)$destInv['QuantityOnHand'] + $qty;
                $destRes = (int)$destInv['QuantityReserved'];
                $destAvail = max(0, $destOnHand - $destRes);

                $updDest = $conn->prepare("UPDATE warehouse_inventory SET QuantityOnHand = ?, QuantityAvailable = ? WHERE InventoryID = ?");
                $updDest->bind_param("iii", $destOnHand, $destAvail, $destInv['InventoryID']);
                $updDest->execute();
                $updDest->close();
            } else {
                $destOnHand = $qty;
                $destAvail = $qty;
                $insDest = $conn->prepare("
                    INSERT INTO warehouse_inventory
                    (WarehouseID, ListingID, QuantityOnHand, QuantityReserved, QuantityAvailable, ReorderLevel, ReorderQuantity, Aisle, Rack, Shelf, Bin)
                    VALUES (?, ?, ?, 0, ?, 5, 15, 'A1', 'R1', 'S1', 'B1')
                ");
                $insDest->bind_param("iiii", $toWh, $listingId, $destOnHand, $destAvail);
                $insDest->execute();
                $insDest->close();
            }

            // 3. Log movement
            $movStmt = $conn->prepare("
                INSERT INTO stock_movement
                (MovementType, ReferenceNumber, ListingID, FromWarehouseID, ToWarehouseID, Quantity, Reason, Notes, PerformedBy)
                VALUES ('Transfer', ?, ?, ?, ?, ?, 'Inter-Warehouse Stock Transfer', ?, ?)
            ");
            $movStmt->bind_param("siiiiss", $ref, $listingId, $fromWh, $toWh, $qty, $notes, $performedBy);
            $movStmt->execute();
            $movStmt->close();

            // 4. Commit
            $conn->commit();

            // 5. Sync product listing total
            sync_listing_quantity($conn, $listingId);

            return true;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}

/**
 * Get Warehouse Inventory Items with Full Product Details
 */
if (!function_exists('get_warehouse_inventory')) {
    function get_warehouse_inventory($conn, $filters = []) {
        ensure_warehouse_tables_exist($conn);

        $where = [];
        $params = [];
        $types = "";

        if (!empty($filters['warehouse_id'])) {
            $where[] = "wi.WarehouseID = ?";
            $params[] = (int)$filters['warehouse_id'];
            $types .= "i";
        }

        if (!empty($filters['listing_id'])) {
            $where[] = "wi.ListingID = ?";
            $params[] = (int)$filters['listing_id'];
            $types .= "i";
        }

        if (!empty($filters['search'])) {
            $term = "%" . trim($filters['search']) . "%";
            $where[] = "(pl.Title LIKE ? OR pm.ModelName LIKE ? OR b.BrandName LIKE ? OR wi.BatchNumber LIKE ? OR CONCAT(wi.Aisle, '-', wi.Rack, '-', wi.Shelf, '-', wi.Bin) LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= "sssss";
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'low_stock') {
                $where[] = "wi.QuantityAvailable > 0 AND wi.QuantityAvailable <= wi.ReorderLevel";
            } elseif ($filters['status'] === 'out_of_stock') {
                $where[] = "wi.QuantityAvailable <= 0";
            } elseif ($filters['status'] === 'in_stock') {
                $where[] = "wi.QuantityAvailable > wi.ReorderLevel";
            }
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "
            SELECT wi.*,
                   w.WarehouseCode, w.WarehouseName, w.City AS WarehouseCity,
                   pl.Title AS ListingTitle, pl.Price, pl.Condition, pl.Status AS ListingStatus,
                   pm.ModelName, b.BrandName, c.CategoryName,
                   (SELECT ImageURL FROM listing_image WHERE ListingID = pl.ListingID ORDER BY IsPrimary DESC, DisplayOrder ASC LIMIT 1) AS PrimaryImage
            FROM warehouse_inventory wi
            INNER JOIN warehouse w ON wi.WarehouseID = w.WarehouseID
            INNER JOIN product_listing pl ON wi.ListingID = pl.ListingID
            INNER JOIN product_model pm ON pl.ModelID = pm.ModelID
            INNER JOIN brand b ON pm.BrandID = b.BrandID
            INNER JOIN category c ON pm.CategoryID = c.CategoryID
            {$whereSql}
            ORDER BY wi.QuantityAvailable ASC, pl.Title ASC
        ";

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
        } else {
            $res = $conn->query($sql);
            $items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }

        return $items;
    }
}

/**
 * Get Low Stock Alert Items
 */
if (!function_exists('get_low_stock_items')) {
    function get_low_stock_items($conn, $warehouseId = null) {
        $filters = ['status' => 'low_stock'];
        if ($warehouseId) {
            $filters['warehouse_id'] = $warehouseId;
        }
        return get_warehouse_inventory($conn, $filters);
    }
}

/**
 * Get Stock Movements Log
 */
if (!function_exists('get_stock_movements')) {
    function get_stock_movements($conn, $limit = 50, $filters = []) {
        ensure_warehouse_tables_exist($conn);

        $where = [];
        $params = [];
        $types = "";

        if (!empty($filters['movement_type'])) {
            $where[] = "sm.MovementType = ?";
            $params[] = $filters['movement_type'];
            $types .= "s";
        }

        if (!empty($filters['warehouse_id'])) {
            $where[] = "(sm.FromWarehouseID = ? OR sm.ToWarehouseID = ?)";
            $params[] = (int)$filters['warehouse_id'];
            $params[] = (int)$filters['warehouse_id'];
            $types .= "ii";
        }

        if (!empty($filters['listing_id'])) {
            $where[] = "sm.ListingID = ?";
            $params[] = (int)$filters['listing_id'];
            $types .= "i";
        }

        if (!empty($filters['search'])) {
            $term = "%" . trim($filters['search']) . "%";
            $where[] = "(sm.ReferenceNumber LIKE ? OR pl.Title LIKE ? OR sm.PerformedBy LIKE ? OR sm.Reason LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= "ssss";
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $limit = max(1, min(200, (int)$limit));

        $sql = "
            SELECT sm.*,
                   pl.Title AS ListingTitle, pl.Price,
                   w_from.WarehouseName AS FromWarehouseName, w_from.WarehouseCode AS FromWarehouseCode,
                   w_to.WarehouseName AS ToWarehouseName, w_to.WarehouseCode AS ToWarehouseCode,
                   pm.ModelName, b.BrandName
            FROM stock_movement sm
            INNER JOIN product_listing pl ON sm.ListingID = pl.ListingID
            LEFT JOIN product_model pm ON pl.ModelID = pm.ModelID
            LEFT JOIN brand b ON pm.BrandID = b.BrandID
            LEFT JOIN warehouse w_from ON sm.FromWarehouseID = w_from.WarehouseID
            LEFT JOIN warehouse w_to ON sm.ToWarehouseID = w_to.WarehouseID
            {$whereSql}
            ORDER BY sm.MovementID DESC
            LIMIT {$limit}
        ";

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
        } else {
            $res = $conn->query($sql);
            $items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }

        return $items;
    }
}
