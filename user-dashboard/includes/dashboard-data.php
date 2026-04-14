<?php
if (!function_exists('ud_h')) {
    function ud_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ud_report_accent')) {
    function ud_report_accent($type)
    {
        switch ($type) {
            case 'Intensive Report':
                return '#2f80ed';
            case 'Chemical Report':
                return '#16a085';
            case 'Machine Report':
                return '#f39c12';
            case 'Attendance Report':
                return '#8e44ad';
            case 'Normal Report':
            default:
                return '#3c8dbc';
        }
    }
}

if (!function_exists('ud_report_status_badge')) {
    function ud_report_status_badge($status)
    {
        return strtolower((string) $status) === 'active' ? 'text-bg-success' : 'text-bg-secondary';
    }
}

if (!function_exists('ud_format_datetime')) {
    function ud_format_datetime($value)
    {
        if (!$value) {
            return '-';
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? date('d M Y, h:i A', $timestamp) : ud_h($value);
    }
}

if (!function_exists('ud_report_page_url')) {
    function ud_report_page_url($reportType)
    {
        $map = [
            'Normal Report' => 'normal-report.php',
            'Intensive Report' => 'intensive-report.php',
            'Chemical Report' => 'chemical-report.php',
            'Machine Report' => 'machine-report.php',
            'Attendance Report' => 'attendance-report.php',
        ];

        return $map[$reportType] ?? 'index.php';
    }
}

if (!function_exists('ud_list_users')) {
    function ud_list_users($conn)
    {
        $users = [];
        $sql = '
            SELECT
                u.user_id,
                COALESCE(NULLIF(u.user_name, \'\'), NULLIF(u.full_name, \'\'), u.username, CONCAT(\'User #\', u.user_id)) AS display_name,
                u.role,
                u.status,
                s.station_name
            FROM Mcc_users u
            LEFT JOIN Mcc_stations s ON s.station_id = u.station_id
            ORDER BY display_name ASC
        ';

        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }

        return $users;
    }
}

if (!function_exists('ud_pick_selected_user')) {
    function ud_pick_selected_user(array $users, $selectedUserId)
    {
        $selectedUser = null;
        $selectedUserId = (int) $selectedUserId;

        foreach ($users as $userRow) {
            if ((int) $userRow['user_id'] === $selectedUserId) {
                $selectedUser = $userRow;
                break;
            }
        }

        if ($selectedUser === null && count($users) > 0) {
            $selectedUser = $users[0];
            $selectedUserId = (int) $selectedUser['user_id'];
        }

        return [$selectedUser, $selectedUserId];
    }
}

if (!function_exists('ud_load_profile')) {
    function ud_load_profile($conn, $selectedUserId)
    {
        $profile = null;
        $sql = '
            SELECT
                u.user_id,
                u.user_name,
                u.username,
                u.email,
                u.full_name,
                u.phone,
                u.designation,
                u.address,
                u.role,
                u.status,
                u.start_date,
                u.end_date,
                s.station_name,
                d.division_name,
                z.zone_name
            FROM Mcc_users u
            LEFT JOIN Mcc_stations s ON s.station_id = u.station_id
            LEFT JOIN Mcc_divisions d ON d.division_id = s.division_id
            LEFT JOIN Mcc_zones z ON z.zone_id = d.zone_id
            WHERE u.user_id = ?
            LIMIT 1
        ';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $selectedUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            $profile = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        }

        return $profile;
    }
}

if (!function_exists('ud_load_contract')) {
    function ud_load_contract($conn, $selectedUserId)
    {
        $contract = null;
        $sql = '
            SELECT
                agreement_no,
                agreement_date,
                contractor_name,
                train_no_count,
                amount,
                no_of_years,
                contract_start_date,
                contract_end_date,
                status
            FROM Mcc_contract_details
            WHERE user_id = ?
            ORDER BY contract_end_date DESC, contract_id DESC
            LIMIT 1
        ';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $selectedUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            $contract = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        }

        return $contract;
    }
}

if (!function_exists('ud_load_reports')) {
    function ud_load_reports($conn, $selectedUserId, $reportTypeFilter = null)
    {
        $reports = [];
        $sql = '
            SELECT
                r.report_id,
                r.report_name,
                r.report_type,
                r.weight_percent,
                r.description,
                r.status,
                COUNT(p.parameter_id) AS parameter_count,
                SUM(CASE WHEN p.status = \'Active\' THEN 1 ELSE 0 END) AS active_parameter_count,
                MAX(p.assigned_at) AS last_assignment_at
            FROM Mcc_reports r
            LEFT JOIN Mcc_parameters p
                ON p.report_id = r.report_id
               AND p.user_id = r.user_id
            WHERE r.user_id = ?
        ';

        $types = ['i'];
        $params = [$selectedUserId];

        if ($reportTypeFilter !== null && $reportTypeFilter !== '') {
            $sql .= ' AND r.report_type = ?';
            $types[] = 's';
            $params[] = $reportTypeFilter;
        }

        $sql .= '
            GROUP BY
                r.report_id,
                r.report_name,
                r.report_type,
                r.weight_percent,
                r.description,
                r.status
            ORDER BY FIELD(r.report_type, \'Normal Report\', \'Intensive Report\', \'Chemical Report\', \'Machine Report\', \'Attendance Report\'), r.report_name ASC
        ';

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(implode('', $types), ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $reports[] = $row;
                }
            }
            $stmt->close();
        }

        return $reports;
    }
}

if (!function_exists('ud_load_parameters')) {
    function ud_load_parameters($conn, $selectedUserId, $reportTypeFilter = null)
    {
        $parameters = [];
        $sql = '
            SELECT
                p.parameter_id,
                p.parameter_name,
                p.category,
                p.status,
                p.assigned_at,
                r.report_name,
                r.report_type
            FROM Mcc_parameters p
            INNER JOIN Mcc_reports r ON r.report_id = p.report_id
            WHERE p.user_id = ?
        ';

        $types = ['i'];
        $params = [$selectedUserId];

        if ($reportTypeFilter !== null && $reportTypeFilter !== '') {
            $sql .= ' AND r.report_type = ?';
            $types[] = 's';
            $params[] = $reportTypeFilter;
        }

        $sql .= '
            ORDER BY r.report_type ASC, r.report_name ASC, p.parameter_name ASC
        ';

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(implode('', $types), ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $parameters[] = $row;
                }
            }
            $stmt->close();
        }

        return $parameters;
    }
}

