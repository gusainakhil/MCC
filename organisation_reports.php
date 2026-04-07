<?php
include 'connection.php';

$alertMessage = '';
$alertType = 'success';
$prefillOrgName = isset($_GET['org']) ? trim($_GET['org']) : '';
$prefillOrgId = isset($_GET['org_id']) ? (int) $_GET['org_id'] : 0;
$prefillReportType = isset($_GET['report_type']) ? trim($_GET['report_type']) : '';
$editingParameterId = isset($_GET['edit_param_id']) ? (int) $_GET['edit_param_id'] : 0;
$isEditOrgFlow = isset($_GET['edit_org']) && $_GET['edit_org'] === '1';
$organisationOptions = [];
$selectedOrganisation = null;
$formParameterRows = [];
$validReportTypes = ['Normal Report', 'Intensive Report', 'Attendance Report'];
$validStatuses = ['Active', 'Inactive'];
$validCategories = ['Coach Interior', 'Coach Exterior', 'Watering'];
$reportPageUrls = [
    'Normal Report' => 'normal_report.php',
    'Intensive Report' => 'intensive_report.php',
    'Attendance Report' => 'attendence.php',
];
$dbSupportedReportTypes = $validReportTypes;
$attendanceReportSupported = true;
$reportDefaults = [];
$prefillWeightPercent = '';
$prefillParameterStatus = 'Active';

$reportTypeMetaSql = "
    SELECT COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Mcc_reports'
      AND COLUMN_NAME = 'report_type'
    LIMIT 1
";
$reportTypeMetaResult = $conn->query($reportTypeMetaSql);
if ($reportTypeMetaResult) {
    $reportTypeMetaRow = $reportTypeMetaResult->fetch_assoc();
    if ($reportTypeMetaRow && isset($reportTypeMetaRow['COLUMN_TYPE'])) {
        $columnType = (string) $reportTypeMetaRow['COLUMN_TYPE'];
        $enumValues = [];
        if (preg_match_all("/'([^']+)'/", $columnType, $matches)) {
            $enumValues = $matches[1];
        }

        if (count($enumValues) > 0) {
            $filteredReportTypes = array_values(array_filter($validReportTypes, function ($type) use ($enumValues) {
                return in_array($type, $enumValues, true);
            }));
            if (count($filteredReportTypes) > 0) {
                $dbSupportedReportTypes = $filteredReportTypes;
            }
        }
    }
}
$attendanceReportSupported = in_array('Attendance Report', $dbSupportedReportTypes, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_report_config'])) {
    $prefillWeightPercent = isset($_POST['parameterWeight']) ? trim((string) $_POST['parameterWeight']) : '';
    $postedStatus = isset($_POST['parameterStatus']) ? trim((string) $_POST['parameterStatus']) : '';
    if (in_array($postedStatus, $validStatuses, true)) {
        $prefillParameterStatus = $postedStatus;
    }
}

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

