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
$formParameterRowsByType = [];
$prefillSelectedReportTypes = [];
$prefillWeightPercentByType = [];
$prefillStatusByType = [];
$validReportTypes = ['Normal Report', 'Intensive Report', 'Chemical Report', 'Machine Report', 'Attendance Report'];
$validStatuses = ['Active', 'Inactive'];
$validCategories = ['Coach Interior', 'Coach Exterior', 'Watering'];
$reportPageUrls = [
    'Normal Report' => 'normal_report.php',
    'Intensive Report' => 'intensive_report.php',
    'Chemical Report' => 'chemical_report.php',
    'Machine Report' => 'machine_report.php',
    'Attendance Report' => 'attendence.php',
];
$reportTypeRequiresParameters = [
    'Normal Report' => true,
    'Intensive Report' => true,
    'Chemical Report' => true,
    'Machine Report' => true,
    'Attendance Report' => false,
];
$reportFieldMeta = [
    'Normal Report' => [
        'parameter_label' => 'Parameter',
        'parameter_placeholder' => 'Enter parameter',
        'category_label' => 'Category',
        'category_type' => 'select',
        'category_placeholder' => '-- Select Category --',
    ],
    'Intensive Report' => [
        'parameter_label' => 'Parameter',
        'parameter_placeholder' => 'Enter parameter',
        'category_label' => 'Category',
        'category_type' => 'select',
        'category_placeholder' => '-- Select Category --',
    ],
    'Chemical Report' => [
        'parameter_label' => 'Name of Chemical',
        'parameter_placeholder' => 'Enter chemical name',
        'category_label' => 'Quantity of chemical per coach',
        'category_type' => 'text',
        'category_placeholder' => 'Enter quantity per coach',
    ],
    'Machine Report' => [
        'parameter_label' => 'Machine Type',
        'parameter_placeholder' => 'Enter machine type',
        'category_label' => 'Nos. of machines',
        'category_type' => 'number',
        'category_placeholder' => 'Enter number of machines',
    ],
    'Attendance Report' => [
        'parameter_label' => 'Parameter',
        'parameter_placeholder' => 'Enter parameter',
        'category_label' => 'Category',
        'category_type' => 'select',
        'category_placeholder' => '-- Select Category --',
    ],
];
$dbSupportedReportTypes = $validReportTypes;
$attendanceReportSupported = true;
$reportDefaults = [];
$prefillReportType = in_array($prefillReportType, $validReportTypes, true) ? $prefillReportType : '';

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

            if (!in_array($prefillReportType, $prefillSelectedReportTypes, true)) {
                $prefillSelectedReportTypes[] = $prefillReportType;
            }
            if (!isset($formParameterRowsByType[$prefillReportType])) {
                $formParameterRowsByType[$prefillReportType] = [];
            }

            $formParameterRowsByType[$prefillReportType][] = [
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
    $selectedReportTypesRaw = isset($_POST['reportTypes']) && is_array($_POST['reportTypes']) ? $_POST['reportTypes'] : [];
    $selectedReportTypes = [];
    foreach ($selectedReportTypesRaw as $reportTypeValue) {
        $reportTypeValue = trim((string) $reportTypeValue);
        if ($reportTypeValue !== '' && in_array($reportTypeValue, $dbSupportedReportTypes, true) && !in_array($reportTypeValue, $selectedReportTypes, true)) {
            $selectedReportTypes[] = $reportTypeValue;
        }
    }

    $prefillSelectedReportTypes = $selectedReportTypes;
    $postedWeightsByType = isset($_POST['parameterWeight']) && is_array($_POST['parameterWeight']) ? $_POST['parameterWeight'] : [];
    $postedStatusesByType = isset($_POST['parameterStatus']) && is_array($_POST['parameterStatus']) ? $_POST['parameterStatus'] : [];
    $postedParameterIdsByType = isset($_POST['parameterId']) && is_array($_POST['parameterId']) ? $_POST['parameterId'] : [];
    $postedParameterNamesByType = isset($_POST['parameterName']) && is_array($_POST['parameterName']) ? $_POST['parameterName'] : [];
    $postedCategoriesByType = isset($_POST['category']) && is_array($_POST['category']) ? $_POST['category'] : [];

    foreach ($selectedReportTypes as $selectedType) {
        $weightValue = isset($postedWeightsByType[$selectedType]) ? trim((string) $postedWeightsByType[$selectedType]) : '';
        $prefillWeightPercentByType[$selectedType] = $weightValue;

        $statusValue = isset($postedStatusesByType[$selectedType]) ? trim((string) $postedStatusesByType[$selectedType]) : 'Active';
        if (!in_array($statusValue, $validStatuses, true)) {
            $statusValue = 'Active';
        }
        $prefillStatusByType[$selectedType] = $statusValue;
    }

    if ($organisationId <= 0 || count($selectedReportTypes) === 0) {
        $alertMessage = 'Please select an organisation and at least one report.';
        $alertType = 'danger';
    } elseif (count($selectedReportTypes) !== count($selectedReportTypesRaw)) {
        $alertMessage = 'One or more selected reports are not supported by the database.';
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

                $conn->begin_transaction();
                try {
                    $reportLookup = $conn->prepare('SELECT report_id FROM Mcc_reports WHERE user_id = ? AND report_type = ? LIMIT 1');
                    if (!$reportLookup) {
                        throw new Exception('Unable to prepare report lookup query.');
                    }

                    $checkReportName = $conn->prepare('SELECT report_id FROM Mcc_reports WHERE report_name = ? AND report_id <> ? LIMIT 1');
                    if (!$checkReportName) {
                        throw new Exception('Unable to prepare report name uniqueness query.');
                    }

                    $insertReport = $conn->prepare('
                        INSERT INTO Mcc_reports (user_id, report_name, report_type, weight_percent, status, page_url)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ');
                    if (!$insertReport) {
                        throw new Exception('Unable to prepare report insert query.');
                    }

                    $updateReport = $conn->prepare('
                        UPDATE Mcc_reports
                        SET report_name = ?, weight_percent = ?, status = ?, page_url = ?
                        WHERE report_id = ? AND user_id = ?
                    ');
                    if (!$updateReport) {
                        throw new Exception('Unable to prepare report update query.');
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

                    foreach ($selectedReportTypes as $reportType) {
                        $pageUrl = isset($reportPageUrls[$reportType]) ? $reportPageUrls[$reportType] : '';
                        $weightRaw = isset($postedWeightsByType[$reportType]) ? trim((string) $postedWeightsByType[$reportType]) : '';
                        $weightPercent = $weightRaw !== '' ? (float) $weightRaw : null;

                        $status = isset($postedStatusesByType[$reportType]) ? trim((string) $postedStatusesByType[$reportType]) : 'Active';
                        if (!in_array($status, $validStatuses, true)) {
                            throw new Exception('Please select a valid status for ' . $reportType . '.');
                        }
                        if ($weightPercent !== null && ($weightPercent < 0 || $weightPercent > 100)) {
                            throw new Exception('Weight must be between 0 and 100 for ' . $reportType . '.');
                        }

                        $parameterIds = isset($postedParameterIdsByType[$reportType]) && is_array($postedParameterIdsByType[$reportType]) ? $postedParameterIdsByType[$reportType] : [];
                        $parameterNames = isset($postedParameterNamesByType[$reportType]) && is_array($postedParameterNamesByType[$reportType]) ? $postedParameterNamesByType[$reportType] : [];
                        $categories = isset($postedCategoriesByType[$reportType]) && is_array($postedCategoriesByType[$reportType]) ? $postedCategoriesByType[$reportType] : [];

                        $normalizedRows = [];
                        $categoryUsesPresetList = in_array($reportType, ['Normal Report', 'Intensive Report'], true);
                        $categoryRequiresNumericValue = $reportType === 'Machine Report';
                        for ($i = 0; $i < count($parameterNames); $i++) {
                            $pId = isset($parameterIds[$i]) ? (int) $parameterIds[$i] : 0;
                            $pName = trim((string) $parameterNames[$i]);
                            $cat = isset($categories[$i]) ? trim((string) $categories[$i]) : '';

                            if ($pName === '' && $cat === '') {
                                continue;
                            }
                            if ($pName === '' || $cat === '') {
                                throw new Exception('Please fill all fields correctly for ' . $reportType . '.');
                            }

                            if ($categoryUsesPresetList && !in_array($cat, $validCategories, true)) {
                                throw new Exception('Please select a valid category for ' . $reportType . '.');
                            }

                            if ($categoryRequiresNumericValue && (!is_numeric($cat) || (float) $cat <= 0)) {
                                throw new Exception('Nos. of machines must be a number greater than 0 for Machine Report.');
                            }

                            $normalizedRows[] = ['id' => $pId, 'name' => $pName, 'category' => $cat];
                        }

                        $requiresParameters = isset($reportTypeRequiresParameters[$reportType]) ? (bool) $reportTypeRequiresParameters[$reportType] : true;
                        if ($requiresParameters && count($normalizedRows) === 0) {
                            throw new Exception('Please add at least one valid Parameter and Category row for ' . $reportType . '.');
                        }

                        $reportLookup->bind_param('is', $userId, $reportType);
                        $reportLookup->execute();
                        $reportLookup->store_result();
                        $existingReportId = 0;
                        if ($reportLookup->num_rows > 0) {
                            $reportLookup->bind_result($existingReportId);
                            $reportLookup->fetch();
                        }

                        $baseReportName = $organisationName . ' - ' . $reportType;
                        $reportName = $baseReportName;
                        $nameSuffix = 1;
                        while (true) {
                            $excludeReportId = $existingReportId > 0 ? $existingReportId : 0;
                            $checkReportName->bind_param('si', $reportName, $excludeReportId);
                            $checkReportName->execute();
                            $checkReportName->store_result();
                            if ($checkReportName->num_rows === 0) {
                                break;
                            }
                            $nameSuffix++;
                            $reportName = $baseReportName . ' (' . $nameSuffix . ')';
                        }

                        if ($existingReportId > 0) {
                            if ($weightPercent === null) {
                                $nullWeight = null;
                                $updateReport->bind_param('sdssii', $reportName, $nullWeight, $status, $pageUrl, $existingReportId, $userId);
                            } else {
                                $updateReport->bind_param('sdssii', $reportName, $weightPercent, $status, $pageUrl, $existingReportId, $userId);
                            }
                            if (!$updateReport->execute()) {
                                throw new Exception('Unable to update report details for ' . $reportType . '.');
                            }
                            $reportId = $existingReportId;
                        } else {
                            if ($weightPercent === null) {
                                $nullWeight = null;
                                $insertReport->bind_param('issdss', $userId, $reportName, $reportType, $nullWeight, $status, $pageUrl);
                            } else {
                                $insertReport->bind_param('issdss', $userId, $reportName, $reportType, $weightPercent, $status, $pageUrl);
                            }
                            if (!$insertReport->execute()) {
                                throw new Exception('Unable to save report details for ' . $reportType . '.');
                            }
                            $reportId = (int) $conn->insert_id;
                        }

                        foreach ($normalizedRows as $row) {
                            $paramId = (int) $row['id'];
                            $paramName = $row['name'];
                            $category = $row['category'];

                            if ($paramId > 0) {
                                $updateParam->bind_param('sssiii', $paramName, $category, $status, $paramId, $reportId, $userId);
                                if (!$updateParam->execute()) {
                                    throw new Exception('Unable to update parameter: ' . $paramName . ' (' . $reportType . ')');
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
                                throw new Exception('Unable to save parameter: ' . $paramName . ' (' . $reportType . ')');
                            }
                        }
                    }

                    $reportLookup->close();
                    $checkReportName->close();
                    $insertReport->close();
                    $updateReport->close();
                    $insertParam->close();
                    $updateParam->close();
                    $findExistingParam->close();

                    $conn->commit();
                    header('Location: organisation_list.php?saved=1');
                    exit;
                } catch (Throwable $e) {
                    $conn->rollback();
                    $rawErrorMessage = (string) $e->getMessage();
                    if (stripos($rawErrorMessage, "Data truncated for column 'category'") !== false) {
                        $alertMessage = "Save failed: Database category column still uses old ENUM values. Please run migration: ALTER TABLE Mcc_parameters MODIFY category VARCHAR(150) NOT NULL;";
                    } else {
                        $alertMessage = 'Save failed: ' . $rawErrorMessage;
                    }
                    $alertType = 'danger';
                }
            }
        }
    }

    if ($alertType === 'danger') {
        foreach ($selectedReportTypes as $selectedType) {
            $typeParamIds = isset($postedParameterIdsByType[$selectedType]) && is_array($postedParameterIdsByType[$selectedType]) ? $postedParameterIdsByType[$selectedType] : [];
            $typeParamNames = isset($postedParameterNamesByType[$selectedType]) && is_array($postedParameterNamesByType[$selectedType]) ? $postedParameterNamesByType[$selectedType] : [];
            $typeCategories = isset($postedCategoriesByType[$selectedType]) && is_array($postedCategoriesByType[$selectedType]) ? $postedCategoriesByType[$selectedType] : [];

            if (!isset($formParameterRowsByType[$selectedType])) {
                $formParameterRowsByType[$selectedType] = [];
            }

            for ($i = 0; $i < count($typeParamNames); $i++) {
                $formParameterRowsByType[$selectedType][] = [
                    'id' => isset($typeParamIds[$i]) ? (int) $typeParamIds[$i] : 0,
                    'name' => trim((string) $typeParamNames[$i]),
                    'category' => isset($typeCategories[$i]) ? trim((string) $typeCategories[$i]) : '',
                ];
            }
        }
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
            WHEN 'Chemical Report' THEN 3
            WHEN 'Machine Report' THEN 4
            WHEN 'Attendance Report' THEN 5
            ELSE 6
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

if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_report_config']))) {
    if ($prefillReportType !== '' && in_array($prefillReportType, $dbSupportedReportTypes, true) && !in_array($prefillReportType, $prefillSelectedReportTypes, true)) {
        $prefillSelectedReportTypes[] = $prefillReportType;
    }

    if ($prefillOrgId > 0) {
        foreach ($prefillSelectedReportTypes as $selectedType) {
            $prefillKey = $prefillOrgId . '__' . $selectedType;
            if (isset($reportDefaults[$prefillKey])) {
                if (!isset($prefillWeightPercentByType[$selectedType])) {
                    $prefillWeightPercentByType[$selectedType] = (string) $reportDefaults[$prefillKey]['weight_percent'];
                }
                if (!isset($prefillStatusByType[$selectedType])) {
                    $statusFromDefault = (string) $reportDefaults[$prefillKey]['status'];
                    $prefillStatusByType[$selectedType] = in_array($statusFromDefault, $validStatuses, true) ? $statusFromDefault : 'Active';
                }
            }
        }
    }
}

foreach ($dbSupportedReportTypes as $reportTypeOption) {
    if (!isset($prefillWeightPercentByType[$reportTypeOption])) {
        $prefillWeightPercentByType[$reportTypeOption] = '';
    }
    if (!isset($prefillStatusByType[$reportTypeOption])) {
        $prefillStatusByType[$reportTypeOption] = 'Active';
    }
    if (!isset($formParameterRowsByType[$reportTypeOption])) {
        $formParameterRowsByType[$reportTypeOption] = [];
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
                                    <label class="form-label">Report Name <span class="text-danger">*</span></label>
                                    <div class="border rounded p-3 bg-light">
                                        <?php foreach ($dbSupportedReportTypes as $reportTypeOption): ?>
                                        <?php
                                            $reportTypeSlug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $reportTypeOption));
                                            $isChecked = in_array($reportTypeOption, $prefillSelectedReportTypes, true);
                                        ?>
                                        <div class="form-check form-check-inline me-3 mb-2">
                                            <input
                                                class="form-check-input report-type-checkbox"
                                                type="checkbox"
                                                id="reportType_<?php echo htmlspecialchars($reportTypeSlug); ?>"
                                                name="reportTypes[]"
                                                value="<?php echo htmlspecialchars($reportTypeOption); ?>"
                                                <?php echo $isChecked ? 'checked' : ''; ?>
                                            >
                                            <label class="form-check-label" for="reportType_<?php echo htmlspecialchars($reportTypeSlug); ?>">
                                                <?php echo htmlspecialchars($reportTypeOption); ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="form-text">You can select multiple reports and save all selected forms together.</div>
                                </div>
                            </div>

                            <div id="multiReportFormsContainer">
                                <?php foreach ($dbSupportedReportTypes as $reportTypeOption): ?>
                                <?php
                                    $reportTypeSlug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $reportTypeOption));
                                    $requiresParameters = isset($reportTypeRequiresParameters[$reportTypeOption]) ? (bool) $reportTypeRequiresParameters[$reportTypeOption] : true;
                                    $isSelected = in_array($reportTypeOption, $prefillSelectedReportTypes, true);
                                    $fieldMeta = isset($reportFieldMeta[$reportTypeOption]) ? $reportFieldMeta[$reportTypeOption] : $reportFieldMeta['Normal Report'];
                                    $parameterLabel = (string) $fieldMeta['parameter_label'];
                                    $parameterPlaceholder = (string) $fieldMeta['parameter_placeholder'];
                                    $categoryLabel = (string) $fieldMeta['category_label'];
                                    $categoryType = (string) $fieldMeta['category_type'];
                                    $categoryPlaceholder = (string) $fieldMeta['category_placeholder'];
                                    $rowsForType = isset($formParameterRowsByType[$reportTypeOption]) ? $formParameterRowsByType[$reportTypeOption] : [];
                                    if ($requiresParameters && $isSelected && count($rowsForType) === 0) {
                                        $rowsForType[] = ['id' => 0, 'name' => '', 'category' => ''];
                                    }
                                ?>
                                <div
                                    class="card border-primary-subtle mb-3 report-config-card <?php echo $isSelected ? '' : 'd-none'; ?>"
                                    data-report-type="<?php echo htmlspecialchars($reportTypeOption); ?>"
                                    data-report-slug="<?php echo htmlspecialchars($reportTypeSlug); ?>"
                                    data-requires-params="<?php echo $requiresParameters ? '1' : '0'; ?>"
                                    data-parameter-label="<?php echo htmlspecialchars($parameterLabel); ?>"
                                    data-parameter-placeholder="<?php echo htmlspecialchars($parameterPlaceholder); ?>"
                                    data-category-label="<?php echo htmlspecialchars($categoryLabel); ?>"
                                    data-category-type="<?php echo htmlspecialchars($categoryType); ?>"
                                    data-category-placeholder="<?php echo htmlspecialchars($categoryPlaceholder); ?>"
                                >
                                    <div class="card-header bg-primary-subtle">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($reportTypeOption); ?> Configuration</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="parameterWeight_<?php echo htmlspecialchars($reportTypeSlug); ?>" class="form-label">Weight (%)</label>
                                                <input
                                                    type="number"
                                                    class="form-control report-weight-input"
                                                    id="parameterWeight_<?php echo htmlspecialchars($reportTypeSlug); ?>"
                                                    name="parameterWeight[<?php echo htmlspecialchars($reportTypeOption); ?>]"
                                                    min="0"
                                                    max="100"
                                                    placeholder="Enter weight"
                                                    value="<?php echo htmlspecialchars((string) $prefillWeightPercentByType[$reportTypeOption]); ?>"
                                                >
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="parameterStatus_<?php echo htmlspecialchars($reportTypeSlug); ?>" class="form-label">Status</label>
                                                <select
                                                    class="form-select report-status-input"
                                                    id="parameterStatus_<?php echo htmlspecialchars($reportTypeSlug); ?>"
                                                    name="parameterStatus[<?php echo htmlspecialchars($reportTypeOption); ?>]"
                                                >
                                                    <option value="Active" <?php echo $prefillStatusByType[$reportTypeOption] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="Inactive" <?php echo $prefillStatusByType[$reportTypeOption] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>

                                        <?php if ($requiresParameters): ?>
                                        <div class="card border-0 border-top pt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($reportTypeOption); ?> Parameters</h6>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary add-parameter-row-btn"
                                                    data-report-type="<?php echo htmlspecialchars($reportTypeOption); ?>"
                                                >
                                                    <i class="bi bi-plus-circle"></i> Add Row
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 80px;">#</th>
                                                            <th><?php echo htmlspecialchars($parameterLabel); ?></th>
                                                            <th style="width: 260px;"><?php echo htmlspecialchars($categoryLabel); ?></th>
                                                            <th style="width: 120px;">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="report-parameter-table-body" id="reportParameterTableBody_<?php echo htmlspecialchars($reportTypeSlug); ?>" data-report-type="<?php echo htmlspecialchars($reportTypeOption); ?>">
                                                        <?php foreach ($rowsForType as $row): ?>
                                                        <tr>
                                                            <td class="row-index"></td>
                                                            <td>
                                                                <input type="hidden" name="parameterId[<?php echo htmlspecialchars($reportTypeOption); ?>][]" value="<?php echo (int) $row['id']; ?>">
                                                                <input type="text" name="parameterName[<?php echo htmlspecialchars($reportTypeOption); ?>][]" class="form-control form-control-sm parameter-input" placeholder="<?php echo htmlspecialchars($parameterPlaceholder); ?>" required value="<?php echo htmlspecialchars((string) $row['name']); ?>">
                                                            </td>
                                                            <td>
                                                                <?php if ($categoryType === 'select'): ?>
                                                                <select name="category[<?php echo htmlspecialchars($reportTypeOption); ?>][]" class="form-select form-select-sm category-input" required>
                                                                    <option value=""><?php echo htmlspecialchars($categoryPlaceholder); ?></option>
                                                                    <?php foreach ($validCategories as $categoryOption): ?>
                                                                    <option value="<?php echo htmlspecialchars($categoryOption); ?>" <?php echo ((string) $row['category'] === $categoryOption) ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoryOption); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <?php else: ?>
                                                                <input
                                                                    type="<?php echo $categoryType === 'number' ? 'number' : 'text'; ?>"
                                                                    name="category[<?php echo htmlspecialchars($reportTypeOption); ?>][]"
                                                                    class="form-control form-control-sm category-input"
                                                                    placeholder="<?php echo htmlspecialchars($categoryPlaceholder); ?>"
                                                                    <?php if ($categoryType === 'number'): ?>min="1" step="1"<?php endif; ?>
                                                                    required
                                                                    value="<?php echo htmlspecialchars((string) $row['category']); ?>"
                                                                >
                                                                <?php endif; ?>
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
                                        <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <?php echo htmlspecialchars($reportTypeOption); ?> does not require parameter rows.
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
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

        const orgReportForm = document.getElementById('orgReportForm');
        const reportOrgId = document.getElementById('reportOrgId');
        const reportTypeCheckboxes = document.querySelectorAll('.report-type-checkbox');
        const reportConfigCards = document.querySelectorAll('.report-config-card');
        const reportDefaults = <?php echo json_encode($reportDefaults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        const sharedCategories = ['Coach Interior', 'Coach Exterior', 'Watering'];

        function buildCategoryOptions(selectedCategory = '') {
            return sharedCategories
                .map((category) => {
                    const isSelected = selectedCategory === category ? 'selected' : '';
                    return `<option value="${category}" ${isSelected}>${category}</option>`;
                })
                .join('');
        }

        function getReportCard(reportType) {
            return Array.from(reportConfigCards).find((card) => card.dataset.reportType === reportType) || null;
        }

        function reindexRows(tableBody) {
            if (!tableBody) {
                return;
            }
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach((row, index) => {
                const indexCell = row.querySelector('.row-index');
                if (indexCell) {
                    indexCell.textContent = index + 1;
                }
            });
        }

        function applyReportDefaults(reportType) {
            if (!reportOrgId || !reportType) {
                return;
            }

            const selectedOrgId = reportOrgId.value;
            if (!selectedOrgId) {
                return;
            }

            const key = `${selectedOrgId}__${reportType}`;
            const defaults = reportDefaults[key];
            if (!defaults) {
                return;
            }

            const card = getReportCard(reportType);
            if (!card) {
                return;
            }

            const weightInput = card.querySelector('.report-weight-input');
            const statusInput = card.querySelector('.report-status-input');

            if (weightInput) {
                weightInput.value = defaults.weight_percent ?? '';
            }
            if (statusInput && (defaults.status === 'Active' || defaults.status === 'Inactive')) {
                statusInput.value = defaults.status;
            }
        }

        function addParameterRow(reportType) {
            const card = getReportCard(reportType);
            if (!card) {
                return;
            }

            const tableBody = card.querySelector('.report-parameter-table-body');
            if (!tableBody) {
                return;
            }

            const parameterPlaceholder = card.dataset.parameterPlaceholder || 'Enter value';
            const categoryType = card.dataset.categoryType || 'select';
            const categoryPlaceholder = card.dataset.categoryPlaceholder || '-- Select --';

            let categoryFieldHtml = '';
            if (categoryType === 'select') {
                categoryFieldHtml = `
                    <select name="category[${reportType}][]" class="form-select form-select-sm category-input" required>
                        <option value="">${categoryPlaceholder}</option>
                        ${buildCategoryOptions()}
                    </select>
                `;
            } else if (categoryType === 'number') {
                categoryFieldHtml = `
                    <input type="number" min="1" step="1" name="category[${reportType}][]" class="form-control form-control-sm category-input" placeholder="${categoryPlaceholder}" required>
                `;
            } else {
                categoryFieldHtml = `
                    <input type="text" name="category[${reportType}][]" class="form-control form-control-sm category-input" placeholder="${categoryPlaceholder}" required>
                `;
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="row-index"></td>
                <td>
                    <input type="hidden" name="parameterId[${reportType}][]" value="">
                    <input type="text" name="parameterName[${reportType}][]" class="form-control form-control-sm parameter-input" placeholder="${parameterPlaceholder}" required>
                </td>
                <td>
                    ${categoryFieldHtml}
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-parameter-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
            reindexRows(tableBody);
        }

        function toggleReportCards() {
            reportConfigCards.forEach((card) => {
                const reportType = card.dataset.reportType;
                const requiresParams = card.dataset.requiresParams === '1';
                const checkbox = Array.from(reportTypeCheckboxes).find((item) => item.value === reportType);
                const isSelected = checkbox ? checkbox.checked : false;

                card.classList.toggle('d-none', !isSelected);

                if (!isSelected) {
                    return;
                }

                applyReportDefaults(reportType);
                const tableBody = card.querySelector('.report-parameter-table-body');
                if (requiresParams && tableBody && tableBody.children.length === 0) {
                    addParameterRow(reportType);
                }

                if (tableBody) {
                    reindexRows(tableBody);
                }
            });
        }

        reportTypeCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', function () {
                toggleReportCards();
            });
        });

        document.addEventListener('click', function (e) {
            const addButton = e.target.closest('.add-parameter-row-btn');
            if (addButton) {
                const reportType = addButton.dataset.reportType;
                addParameterRow(reportType);
            }
        });

        document.addEventListener('click', function (e) {
            const removeButton = e.target.closest('.remove-parameter-row');
            if (!removeButton) {
                return;
            }

            const row = removeButton.closest('tr');
            if (row) {
                row.remove();
                const tableBody = removeButton.closest('.report-parameter-table-body');
                reindexRows(tableBody);

                const card = removeButton.closest('.report-config-card');
                if (card && card.dataset.requiresParams === '1' && tableBody && tableBody.children.length === 0) {
                    addParameterRow(card.dataset.reportType);
                }
            }
        });

        orgReportForm.addEventListener('submit', function (e) {
            const selectedCheckboxes = Array.from(reportTypeCheckboxes).filter((checkbox) => checkbox.checked);

            if (selectedCheckboxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one report.');
                return;
            }

            const hasInvalidSelectedReport = selectedCheckboxes.some((checkbox) => {
                const reportType = checkbox.value;
                const card = getReportCard(reportType);
                if (!card) {
                    return true;
                }

                const requiresParams = card.dataset.requiresParams === '1';
                if (!requiresParams) {
                    return false;
                }

                const categoryType = card.dataset.categoryType || 'select';

                const rows = card.querySelectorAll('.report-parameter-table-body tr');
                if (rows.length === 0) {
                    return true;
                }

                return Array.from(rows).some((row) => {
                    const parameterValue = row.querySelector('.parameter-input')?.value.trim();
                    const categoryValueRaw = row.querySelector('.category-input')?.value;
                    const categoryValue = typeof categoryValueRaw === 'string' ? categoryValueRaw.trim() : '';
                    if (!parameterValue || !categoryValue) {
                        return true;
                    }

                    if (categoryType === 'number') {
                        const numericValue = Number(categoryValue);
                        if (!Number.isFinite(numericValue) || numericValue <= 0) {
                            return true;
                        }
                    }

                    return false;
                });
            });

            if (hasInvalidSelectedReport) {
                e.preventDefault();
                alert('Please fill all Parameter and Category rows for selected reports before saving.');
            }
        });

        reportConfigCards.forEach((card) => {
            const tableBody = card.querySelector('.report-parameter-table-body');
            if (tableBody) {
                reindexRows(tableBody);
            }
        });

        if (reportOrgId) {
            reportOrgId.addEventListener('change', function () {
                reportTypeCheckboxes.forEach((checkbox) => {
                    if (checkbox.checked) {
                        applyReportDefaults(checkbox.value);
                    }
                });
            });
        }

        toggleReportCards();
    </script>
</body>
</html>
