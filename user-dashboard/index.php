<?php
//print php errors
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dashboard-data.php';
require_once __DIR__ . '/includes/dashboard-layout.php';

ud_require_auth('../login.php');
ud_require_org_admin_dashboard('../index.php');

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

$selectedUserId = ud_authenticated_user_id();
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

}

$reportTypes = [];
$assignedReportsCount = count($reports);
$totalWeight = 0.0;
$activeReportCount = 0;

foreach ($reports as $reportRow) {
    $reportTypes[] = (string) $reportRow['report_type'];
    $totalWeight += (float) ($reportRow['weight_percent'] ?? 0);
    if (strtolower((string) $reportRow['status']) === 'active') {
        $activeReportCount++;
    }
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
                                <span>Active reports</span>
                                <strong><?php echo (int) $activeReportCount; ?></strong>
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
                            <div class="metric-icon"><i class="bi bi-train-freight-front"></i></div>
                            <div class="metric-label">Mapped Trains</div>
                            <div class="metric-value" data-count="<?php echo (int) $trainCount; ?>"><?php echo (int) $trainCount; ?></div>
                            <div class="metric-note">Train master records tied to the user.</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="metric-card reveal" style="--delay: 0.20s;">
                <div class="row g-3 g-xl-4 mt-1">
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