if ($editingParameterId > 0) {
    $editParameterSql = '
        SELECT
            p.parameter_id,
            p.parameter_name,
            p.category,
            p.user_id,
            r.report_type
        FROM Mcc_parameters p
        INNER JOIN Mcc_reports r ON r.report_id = p.report_id
        WHERE p.parameter_id = ?
        LIMIT 1
    ';
    $editParameterStmt = $conn->prepare($editParameterSql);
    if ($editParameterStmt) {
        $editParameterStmt->bind_param('i', $editingParameterId);
        $editParameterStmt->execute();
        $editParameterResult = $editParameterStmt->get_result();
        $editParameterRow = $editParameterResult ? $editParameterResult->fetch_assoc() : null;
        $editParameterStmt->close();

        if ($editParameterRow) {
            if ($prefillOrgId <= 0) {
                $prefillOrgId = (int) $editParameterRow['user_id'];
            }
            if ($prefillReportType === '') {
                $prefillReportType = (string) $editParameterRow['report_type'];
            }

            $formParameterRows[] = [
                'id' => (int) $editParameterRow['parameter_id'],
                'name' => (string) $editParameterRow['parameter_name'],
                'category' => (string) $editParameterRow['category'],
            ];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_report_config'])) {
    $organisationId = isset($_POST['reportOrgId']) ? (int) $_POST['reportOrgId'] : 0;
    if ($organisationId > 0 && $prefillOrgId <= 0) {
        $prefillOrgId = $organisationId;
    }
    $reportType = isset($_POST['reportName']) ? trim($_POST['reportName']) : '';
    if ($reportType !== '') {
        $prefillReportType = $reportType;
    }
    $pageUrl = isset($reportPageUrls[$reportType]) ? $reportPageUrls[$reportType] : '';
    $weightPercent = isset($_POST['parameterWeight']) && $_POST['parameterWeight'] !== '' ? (float) $_POST['parameterWeight'] : null;
    $status = isset($_POST['parameterStatus']) ? trim($_POST['parameterStatus']) : 'Active';
    $parameterIds = isset($_POST['parameterId']) && is_array($_POST['parameterId']) ? $_POST['parameterId'] : [];
    $parameterNames = isset($_POST['parameterName']) && is_array($_POST['parameterName']) ? $_POST['parameterName'] : [];
    $categories = isset($_POST['category']) && is_array($_POST['category']) ? $_POST['category'] : [];

    if ($organisationId <= 0 || !in_array($reportType, $dbSupportedReportTypes, true)) {
        $alertMessage = 'Please select an organisation and a valid report name.';
        $alertType = 'danger';
        if ($reportType === 'Attendance Report' && !$attendanceReportSupported) {
            $alertMessage = 'Attendance Report is not enabled in database yet. Please ask admin to add it in Mcc_reports.report_type enum.';
        }
    } elseif ($status === '' || !in_array($status, $validStatuses, true)) {
        $alertMessage = 'Please select a valid status.';
        $alertType = 'danger';
    } elseif ($weightPercent !== null && ($weightPercent < 0 || $weightPercent > 100)) {
        $alertMessage = 'Weight must be between 0 and 100.';
        $alertType = 'danger';
    } else {
        $normalizedRows = [];
        for ($i = 0; $i < count($parameterNames); $i++) {
            $pId = isset($parameterIds[$i]) ? (int) $parameterIds[$i] : 0;
            $pName = trim((string) $parameterNames[$i]);
            $cat = isset($categories[$i]) ? trim((string) $categories[$i]) : '';

            if ($pName === '' && $cat === '') {
                continue;
            }
            if ($pName === '' || !in_array($cat, $validCategories, true)) {
                $normalizedRows = [];
                break;
            }

            $normalizedRows[] = ['id' => $pId, 'name' => $pName, 'category' => $cat];
        }

        if ($reportType !== 'Attendance Report' && count($normalizedRows) === 0) {
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
                                SET report_name = ?, weight_percent = ?, status = ?, page_url = ?
                                WHERE report_id = ? AND user_id = ?
                            ');
                            if (!$updateReport) {
                                throw new Exception('Unable to prepare report update query.');
                            }
                            if ($weightPercent === null) {
                                $nullWeight = null;
                                $updateReport->bind_param('sdssii', $reportName, $nullWeight, $status, $pageUrl, $existingReportId, $userId);
                            } else {
                                $updateReport->bind_param('sdssii', $reportName, $weightPercent, $status, $pageUrl, $existingReportId, $userId);
                            }
                            if (!$updateReport->execute()) {
                                throw new Exception('Unable to update report details.');
                            }
                            $reportId = $existingReportId;
                            $updateReport->close();
                        } else {
                            $insertReport = $conn->prepare('
                                INSERT INTO Mcc_reports (user_id, report_name, report_type, weight_percent, status, page_url)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ');
                            if (!$insertReport) {
                                throw new Exception('Unable to prepare report insert query.');
                            }

                            if ($weightPercent === null) {
                                $nullWeight = null;
                                $insertReport->bind_param('issdss', $userId, $reportName, $reportType, $nullWeight, $status, $pageUrl);
                            } else {
                                $insertReport->bind_param('issdss', $userId, $reportName, $reportType, $weightPercent, $status, $pageUrl);
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

                        $updateParam = $conn->prepare('
                            UPDATE Mcc_parameters
                            SET parameter_name = ?, category = ?, status = ?
                            WHERE parameter_id = ? AND report_id = ? AND user_id = ?
                        ');
                        if (!$updateParam) {
                            throw new Exception('Unable to prepare parameter update query.');
                        }

                        $findExistingParam = $conn->prepare('
                            SELECT parameter_id
                            FROM Mcc_parameters
                            WHERE user_id = ? AND report_id = ? AND parameter_name = ? AND category = ?
                            LIMIT 1
                        ');
                        if (!$findExistingParam) {
                            throw new Exception('Unable to prepare duplicate parameter check query.');
                        }

                        foreach ($normalizedRows as $row) {
                            $paramId = (int) $row['id'];
                            $paramName = $row['name'];
                            $category = $row['category'];

                            if ($paramId > 0) {
                                $updateParam->bind_param('sssiii', $paramName, $category, $status, $paramId, $reportId, $userId);
                                if (!$updateParam->execute()) {
                                    throw new Exception('Unable to update parameter: ' . $paramName);
                                }
                                continue;
                            }

                            $findExistingParam->bind_param('iiss', $userId, $reportId, $paramName, $category);
                            $findExistingParam->execute();
                            $findExistingParam->store_result();
                            if ($findExistingParam->num_rows > 0) {
                                continue;
                            }

                            $insertParam->bind_param('iissis', $userId, $reportId, $paramName, $category, $userId, $status);
                            if (!$insertParam->execute()) {
                                throw new Exception('Unable to save parameter: ' . $paramName);
                            }
                        }

                        $insertParam->close();
                        $updateParam->close();
                        $findExistingParam->close();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_report_config']) && $alertType === 'danger') {
    $postedParameterIds = isset($_POST['parameterId']) && is_array($_POST['parameterId']) ? $_POST['parameterId'] : [];
    $postedParameterNames = isset($_POST['parameterName']) && is_array($_POST['parameterName']) ? $_POST['parameterName'] : [];
    $postedCategories = isset($_POST['category']) && is_array($_POST['category']) ? $_POST['category'] : [];

    for ($i = 0; $i < count($postedParameterNames); $i++) {
        $formParameterRows[] = [
            'id' => isset($postedParameterIds[$i]) ? (int) $postedParameterIds[$i] : 0,
            'name' => trim((string) $postedParameterNames[$i]),
            'category' => isset($postedCategories[$i]) ? trim((string) $postedCategories[$i]) : '',
        ];
    }
}

$configuredRows = [];
$configuredSql = "
    SELECT
        p.user_id,
        p.parameter_id,
        COALESCE(NULLIF(u.user_name, ''), u.username, u.full_name, CONCAT('User #', u.user_id)) AS organisation_name,
        r.report_name,
        r.report_type,
        p.parameter_name,
        p.category,
        r.weight_percent,
        p.status
    FROM Mcc_parameters p
    INNER JOIN Mcc_reports r ON r.report_id = p.report_id
    INNER JOIN Mcc_users u ON u.user_id = p.user_id
    ORDER BY
        CASE r.report_type
            WHEN 'Normal Report' THEN 1
            WHEN 'Intensive Report' THEN 2
            WHEN 'Attendance Report' THEN 3
            ELSE 4
        END,
        r.report_name ASC,
        p.created_at DESC
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

$reportDefaultsSql = '
    SELECT user_id, report_type, weight_percent, status, report_id
    FROM Mcc_reports
    ORDER BY report_id DESC
';
$reportDefaultsResult = $conn->query($reportDefaultsSql);
if ($reportDefaultsResult) {
    while ($defaultRow = $reportDefaultsResult->fetch_assoc()) {
        $defaultKey = (int) $defaultRow['user_id'] . '__' . (string) $defaultRow['report_type'];
        if (!isset($reportDefaults[$defaultKey])) {
            $reportDefaults[$defaultKey] = [
                'weight_percent' => $defaultRow['weight_percent'] === null ? '' : (string) $defaultRow['weight_percent'],
                'status' => (string) $defaultRow['status'],
            ];
        }
    }
}

if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_report_config'])) && $prefillOrgId > 0 && $prefillReportType !== '') {
    $prefillKey = $prefillOrgId . '__' . $prefillReportType;
    if (isset($reportDefaults[$prefillKey])) {
        $prefillWeightPercent = (string) $reportDefaults[$prefillKey]['weight_percent'];
        $statusFromDefault = (string) $reportDefaults[$prefillKey]['status'];
        if (in_array($statusFromDefault, $validStatuses, true)) {
            $prefillParameterStatus = $statusFromDefault;
        }
    }
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
                                        <option value="Normal Report" <?php echo $prefillReportType === 'Normal Report' ? 'selected' : ''; ?>>Normal Report</option>
                                        <option value="Intensive Report" <?php echo $prefillReportType === 'Intensive Report' ? 'selected' : ''; ?>>Intensive Report</option>
                                        <?php if ($attendanceReportSupported): ?>
                                        <option value="Attendance Report" <?php echo $prefillReportType === 'Attendance Report' ? 'selected' : ''; ?>>Attendance Report</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                     

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="parameterWeight" class="form-label">Weight (%)</label>
                                    <input type="number" class="form-control" id="parameterWeight" name="parameterWeight" min="0" max="100" placeholder="Enter weight" value="<?php echo htmlspecialchars($prefillWeightPercent); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="parameterStatus" class="form-label">Status</label>
                                    <select class="form-select" id="parameterStatus" name="parameterStatus">
                                        <option value="Active" <?php echo $prefillParameterStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo $prefillParameterStatus === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
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
                                            <tbody id="reportParameterTableBody">
                                                <?php foreach ($formParameterRows as $row): ?>
                                                <tr>
                                                    <td class="row-index"></td>
                                                    <td>
                                                        <input type="hidden" name="parameterId[]" value="<?php echo (int) $row['id']; ?>">
                                                        <input type="text" name="parameterName[]" class="form-control form-control-sm parameter-input" placeholder="Enter parameter" required value="<?php echo htmlspecialchars((string) $row['name']); ?>">
                                                    </td>
                                                    <td>
                                                        <select name="category[]" class="form-select form-select-sm category-input" required>
                                                            <option value="">-- Select Category --</option>
                                                            <?php foreach ($validCategories as $categoryOption): ?>
                                                            <option value="<?php echo htmlspecialchars($categoryOption); ?>" <?php echo ((string) $row['category'] === $categoryOption) ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoryOption); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-parameter-row">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
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

                <?php if ($prefillOrgId > 0): ?>
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Configured Parameters</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Report</th>
                                    <th>Parameter</th>
                                    <th>Category</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($configuredRows) === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No configured parameters found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($configuredRows as $row): ?>
                                <?php $editLink = 'organisation_reports.php?org_id=' . urlencode((string) $row['user_id']) . '&report_type=' . urlencode((string) $row['report_type']) . '&edit_param_id=' . urlencode((string) $row['parameter_id']) . '&edit_org=1'; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['report_name']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['parameter_name']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['category']); ?></td>
                                    <td><a href="<?php echo htmlspecialchars($editLink); ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($prefillOrgId > 0): ?>
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
                <?php else: ?>
                <div class="alert alert-info mt-4" role="alert">
                    Select an organisation first to view configured parameters and available organisations.
                </div>
                <?php endif; ?>
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
        const parameterWeight = document.getElementById('parameterWeight');
        const parameterStatus = document.getElementById('parameterStatus');
        const reportParameterBox = document.getElementById('reportParameterBox');
        const reportParameterTitle = document.getElementById('reportParameterTitle');
        const reportParameterTableBody = document.getElementById('reportParameterTableBody');
        const addParameterRowBtn = document.getElementById('addParameterRowBtn');

        const reportOrgId = document.getElementById('reportOrgId');
        const reportDefaults = <?php echo json_encode($reportDefaults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

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

        function applyReportDefaults() {
            if (!reportOrgId || !reportName) {
                return;
            }

            const selectedOrgId = reportOrgId.value;
            const selectedReportType = reportName.value;
            if (!selectedOrgId || !selectedReportType) {
                return;
            }

            const key = `${selectedOrgId}__${selectedReportType}`;
            const defaults = reportDefaults[key];
            if (!defaults) {
                return;
            }

            if (parameterWeight) {
                parameterWeight.value = defaults.weight_percent ?? '';
            }
            if (parameterStatus && (defaults.status === 'Active' || defaults.status === 'Inactive')) {
                parameterStatus.value = defaults.status;
            }
        }

        function addParameterRow() {
            rowCount += 1;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="row-index"></td>
                <td>
                    <input type="hidden" name="parameterId[]" value="">
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
                applyReportDefaults();
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

        // Keep one row by default and respect pre-selected report in edit flow.
        if (reportName.value === 'Normal Report' || reportName.value === 'Intensive Report') {
            reportParameterBox.classList.remove('d-none');
            reportParameterTitle.textContent = `${reportName.value} - Add Parameters & Categories`;
        }

        if (reportParameterTableBody.children.length === 0) {
            addParameterRow();
        }

        reindexRows();

        if (reportOrgId) {
            reportOrgId.addEventListener('change', function () {
                applyReportDefaults();
            });
        }

        applyReportDefaults();
    </script>
</body>
</html>
