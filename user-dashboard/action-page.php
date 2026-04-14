<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dashboard-data.php';
require_once __DIR__ . '/includes/dashboard-layout.php';

ud_require_auth('../login.php');
ud_require_org_admin_dashboard('../index.php');

$actionPageConfig = isset($actionPageConfig) && is_array($actionPageConfig) ? $actionPageConfig : [];
$actionKey = (string) ($actionPageConfig['key'] ?? 'action');
$pageTitle = (string) ($actionPageConfig['title'] ?? 'Quick Action');
$pageDescription = (string) ($actionPageConfig['description'] ?? 'Action form');
$pageIcon = (string) ($actionPageConfig['icon'] ?? 'bi-ui-checks-grid');
$pageAccent = (string) ($actionPageConfig['accent'] ?? '#3c8dbc');

$selectedUserId = ud_authenticated_user_id();
$context = ud_load_dashboard_context($conn, $selectedUserId);
extract($context, EXTR_OVERWRITE);

$selectedUserName = 'User';
if ($userProfile) {
    $selectedUserName = trim((string) ($userProfile['user_name'] ?? ''));
    if ($selectedUserName === '') {
        $selectedUserName = trim((string) ($userProfile['username'] ?? ''));
    }
    if ($selectedUserName === '') {
        $selectedUserName = 'User #' . (int) $selectedUserId;
    }
} elseif ($selectedUser) {
    $selectedUserName = trim((string) ($selectedUser['display_name'] ?? 'User'));
}

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
    'pageTitle' => $pageTitle,
    'pageDescription' => $pageDescription,
    'pageIcon' => $pageIcon,
    'pageAccent' => $pageAccent,
    'activePage' => 'dashboard',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ud_h($pageTitle); ?> | MCC User Dashboard</title>
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

            <div class="panel-card reveal">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-title mb-1"><?php echo ud_h($pageTitle); ?></h3>
                        <p class="panel-subtitle mb-0"><?php echo ud_h($pageDescription); ?> (static by default)</p>
                    </div>
                </div>
                <div class="panel-card__body">
                    <form class="row g-3" onsubmit="return false;">
                        <?php if ($actionKey === 'add-employee'): ?>
                            <div class="col-md-6"><label class="form-label">Employee Name</label><input type="text" class="form-control" placeholder="Enter employee name"></div>
                            <div class="col-md-6"><label class="form-label">Employee ID</label><input type="text" class="form-control" placeholder="EMP-001"></div>
                            <div class="col-md-6"><label class="form-label">Mobile</label><input type="text" class="form-control" placeholder="98XXXXXXXX"></div>
                            <div class="col-md-6"><label class="form-label">Designation</label><input type="text" class="form-control" placeholder="Cleaner"></div>
                        <?php elseif ($actionKey === 'add-train'): ?>
                            <div class="col-md-4"><label class="form-label">Train No</label><input type="text" class="form-control" placeholder="12345"></div>
                            <div class="col-md-4"><label class="form-label">Rake ID</label><input type="text" class="form-control" placeholder="R-101"></div>
                            <div class="col-md-4"><label class="form-label">No. of Coaches</label><input type="number" class="form-control" placeholder="24"></div>
                            <div class="col-md-6"><label class="form-label">Station</label><input type="text" class="form-control" placeholder="Dehradun"></div>
                            <div class="col-md-6"><label class="form-label">Shift</label><select class="form-select"><option>Morning</option><option>Evening</option><option>Night</option></select></div>
                        <?php elseif ($actionKey === 'change-password'): ?>
                            <div class="col-md-4"><label class="form-label">Current Password</label><input type="password" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">New Password</label><input type="password" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Confirm Password</label><input type="password" class="form-control"></div>
                        <?php elseif ($actionKey === 'billing'): ?>
                            <div class="col-md-4"><label class="form-label">Bill No</label><input type="text" class="form-control" placeholder="BILL-2026-001"></div>
                            <div class="col-md-4"><label class="form-label">Amount</label><input type="number" class="form-control" placeholder="0"></div>
                            <div class="col-md-4"><label class="form-label">Billing Month</label><input type="month" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Invoice Date</label><input type="date" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Status</label><select class="form-select"><option>Pending</option><option>Paid</option></select></div>
                        <?php elseif ($actionKey === 'add-penalty'): ?>
                            <div class="col-md-4"><label class="form-label">Penalty Amount</label><input type="number" class="form-control" placeholder="0"></div>
                            <div class="col-md-4"><label class="form-label">Penalty Date</label><input type="date" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Penalty Type</label><select class="form-select"><option>Delay</option><option>Quality Issue</option><option>Other</option></select></div>
                            <div class="col-12"><label class="form-label">Reason</label><textarea class="form-control" rows="3" placeholder="Reason for penalty"></textarea></div>
                        <?php endif; ?>

                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-soft">Reset</button>
                            <button type="button" class="btn btn-brand">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