if (!function_exists('ud_load_recent_activity')) {
    function ud_load_recent_activity($conn, $selectedUserId, $reportTypeFilter = null)
    {
        $activity = [];
        if ($reportTypeFilter === 'Normal Report') {
            $sql = '
                SELECT
                    n.created_at,
                    r.report_name,
                    r.report_type,
                    p.parameter_name,
                    n.train_no,
                    n.`value`
                FROM Mcc_normal_report_data n
                INNER JOIN Mcc_parameters p ON p.parameter_id = n.parameter_id
                INNER JOIN Mcc_reports r ON r.report_id = p.report_id
                WHERE n.user_id = ? AND r.report_type = ?
                ORDER BY n.created_at DESC
                LIMIT 8
            ';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('is', $selectedUserId, $reportTypeFilter);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $row['source_type'] = 'Normal Report';
                        $activity[] = $row;
                    }
                }
                $stmt->close();
            }
        } elseif ($reportTypeFilter === 'Intensive Report') {
            $sql = '
                SELECT
                    i.created_at,
                    r.report_name,
                    r.report_type,
                    p.parameter_name,
                    i.train_no,
                    i.`value`
                FROM Mcc_intensive_report_data i
                INNER JOIN Mcc_parameters p ON p.parameter_id = i.parameter_id
                INNER JOIN Mcc_reports r ON r.report_id = p.report_id
                WHERE i.user_id = ? AND r.report_type = ?
                ORDER BY i.created_at DESC
                LIMIT 8
            ';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('is', $selectedUserId, $reportTypeFilter);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $row['source_type'] = 'Intensive Report';
                        $activity[] = $row;
                    }
                }
                $stmt->close();
            }
        }

        return $activity;
    }
}

if (!function_exists('ud_load_dashboard_context')) {
    function ud_load_dashboard_context($conn, $selectedUserId, $reportTypeFilter = null)
    {
        $users = ud_list_users($conn);
        list($selectedUser, $resolvedUserId) = ud_pick_selected_user($users, $selectedUserId);

        $profile = $resolvedUserId > 0 ? ud_load_profile($conn, $resolvedUserId) : null;
        $contract = $resolvedUserId > 0 ? ud_load_contract($conn, $resolvedUserId) : null;
        $reports = $resolvedUserId > 0 ? ud_load_reports($conn, $resolvedUserId, $reportTypeFilter) : [];
        $parameters = $resolvedUserId > 0 ? ud_load_parameters($conn, $resolvedUserId, $reportTypeFilter) : [];
        $recentActivity = $resolvedUserId > 0 ? ud_load_recent_activity($conn, $resolvedUserId, $reportTypeFilter) : [];

        $trainCount = 0;
        if ($resolvedUserId > 0) {
            $trainCountSql = 'SELECT COUNT(*) AS total_count FROM Mcc_train_information WHERE user_id = ?';
            $trainCountStmt = $conn->prepare($trainCountSql);
            if ($trainCountStmt) {
                $trainCountStmt->bind_param('i', $resolvedUserId);
                $trainCountStmt->execute();
                $trainCountResult = $trainCountStmt->get_result();
                if ($trainCountResult && ($trainRow = $trainCountResult->fetch_assoc())) {
                    $trainCount = (int) $trainRow['total_count'];
                }
                $trainCountStmt->close();
            }
        }

        return [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'selectedUserId' => $resolvedUserId,
            'userProfile' => $profile,
            'contract' => $contract,
            'reports' => $reports,
            'parameters' => $parameters,
            'recentActivity' => $recentActivity,
            'trainCount' => $trainCount,
        ];
    }
}
