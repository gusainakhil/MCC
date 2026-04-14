<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dashboard-data.php';
require_once __DIR__ . '/includes/dashboard-layout.php';

ud_require_auth('../login.php');
ud_require_org_admin_dashboard('../index.php');

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
    'reportType' => 'Machine Report',
    'pageTitle' => 'Machine Report',
    'pageDescription' => 'Static machine resources sheet with target and achieve columns.',
    'pageIcon' => 'bi-gear-wide-connected',
    'pageAccent' => '#f39c12',
    'activePage' => 'dashboard',
];

$machineRows = [
    ['type' => 'Portable high-pressure jet hose pipe', 'count' => '2 per pit', 'location' => 'Coach exterior washing. If high pressure jet cleaning plants are available on washing lines, contractor will operate and maintain the plant during currency of contract including spares, consumables, nozzles and pipe line etc. Otherwise contractor should provide own jet machines for exterior cleaning of coaches.', 'target' => 2, 'achieve' => 2],
    ['type' => 'Portable powered single disc floor scrubber', 'count' => '2 per pit', 'location' => 'PVC floor & Aluminum chequered plate etc.', 'target' => 2, 'achieve' => 2],
    ['type' => 'High pressure jet machine', 'count' => '2 per pit', 'location' => 'For exterior washing.', 'target' => 2, 'achieve' => 1],
    ['type' => 'Hand held single disc electrically operated mini scrubber', 'count' => '2 per pit', 'location' => 'For scrubbing toilet floor, skirting and panels etc.', 'target' => 2, 'achieve' => 2],
    ['type' => 'Portable high-pressure jet machine of smaller size', 'count' => '2 per pit', 'location' => 'For squatting pan, wall protector, commode pan etc.', 'target' => 2, 'achieve' => 1],
    ['type' => 'Portable wet & dry vacuum cleaner', 'count' => '2 per pit', 'location' => 'For intensive cleaning of coaches.', 'target' => 2, 'achieve' => 2],
    ['type' => 'Buffing machine', 'count' => '2 per pit', 'location' => 'For intensive cleaning of coaches.', 'target' => 2, 'achieve' => 1],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Report | MCC User Dashboard</title>
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
                <div class="panel-card reveal report-filter-panel">
                    <div class="panel-card__header">
                        <div>
                            <h3 class="panel-title mb-1">Filter (Static)</h3>
                            <p class="panel-subtitle mb-0">Machine report filter placeholders. Values are static for now.</p>
                        </div>
                    </div>
                    <div class="panel-card__body">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" value="2026-04-01">
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" value="2026-04-14">
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label">Train No</label>
                                <input type="text" class="form-control" value="12345">
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label">Add Target</label>
                                <input type="number" class="form-control" value="2" min="0">
                            </div>
                            <div class="col-12 d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-soft">Reset</button>
                                <button type="button" class="btn btn-brand">Apply Filter</button>
                                <button type="button" class="btn btn-outline-primary btn-soft" onclick="udPrintMachineReportCard();">
                                    <i class="bi bi-printer me-1"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="machineReportPrintCard" class="panel-card reveal mt-3">
                    <div class="panel-card__body">
                        <div class="normal-score-sheet">
                            <div class="normal-score-sheet__head">
                                <span>Project Resources for MCC</span>
                                <h3>Machine Resources Sheet</h3>
                            </div>

                            <div class="normal-score-sheet__meta">
                                <div>Date: .......................</div>
                                <div>Train No: ............................</div>
                                <div>Depot: <?php echo ud_h($stationLabel); ?></div>
                                <div>Contractor: ..................................</div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered normal-score-table mb-0 machine-table">
                                    <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>Machine Type</th>
                                            <th>Target</th>
                                            <th>Achieve</th>
                                            <th>Nos. of machines to be Deployed in each pit</th>
                                            <th>Location of use</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($machineRows as $index => $row): ?>
                                            <tr>
                                                <td><?php echo (int) ($index + 1); ?></td>
                                                <td><?php echo ud_h($row['type']); ?></td>
                                                <td><?php echo (int) $row['target']; ?></td>
                                                <td><?php echo (int) $row['achieve']; ?></td>
                                                <td><?php echo ud_h($row['count']); ?></td>
                                                <td><?php echo ud_h($row['location']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="normal-score-sheet__footer-note">Target and achieve columns are added for monthly machine tracking.</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function udPrintMachineReportCard() {
            var printCard = document.getElementById('machineReportPrintCard');
            if (!printCard) {
                window.print();
                return;
            }

            var printWindow = window.open('', '_blank', 'width=1200,height=900');
            if (!printWindow) {
                window.print();
                return;
            }

            var styleTags = Array.prototype.slice.call(document.querySelectorAll('link[rel="stylesheet"], style'))
                .map(function (node) { return node.outerHTML; })
                .join('');

            printWindow.document.open();
            printWindow.document.write(
                '<!DOCTYPE html>' +
                '<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Machine Report Print</title>' +
                styleTags +
                '<style>@page{size:landscape;margin:10mm;}body{margin:0;padding:10mm;background:#fff;}#machineReportPrintCard{display:block!important;visibility:visible!important;opacity:1!important;transform:none!important;}</style>' +
                '</head><body>' +
                printCard.outerHTML +
                '</body></html>'
            );
            printWindow.document.close();

            printWindow.onload = function () {
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            };
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
