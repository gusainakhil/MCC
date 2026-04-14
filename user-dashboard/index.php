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

$selectedUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : ud_authenticated_user_id();
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

foreach ($reports as $reportRow) {
    $reportTypes[] = (string) $reportRow['report_type'];
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
    'pageDescription' => 'Assigned reports in one compact view.',
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

            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
