<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/includes/dashboard-data.php';
require_once __DIR__ . '/includes/dashboard-layout.php';

$reportPageConfig = isset($reportPageConfig) && is_array($reportPageConfig) ? $reportPageConfig : [];
$reportType = $reportPageConfig['report_type'] ?? 'Normal Report';
$pageTitle = $reportPageConfig['page_title'] ?? $reportType;
$pageDescription = $reportPageConfig['page_description'] ?? 'Dedicated report page.';
$pageIcon = $reportPageConfig['page_icon'] ?? 'bi-journal-text';
$pageAccent = $reportPageConfig['page_accent'] ?? ud_report_accent($reportType);

$selectedUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$context = ud_load_dashboard_context($conn, $selectedUserId, $reportType);
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
    'reportType' => $reportType,
    'pageTitle' => $pageTitle,
    'pageDescription' => $pageDescription,
    'pageIcon' => $pageIcon,
    'pageAccent' => $pageAccent,
    'activePage' => $reportType === 'Normal Report' ? 'normal-report' : ($reportType === 'Intensive Report' ? 'intensive-report' : 'report'),
];

$allReportPages = [
    'Normal Report' => 'normal-report.php',
    'Intensive Report' => 'intensive-report.php',
    'Chemical Report' => 'chemical-report.php',
    'Machine Report' => 'machine-report.php',
    'Attendance Report' => 'attendance-report.php',
];

$primaryReport = count($reports) > 0 ? $reports[0] : null;
$totalWeight = 0.0;
$activeReportCount = 0;
foreach ($reports as $reportRow) {
    $totalWeight += (float) ($reportRow['weight_percent'] ?? 0);
    if (strtolower((string) ($reportRow['status'] ?? '')) === 'active') {
        $activeReportCount++;
    }
}

