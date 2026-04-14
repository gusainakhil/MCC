<?php
//print php errors
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/includes/dashboard-data.php';
require_once __DIR__ . '/includes/dashboard-layout.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function badgeClass($status)
{
    return strtolower((string) $status) === 'active' ? 'text-bg-success' : 'text-bg-secondary';
}

function reportAccent($type)
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

$users = [];
$selectedUser = null;
$userProfile = null;
$contract = null;
$reports = [];
$parameters = [];
$recentActivity = [];
$trainCount = 0;

$userListSql = '
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
$userListResult = $conn->query($userListSql);
if ($userListResult) {
    while ($row = $userListResult->fetch_assoc()) {
        $users[] = $row;
    }
}

$selectedUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
if ($selectedUserId <= 0 && count($users) > 0) {
    $selectedUserId = (int) $users[0]['user_id'];
}

if ($selectedUserId > 0) {
    foreach ($users as $userRow) {
        if ((int) $userRow['user_id'] === $selectedUserId) {
            $selectedUser = $userRow;
            break;
        }
    }
}

if ($selectedUser === null && count($users) > 0) {
    $selectedUser = $users[0];
    $selectedUserId = (int) $selectedUser['user_id'];
}

if ($selectedUserId > 0) {
    $profileSql = '
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
    $profileStmt = $conn->prepare($profileSql);
    if ($profileStmt) {
        $profileStmt->bind_param('i', $selectedUserId);
        $profileStmt->execute();
        $profileResult = $profileStmt->get_result();
        $userProfile = $profileResult ? $profileResult->fetch_assoc() : null;
        $profileStmt->close();
    }

    $contractSql = '
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
    $contractStmt = $conn->prepare($contractSql);
    if ($contractStmt) {
        $contractStmt->bind_param('i', $selectedUserId);
        $contractStmt->execute();
        $contractResult = $contractStmt->get_result();
        $contract = $contractResult ? $contractResult->fetch_assoc() : null;
        $contractStmt->close();
    }

    $trainCountSql = 'SELECT COUNT(*) AS total_count FROM Mcc_train_information WHERE user_id = ?';
    $trainCountStmt = $conn->prepare($trainCountSql);
    if ($trainCountStmt) {
        $trainCountStmt->bind_param('i', $selectedUserId);
        $trainCountStmt->execute();
        $trainCountResult = $trainCountStmt->get_result();
        if ($trainCountResult && ($trainRow = $trainCountResult->fetch_assoc())) {
            $trainCount = (int) $trainRow['total_count'];
        }
        $trainCountStmt->close();
    }

    $reportSql = '
        SELECT
            r.report_id,
            r.report_name,
            r.report_type,
            r.weight_percent,
            r.status,
            COUNT(p.parameter_id) AS parameter_count,
            SUM(CASE WHEN p.status = \'Active\' THEN 1 ELSE 0 END) AS active_parameter_count,
            MAX(p.assigned_at) AS last_assignment_at
        FROM Mcc_reports r
        LEFT JOIN Mcc_parameters p
            ON p.report_id = r.report_id
           AND p.user_id = r.user_id
        WHERE r.user_id = ?
        GROUP BY
            r.report_id,
            r.report_name,
            r.report_type,
            r.weight_percent,
            r.status
        ORDER BY FIELD(r.report_type, \'Normal Report\', \'Intensive Report\', \'Chemical Report\', \'Machine Report\', \'Attendance Report\'), r.report_name ASC
    ';
    $reportStmt = $conn->prepare($reportSql);
    if ($reportStmt) {
        $reportStmt->bind_param('i', $selectedUserId);
        $reportStmt->execute();
        $reportResult = $reportStmt->get_result();
        if ($reportResult) {
            while ($row = $reportResult->fetch_assoc()) {
                $reports[] = $row;
            }
        }
        $reportStmt->close();
    }

    $parameterSql = '
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
        ORDER BY r.report_type ASC, r.report_name ASC, p.parameter_name ASC
    ';
    $parameterStmt = $conn->prepare($parameterSql);
    if ($parameterStmt) {
        $parameterStmt->bind_param('i', $selectedUserId);
        $parameterStmt->execute();
        $parameterResult = $parameterStmt->get_result();
        if ($parameterResult) {
            while ($row = $parameterResult->fetch_assoc()) {
                $parameters[] = $row;
            }
        }
        $parameterStmt->close();
    }

    $activitySql = '
        SELECT source_type, created_at, report_name, report_type, parameter_name, train_no, value
        FROM (
            SELECT
                \'Normal Report\' AS source_type,
                n.created_at AS created_at,
                r.report_name AS report_name,
                r.report_type AS report_type,
                p.parameter_name AS parameter_name,
                n.train_no AS train_no,
                n.`value` AS value
            FROM Mcc_normal_report_data n
            INNER JOIN Mcc_parameters p ON p.parameter_id = n.parameter_id
            INNER JOIN Mcc_reports r ON r.report_id = p.report_id
            WHERE n.user_id = ?

            UNION ALL

            SELECT
                \'Intensive Report\' AS source_type,
                i.created_at AS created_at,
                r.report_name AS report_name,
                r.report_type AS report_type,
                p.parameter_name AS parameter_name,
                i.train_no AS train_no,
                i.`value` AS value
            FROM Mcc_intensive_report_data i
            INNER JOIN Mcc_parameters p ON p.parameter_id = i.parameter_id
            INNER JOIN Mcc_reports r ON r.report_id = p.report_id
            WHERE i.user_id = ?
        ) AS combined_activity
        ORDER BY created_at DESC
        LIMIT 6
    ';
    $activityStmt = $conn->prepare($activitySql);
    if ($activityStmt) {
        $activityStmt->bind_param('ii', $selectedUserId, $selectedUserId);
        $activityStmt->execute();
        $activityResult = $activityStmt->get_result();
        if ($activityResult) {
            while ($row = $activityResult->fetch_assoc()) {
                $recentActivity[] = $row;
            }
        }
        $activityStmt->close();
    }
}

$reportTypes = [];
$assignedReportsCount = count($reports);
$assignedParametersCount = count($parameters);
$recentSubmissionCount = count($recentActivity);
$totalWeight = 0.0;
$activeReportCount = 0;
$activeParameterCount = 0;

foreach ($reports as $reportRow) {
    $reportTypes[] = (string) $reportRow['report_type'];
    $totalWeight += (float) ($reportRow['weight_percent'] ?? 0);
    if (strtolower((string) $reportRow['status']) === 'active') {
        $activeReportCount++;
    }
    $activeParameterCount += (int) ($reportRow['active_parameter_count'] ?? 0);
}

$reportTypes = array_values(array_unique($reportTypes));
$reportTypeCount = count($reportTypes);
$reportPageUrls = [
    'Normal Report' => 'normal-report.php',
    'Intensive Report' => 'intensive-report.php',
    'Chemical Report' => 'chemical-report.php',
    'Machine Report' => 'machine-report.php',
    'Attendance Report' => 'attendance-report.php',
];
$dashboardUserName = $userProfile['user_name'] ?? ($selectedUser['display_name'] ?? 'User');
if (trim((string) $dashboardUserName) === '') {
    $dashboardUserName = $selectedUser['display_name'] ?? 'User';
}

$selectedUserName = $dashboardUserName;

$stationLabel = '-';
if ($userProfile) {
    $stationParts = array_filter([
        $userProfile['station_name'] ?? '',
        $userProfile['division_name'] ?? '',
        $userProfile['zone_name'] ?? '',
    ]);
    if (count($stationParts) > 0) {
        $stationLabel = implode(' / ', $stationParts);
    }
}

$contractLabel = '-';
if ($contract) {
    $contractLabel = trim((string) ($contract['agreement_no'] ?? ''));
    if ($contractLabel === '') {
        $contractLabel = 'Contract available';
    }
}

$layoutContext = [
    'selectedUserId' => $selectedUserId,
    'selectedUserName' => $selectedUserName,
    'stationLabel' => $stationLabel,
    'contractLabel' => $contractLabel,
    'users' => $users,
    'reports' => $reports,
    'reportType' => 'Dashboard',
    'pageTitle' => 'User Dashboard',
    'pageDescription' => 'Assigned reports, parameters, and recent activity in one compact view.',
    'pageIcon' => 'bi-speedometer2',
    'pageAccent' => '#3c8dbc',
    'activePage' => 'dashboard',
];

function formatDateTimeValue($value)
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y, h:i A', $timestamp) : h($value);
}

function reportStatusBadge($status)
{
    return strtolower((string) $status) === 'active' ? 'text-bg-success' : 'text-bg-secondary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | MCC Railway</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-app">
        <?php ud_render_dashboard_sidebar($layoutContext); ?>
        <div class="dashboard-main">
            <?php ud_render_dashboard_header($layoutContext); ?>

            <?php if (!$userProfile): ?>
                <div class="alert alert-warning border-0 shadow-sm reveal">No user data found. Please create a valid user first.</div>
            <?php else: ?>
                <div class="hero-card reveal">
                    <div class="hero-card__content">
                        <div class="hero-copy">
                            <span class="hero-chip">Color theme: #3c8dbc</span>
                            <h2><?php echo h($userProfile['designation'] ?: 'Railway User Dashboard'); ?></h2>
                            <p>
                                <?php echo h($stationLabel); ?>
                            </p>
                            <div class="hero-meta">
                                <div>
                                    <span>Role</span>
                                    <strong><?php echo h($userProfile['role']); ?></strong>
                                </div>
                                <div>
                                    <span>Status</span>
                                    <strong><?php echo h($userProfile['status']); ?></strong>
                                </div>
                                <div>
                                    <span>Trains</span>
                                    <strong><?php echo (int) $trainCount; ?></strong>
                                </div>
                                <div>
                                    <span>Reports</span>
                                    <strong><?php echo (int) $assignedReportsCount; ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="hero-card__panel">
                            <div class="mini-stat">
                                <span>Assigned parameters</span>
                                <strong><?php echo (int) $assignedParametersCount; ?></strong>
                            </div>
                            <div class="mini-stat">
                                <span>Active reports</span>
                                <strong><?php echo (int) $activeReportCount; ?></strong>
                            </div>
                            <div class="mini-stat">
                                <span>Recent entries</span>
                                <strong><?php echo (int) $recentSubmissionCount; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 g-xl-4 mt-1">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="metric-card reveal" style="--delay: 0.05s;">
                            <div class="metric-icon"><i class="bi bi-journal-check"></i></div>
                            <div class="metric-label">Assigned Reports</div>
                            <div class="metric-value" data-count="<?php echo (int) $assignedReportsCount; ?>"><?php echo (int) $assignedReportsCount; ?></div>
                            <div class="metric-note">Dynamic reports linked to this user.</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="metric-card reveal" style="--delay: 0.10s;">
                            <div class="metric-icon"><i class="bi bi-diagram-3"></i></div>
                            <div class="metric-label">Assigned Parameters</div>
                            <div class="metric-value" data-count="<?php echo (int) $assignedParametersCount; ?>"><?php echo (int) $assignedParametersCount; ?></div>
                            <div class="metric-note">Report parameters currently configured.</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="metric-card reveal" style="--delay: 0.15s;">
                            <div class="metric-icon"><i class="bi bi-train-freight-front"></i></div>
                            <div class="metric-label">Mapped Trains</div>
                            <div class="metric-value" data-count="<?php echo (int) $trainCount; ?>"><?php echo (int) $trainCount; ?></div>
                            <div class="metric-note">Train master records tied to the user.</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="metric-card reveal" style="--delay: 0.20s;">
                            <div class="metric-icon"><i class="bi bi-activity"></i></div>
                            <div class="metric-label">Recent Entries</div>
                            <div class="metric-value" data-count="<?php echo (int) $recentSubmissionCount; ?>"><?php echo (int) $recentSubmissionCount; ?></div>
                            <div class="metric-note">Latest normal and intensive report activity.</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 g-xl-4 mt-1">
                    <div class="col-xl-8">
                        <div class="panel-card reveal" id="assigned-reports">
                            <div class="panel-card__header">
                                <div>
                                    <h3 class="panel-title mb-1">Assigned Reports</h3>
                                    <p class="panel-subtitle mb-0">Reports assigned to this user appear here automatically from the database.</p>
                                </div>
                                <div class="panel-badge"><?php echo (int) $reportTypeCount; ?> types</div>
                            </div>
                            <div class="panel-card__body">
                                <?php if (count($reports) === 0): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h4>No reports assigned yet</h4>
                                        <p class="mb-0">As soon as reports are attached to this user, they will show up here automatically.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="report-grid">
                                        <?php foreach ($reports as $index => $reportRow): ?>
                                            <?php
                                                $reportType = (string) $reportRow['report_type'];
                                                $accent = reportAccent($reportType);
                                                $weight = (float) ($reportRow['weight_percent'] ?? 0);
                                                $weightClamped = max(0, min(100, $weight));
                                                $paramCount = (int) ($reportRow['parameter_count'] ?? 0);
                                                $activeParams = (int) ($reportRow['active_parameter_count'] ?? 0);
                                                $reportName = trim((string) ($reportRow['report_name'] ?? ''));
                                                if ($reportName === '') {
                                                    $reportName = $reportType;
                                                }
                                            ?>
                                            <article class="report-card reveal" style="--accent: <?php echo h($accent); ?>; --delay: <?php echo number_format(0.08 + ($index * 0.05), 2, '.', ''); ?>s;">
                                                <div class="report-card__top">
                                                    <div>
                                                        <div class="report-type"><?php echo h($reportType); ?></div>
                                                        <h4 class="report-name mb-1"><?php echo h($reportName); ?></h4>
                                                        <div class="report-description">
                                                            <?php echo $paramCount > 0 ? h($activeParams . ' active parameters') : 'No parameters assigned yet'; ?>
                                                        </div>
                                                    </div>
                                                    <span class="badge <?php echo reportStatusBadge($reportRow['status'] ?? 'Inactive'); ?> rounded-pill">
                                                        <?php echo h($reportRow['status'] ?? 'Inactive'); ?>
                                                    </span>
                                                </div>

                                                <div class="report-metrics">
                                                    <div>
                                                        <span>Weight</span>
                                                        <strong><?php echo number_format($weightClamped, 2); ?>%</strong>
                                                    </div>
                                                    <div>
                                                        <span>Parameters</span>
                                                        <strong><?php echo (int) $paramCount; ?></strong>
                                                    </div>
                                                    <div>
                                                        <span>Updated</span>
                                                        <strong><?php echo $reportRow['last_assignment_at'] ? h(formatDateTimeValue($reportRow['last_assignment_at'])) : '-'; ?></strong>
                                                    </div>
                                                </div>

                                                <div class="progress progress-soft" role="progressbar" aria-valuenow="<?php echo (int) $weightClamped; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar" style="width: <?php echo (int) $weightClamped; ?>%; background: <?php echo h($accent); ?>;"></div>
                                                </div>

                                                <div class="report-card__footer">
                                                    <span class="text-muted small">Assigned for <?php echo h($stationLabel); ?></span>
                                                    <a href="<?php echo h($reportPageUrls[$reportType] ?? 'index.php'); ?>?user_id=<?php echo (int) $selectedUserId; ?>" class="btn btn-sm btn-outline-primary btn-soft">Open page</a>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="panel-card reveal">
                            <div class="panel-card__header">
                                <div>
                                    <h3 class="panel-title mb-1">User Profile</h3>
                                    <p class="panel-subtitle mb-0">Quick railway context and contract summary.</p>
                                </div>
                            </div>
                            <div class="panel-card__body">
                                <div class="profile-list">
                                    <div><span>Name</span><strong><?php echo h($userProfile['user_name'] ?: $userProfile['username'] ?: $dashboardUserName); ?></strong></div>
                                    <div><span>Email</span><strong><?php echo h($userProfile['email'] ?? '-'); ?></strong></div>
                                    <div><span>Phone</span><strong><?php echo h($userProfile['phone'] ?? '-'); ?></strong></div>
                                    <div><span>Designation</span><strong><?php echo h($userProfile['designation'] ?? '-'); ?></strong></div>
                                    <div><span>Station</span><strong><?php echo h($stationLabel); ?></strong></div>
                                    <div><span>Contract</span><strong><?php echo h($contractLabel); ?></strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="panel-card reveal mt-3">
                            <div class="panel-card__header">
                                <div>
                                    <h3 class="panel-title mb-1">Contract Snapshot</h3>
                                    <p class="panel-subtitle mb-0">Latest linked contract for this user.</p>
                                </div>
                            </div>
                            <div class="panel-card__body">
                                <?php if (!$contract): ?>
                                    <div class="empty-mini">No contract linked to this user.</div>
                                <?php else: ?>
                                    <div class="mini-stack">
                                        <div><span>Agreement No</span><strong><?php echo h($contract['agreement_no']); ?></strong></div>
                                        <div><span>Contractor</span><strong><?php echo h($contract['contractor_name']); ?></strong></div>
                                        <div><span>Train Count</span><strong><?php echo h($contract['train_no_count'] ?? '-'); ?></strong></div>
                                        <div><span>Amount</span><strong><?php echo $contract['amount'] !== null ? h(number_format((float) $contract['amount'], 2)) : '-'; ?></strong></div>
                                        <div><span>Period</span><strong><?php echo h(($contract['contract_start_date'] ?? '-') . ' to ' . ($contract['contract_end_date'] ?? '-')); ?></strong></div>
                                        <div><span>Status</span><strong><span class="badge <?php echo reportStatusBadge($contract['status'] ?? 'Inactive'); ?> rounded-pill"><?php echo h($contract['status'] ?? 'Inactive'); ?></span></strong></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 g-xl-4 mt-1" id="assigned-parameters">
                    <div class="col-xl-7">
                        <div class="panel-card reveal">
                            <div class="panel-card__header">
                                <div>
                                    <h3 class="panel-title mb-1">Assigned Parameters</h3>
                                    <p class="panel-subtitle mb-0">Parameters are grouped by the assigned report type.</p>
                                </div>
                            </div>
                            <div class="panel-card__body table-responsive">
                                <table class="table align-middle table-hover dashboard-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Report</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($parameters) === 0): ?>
                                            <tr>
                                                <td colspan="4">
                                                    <div class="empty-inline">No parameters assigned yet.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($parameters as $parameterRow): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo h($parameterRow['parameter_name']); ?></div>
                                                        <div class="text-muted small">Assigned <?php echo h(formatDateTimeValue($parameterRow['assigned_at'])); ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo h($parameterRow['report_name']); ?></div>
                                                        <div class="text-muted small"><?php echo h($parameterRow['report_type']); ?></div>
                                                    </td>
                                                    <td><?php echo h($parameterRow['category']); ?></td>
                                                    <td><span class="badge <?php echo reportStatusBadge($parameterRow['status'] ?? 'Inactive'); ?> rounded-pill"><?php echo h($parameterRow['status'] ?? 'Inactive'); ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-5">
                        <div class="panel-card reveal">
                            <div class="panel-card__header">
                                <div>
                                    <h3 class="panel-title mb-1">Recent Activity</h3>
                                    <p class="panel-subtitle mb-0">Latest normal and intensive report entries.</p>
                                </div>
                            </div>
                            <div class="panel-card__body table-responsive">
                                <table class="table align-middle dashboard-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Report</th>
                                            <th>Train</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($recentActivity) === 0): ?>
                                            <tr>
                                                <td colspan="4">
                                                    <div class="empty-inline">No recent report entries found.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentActivity as $activityRow): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo h(formatDateTimeValue($activityRow['created_at'])); ?></div>
                                                        <div class="text-muted small"><?php echo h($activityRow['source_type']); ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo h($activityRow['report_name']); ?></div>
                                                        <div class="text-muted small"><?php echo h($activityRow['parameter_name']); ?></div>
                                                    </td>
                                                    <td><?php echo h($activityRow['train_no']); ?></td>
                                                    <td><?php echo h($activityRow['value']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
