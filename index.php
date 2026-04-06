<?php
include 'connection.php';

function getCount($conn, $sql)
{
    $result = $conn->query($sql);
    if ($result && ($row = $result->fetch_row())) {
        return (int) $row[0];
    }
    return 0;
}

$zoneCount = getCount($conn, "SELECT COUNT(*) FROM Mcc_zones");
$divisionCount = getCount($conn, "SELECT COUNT(*) FROM Mcc_divisions");
$stationCount = getCount($conn, "SELECT COUNT(*) FROM Mcc_stations");
$userCount = getCount($conn, "SELECT COUNT(*) FROM Mcc_users");
$trainCount = getCount($conn, "SELECT COUNT(*) FROM Mcc_train_information");

$normalDailyCount = getCount(
    $conn,
    "SELECT COUNT(*) FROM Mcc_normal_report_data WHERE DATE(created_at) = CURDATE()"
);

$intensiveDailyCount = getCount(
    $conn,
    "SELECT COUNT(*) FROM Mcc_intensive_report_data WHERE DATE(created_at) = CURDATE()"
);

$expiringContracts = [];
$expiringSql = "
    SELECT
        contractor_name,
        agreement_no,
        station_id,
        contract_end_date,
        DATEDIFF(contract_end_date, CURDATE()) AS days_left
    FROM Mcc_contract_details
    WHERE contract_end_date >= CURDATE()
      AND contract_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY contract_end_date ASC
    LIMIT 10
";

$expiringResult = $conn->query($expiringSql);
if ($expiringResult) {
    while ($row = $expiringResult->fetch_assoc()) {
        $expiringContracts[] = $row;
    }
}

$stationNames = [];
$stationResult = $conn->query("SELECT station_id, station_name FROM Mcc_stations");
if ($stationResult) {
    while ($row = $stationResult->fetch_assoc()) {
        $stationNames[(int) $row['station_id']] = $row['station_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCC Railway Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(13, 110, 253, 0.07), transparent 28%),
                radial-gradient(circle at top right, rgba(255, 193, 7, 0.08), transparent 24%),
                #f8fafc;
        }

        .page-header {
            padding: 1rem 1.25rem;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(32, 201, 151, 0.08));
            border: 1px solid rgba(13, 110, 253, 0.08);
            margin-bottom: 1.25rem;
        }

        .page-header h1,
        .page-header p,
        .stat-card,
        .card.shadow-sm {
            animation: fadeUp 0.55s ease both;
        }

        .stat-card {
            border: 1px solid rgba(0, 0, 0, 0.06);
            overflow: hidden;
            position: relative;
        }

        .stat-card::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #0d6efd, #20c997, #ffc107, #dc3545);
            opacity: 0.9;
        }

        .stat-card,
        .card.shadow-sm {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover,
        .card.shadow-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08) !important;
        }

        .stat-card .card-body {
            position: relative;
            z-index: 1;
        }

        .stat-card.zone {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(13, 110, 253, 0.02));
        }

        .stat-card.division {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.08), rgba(25, 135, 84, 0.02));
        }

        .stat-card.station {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.12), rgba(255, 193, 7, 0.03));
        }

        .stat-card.user {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.08), rgba(220, 53, 69, 0.02));
        }

        .stat-card.train {
            background: linear-gradient(135deg, rgba(111, 66, 193, 0.08), rgba(111, 66, 193, 0.02));
        }

        .stat-card.normal {
            background: linear-gradient(135deg, rgba(32, 201, 151, 0.10), rgba(32, 201, 151, 0.03));
        }

        .stat-card.intensive {
            background: linear-gradient(135deg, rgba(253, 126, 20, 0.10), rgba(253, 126, 20, 0.03));
        }

        .expiring-card {
            border: 1px solid rgba(0, 0, 0, 0.06);
            overflow: hidden;
            position: relative;
        }

        .expiring-card::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #fd7e14, #ffc107);
            opacity: 0.95;
        }

        .expiring-card .card-header {
            background: linear-gradient(90deg, rgba(220, 53, 69, 0.08), rgba(255, 193, 7, 0.10)) !important;
            border-bottom: 1px solid rgba(220, 53, 69, 0.08);
        }

        .expiring-card .table thead th {
            background: rgba(248, 249, 250, 0.95);
            color: #495057;
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.10s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.20s; }
        .stat-card:nth-child(5) { animation-delay: 0.25s; }

        .content-area .row.mb-4.g-3:nth-of-type(2) .stat-card:nth-child(1) { animation-delay: 0.30s; }
        .content-area .row.mb-4.g-3:nth-of-type(2) .stat-card:nth-child(2) { animation-delay: 0.35s; }

        .card.shadow-sm { animation-delay: 0.40s; }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
