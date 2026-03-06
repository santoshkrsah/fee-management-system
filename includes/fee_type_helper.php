<?php
/**
 * Fee Type Helper Functions
 * Centralized management of dynamic fee types
 */

/**
 * Get all active fee types ordered by sort_order
 * @param bool $includeInactive Include deactivated types
 * @return array Fee types array
 */
function getAllFeeTypes($includeInactive = false) {
    static $cache = [];
    $key = $includeInactive ? 'all' : 'active';

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    try {
        $db = getDB();
        $sql = "SELECT * FROM fee_types";
        if (!$includeInactive) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC";

        $cache[$key] = $db->fetchAll($sql);
        return $cache[$key];
    } catch (Exception $e) {
        error_log("Error fetching fee types: " . $e->getMessage());
        return [];
    }
}

/**
 * Get fee type by code
 * @param string $code Fee type code
 * @return array|null Fee type data or null
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
 * @param string $feeTypeCode Code of fee type
 * @return string|null Column name or null
 */
function getFeeTypeColumn($feeTypeCode) {
    $type = getFeeTypeByCode($feeTypeCode);
    return $type ? $type['column_name'] : null;
}

/**
 * Get all active fee type column names
 * @return array Array of column names
 */
function getFeeTypeColumns() {
    $types = getAllFeeTypes();
    return array_map(function($t) {
        return $t['column_name'];
    }, $types);
}

/**
 * Build comma-separated column list for SQL with optional table alias
 * @param string $tableAlias Optional table alias
 * @return string Comma-separated SQL columns
 */
function getFeeColumnsSql($tableAlias = '') {
    $cols = getFeeTypeColumns();
    $prefixed = array_map(function($c) use ($tableAlias) {
        return $tableAlias ? "{$tableAlias}.{$c}" : $c;
    }, $cols);
    return implode(', ', $prefixed);
}

/**
 * Build fee items array from payment record
 * Used for receipts and displays
 * @param array $paymentRecord Payment data
 * @param string $type 'paid' or 'structure'
 * @return array Formatted fee items
 */
function buildFeeItemsArray($paymentRecord, $type = 'paid') {
    $feeItems = [];
    $sn = 1;

    $feeTypes = getAllFeeTypes();
    foreach ($feeTypes as $feeType) {
        $columnKey = $feeType['column_name'];
        if ($type === 'paid') {
            $columnKey .= '_paid';
        }

        $amount = (float)($paymentRecord[$columnKey] ?? 0);

        if ($amount > 0) {
            $feeItems[] = [
                'sn' => $sn++,
                'code' => $feeType['code'],
                'name' => $feeType['label'],
                'amount' => $amount
            ];
        }
    }

    return $feeItems;
}

/**
 * Get fee types as JSON (for JavaScript)
 * @return string JSON encoded fee types
 */
function getFeeTypesJson() {
    $types = getAllFeeTypes();
    $json = [];
    foreach ($types as $type) {
        $json[$type['code']] = [
            'label' => $type['label'],
            'column' => $type['column_name'],
            'id' => $type['fee_type_id'],
            'description' => $type['description']
        ];
    }
    return json_encode($json);
}

/**
 * Get fee types as JavaScript variable initialization
 * @return string JavaScript code
 */
function getFeeTypesJavaScript() {
    $json = getFeeTypesJson();
    return "var feeTypes = " . $json . ";";
}

/**
 * Check if a fee type can be deleted
 * @param int $feeTypeId Fee type ID
 * @return array ['allowed' => bool, 'reason' => string]
 */
