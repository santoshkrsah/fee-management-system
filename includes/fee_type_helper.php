<?php
/**
 * Fee Type Helper Functions
 * Centralized management of dynamic fee types
 */

/**
 * Internal cache store — using a function-scoped static reference lets
 * clearFeeTypesCache() actually wipe it.
 */
function &_feeTypesCache() {
    static $cache = ['active' => null, 'all' => null];
    return $cache;
}

/**
 * Ensure fee_types table exists and is seeded with default system types.
 * Called automatically by getAllFeeTypes() on first use.
 */
function ensureFeeTypesTable() {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $db = getDB();

        // Create table if not exists
        $db->query("CREATE TABLE IF NOT EXISTS `fee_types` (
            `fee_type_id`      INT PRIMARY KEY AUTO_INCREMENT,
            `code`             VARCHAR(50)  NOT NULL UNIQUE,
            `label`            VARCHAR(100) NOT NULL,
            `description`      TEXT,
            `is_active`        TINYINT(1)  NOT NULL DEFAULT 1,
            `sort_order`       INT         NOT NULL DEFAULT 0,
            `column_name`      VARCHAR(50) NOT NULL,
            `is_system_defined` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at`       TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
            `updated_at`       TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Seed default system-defined fee types (safe to run multiple times)
        $db->query("INSERT IGNORE INTO `fee_types`
            (`code`, `label`, `description`, `column_name`, `is_system_defined`, `sort_order`)
            VALUES
            ('tuition_fee',   'Tuition Fee',   'Core tuition charges for academic instruction',        'tuition_fee',   1, 1),
            ('exam_fee',      'Exam Fee',      'Examination fees including assessments and tests',     'exam_fee',      1, 2),
            ('library_fee',   'Library Fee',   'Library access and reference materials',               'library_fee',   1, 3),
            ('sports_fee',    'Sports Fee',    'Sports activities, facilities and programs',           'sports_fee',    1, 4),
            ('lab_fee',       'Lab Fee',       'Laboratory access and equipment usage',                'lab_fee',       1, 5),
            ('transport_fee', 'Transport Fee', 'School transportation and conveyance',                 'transport_fee', 1, 6),
            ('other_charges', 'Other Charges', 'Miscellaneous charges and fees',                       'other_charges', 1, 7)");

    } catch (Exception $e) {
        error_log("ensureFeeTypesTable failed: " . $e->getMessage());
    }
}

/**
 * Clear the in-memory fee types cache (call after any insert/update/delete).
 */
function clearFeeTypesCache() {
    $cache = &_feeTypesCache();
    $cache['active'] = null;
    $cache['all']    = null;
}

/**
 * Get all fee types ordered by sort_order.
 * @param bool $includeInactive Include deactivated types
 * @return array
 */
function getAllFeeTypes($includeInactive = false) {
    ensureFeeTypesTable();

    $cache = &_feeTypesCache();
    $key   = $includeInactive ? 'all' : 'active';

    if ($cache[$key] !== null) {
        return $cache[$key];
    }

    try {
        $db  = getDB();
        $sql = "SELECT * FROM fee_types";
        if (!$includeInactive) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, fee_type_id ASC";

        $cache[$key] = $db->fetchAll($sql);
        return $cache[$key];
    } catch (Exception $e) {
        error_log("Error fetching fee types: " . $e->getMessage());
        return [];
    }
}

/**
 * Get fee type by code
 */
function getFeeTypeByCode($code) {
    try {
        $db = getDB();
        return $db->fetchOne(
            "SELECT * FROM fee_types WHERE code = :code AND is_active = 1",
            ['code' => $code]
        );
    } catch (Exception $e) {
        error_log("Error fetching fee type by code: " . $e->getMessage());
        return null;
    }
}

/**
 * Get database column name for fee type code
 */
function getFeeTypeColumn($feeTypeCode) {
    $type = getFeeTypeByCode($feeTypeCode);
    return $type ? $type['column_name'] : null;
}

/**
 * Get all active fee type column names
 */
function getFeeTypeColumns() {
    $types = getAllFeeTypes();
    return array_map(function($t) { return $t['column_name']; }, $types);
}

/**
 * Build comma-separated column list for SQL with optional table alias
 */
function getFeeColumnsSql($tableAlias = '') {
    $cols = getFeeTypeColumns();
    $prefixed = array_map(function($c) use ($tableAlias) {
        return $tableAlias ? "{$tableAlias}.{$c}" : $c;
    }, $cols);
    return implode(', ', $prefixed);
}

/**
 * Build fee items array from a payment record (for receipts)
 * @param array  $paymentRecord
 * @param string $type  'paid' or 'structure'
 */
function buildFeeItemsArray($paymentRecord, $type = 'paid') {
    $feeItems = [];
    $sn = 1;

    foreach (getAllFeeTypes() as $feeType) {
        $col    = $feeType['column_name'];
        $colKey = $type === 'paid' ? $col . '_paid' : $col;
        $amount = (float)($paymentRecord[$colKey] ?? 0);

        if ($amount > 0) {
            $feeItems[] = [
                'sn'     => $sn++,
                'code'   => $feeType['code'],
                'name'   => $feeType['label'],
                'amount' => $amount
            ];
        }
    }

    return $feeItems;
}

/**
 * Get fee types as JSON string (for JavaScript)
 */
function getFeeTypesJson() {
    $json = [];
    foreach (getAllFeeTypes() as $type) {
        $json[$type['code']] = [
            'label'       => $type['label'],
            'column'      => $type['column_name'],
            'id'          => $type['fee_type_id'],
            'description' => $type['description']
        ];
    }
    return json_encode($json);
}

/**
 * Get fee types as JavaScript variable assignment
 */
function getFeeTypesJavaScript() {
    return "var feeTypes = " . getFeeTypesJson() . ";";
}

/**
 * Update a fee type label / description / sort order
 */
function updateFeeType($feeTypeId, $label, $description = '', $sortOrder = null) {
    try {
        $db     = getDB();
        $params = ['id' => (int)$feeTypeId, 'label' => trim($label)];
        $sql    = "UPDATE fee_types SET label = :label";

        if ($description !== null) {
            $sql .= ", description = :description";
            $params['description'] = trim($description);
        }
        if ($sortOrder !== null) {
            $sql .= ", sort_order = :sort_order";
            $params['sort_order'] = (int)$sortOrder;
        }
        $sql .= " WHERE fee_type_id = :id";

        $db->query($sql, $params);
        clearFeeTypesCache();
        return true;
    } catch (Exception $e) {
        error_log("Error updating fee type: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a fee type can be deactivated
 */
function canDeactivateFeeType($feeTypeId) {
    try {
        $db      = getDB();
        $feeType = $db->fetchOne(
            "SELECT * FROM fee_types WHERE fee_type_id = :id",
            ['id' => $feeTypeId]
        );

        if (!$feeType) {
            return ['allowed' => false, 'reason' => 'Fee type not found'];
        }
        return ['allowed' => true, 'reason' => ''];
    } catch (Exception $e) {
        return ['allowed' => false, 'reason' => 'Error checking dependencies'];
    }
}

/**
 * Check if a fee type can be deleted (custom types only, no payments)
 */
function canDeleteFeeType($feeTypeId) {
    try {
        $db      = getDB();
        $feeType = $db->fetchOne(
            "SELECT * FROM fee_types WHERE fee_type_id = :id",
            ['id' => $feeTypeId]
        );

        if (!$feeType) {
            return ['allowed' => false, 'reason' => 'Fee type not found'];
        }
        if ($feeType['is_system_defined']) {
            return ['allowed' => false, 'reason' => 'System fee types cannot be deleted'];
        }

        $col   = $feeType['column_name'];
        $count = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM fee_collection WHERE `{$col}_paid` > 0"
        );
        if ((int)($count['cnt'] ?? 0) > 0) {
            return ['allowed' => false, 'reason' => $count['cnt'] . ' payment record(s) reference this type'];
        }

        return ['allowed' => true, 'reason' => ''];
    } catch (Exception $e) {
        return ['allowed' => false, 'reason' => 'Error checking dependencies'];
    }
}

/**
 * Deactivate a fee type (soft delete)
 */
function deactivateFeeType($feeTypeId) {
    try {
        $check = canDeactivateFeeType($feeTypeId);
        if (!$check['allowed']) throw new Exception($check['reason']);

        $db = getDB();
        $db->query("UPDATE fee_types SET is_active = 0 WHERE fee_type_id = :id", ['id' => $feeTypeId]);
        clearFeeTypesCache();
        return true;
    } catch (Exception $e) {
        error_log("Error deactivating fee type: " . $e->getMessage());
        return false;
    }
}

/**
 * Reactivate a deactivated fee type
 */
function reactivateFeeType($feeTypeId) {
    try {
        $db = getDB();
        $db->query("UPDATE fee_types SET is_active = 1 WHERE fee_type_id = :id", ['id' => $feeTypeId]);
        clearFeeTypesCache();
        return true;
    } catch (Exception $e) {
        error_log("Error reactivating fee type: " . $e->getMessage());
        return false;
    }
}

// ── Dynamic schema helpers ─────────────────────────────────────────────────────

/**
 * Build the GENERATED ALWAYS AS expression for total_fee
 * (used in fee_structure and monthly_fee_structure).
 * e.g. `tuition_fee` + `exam_fee` + ...
 */
function buildTotalFeeExpression(array $columns) {
    return implode(' + ', array_map(function($c) { return "`{$c}`"; }, $columns));
}

/**
 * Build the GENERATED ALWAYS AS expression for total_paid (fee_collection).
 * e.g. `tuition_fee_paid` + `exam_fee_paid` + ... + `fine` - `discount`
 */
function buildTotalPaidExpression(array $columns) {
    $parts = array_map(function($c) { return "`{$c}_paid`"; }, $columns);
    return implode(' + ', $parts) . ' + `fine` - `discount`';
}

/**
 * Add a new custom fee type.
 * Adds columns to fee_structure, fee_collection, monthly_fee_structure
 * and rebuilds the generated total columns to include the new one.
 *
 * @param  string $label       Display label (e.g. "Activity Fee")
 * @param  string $description Optional description
 * @param  int    $sortOrder   Sort position (default 99 = after existing types)
 * @return int    New fee_type_id
 * @throws Exception on any failure
 */
function addFeeType($label, $description = '', $sortOrder = 99) {
    $label = trim($label);
    if ($label === '') throw new Exception('Fee type label cannot be empty.');
    if (strlen($label) > 100) throw new Exception('Label must be 100 characters or less.');

    // Generate safe snake_case column name, prefixed with "custom_"
    $slug    = preg_replace('/[^a-z0-9]+/', '_', strtolower($label));
    $slug    = trim($slug, '_');
    $colName = 'custom_' . $slug;
    if (strlen($colName) > 50) $colName = substr($colName, 0, 50);

    ensureFeeTypesTable();
    $db = getDB();

    // Ensure column name is unique
    $existing = $db->fetchOne(
        "SELECT fee_type_id FROM fee_types WHERE column_name = :col1 OR code = :col2",
        ['col1' => $colName, 'col2' => $colName]
    );
    if ($existing) {
        throw new Exception("A fee type with column name \"{$colName}\" already exists. Please choose a different label.");
    }

    // Build new generated expressions (current active cols + new col)
    $currentCols = array_column(getAllFeeTypes(false), 'column_name');
    $newCols     = array_merge($currentCols, [$colName]);

    $totalFeeExpr  = buildTotalFeeExpression($newCols);
    $totalPaidExpr = buildTotalPaidExpression($newCols);

    // Track tables altered for rollback on failure
    $altered = [];

    try {
        // fee_structure: add column first, then rebuild generated total
        $db->query("ALTER TABLE `fee_structure` ADD COLUMN `{$colName}` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        $db->query("ALTER TABLE `fee_structure` MODIFY COLUMN `total_fee` DECIMAL(10,2) GENERATED ALWAYS AS ({$totalFeeExpr}) STORED");
        $altered[] = 'fee_structure';

        // fee_collection: add _paid column, then rebuild generated total
        $db->query("ALTER TABLE `fee_collection` ADD COLUMN `{$colName}_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        $db->query("ALTER TABLE `fee_collection` MODIFY COLUMN `total_paid` DECIMAL(10,2) GENERATED ALWAYS AS ({$totalPaidExpr}) STORED");
        $altered[] = 'fee_collection';

        // monthly_fee_structure is optional — only alter if it exists
        $monthlyExists = $db->fetchOne(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'monthly_fee_structure'"
        );
        if ($monthlyExists) {
            $db->query("ALTER TABLE `monthly_fee_structure` ADD COLUMN `{$colName}` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
            $db->query("ALTER TABLE `monthly_fee_structure` MODIFY COLUMN `total_fee` DECIMAL(10,2) GENERATED ALWAYS AS ({$totalFeeExpr}) STORED");
            $altered[] = 'monthly_fee_structure';
        }

        // Register the new type in fee_types table
        $db->query("INSERT INTO fee_types
            (code, label, description, column_name, is_system_defined, sort_order, is_active)
            VALUES (:code, :label, :desc, :col, 0, :sort, 1)", [
            'code'  => $colName,
            'label' => $label,
            'desc'  => trim($description),
            'col'   => $colName,
            'sort'  => (int)$sortOrder,
        ]);

    } catch (Exception $e) {
        // Attempt to roll back any column additions that already succeeded
        foreach ($altered as $table) {
            try {
                $dropCol = ($table === 'fee_collection') ? "`{$colName}_paid`" : "`{$colName}`";
                $db->query("ALTER TABLE `{$table}` DROP COLUMN {$dropCol}");
            } catch (Exception $rollbackEx) {
                error_log("Rollback DROP COLUMN failed for {$table}: " . $rollbackEx->getMessage());
            }
        }
        throw $e;
    }

    clearFeeTypesCache();
    return (int)$db->lastInsertId();
}

