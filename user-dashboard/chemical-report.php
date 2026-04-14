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
    'reportType' => 'Chemical Report',
    'pageTitle' => 'Chemical Report',
    'pageDescription' => 'Static chemical report structure with monthly used chemical column.',
    'pageIcon' => 'bi-droplet-half',
    'pageAccent' => '#16a085',
    'activePage' => 'dashboard',
];

$chemicalRows = [
    ['sn' => 1, 'name' => 'PVC Floor Cleaning Agent', 'brands' => 'Spiral, Sigla Neutral of Eco Lab, Chela, APC F of Haylide or Fresher brand', 'used_monthly' => '120 ml', 'quantity' => '50 ml'],
    ['sn' => 2, 'name' => 'Ceramic & stainless steel Toilet fittings Cleaning agent', 'brands' => 'Taski R1, Spiral HD or Fresher brand', 'used_monthly' => '100 ml', 'quantity' => '50 ml'],
    ['sn' => 3, 'name' => 'Glass Cleaning agent', 'brands' => 'Taski R3, OC Glass cleaner of Eco Lab, Collin, Klean & Shine of Haylide or Fresher brand', 'used_monthly' => '60 ml', 'quantity' => '20 ml'],
    ['sn' => 4, 'name' => 'Laminated Plastic Sheet & Berth Rexene cleaner', 'brands' => 'Taski R7/Taski R2, OC Neutral cleaner, Solvex for hard stains, Chela, APC F of Haylide or Fresher brand', 'used_monthly' => '140 ml', 'quantity' => '50 ml'],
    ['sn' => 5, 'name' => 'Painted Surface cleaner', 'brands' => 'Spiral, Absorbit of Eco Lab, Super max, Chela, APC F of Haylide or Fresher brand', 'used_monthly' => '180 ml', 'quantity' => '90 ml'],
    ['sn' => 6, 'name' => 'Disinfectants', 'brands' => 'TRIAD-III, Antiback of Eco Lab, Nimy, or Fresher brand', 'used_monthly' => '40 ml', 'quantity' => '10 ml'],
    ['sn' => 7, 'name' => 'Air Freshener', 'brands' => 'Taski R5, Ecolab, Air Fresh of Chela, Haylide or any approved water-based brand', 'used_monthly' => '45 ml', 'quantity' => '10 ml'],
    ['sn' => 8, 'name' => 'Cleaning agent for removing old labels, stickers, glue marks etc.', 'brands' => 'Erazel Gel/Plus of Chela, Stainex G/SC of Haylide or Fresher brand', 'used_monthly' => '35 ml', 'quantity' => '10 ml'],
];

$printTitle = 'Chemical Report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chemical Report | MCC User Dashboard</title>
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
                            <p class="panel-subtitle mb-0">Chemical report filter placeholders. All values are static for now.</p>
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
                                <label class="form-label">Used Chemical Monthly</label>
                                <input type="text" class="form-control" value="Monthly">
                            </div>
                            <div class="col-12 col-md-6 col-xl-3 d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-soft">Reset</button>
                                <button type="button" class="btn btn-brand">Apply Filter</button>
                                <button type="button" class="btn btn-outline-primary btn-soft" onclick="udPrintChemicalReportCard();">
                                    <i class="bi bi-printer me-1"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="chemicalReportPrintCard" class="panel-card reveal mt-3">
                    <div class="panel-card__body">
                        <div class="normal-score-sheet">
                            <div class="normal-score-sheet__head">
                                <span>CHEMICAL REPORT</span>
                                <h3>Approved Chemical Schedule</h3>
                            </div>

                            <div class="normal-score-sheet__meta">
                                <div>Date: .......................</div>
                                <div>Train No: ............................</div>
                                <div>Depot: <?php echo ud_h($stationLabel); ?></div>
                                <div>Contractor: ..................................</div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered normal-score-table mb-0 chemical-table">
                                    <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>Name of Chemical</th>
                                            <th>Approved brands</th>
                                            <th>Used Chemical Monthly</th>
                                            <th>Quantity per coach</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($chemicalRows as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['sn']; ?></td>
                                                <td><?php echo ud_h($row['name']); ?></td>
                                                <td><?php echo ud_h($row['brands']); ?></td>
                                                <td><?php echo ud_h($row['used_monthly']); ?></td>
                                                <td><?php echo ud_h($row['quantity']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="normal-score-sheet__footer-note">*Any other approved chemical brand can be used as per Railway guidelines from time to time.</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function udPrintChemicalReportCard() {
            var printCard = document.getElementById('chemicalReportPrintCard');
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
                '<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Chemical Report Print</title>' +
                styleTags +
                '<style>@page{size:landscape;margin:10mm;}body{margin:0;padding:10mm;background:#fff;}#chemicalReportPrintCard{display:block!important;visibility:visible!important;opacity:1!important;transform:none!important;}</style>' +
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