<?php include 'sidebar.php'; ?> 

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button class="menu-toggle d-lg-none" id="sidebarToggle" type="button">
                        <i class="bi bi-list"></i>
                    </button>
                    <span class="navbar-brand fw-bold">
                        <i class="bi bi-train-freight" style="color: #1f2937;"></i> Railway Mechanized Cleaning Coach Management System
                    </span>
                    <div class="ms-auto d-flex align-items-center gap-3">
                        <span class="text-muted small">Welcome, Admin User</span>
                        <img src="https://via.placeholder.com/40" alt="Avatar" class="rounded-circle">
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="content-area">
                <div class="page-header">
                    <h1><i class="bi bi-speedometer2" style="color: #1f2937;"></i> Dashboard</h1>
                    <p class="text-muted">Overview of MCC Management System</p>
                </div>

                <!-- Database Summary Cards -->
                <div class="row mb-4 g-3">
                    <div class="col-xl col-lg-4 col-md-6 stat-card-wrap">
                        <div class="card stat-card zone h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Zones</p>
                                    <h3 class="mb-0"><?php echo $zoneCount; ?></h3>
                                </div>
                                <i class="bi bi-globe2 stat-icon text-dark"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl col-lg-4 col-md-6 stat-card-wrap">
                        <div class="card stat-card division h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Divisions</p>
                                    <h3 class="mb-0"><?php echo $divisionCount; ?></h3>
                                </div>
                                <i class="bi bi-diagram-3 stat-icon text-dark"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl col-lg-4 col-md-6 stat-card-wrap">
                        <div class="card stat-card station h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Stations</p>
                                    <h3 class="mb-0"><?php echo $stationCount; ?></h3>
                                </div>
                                <i class="bi bi-building stat-icon text-dark"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl col-lg-4 col-md-6 stat-card-wrap">
                        <div class="card stat-card user h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Users</p>
                                    <h3 class="mb-0"><?php echo $userCount; ?></h3>
                                </div>
                                <i class="bi bi-people-fill stat-icon text-dark"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl col-lg-4 col-md-6 stat-card-wrap">
                        <div class="card stat-card train h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Trains</p>
                                    <h3 class="mb-0"><?php echo $trainCount; ?></h3>
                                </div>
                                <i class="bi bi-train-front-fill stat-icon text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4 g-3 flex-nowrap flex-md-nowrap">
                    <div class="col-6 stat-card-wrap">
                        <div class="card stat-card normal h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Daily Normal Report Count</p>
                                    <h3 class="mb-0"><?php echo $normalDailyCount; ?></h3>
                                </div>
                                <i class="bi bi-bar-chart-line-fill stat-icon text-dark"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 stat-card-wrap">
                        <div class="card stat-card intensive h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Daily Intensive Report Count</p>
                                    <h3 class="mb-0"><?php echo $intensiveDailyCount; ?></h3>
                                </div>
                                <i class="bi bi-activity stat-icon text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expiring Organisations List -->
                <div class="card shadow-sm expiring-card">
                    <div class="card-header" style="background: linear-gradient(90deg, rgba(13,110,253,0.10), rgba(32,201,151,0.08));">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Organisations Expiring Soon</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Organisation</th>
                                    <th>Agreement No</th>
                                    <th>Station</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($expiringContracts) === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No contracts expiring in the next 30 days.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($expiringContracts as $contract): ?>
                                <?php
                                    $daysLeft = (int) $contract['days_left'];
                                    $badgeClass = 'bg-success';
                                    if ($daysLeft <= 3) {
                                        $badgeClass = 'bg-danger';
                                    } elseif ($daysLeft <= 10) {
                                        $badgeClass = 'bg-warning text-dark';
                                    } elseif ($daysLeft <= 20) {
                                        $badgeClass = 'bg-info text-dark';
                                    }
                                    $stationId = (int) $contract['station_id'];
                                    $stationName = isset($stationNames[$stationId]) ? $stationNames[$stationId] : ('Station #' . $stationId);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contract['contractor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($contract['agreement_no']); ?></td>
                                    <td><?php echo htmlspecialchars($stationName); ?></td>
                                    <td><?php echo htmlspecialchars($contract['contract_end_date']); ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $daysLeft; ?> Days Left</span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Mobile Menu Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });

            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });

            // Close sidebar when a link is clicked
            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                });
            });
        }

        // Responsive sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
    </script>
</body>
</html>