/**
 * Permanently delete a custom fee type.
 * Drops its columns from fee_structure, fee_collection, monthly_fee_structure
 * and rebuilds the generated totals. Only allowed when no data references it.
 *
 * @param  int $feeTypeId
 * @throws Exception on any failure
 */
function deleteFeeType($feeTypeId) {
    ensureFeeTypesTable();
    $db = getDB();

    $feeType = $db->fetchOne(
        "SELECT * FROM fee_types WHERE fee_type_id = :id",
        ['id' => $feeTypeId]
    );
    if (!$feeType)                   throw new Exception('Fee type not found.');
    if ($feeType['is_system_defined']) throw new Exception('System fee types cannot be deleted.');

    $col = $feeType['column_name'];

    // Prevent deletion if any payments carry amounts for this type
    $pcount = $db->fetchOne("SELECT COUNT(*) AS cnt FROM fee_collection WHERE `{$col}_paid` > 0");
    if ((int)($pcount['cnt'] ?? 0) > 0) {
        throw new Exception("Cannot delete: {$pcount['cnt']} payment record(s) have amounts for this fee type.");
    }

    // Prevent deletion if any fee structures carry non-zero amounts
    $scount = $db->fetchOne("SELECT COUNT(*) AS cnt FROM fee_structure WHERE `{$col}` > 0");
    if ((int)($scount['cnt'] ?? 0) > 0) {
        throw new Exception("Cannot delete: {$scount['cnt']} fee structure(s) still have amounts for this fee type. Set them to 0 first.");
    }

    // Build expressions WITHOUT this column (for the rebuilt generated columns)
    clearFeeTypesCache();
    $remainingCols = array_values(array_filter(
        array_column(getAllFeeTypes(true), 'column_name'),
        function($c) use ($col) { return $c !== $col; }
    ));

    if (empty($remainingCols)) {
        throw new Exception('Cannot delete the last remaining fee type.');
    }

    $totalFeeExpr  = buildTotalFeeExpression($remainingCols);
    $totalPaidExpr = buildTotalPaidExpression($remainingCols);

    // fee_structure: rebuild generated total first, then drop column
    $db->query("ALTER TABLE `fee_structure` MODIFY COLUMN `total_fee` DECIMAL(10,2) GENERATED ALWAYS AS ({$totalFeeExpr}) STORED");
    $db->query("ALTER TABLE `fee_structure` DROP COLUMN `{$col}`");

    // fee_collection: rebuild generated total first, then drop _paid column
    $db->query("ALTER TABLE `fee_collection` MODIFY COLUMN `total_paid` DECIMAL(10,2) GENERATED ALWAYS AS ({$totalPaidExpr}) STORED");
    $db->query("ALTER TABLE `fee_collection` DROP COLUMN `{$col}_paid`");

    // monthly_fee_structure (only if it exists)
    $monthlyExists = $db->fetchOne(
        "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'monthly_fee_structure'"
    );
    if ($monthlyExists) {
        $db->query("ALTER TABLE `monthly_fee_structure` MODIFY COLUMN `total_fee` DECIMAL(10,2) GENERATED ALWAYS AS ({$totalFeeExpr}) STORED");
        $db->query("ALTER TABLE `monthly_fee_structure` DROP COLUMN `{$col}`");
    }

    // Remove from fee_types registry
    $db->query("DELETE FROM fee_types WHERE fee_type_id = :id", ['id' => $feeTypeId]);

    clearFeeTypesCache();
}
