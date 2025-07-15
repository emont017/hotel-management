<?php
/**
 * Centralized Audit Logging Functions
 * Used to track all user actions in the hotel management system
 */

/**
 * Log an audit event to the audit_logs table
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id ID of the user performing the action
 * @param string $action Description of the action performed
 * @param string|null $target_table Table affected by the action
 * @param int|null $target_id ID of the record affected
 * @param string|null $details Additional details about the action
 * @return bool True if logged successfully, false otherwise
 */
function log_audit_event($conn, $user_id, $action, $target_table = null, $target_id = null, $details = null) {
    try {
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, target_table, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $user_id, $action, $target_table, $target_id, $details);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (Exception $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user authentication events
 */
function log_auth_event($conn, $user_id, $action, $details = null) {
    return log_audit_event($conn, $user_id, $action, 'users', $user_id, $details);
}

/**
 * Log booking-related events
 */
function log_booking_event($conn, $user_id, $action, $booking_id, $details = null) {
    return log_audit_event($conn, $user_id, $action, 'bookings', $booking_id, $details);
}

/**
 * Log user management events
 */
function log_user_management_event($conn, $user_id, $action, $target_user_id, $details = null) {
    return log_audit_event($conn, $user_id, $action, 'users', $target_user_id, $details);
}

/**
 * Log payment events
 */
function log_payment_event($conn, $user_id, $action, $payment_id, $details = null) {
    return log_audit_event($conn, $user_id, $action, 'payments', $payment_id, $details);
}

/**
 * Log room status events
 */
function log_room_event($conn, $user_id, $action, $room_id, $details = null) {
    return log_audit_event($conn, $user_id, $action, 'rooms', $room_id, $details);
}

/**
 * Log system events (like night audit)
 */
function log_system_event($conn, $user_id, $action, $details = null) {
    return log_audit_event($conn, $user_id, $action, null, null, $details);
}

/**
 * Log financial events
 */
function log_financial_event($conn, $user_id, $action, $target_table, $target_id, $details = null) {
    return log_audit_event($conn, $user_id, $action, $target_table, $target_id, $details);
}

/**
 * Get audit logs with filtering options
 * 
 * @param mysqli $conn Database connection
 * @param array $filters Associative array of filters (user_id, action, target_table, date_from, date_to)
 * @param int $limit Number of records to return
 * @param int $offset Offset for pagination
 * @return array Array of audit log records
 */
function get_audit_logs($conn, $filters = [], $limit = 100, $offset = 0) {
    $where_conditions = [];
    $params = [];
    $param_types = "";
    
    // Build WHERE clause based on filters
    if (!empty($filters['user_id'])) {
        $where_conditions[] = "l.user_id = ?";
        $params[] = $filters['user_id'];
        $param_types .= "i";
    }
    
    if (!empty($filters['action'])) {
        $where_conditions[] = "l.action LIKE ?";
        $params[] = "%" . $filters['action'] . "%";
        $param_types .= "s";
    }
    
    if (!empty($filters['target_table'])) {
        $where_conditions[] = "l.target_table = ?";
        $params[] = $filters['target_table'];
        $param_types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(l.timestamp) >= ?";
        $params[] = $filters['date_from'];
        $param_types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(l.timestamp) <= ?";
        $params[] = $filters['date_to'];
        $param_types .= "s";
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $sql = "
        SELECT l.id, l.timestamp, l.action, l.target_table, l.target_id, l.details, 
               u.full_name as user_name, u.username, u.role
        FROM audit_logs l
        JOIN users u ON l.user_id = u.id
        {$where_clause}
        ORDER BY l.timestamp DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= "ii";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    return $logs;
}

/**
 * Get count of audit logs (for pagination)
 */
function get_audit_logs_count($conn, $filters = []) {
    $where_conditions = [];
    $params = [];
    $param_types = "";
    
    // Build WHERE clause based on filters (same as get_audit_logs)
    if (!empty($filters['user_id'])) {
        $where_conditions[] = "l.user_id = ?";
        $params[] = $filters['user_id'];
        $param_types .= "i";
    }
    
    if (!empty($filters['action'])) {
        $where_conditions[] = "l.action LIKE ?";
        $params[] = "%" . $filters['action'] . "%";
        $param_types .= "s";
    }
    
    if (!empty($filters['target_table'])) {
        $where_conditions[] = "l.target_table = ?";
        $params[] = $filters['target_table'];
        $param_types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(l.timestamp) >= ?";
        $params[] = $filters['date_from'];
        $param_types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(l.timestamp) <= ?";
        $params[] = $filters['date_to'];
        $param_types .= "s";
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $sql = "SELECT COUNT(*) as total FROM audit_logs l JOIN users u ON l.user_id = u.id {$where_clause}";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['total'];
}
?> 