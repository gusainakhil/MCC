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
    'reportType' => 'Attendance Report',
    'pageTitle' => 'Attendance Report',
    'pageDescription' => 'Static attendance sheet with employee details, location and GPS tracking.',
    'pageIcon' => 'bi-person-check',
    'pageAccent' => '#8e44ad',
    'activePage' => 'dashboard',
];

$attendanceRows = [
    [
        'employee_name' => 'Aman Kumar',
        'employee_id' => 'EMP-001',
        'in_time' => '08:05',
        'out_time' => '17:55',
        'worked_time' => '9h 50m',
        'photo' => 'AK',
        'location' => 'Dehradun Coach Depot',
        'latitude' => '30.3165',
        'longitude' => '78.0322',
    ],
    [
        'employee_name' => 'Rohit Sharma',
        'employee_id' => 'EMP-002',
        'in_time' => '08:20',
        'out_time' => '18:10',
        'worked_time' => '9h 50m',
        'photo' => 'RS',
        'location' => 'Ahmedabad Yard',
        'latitude' => '23.0225',
        'longitude' => '72.5714',
    ],
    [
        'employee_name' => 'Neha Verma',
        'employee_id' => 'EMP-003',
        'in_time' => '07:50',
        'out_time' => '17:15',
        'worked_time' => '9h 25m',
        'photo' => 'NV',
        'location' => 'Western Railway Workshop',
        'latitude' => '21.1702',
        'longitude' => '72.8311',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report | MCC User Dashboard</title>
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
                            <p class="panel-subtitle mb-0">Attendance filter placeholders. Values are static for now.</p>
                        </div>
                    </div>
                    <div class="panel-card__body">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" value="2026-04-14">
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label">Train No</label>
                                <input type="text" class="form-control" value="12345">
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label">Shift</label>
                                <select class="form-select">
                                    <option selected>Morning</option>
                                    <option>Evening</option>
                                    <option>Night</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3 d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-soft">Reset</button>
                                <button type="button" class="btn btn-brand">Apply Filter</button>
                                <button type="button" class="btn btn-outline-primary btn-soft" onclick="udPrintAttendanceReportCard();">
                                    <i class="bi bi-printer me-1"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="attendanceReportPrintCard" class="panel-card reveal mt-3">
                    <div class="panel-card__body">
                        <div class="attendance-sheet">
                            <div class="attendance-sheet__head">
                                <span>Attendance Register</span>
                                <h3>Employee Attendance Sheet</h3>
                            </div>

                            <div class="attendance-sheet__meta">
                                <div>Date: .......................</div>
                                <div>Train No: ............................</div>
                                <div>Depot: <?php echo ud_h($stationLabel); ?></div>
                                <div>Contractor: ..................................</div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered attendance-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>Employee Name</th>
                                            <th>Employee ID</th>
                                            <th>In Time</th>
                                            <th>Out Time</th>
                                            <th>Worked Time</th>
                                            <th>Photo</th>
                                            <th>Location</th>
                                            <th>Latitude</th>
                                            <th>Longitude</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendanceRows as $index => $row): ?>
                                            <tr>
                                                <td><?php echo (int) ($index + 1); ?></td>
                                                <td><?php echo ud_h($row['employee_name']); ?></td>
                                                <td><?php echo ud_h($row['employee_id']); ?></td>
                                                <td><?php echo ud_h($row['in_time']); ?></td>
                                                <td><?php echo ud_h($row['out_time']); ?></td>
                                                <td><?php echo ud_h($row['worked_time']); ?></td>
                                                <td>
                                                    <div class="attendance-photo"><?php echo ud_h($row['photo']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="attendance-location"><?php echo ud_h($row['location']); ?></div>
                                                </td>
                                                <td><?php echo ud_h($row['latitude']); ?></td>
                                                <td><?php echo ud_h($row['longitude']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="attendance-summary mt-3">
                                <div><span>Total Employees</span><strong><?php echo (int) count($attendanceRows); ?></strong></div>
                                <div><span>Calculated Time</span><strong>Static preview</strong></div>
                                <div><span>Location Tracking</span><strong>Enabled</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function udPrintAttendanceReportCard() {
            var printCard = document.getElementById('attendanceReportPrintCard');
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
                '<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Attendance Report Print</title>' +
                styleTags +
                '<style>@page{size:landscape;margin:10mm;}body{margin:0;padding:10mm;background:#fff;}#attendanceReportPrintCard{display:block!important;visibility:visible!important;opacity:1!important;transform:none!important;}</style>' +
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
