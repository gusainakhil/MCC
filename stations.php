<?php
include 'connection.php';

$zones = [];
$zoneDivisionMap = [];
$stations = [];
$alertMessage = '';
$alertType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_station'])) {
    $zoneId = isset($_POST['stationZone']) ? (int) $_POST['stationZone'] : 0;
    $divisionId = isset($_POST['stationDivision']) ? (int) $_POST['stationDivision'] : 0;
    $stationName = isset($_POST['stationName']) ? trim($_POST['stationName']) : '';

    if ($zoneId <= 0 || $divisionId <= 0 || $stationName === '') {
        $alertMessage = 'Please fill Zone, Division and Station Name.';
        $alertType = 'danger';
    } else {
        $divisionCheck = $conn->prepare('SELECT division_id FROM Mcc_divisions WHERE division_id = ? AND zone_id = ? LIMIT 1');
        if ($divisionCheck) {
            $divisionCheck->bind_param('ii', $divisionId, $zoneId);
            $divisionCheck->execute();
            $divisionCheck->store_result();

            if ($divisionCheck->num_rows === 0) {
                $alertMessage = 'Selected division does not belong to selected zone.';
                $alertType = 'danger';
            } else {
                $insertStation = $conn->prepare('INSERT INTO Mcc_stations (station_name, division_id, status) VALUES (?, ?, ?)');
                if ($insertStation) {
                    $status = 'Active';
                    $insertStation->bind_param('sis', $stationName, $divisionId, $status);

                    if ($insertStation->execute()) {
                        header('Location: stations.php?created=1');
                        exit;
                    }

                    if ((int) $conn->errno === 1062) {
                        $alertMessage = 'Station already exists for this division.';
                    } else {
                        $alertMessage = 'Unable to create station. Please try again.';
                    }
                    $alertType = 'danger';
                    $insertStation->close();
                } else {
                    $alertMessage = 'Unable to prepare station create query.';
                    $alertType = 'danger';
                }
            }

            $divisionCheck->close();
        } else {
            $alertMessage = 'Unable to validate zone and division.';
            $alertType = 'danger';
        }
    }
}

if (isset($_GET['created']) && $_GET['created'] === '1') {
    $alertMessage = 'Station created successfully.';
    $alertType = 'success';
}

$zoneSql = "
    SELECT
        z.zone_id,
        z.zone_name
    FROM Mcc_zones z
    ORDER BY z.zone_name
";

$zoneResult = $conn->query($zoneSql);
if ($zoneResult) {
    while ($row = $zoneResult->fetch_assoc()) {
        $zoneId = (int) $row['zone_id'];
        $zones[] = $row;
        $zoneDivisionMap[$zoneId] = [];
    }
}

$divisionSql = "
    SELECT division_id, division_name, zone_id
    FROM Mcc_divisions
    ORDER BY division_name
";

$divisionResult = $conn->query($divisionSql);
if ($divisionResult) {
    while ($row = $divisionResult->fetch_assoc()) {
        $zoneId = (int) $row['zone_id'];
        if (!isset($zoneDivisionMap[$zoneId])) {
            $zoneDivisionMap[$zoneId] = [];
        }
        $zoneDivisionMap[$zoneId][] = [
            'division_id' => (int) $row['division_id'],
            'division_name' => $row['division_name']
        ];
    }
}

$stationSql = "
    SELECT
        s.station_id,
        s.station_name,
        s.status,
        z.zone_name,
        COUNT(c.contract_id) AS contracts_count
    FROM Mcc_stations s
    LEFT JOIN Mcc_divisions d ON d.division_id = s.division_id
    LEFT JOIN Mcc_zones z ON z.zone_id = d.zone_id
    LEFT JOIN Mcc_contract_details c ON c.station_id = s.station_id
    GROUP BY s.station_id, s.station_name, s.status, z.zone_name
    ORDER BY s.station_name
";

