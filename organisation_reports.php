<?php
include 'connection.php';

$alertMessage = '';
$alertType = 'success';
$prefillOrgName = isset($_GET['org']) ? trim($_GET['org']) : '';
$prefillOrgId = isset($_GET['org_id']) ? (int) $_GET['org_id'] : 0;
$isEditOrgFlow = isset($_GET['edit_org']) && $_GET['edit_org'] === '1';
$organisationOptions = [];
$selectedOrganisation = null;

$organisationSql = "
    SELECT
        u.user_id,
        COALESCE(NULLIF(u.user_name, ''), u.username, u.full_name, CONCAT('User #', u.user_id)) AS organisation_name,
        u.email,
        u.status
    FROM Mcc_users u
    ORDER BY organisation_name
";
$organisationResult = $conn->query($organisationSql);
if ($organisationResult) {
    while ($row = $organisationResult->fetch_assoc()) {
        $organisationOptions[] = $row;
        if ($prefillOrgId > 0 && (int) $row['user_id'] === $prefillOrgId) {
            $selectedOrganisation = $row;
        }
    }
}

if ($selectedOrganisation === null && $prefillOrgName !== '') {
    foreach ($organisationOptions as $organisation) {
        if (strcasecmp((string) $organisation['organisation_name'], $prefillOrgName) === 0) {
            $selectedOrganisation = $organisation;
            $prefillOrgId = (int) $organisation['user_id'];
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_report_config'])) {
    $organisationId = isset($_POST['reportOrgId']) ? (int) $_POST['reportOrgId'] : 0;
    $reportType = isset($_POST['reportName']) ? trim($_POST['reportName']) : '';
    $weightPercent = isset($_POST['parameterWeight']) && $_POST['parameterWeight'] !== '' ? (float) $_POST['parameterWeight'] : null;
    $status = isset($_POST['parameterStatus']) ? trim($_POST['parameterStatus']) : 'Active';
    $parameterNames = isset($_POST['parameterName']) && is_array($_POST['parameterName']) ? $_POST['parameterName'] : [];
    $categories = isset($_POST['category']) && is_array($_POST['category']) ? $_POST['category'] : [];

    $validReportTypes = ['Normal Report', 'Intensive Report'];
    $validStatuses = ['Active', 'Inactive'];
    $validCategories = ['Coach Interior', 'Coach Exterior', 'Watering'];

    if ($organisationId <= 0 || !in_array($reportType, $validReportTypes, true)) {
        $alertMessage = 'Please select an organisation and a valid report name.';
        $alertType = 'danger';
    } elseif ($status === '' || !in_array($status, $validStatuses, true)) {
        $alertMessage = 'Please select a valid status.';
        $alertType = 'danger';
    } elseif ($weightPercent !== null && ($weightPercent < 0 || $weightPercent > 100)) {
        $alertMessage = 'Weight must be between 0 and 100.';
        $alertType = 'danger';
    } else {
        $normalizedRows = [];
        for ($i = 0; $i < count($parameterNames); $i++) {
            $pName = trim((string) $parameterNames[$i]);
            $cat = isset($categories[$i]) ? trim((string) $categories[$i]) : '';

            if ($pName === '' && $cat === '') {
                continue;
            }
            if ($pName === '' || !in_array($cat, $validCategories, true)) {
                $normalizedRows = [];
                break;
            }

            $normalizedRows[] = ['name' => $pName, 'category' => $cat];
        }

        if (count($normalizedRows) === 0) {
            $alertMessage = 'Please add at least one valid Parameter and Category row.';
            $alertType = 'danger';
        } else {
            $findUserSql = "
                SELECT user_id, user_name, username, full_name
                FROM Mcc_users
                WHERE user_id = ?
                LIMIT 1
            ";
            $findUser = $conn->prepare($findUserSql);

            if (!$findUser) {
                $alertMessage = 'Unable to prepare user lookup query.';
                $alertType = 'danger';
            } else {
                $findUser->bind_param('i', $organisationId);
                $findUser->execute();
                $userResult = $findUser->get_result();
                $user = $userResult ? $userResult->fetch_assoc() : null;
                $findUser->close();

                if (!$user) {
                    $alertMessage = 'Selected organisation/user not found. Please create the user first in organisation setup.';
                    $alertType = 'danger';
                } else {
                    $userId = (int) $user['user_id'];
                    $organisationName = trim((string) ($user['user_name'] ?? ''));
                    if ($organisationName === '') {
                        $organisationName = trim((string) ($user['username'] ?? ''));
                    }
                    if ($organisationName === '') {
                        $organisationName = trim((string) ($user['full_name'] ?? ''));
                    }
                    if ($organisationName === '') {
                        $organisationName = 'User #' . $userId;
                    }
                    $baseReportName = $organisationName . ' - ' . $reportType;
                    $reportName = $baseReportName;
                    $suffix = 1;

                    while (true) {
                        $checkReport = $conn->prepare('SELECT report_id FROM Mcc_reports WHERE report_name = ? LIMIT 1');
                        if (!$checkReport) {
                            break;
                        }
                        $checkReport->bind_param('s', $reportName);
                        $checkReport->execute();
                        $checkReport->store_result();
                        $exists = $checkReport->num_rows > 0;
                        $checkReport->close();

                        if (!$exists) {
                            break;
                        }

                        $suffix++;
                        $reportName = $baseReportName . ' (' . $suffix . ')';
                    }

                    $conn->begin_transaction();
                    try {
                        $reportLookup = $conn->prepare('SELECT report_id FROM Mcc_reports WHERE user_id = ? AND report_type = ? LIMIT 1');
                        if (!$reportLookup) {
                            throw new Exception('Unable to prepare report lookup query.');
                        }
                        $reportLookup->bind_param('is', $userId, $reportType);
                        $reportLookup->execute();
                        $reportLookup->store_result();
                        $existingReportId = 0;
                        if ($reportLookup->num_rows > 0) {
                            $reportLookup->bind_result($existingReportId);
                            $reportLookup->fetch();
                        }
                        $reportLookup->close();

                        if ($existingReportId > 0) {
                            $updateReport = $conn->prepare('
                                UPDATE Mcc_reports
                                SET report_name = ?, weight_percent = ?, status = ?
                                WHERE report_id = ? AND user_id = ?
                            ');
                            if (!$updateReport) {
                                throw new Exception('Unable to prepare report update query.');
                            }
                            if ($weightPercent === null) {
                                $nullWeight = null;
                                $updateReport->bind_param('sdsii', $reportName, $nullWeight, $status, $existingReportId, $userId);
                            } else {
                                $updateReport->bind_param('sdsii', $reportName, $weightPercent, $status, $existingReportId, $userId);
                            }
                            if (!$updateReport->execute()) {
                                throw new Exception('Unable to update report details.');
                            }
                            $reportId = $existingReportId;
                            $updateReport->close();

                            $deleteParams = $conn->prepare('DELETE FROM Mcc_parameters WHERE report_id = ? AND user_id = ?');
                            if (!$deleteParams) {
                                throw new Exception('Unable to prepare parameter cleanup query.');
                            }
                            $deleteParams->bind_param('ii', $reportId, $userId);
                            if (!$deleteParams->execute()) {
                                throw new Exception('Unable to clear old parameters.');
                            }
                            $deleteParams->close();
                        } else {
                            $insertReport = $conn->prepare('
                                INSERT INTO Mcc_reports (user_id, report_name, report_type, weight_percent, status)
                                VALUES (?, ?, ?, ?, ?)
                            ');
                            if (!$insertReport) {
                                throw new Exception('Unable to prepare report insert query.');
                            }

                            if ($weightPercent === null) {
                                $nullWeight = null;
                                $insertReport->bind_param('issds', $userId, $reportName, $reportType, $nullWeight, $status);
                            } else {
                                $insertReport->bind_param('issds', $userId, $reportName, $reportType, $weightPercent, $status);
                            }

                            if (!$insertReport->execute()) {
                                throw new Exception('Unable to save report details.');
                            }

                            $reportId = (int) $conn->insert_id;
                            $insertReport->close();
                        }

                        $insertParam = $conn->prepare('
                            INSERT INTO Mcc_parameters
                                (user_id, report_id, parameter_name, category, assigned_by_user_id, status)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ');
                        if (!$insertParam) {
                            throw new Exception('Unable to prepare parameter insert query.');
                        }

                        foreach ($normalizedRows as $row) {
                            $paramName = $row['name'];
                            $category = $row['category'];
                            $insertParam->bind_param('iissis', $userId, $reportId, $paramName, $category, $userId, $status);
                            if (!$insertParam->execute()) {
                                throw new Exception('Unable to save parameter: ' . $paramName);
                            }
                        }

                        $insertParam->close();
                        $conn->commit();

                        header('Location: organisation_list.php?saved=1');
                        exit;
                    } catch (Throwable $e) {
                        $conn->rollback();
                        $alertMessage = 'Save failed: ' . $e->getMessage();
                        $alertType = 'danger';
                    }
                }
            }
        }
    }
}

$configuredRows = [];
$configuredSql = "
    SELECT
        p.user_id,
        COALESCE(NULLIF(u.user_name, ''), u.username, u.full_name, CONCAT('User #', u.user_id)) AS organisation_name,
        r.report_name,
        p.parameter_name,
        p.category,
        r.weight_percent,
        p.status
    FROM Mcc_parameters p
    INNER JOIN Mcc_reports r ON r.report_id = p.report_id
    INNER JOIN Mcc_users u ON u.user_id = p.user_id
    ORDER BY p.created_at DESC
    LIMIT 100
";

$configuredResult = $conn->query($configuredSql);
if ($configuredResult) {
    while ($row = $configuredResult->fetch_assoc()) {
        $configuredRows[] = $row;
    }
}

if ($prefillOrgId > 0) {
    $configuredRows = array_values(array_filter($configuredRows, function ($row) use ($prefillOrgId) {
        return isset($row['user_id']) && (int) $row['user_id'] === $prefillOrgId;
    }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organisation Reports Setup - MCC Railway Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
      <?php include 'sidebar.php'; ?> 

        <div class="main-content flex-grow-1">
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

            <div class="content-area">
                <div class="page-header mb-4">
                    <h1><i class="bi bi-journal-text"></i> Organisation Reports Setup</h1>
                    <p class="text-muted"><?php echo $isEditOrgFlow ? 'Update report definitions, parameters, and categories for the selected organisation' : 'Add report definitions, parameters, and categories for each organisation'; ?></p>
                </div>

                <?php if ($alertMessage !== ''): ?>
                <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($alertMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="mb-4">
                    <a href="organisation.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Organisation Setup
                    </a>
                    <a href="organisation_list.php" class="btn btn-outline-primary ms-2">
                        <i class="bi bi-list-check"></i> Go To Organisation List
                    </a>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Add Organisation Report Configuration</h5>
                    </div>
                    <div class="card-body">
                        <form id="orgReportForm" method="post" novalidate>
                            <input type="hidden" name="save_report_config" value="1">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reportOrgId" class="form-label">Organisation <span class="text-danger">*</span></label>
                                    <select class="form-select" id="reportOrgId" name="reportOrgId" required>
                                        <option value="">-- Select Organisation --</option>
                                        <?php foreach ($organisationOptions as $organisation): ?>
                                        <?php
                                            $organisationLabel = $organisation['organisation_name'] . ' (' . $organisation['email'] . ')';
                                            $selected = ($prefillOrgId > 0 && (int) $organisation['user_id'] === $prefillOrgId) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo (int) $organisation['user_id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($organisationLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="reportName" class="form-label">Report Name <span class="text-danger">*</span></label>
                                    <select class="form-select" id="reportName" name="reportName" required>
                                        <option value="">-- Select Report --</option>
                                        <option value="Normal Report">Normal Report</option>
                                        <option value="Intensive Report">Intensive Report</option>
                                    </select>
                                </div>
                            </div>

                     

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="parameterWeight" class="form-label">Weight (%)</label>
                                    <input type="number" class="form-control" id="parameterWeight" name="parameterWeight" min="0" max="100" placeholder="Enter weight">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="parameterStatus" class="form-label">Status</label>
                                    <select class="form-select" id="parameterStatus" name="parameterStatus">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div id="reportParameterBox" class="card border-primary-subtle mb-3 d-none">
                                <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0" id="reportParameterTitle">Report Parameters</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addParameterRowBtn">
                                        <i class="bi bi-plus-circle"></i> Add Row
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0" id="reportParameterTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 80px;">#</th>
                                                    <th>Parameter</th>
                                                    <th style="width: 260px;">Category</th>
                                                    <th style="width: 120px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reportParameterTableBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                            <div class="d-grid gap-2 d-sm-flex mt-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Save Report Configuration
                                </button>
                                <button type="reset" class="btn btn-secondary btn-lg">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Configured Parameters</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Organisation</th>
                                    <th>Report</th>
                                    <th>Parameter</th>
                                    <th>Category</th>
                                    <th>Weight</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($configuredRows) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No configured parameters found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($configuredRows as $row): ?>
                                <?php $rowStatus = strtolower((string) $row['status']) === 'active' ? 'bg-success' : 'bg-secondary'; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['organisation_name']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['report_name']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['parameter_name']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['category']); ?></td>
                                    <td><?php echo $row['weight_percent'] === null ? '-' : htmlspecialchars((string) $row['weight_percent']) . '%'; ?></td>
                                    <td><span class="badge <?php echo $rowStatus; ?>"><?php echo htmlspecialchars((string) $row['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Available Organisations</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Organisation</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($organisationOptions) === 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No organisations found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($organisationOptions as $organisation): ?>
                                <?php $organisationStatusClass = strtolower((string) $organisation['status']) === 'active' ? 'bg-success' : 'bg-secondary'; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $organisation['organisation_name']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $organisation['email']); ?></td>
                                    <td><span class="badge <?php echo $organisationStatusClass; ?>"><?php echo htmlspecialchars((string) $organisation['status']); ?></span></td>
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
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });

            sidebarOverlay.addEventListener('click', function () {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        const reportName = document.getElementById('reportName');
        const reportParameterBox = document.getElementById('reportParameterBox');
        const reportParameterTitle = document.getElementById('reportParameterTitle');
        const reportParameterTableBody = document.getElementById('reportParameterTableBody');
        const addParameterRowBtn = document.getElementById('addParameterRowBtn');

        const reportOrgId = document.getElementById('reportOrgId');

        const sharedCategories = ['Coach Interior', 'Coach Exterior', 'Watering'];
        let rowCount = 0;

        function buildCategoryOptions() {
            return sharedCategories
                .map((category) => `<option value="${category}">${category}</option>`)
                .join('');
        }

        function reindexRows() {
            const rows = reportParameterTableBody.querySelectorAll('tr');
            rows.forEach((row, index) => {
                const indexCell = row.querySelector('.row-index');
                if (indexCell) {
                    indexCell.textContent = index + 1;
                }
            });
        }

        function addParameterRow() {
            rowCount += 1;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="row-index"></td>
                <td>
                    <input type="text" name="parameterName[]" class="form-control form-control-sm parameter-input" placeholder="Enter parameter" required>
                </td>
                <td>
                    <select name="category[]" class="form-select form-select-sm category-input" required>
                        <option value="">-- Select Category --</option>
                        ${buildCategoryOptions()}
                    </select>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-parameter-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            reportParameterTableBody.appendChild(row);
            reindexRows();
        }

        reportName.addEventListener('change', function () {
            if (this.value === 'Normal Report' || this.value === 'Intensive Report') {
                reportParameterBox.classList.remove('d-none');
                reportParameterTitle.textContent = `${this.value} - Add Parameters & Categories`;
                if (reportParameterTableBody.children.length === 0) {
                    addParameterRow();
                }
                return;
            }

            reportParameterBox.classList.add('d-none');
            reportParameterTableBody.innerHTML = '';
            rowCount = 0;
        });

        addParameterRowBtn.addEventListener('click', function () {
            addParameterRow();
        });

        reportParameterTableBody.addEventListener('click', function (e) {
            const removeButton = e.target.closest('.remove-parameter-row');
            if (!removeButton) {
                return;
            }

            const row = removeButton.closest('tr');
            if (row) {
                row.remove();
                reindexRows();
            }

            if (reportParameterTableBody.children.length === 0) {
                addParameterRow();
            }
        });

        document.getElementById('orgReportForm').addEventListener('submit', function (e) {

            if (!reportParameterBox.classList.contains('d-none')) {
                const rows = reportParameterTableBody.querySelectorAll('tr');
                const hasEmptyValues = Array.from(rows).some((row) => {
                    const parameterValue = row.querySelector('.parameter-input')?.value.trim();
                    const categoryValue = row.querySelector('.category-input')?.value;
                    return !parameterValue || !categoryValue;
                });

                if (hasEmptyValues) {
                    e.preventDefault();
                    alert('Please fill all Parameter and Category rows before saving.');
                    return;
                }
            }
        });

        // Show one row by default for better UX.
        addParameterRow();
        reportParameterBox.classList.remove('d-none');
        reportParameterTitle.textContent = 'Add Parameters & Categories';

        if (reportOrgId && reportOrgId.value !== '') {
            reportOrgId.addEventListener('change', function () {
                // no-op, placeholder for future org-specific filtering
            });
        }
    </script>
</body>
</html>
