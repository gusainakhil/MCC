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
    'reportType' => 'Intensive Report',
    'pageTitle' => 'Intensive Report',
    'pageDescription' => 'Static score card for intensive mechanized coach cleaning.',
    'pageIcon' => 'bi-activity',
    'pageAccent' => '#2f80ed',
    'activePage' => 'dashboard',
];

$coachPositionCount = 11;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intensive Report | MCC User Dashboard</title>
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
                            <p class="panel-subtitle mb-0">Date and Train filter placeholders for intensive report.</p>
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
                            <div class="col-12 col-md-6 col-xl-3 d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-soft">Reset</button>
                                <button type="button" class="btn btn-brand">Apply Filter</button>
                                <button type="button" class="btn btn-outline-primary btn-soft" onclick="udPrintIntensiveReportCard();">
                                    <i class="bi bi-printer me-1"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="intensiveReportPrintCard" class="panel-card reveal mt-3">
                    <div class="panel-card__body">
                        <div class="normal-score-sheet">
                            <div class="normal-score-sheet__head">
                                <span>ANNEXURE A-2</span>
                                <h3>Score card for intensive Mechanized cleaning of coach.</h3>
                            </div>

                            <div class="text-center mb-3">(To be filled by the supervisor/Nominated representative of CDO/ADME)</div>

                            <div class="normal-score-sheet__meta">
                                <div>Agreement No &amp; date: ..................................</div>
                                <div>Name of Contractor: ..................................</div>
                                <div>Date of Inspection: ..............</div>
                                <div>Name of Depot: <?php echo ud_h($userProfile['station_name'] ?? '..............'); ?></div>
                                <div>Name of Supervisor: ..................................</div>
                                <div>Train No: ........................</div>
                                <div>Time Work Started: ..............</div>
                                <div>Time Work Completed: ..............</div>
                                <div>No. of Coaches in the rake: ..............</div>
                                <div>No. of Coaches attended: ..............</div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered normal-score-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Sr</th>
                                            <th>Coach position</th>
                                            <?php for ($coachPosition = 1; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
                                                <th><?php echo (int) $coachPosition; ?></th>
                                            <?php endfor; ?>
                                            <th>...</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td></td>
                                            <td>Coach No</td>
                                            <?php for ($coachPosition = 1; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
                                                <td></td>
                                            <?php endfor; ?>
                                            <td></td>
                                        </tr>
                                        <tr class="section-row">
                                            <td>(A)</td>
                                            <td colspan="<?php echo (int) (2 + $coachPositionCount); ?>">Coach Interior</td>
                                        </tr>
                                        <tr>
                                            <td>1</td>
                                            <td>Toilet (Panel, Ceiling, Floor, Skirting Items, Mirror, Stickers and graffiti etc.)</td>
                                            <td>8</td>
                                            <?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
                                                <td></td>
                                            <?php endfor; ?>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>Vestibules, Doorways and Gangways including washbasin, mirror and dustbins</td>
                                            <td>8</td>
                                            <?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
                                                <td></td>
                                            <?php endfor; ?>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>3</td>
                                            <td>Floor/Floor PVC including skirting and under the berths/seats</td>
                                            <td>8</td>
                                            <?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
                                                <td></td>
                                            <?php endfor; ?>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>4</td>
                                            <td>Seats and Berths (for pantry car: Cup boards, shelves, counter kitchen area etc.)</td>
                                            <td>8</td>
                                            <?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
                                                <td></td>
                                            <?php endfor; ?>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>5</td>
                                            <td>Panels, Ceiling, Window Sills, Window Glasses, Shutters, Snack Tables and all other items</td>
                                            <td>8</td>
                                            <?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
                                                <td></td>
                                            <?php endfor; ?>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="<?php echo (int) (3 + $coachPositionCount); ?>" style="height: 12px;"></td>
                                        </tr>
                                        <tr class="section-row">
                                            <td>(B)</td>
                                            <td>Coach Exterior: Outside Washing of the Coach including end panels and turn under (All unauthorized stickers need to be removed)</td>
                                            <td>8</td>
                                            <?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
                                                <td></td>
                                            <?php endfor; ?>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="<?php echo (int) (3 + $coachPositionCount); ?>" style="height: 10px;"></td>
                                        </tr>
                                        <tr class="section-row">
                                            <td>(C)</td>
                                            <td colspan="<?php echo (int) (2 + $coachPositionCount); ?>">Watering (Please mention Yes/No)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <ul class="normal-score-notes mb-0">
                                <li>Maximum Marks will be 40 for internal cleaning.</li>
                                <li>This will be counted as: Excellent-10, Very Good-8-9, Satisfactory-6-7, Poor-5 and below 5, Not attended-0.</li>
                                <li>In above example there are 40 marks for internal cleaning, therefore rating will be 40/50x100=80%.</li>
                                <li>Maximum Marks will be 10 for exterior cleaning and washing.</li>
                                <li>This will be counted as: Excellent-10, Very Good-8-9, Satisfactory-6-7, Poor-5 and below 5, Not attended-0.</li>
                                <li>In above example there are 8 marks for exterior cleaning, therefore rating will be 8/10x100=80%.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function udPrintIntensiveReportCard() {
            var printCard = document.getElementById('intensiveReportPrintCard');
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
                '<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Intensive Report Print</title>' +
                styleTags +
                '<style>@page{size:landscape;margin:10mm;}body{margin:0;padding:10mm;background:#fff;}#intensiveReportPrintCard{display:block!important;visibility:visible!important;opacity:1!important;transform:none!important;}</style>' +
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