function canDeleteFeeType($feeTypeId) {
    try {
        $db = getDB();

        $feeType = $db->fetchOne(
            "SELECT * FROM fee_types WHERE fee_type_id = :id",
            ['id' => $feeTypeId]
        );

        if (!$feeType) {
            return ['allowed' => false, 'reason' => 'Fee type not found'];
        }

        // System-defined cannot be deleted
        if ($feeType['is_system_defined']) {
            return ['allowed' => false, 'reason' => 'System fee types cannot be deleted'];
        }

        // Check for existing payments
        $column = $feeType['column_name'];
        $count = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM fee_collection WHERE `{$column}_paid` > 0",
            []
        );

        if ((int)($count['cnt'] ?? 0) > 0) {
            return ['allowed' => false, 'reason' => "Cannot delete: " . $count['cnt'] . " payment records reference this type"];
        }

        // Check for fee structure definitions
        $structCount = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM fee_structure WHERE `{$column}` > 0",
            []
        );

        if ((int)($structCount['cnt'] ?? 0) > 0) {
            return ['allowed' => false, 'reason' => "Cannot delete: " . $structCount['cnt'] . " fee structures define this type"];
        }

        return ['allowed' => true, 'reason' => ''];

    } catch (Exception $e) {
        error_log("Error checking if fee type can be deleted: " . $e->getMessage());
        return ['allowed' => false, 'reason' => 'Error checking dependencies'];
    }
}

/**
 * Check if a fee type can be deactivated
 * @param int $feeTypeId Fee type ID
 * @return array ['allowed' => bool, 'reason' => string]
 */
function canDeactivateFeeType($feeTypeId) {
    try {
        $db = getDB();

        $feeType = $db->fetchOne(
            "SELECT * FROM fee_types WHERE fee_type_id = :id",
            ['id' => $feeTypeId]
        );

        if (!$feeType) {
            return ['allowed' => false, 'reason' => 'Fee type not found'];
        }

        // System-defined cannot be deactivated
        if ($feeType['is_system_defined']) {
            return ['allowed' => false, 'reason' => 'Cannot deactivate system fee types'];
        }

        return ['allowed' => true, 'reason' => ''];

    } catch (Exception $e) {
        error_log("Error checking if fee type can be deactivated: " . $e->getMessage());
        return ['allowed' => false, 'reason' => 'Error checking']);
    }
}

/**
 * Update fee type label/description
 * @param int $feeTypeId Fee type ID
 * @param string $label New label
 * @param string $description New description
 * @param int $sortOrder New sort order
 * @return bool Success
 */
function updateFeeType($feeTypeId, $label, $description = '', $sortOrder = null) {
    try {
        $db = getDB();

        $params = [
            'id' => $feeTypeId,
            'label' => sanitize($label)
        ];

        $query = "UPDATE fee_types SET label = :label";

        if ($description !== null) {
            $query .= ", description = :description";
            $params['description'] = sanitize($description);
        }

        if ($sortOrder !== null) {
            $query .= ", sort_order = :sort_order";
            $params['sort_order'] = (int)$sortOrder;
        }

        $query .= " WHERE fee_type_id = :id";

        $db->query($query, $params);

        // Clear cache
        clearFeeTypesCache();

        return true;

    } catch (Exception $e) {
        error_log("Error updating fee type: " . $e->getMessage());
        return false;
    }
}

/**
 * Deactivate a fee type (soft delete)
 * @param int $feeTypeId Fee type ID
 * @return bool Success
 */
function deactivateFeeType($feeTypeId) {
    try {
        $canDeactivate = canDeactivateFeeType($feeTypeId);
        if (!$canDeactivate['allowed']) {
            throw new Exception($canDeactivate['reason']);
        }

        $db = getDB();
        $db->query(
            "UPDATE fee_types SET is_active = 0 WHERE fee_type_id = :id",
            ['id' => $feeTypeId]
        );

        clearFeeTypesCache();
        return true;

    } catch (Exception $e) {
        error_log("Error deactivating fee type: " . $e->getMessage());
        return false;
    }
}

/**
 * Reactivate a deactivated fee type
 * @param int $feeTypeId Fee type ID
 * @return bool Success
 */
function reactivateFeeType($feeTypeId) {
    try {
        $db = getDB();
        $db->query(
            "UPDATE fee_types SET is_active = 1 WHERE fee_type_id = :id",
            ['id' => $feeTypeId]
        );

        clearFeeTypesCache();
        return true;

    } catch (Exception $e) {
        error_log("Error reactivating fee type: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear fee types cache
 */
function clearFeeTypesCache() {
    // Cache clearing handled by static array reset in getAllFeeTypes
    // This is a placeholder for future advanced caching
}

?>