$stationResult = $conn->query($stationSql);
if ($stationResult) {
    while ($row = $stationResult->fetch_assoc()) {
        $stations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stations - MCC Railway Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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
                        <i class="bi bi-train-freight"></i> Railway Mechanized Cleaning Coach Management System
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
                    <h1><i class="bi bi-building"></i> Station Management</h1>
                    <p class="text-muted">Add and view railway stations</p>
                </div>

                <?php if ($alertMessage !== ''): ?>
                <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($alertMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

             

                <div class="row mb-4">
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header" style="background: linear-gradient(90deg, rgba(13,110,253,0.10), rgba(32,201,151,0.08));">
                                <h5 class="mb-0"><i class="bi bi-building"></i> Station Details</h5>
                            </div>
                            <div class="card-body">
                                <form id="addStationForm" method="post" novalidate>
                                    <input type="hidden" name="add_station" value="1">
                                    <div class="mb-3">
                                        <label for="stationZone" class="form-label">Select Zone <span class="text-danger">*</span></label>
                                        <select class="form-select" id="stationZone" name="stationZone" required>
                                            <option value="">-- Select Zone --</option>
                                            <?php foreach ($zones as $zone): ?>
                                            <option value="<?php echo (int) $zone['zone_id']; ?>"><?php echo htmlspecialchars($zone['zone_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Select the zone this station belongs to</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="stationDivision" class="form-label">Division</label>
                                        <select class="form-select" id="stationDivision" name="stationDivision" required>
                                            <option value="">-- Select Division --</option>
                                        </select>
                                        <small class="form-text text-muted">Division options are loaded based on selected zone</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="stationName" class="form-label">Station Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="stationName" name="stationName" placeholder="e.g., Central Station" required>
                                        <small class="form-text text-muted">Enter unique station name</small>
                                    </div>

                                    <div class="d-grid gap-2 d-sm-flex">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-plus-circle"></i> Add Station
                                        </button>
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="bi bi-arrow-counterclockwise"></i> Clear
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header" style="background: linear-gradient(90deg, rgba(13,110,253,0.10), rgba(32,201,151,0.08));">
                                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Station List</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Station Name</th>
                                            <th>Zone</th>
                                            <th>Contracts</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($stations) === 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No station records found.</td>
                                        </tr>
                                        <?php else: ?>
                                        <?php
                                            $zoneBadgeClasses = ['bg-primary', 'bg-info', 'bg-warning text-dark', 'bg-secondary'];
                                            $zoneBadgeIndex = 0;
                                        ?>
                                        <?php foreach ($stations as $station): ?>
                                        <?php
                                            $statusClass = strtolower((string) $station['status']) === 'active' ? 'bg-success' : 'bg-secondary';
                                            $zoneBadgeClass = $zoneBadgeClasses[$zoneBadgeIndex % count($zoneBadgeClasses)];
                                            $zoneBadgeIndex++;
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($station['station_name']); ?></strong></td>
                                            <td><span class="badge <?php echo $zoneBadgeClass; ?>"><?php echo htmlspecialchars((string) $station['zone_name']); ?></span></td>
                                            <td><span class="badge bg-info"><?php echo (int) $station['contracts_count']; ?></span></td>
                                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($station['status']); ?></span></td>
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
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        
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

        const zoneDivisionMap = <?php echo json_encode($zoneDivisionMap, JSON_UNESCAPED_UNICODE); ?>;

        const stationZoneSelect = document.getElementById('stationZone');
        const stationDivisionInput = document.getElementById('stationDivision');
        const addStationForm = document.getElementById('addStationForm');

        stationZoneSelect.addEventListener('change', function () {
            const zoneId = this.value;
            const divisions = zoneDivisionMap[zoneId] || [];

            stationDivisionInput.innerHTML = '<option value="">-- Select Division --</option>';

            divisions.forEach(function (division) {
                const option = document.createElement('option');
                option.value = division.division_id;
                option.textContent = division.division_name;
                stationDivisionInput.appendChild(option);
            });
        });

        addStationForm.addEventListener('reset', function () {
            stationDivisionInput.innerHTML = '<option value="">-- Select Division --</option>';
        });
    </script>
</body>
</html>