$recentCount = count($recentActivity);
$isSupportedTransactionalType = in_array($reportType, ['Normal Report', 'Intensive Report'], true);
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

            <?php if (!$userProfile): ?>
                <div class="alert alert-warning border-0 shadow-sm reveal">No user data found. Please create a valid user first.</div>
            <?php else: ?>
                <div class="hero-card reveal">
                    <div class="hero-card__content">
                        <div class="hero-copy">
                            <span class="hero-chip">Report type: <?php echo ud_h($reportType); ?></span>
                            <h2><?php echo ud_h($pageTitle); ?></h2>
                            <p><?php echo ud_h($selectedUserName); ?> - <?php echo ud_h($stationLabel); ?></p>
                            <div class="hero-meta">
                                <div>
                                    <span>Assigned Reports</span>
                                    <strong><?php echo (int) count($reports); ?></strong>
                                </div>
                                <div>
                                    <span>Assigned Parameters</span>
                                    <strong><?php echo (int) count($parameters); ?></strong>
                                </div>
                                <div>
                                    <span>Recent Entries</span>
                                    <strong><?php echo (int) $recentCount; ?></strong>
                                </div>
                                <div>
                                    <span>Active Reports</span>
                                    <strong><?php echo (int) $activeReportCount; ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="hero-card__panel">
                            <div class="mini-stat">
                                <span>Total weight</span>
                                <strong><?php echo number_format($totalWeight, 2); ?>%</strong>
                            </div>
                            <div class="mini-stat">
                                <span>Train count</span>
                                <strong><?php echo (int) $trainCount; ?></strong>
                            </div>
                            <div class="mini-stat">
                                <span>Contract</span>
                                <strong><?php echo ud_h($contractLabel); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 g-xl-4 mt-1">
                    <div class="col-xl-8">
                        <div class="panel-card reveal">
                            <div class="panel-card__header">
                                <div>
                                    <h3 class="panel-title mb-1">Report Summary</h3>
                                    <p class="panel-subtitle mb-0">All assigned reports of this type are shown below.</p>
                                </div>
                                <div class="panel-badge"><?php echo (int) count($reports); ?> report<?php echo count($reports) === 1 ? '' : 's'; ?></div>
                            </div>
                            <div class="panel-card__body">
                                <?php if (count($reports) === 0): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h4>No <?php echo ud_h($reportType); ?> assigned</h4>
                                        <p class="mb-0">This user does not have any report records for this page yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="report-grid">
                                        <?php foreach ($reports as $index => $reportRow): ?>
                                            <?php
                                                $accent = ud_report_accent((string) $reportRow['report_type']);
                                                $weight = (float) ($reportRow['weight_percent'] ?? 0);
                                                $weightClamped = max(0, min(100, $weight));
                                                $paramCount = (int) ($reportRow['parameter_count'] ?? 0);
                                                $activeParams = (int) ($reportRow['active_parameter_count'] ?? 0);
                                                $reportName = trim((string) ($reportRow['report_name'] ?? ''));
                                                if ($reportName === '') {
                                                    $reportName = $reportRow['report_type'];
                                                }
                                            ?>
                                            <article class="report-card reveal" style="--accent: <?php echo ud_h($accent); ?>; --delay: <?php echo number_format(0.08 + ($index * 0.05), 2, '.', ''); ?>s;">
                                                <div class="report-card__top">
                                                    <div>
                                                        <div class="report-type"><?php echo ud_h($reportRow['report_type']); ?></div>
                                                        <h4 class="report-name mb-1"><?php echo ud_h($reportName); ?></h4>
                                                        <div class="report-description">
                                                            <?php echo $paramCount > 0 ? ud_h($activeParams . ' active parameters') : 'No parameters assigned yet'; ?>
                                                        </div>
                                                    </div>
                                                    <span class="badge <?php echo ud_report_status_badge($reportRow['status'] ?? 'Inactive'); ?> rounded-pill"><?php echo ud_h($reportRow['status'] ?? 'Inactive'); ?></span>
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
                                                        <strong><?php echo $reportRow['last_assignment_at'] ? ud_h(ud_format_datetime($reportRow['last_assignment_at'])) : '-'; ?></strong>
                                                    </div>
                                                </div>

                                                <div class="progress progress-soft" role="progressbar" aria-valuenow="<?php echo (int) $weightClamped; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar" style="width: <?php echo (int) $weightClamped; ?>%; background: <?php echo ud_h($accent); ?>;"></div>
                                                </div>

                                                <div class="report-card__footer">
                                                    <span class="text-muted small">Dynamic page for this report type</span>
                                                    <a href="<?php echo ud_h(ud_report_page_url((string) $reportRow['report_type'])); ?>?user_id=<?php echo (int) $selectedUserId; ?>" class="btn btn-sm btn-outline-primary btn-soft">Open page</a>
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
                                    <div><span>Name</span><strong><?php echo ud_h($userProfile['user_name'] ?: $userProfile['username'] ?: $selectedUserName); ?></strong></div>
                                    <div><span>Email</span><strong><?php echo ud_h($userProfile['email'] ?? '-'); ?></strong></div>
                                    <div><span>Role</span><strong><?php echo ud_h($userProfile['role']); ?></strong></div>
                                    <div><span>Station</span><strong><?php echo ud_h($stationLabel); ?></strong></div>
                                    <div><span>Contract</span><strong><?php echo ud_h($contractLabel); ?></strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="panel-card reveal mt-3">
                            <div class="panel-card__header">
                                <div>
                                    <h3 class="panel-title mb-1">Page Links</h3>
                                    <p class="panel-subtitle mb-0">Open any report page directly.</p>
                                </div>
                            </div>
                            <div class="panel-card__body d-grid gap-2">
                                <?php foreach ($allReportPages as $label => $url): ?>
                                    <a class="btn <?php echo $label === $reportType ? 'btn-brand' : 'btn-soft'; ?> text-start" href="<?php echo ud_h($url); ?>?user_id=<?php echo (int) $selectedUserId; ?>">
                                        <i class="bi bi-arrow-right-circle me-2"></i><?php echo ud_h($label); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 g-xl-4 mt-1">
                    <div class="col-xl-7">
                        <div class="panel-card reveal">
                            <div class="panel-card__header">
                                <div>
                                    <h3 class="panel-title mb-1">Assigned Parameters</h3>
                                    <p class="panel-subtitle mb-0">Parameters connected to <?php echo ud_h($reportType); ?>.</p>
                                </div>
                            </div>
                            <div class="panel-card__body table-responsive">
                                <table class="table align-middle dashboard-table mb-0">
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
                                                <td colspan="4"><div class="empty-inline">No parameters assigned for this report type.</div></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($parameters as $parameterRow): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo ud_h($parameterRow['parameter_name']); ?></div>
                                                        <div class="text-muted small">Assigned <?php echo ud_h(ud_format_datetime($parameterRow['assigned_at'])); ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo ud_h($parameterRow['report_name']); ?></div>
                                                        <div class="text-muted small"><?php echo ud_h($parameterRow['report_type']); ?></div>
                                                    </td>
                                                    <td><?php echo ud_h($parameterRow['category']); ?></td>
                                                    <td><span class="badge <?php echo ud_report_status_badge($parameterRow['status'] ?? 'Inactive'); ?> rounded-pill"><?php echo ud_h($parameterRow['status'] ?? 'Inactive'); ?></span></td>
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
                                    <p class="panel-subtitle mb-0">Latest entries for this report page.</p>
                                </div>
                            </div>
                            <div class="panel-card__body table-responsive">
                                <?php if ($isSupportedTransactionalType): ?>
                                    <table class="table align-middle dashboard-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Train</th>
                                                <th>Parameter</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recentActivity) === 0): ?>
                                                <tr>
                                                    <td colspan="4"><div class="empty-inline">No recent entries found for this report type.</div></td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recentActivity as $activityRow): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold"><?php echo ud_h(ud_format_datetime($activityRow['created_at'])); ?></div>
                                                            <div class="text-muted small"><?php echo ud_h($activityRow['source_type']); ?></div>
                                                        </td>
                                                        <td><?php echo ud_h($activityRow['train_no']); ?></td>
                                                        <td><?php echo ud_h($activityRow['parameter_name']); ?></td>
                                                        <td><?php echo ud_h($activityRow['value']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="bi bi-journal-text"></i>
                                        <h4>No transaction table yet</h4>
                                        <p class="mb-0">This report type is configured and assigned, but it does not have a dedicated data table in the current schema.</p>
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
