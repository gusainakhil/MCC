<?php
if (!isset($conn)) {
    include 'connection.php';
}
require_once __DIR__ . '/user-dashboard/includes/auth.php';

ud_require_admin_panel('login.php', 'user-dashboard/index.php');

$alertMessage = '';
$alertType = 'success';
$editingUserId = 0;
$editingContractId = 0;
$isEditMode = false;

$formData = [
    'organisationName' => '',
    'organisationEmail' => '',
    'organisationPassword' => '',
    'orgStartDate' => '',
    'orgEndDate' => '',
    'agreementNo' => '',
    'agreementDate' => '',
    'contractorName' => '',
    'trainNo' => '',
    'contractStation' => '',
    'amount' => '',
    'noOfYears' => ''
];

if (isset($_GET['edit_id'])) {
    $editingUserId = (int) $_GET['edit_id'];
    if ($editingUserId > 0) {
        $isEditMode = true;
        $editSql = '
            SELECT
                u.user_id,
                u.user_name,
                u.email,
                u.start_date,
                u.end_date,
                u.station_id,
                c.contract_id,
                c.agreement_no,
                c.agreement_date,
                c.contractor_name,
                c.train_no_count,
                c.amount,
                c.no_of_years,
                c.contract_start_date,
                c.contract_end_date
            FROM Mcc_users u
            LEFT JOIN Mcc_contract_details c ON c.user_id = u.user_id
            WHERE u.user_id = ?
            LIMIT 1
        ';

        $editStmt = $conn->prepare($editSql);
        if ($editStmt) {
            $editStmt->bind_param('i', $editingUserId);
            $editStmt->execute();
            $editResult = $editStmt->get_result();
            if ($editResult && ($editRow = $editResult->fetch_assoc())) {
                $editingContractId = isset($editRow['contract_id']) ? (int) $editRow['contract_id'] : 0;
                $formData['organisationName'] = (string) $editRow['user_name'];
                $formData['organisationEmail'] = (string) $editRow['email'];
                $formData['orgStartDate'] = (string) $editRow['start_date'];
                $formData['orgEndDate'] = (string) $editRow['end_date'];
                $formData['agreementNo'] = (string) $editRow['agreement_no'];
                $formData['agreementDate'] = (string) $editRow['agreement_date'];
                $formData['contractorName'] = (string) $editRow['contractor_name'];
                $formData['trainNo'] = (string) $editRow['train_no_count'];
                $formData['contractStation'] = (string) $editRow['station_id'];
                $formData['amount'] = (string) $editRow['amount'];
                $formData['noOfYears'] = (string) $editRow['no_of_years'];
            }
            $editStmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_organisation_contract'])) {
    foreach ($formData as $key => $value) {
        $formData[$key] = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
    }

    $editingUserId = isset($_POST['edit_user_id']) ? (int) $_POST['edit_user_id'] : 0;
    $editingContractId = isset($_POST['edit_contract_id']) ? (int) $_POST['edit_contract_id'] : 0;
    $isEditMode = $editingUserId > 0;

    $organisationName = $formData['organisationName'];
    $organisationEmail = $formData['organisationEmail'];
    $organisationPassword = $formData['organisationPassword'];
    $orgStartDate = $formData['orgStartDate'];
    $orgEndDate = $formData['orgEndDate'];
    $agreementNo = $formData['agreementNo'];
    $agreementDate = $formData['agreementDate'];
    $contractorName = $formData['contractorName'];
    $trainNoCount = $formData['trainNo'] === '' ? null : (int) $formData['trainNo'];
    $stationId = $formData['contractStation'] === '' ? 0 : (int) $formData['contractStation'];
    $amount = $formData['amount'] === '' ? null : (float) $formData['amount'];
    $noOfYears = $formData['noOfYears'] === '' ? null : (int) $formData['noOfYears'];

    $passwordRequired = !$isEditMode;

    if (
        $organisationName === '' ||
        $organisationEmail === '' ||
        ($passwordRequired && $organisationPassword === '') ||
        $orgStartDate === '' ||
        $orgEndDate === '' ||
        $agreementNo === '' ||
        $agreementDate === '' ||
        $contractorName === '' ||
        $stationId <= 0
    ) {
        $alertMessage = 'Please fill all required fields.';
        $alertType = 'danger';
    } elseif (!filter_var($organisationEmail, FILTER_VALIDATE_EMAIL)) {
        $alertMessage = 'Please enter a valid email address.';
        $alertType = 'danger';
    } elseif ($orgEndDate < $orgStartDate) {
        $alertMessage = 'Organisation End Date must be after Start Date.';
        $alertType = 'danger';
    } else {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', $organisationName));
        $username = trim($username, '.');
        if ($username === '') {
            $username = 'orguser';
        }

        if (!$isEditMode) {
            $baseUsername = $username;
            $counter = 1;
            while (true) {
                $checkUser = $conn->prepare('SELECT user_id FROM Mcc_users WHERE username = ? LIMIT 1');
                if (!$checkUser) {
                    break;
                }
                $checkUser->bind_param('s', $username);
                $checkUser->execute();
                $checkUser->store_result();
                $exists = $checkUser->num_rows > 0;
                $checkUser->close();

                if (!$exists) {
                    break;
                }

                $counter++;
                $username = $baseUsername . $counter;
            }
        }

        $conn->begin_transaction();
        try {
            $status = 'Active';
            $role = 'ORG_ADMIN';
            $userId = $editingUserId;

            if ($isEditMode) {
                $updateUser = $conn->prepare('
                    UPDATE Mcc_users
                    SET user_name = ?, username = ?, email = ?, station_id = ?, start_date = ?, end_date = ?, status = ?
                    WHERE user_id = ?
                ');
                if (!$updateUser) {
                    throw new Exception('Unable to prepare organisation update query.');
                }
                $updateUser->bind_param('sssisssi', $organisationName, $username, $organisationEmail, $stationId, $orgStartDate, $orgEndDate, $status, $userId);
                if (!$updateUser->execute()) {
                    throw new Exception('Unable to update organisation user.');
                }
                $updateUser->close();

                if ($organisationPassword !== '') {
                    $passwordHash = password_hash($organisationPassword, PASSWORD_BCRYPT);
                    $updatePassword = $conn->prepare('UPDATE Mcc_users SET password_hash = ? WHERE user_id = ?');
                    if (!$updatePassword) {
                        throw new Exception('Unable to prepare password update.');
                    }
                    $updatePassword->bind_param('si', $passwordHash, $userId);
                    if (!$updatePassword->execute()) {
                        throw new Exception('Unable to update password.');
                    }
                    $updatePassword->close();
                }

                if ($editingContractId > 0) {
                    $updateContract = $conn->prepare('
                        UPDATE Mcc_contract_details
                        SET agreement_no = ?, agreement_date = ?, contractor_name = ?, train_no_count = ?, station_id = ?, amount = ?, no_of_years = ?, contract_start_date = ?, contract_end_date = ?, status = ?
                        WHERE contract_id = ? AND user_id = ?
                    ');
                    if (!$updateContract) {
                        throw new Exception('Unable to prepare contract update query.');
                    }
                    $contractStatus = 'Active';
                    $updateContract->bind_param('sssiiidsssii', $agreementNo, $agreementDate, $contractorName, $trainNoCount, $stationId, $amount, $noOfYears, $orgStartDate, $orgEndDate, $contractStatus, $editingContractId, $userId);
                    if (!$updateContract->execute()) {
                        throw new Exception('Unable to update contract details.');
                    }
                    $updateContract->close();
                }
            } else {
                $passwordHash = password_hash($organisationPassword, PASSWORD_BCRYPT);

                $insertUser = $conn->prepare('
                    INSERT INTO Mcc_users
                        (user_name, username, email, password_hash, role, station_id, start_date, end_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                if (!$insertUser) {
                    throw new Exception('Unable to prepare organisation user query.');
                }
                $insertUser->bind_param('sssssisss', $organisationName, $username, $organisationEmail, $passwordHash, $role, $stationId, $orgStartDate, $orgEndDate, $status);
                if (!$insertUser->execute()) {
                    throw new Exception('Unable to create organisation user.');
                }
                $userId = (int) $conn->insert_id;
                $insertUser->close();

                $insertContract = $conn->prepare('
                    INSERT INTO Mcc_contract_details
                        (user_id, agreement_no, agreement_date, contractor_name, train_no_count, station_id, amount, no_of_years, contract_start_date, contract_end_date, status, created_by_user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                if (!$insertContract) {
                    throw new Exception('Unable to prepare contract query.');
                }
                $contractStatus = 'Active';
                $insertContract->bind_param('isssiidisssi', $userId, $agreementNo, $agreementDate, $contractorName, $trainNoCount, $stationId, $amount, $noOfYears, $orgStartDate, $orgEndDate, $contractStatus, $userId);
                if (!$insertContract->execute()) {
                    throw new Exception('Unable to create contract details.');
                }
                $insertContract->close();
            }

            $conn->commit();

            $redirectTarget = $isEditMode
                ? 'organisation_reports.php?org_id=' . urlencode((string) $userId) . '&org=' . urlencode($organisationName) . '&edit_org=1'
                : 'organisation_reports.php?org=' . urlencode($organisationName);
            header('Location: ' . $redirectTarget);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $alertMessage = 'Save failed: ' . $e->getMessage();
            $alertType = 'danger';
        }
    }
}

$stations = [];
$stationQuery = '
    SELECT s.station_id, s.station_name, d.division_name, z.zone_name
    FROM Mcc_stations s
    LEFT JOIN Mcc_divisions d ON d.division_id = s.division_id
    LEFT JOIN Mcc_zones z ON z.zone_id = d.zone_id
    ORDER BY z.zone_name, d.division_name, s.station_name
';
$stationResult = $conn->query($stationQuery);
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
    <title>Organisation & Contracts - MCC Railway Dashboard</title>
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
                    <h1><i class="bi bi-diagram-3"></i> Organisation & Contract Management</h1>
                    <p class="text-muted">Create organisations and manage contracts with contractors</p>
                </div>

                <?php if ($alertMessage !== ''): ?>
                <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($alertMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Combined Form Section -->
                <div class="mb-5">
                    <h3 class="mb-4"><i class="bi bi-building-add"></i> Organisation & Contract Setup</h3>
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><?php echo $isEditMode ? 'Edit Organisation & Contract' : 'Create New Organisation & Contract'; ?></h5>
                            </div>
                            <div class="card-body">
                                <form id="combinedForm" method="post" novalidate>
                                    <input type="hidden" name="save_organisation_contract" value="1">
                                    <?php if ($isEditMode): ?>
                                    <input type="hidden" name="edit_user_id" value="<?php echo (int) $editingUserId; ?>">
                                    <input type="hidden" name="edit_contract_id" value="<?php echo (int) $editingContractId; ?>">
                                    <?php endif; ?>
                                    <!-- Organisation Section -->
                                    <div class="mb-4">
                                        <h6 class="text-primary mb-3"><i class="bi bi-diagram-3"></i> Organisation Details</h6>
                               
                                    <div class="mb-3">
                                        <label for="organisationName" class="form-label">Organisation Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="organisationName" name="organisationName" placeholder="Enter organisation name" value="<?php echo htmlspecialchars($formData['organisationName']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="organisationEmail" class="form-label">Organisation Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="organisationEmail" name="organisationEmail" placeholder="Enter email" value="<?php echo htmlspecialchars($formData['organisationEmail']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="organisationPassword" class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="organisationPassword" name="organisationPassword" placeholder="<?php echo $isEditMode ? 'Leave blank to keep current password' : 'Enter password'; ?>" value="<?php echo htmlspecialchars($formData['organisationPassword']); ?>" <?php echo $isEditMode ? '' : 'required'; ?>>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="orgStartDate" class="form-label">Start Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="orgStartDate" name="orgStartDate" value="<?php echo htmlspecialchars($formData['orgStartDate']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="orgEndDate" class="form-label">End Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="orgEndDate" name="orgEndDate" value="<?php echo htmlspecialchars($formData['orgEndDate']); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- <div class="mb-4">
                                        <label for="reportType" class="form-label">Select Report <span class="text-danger">*</span></label>
                                        <select class="form-select" id="reportType" name="reportType" required>
                                            <option value="">-- Select Report --</option>
                                            <option value="MCC Report">MCC Report</option>
                                            <option value="PFTR Report">PFTR Report</option>
                                            <option value="OBHS Report">OBHS Report</option>
                                        </select>
                                    </div> -->

                                    <!-- Divider -->
                                    <hr class="my-4">

                                    <!-- Contract Section -->
                                    <div>
                                        <h6 class="text-primary mb-3"><i class="bi bi-file-earmark-text"></i> Contract Details</h6>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="agreementNo" class="form-label">Agreement No <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="agreementNo" name="agreementNo" placeholder="e.g., AGR-2026-001" value="<?php echo htmlspecialchars($formData['agreementNo']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="agreementDate" class="form-label">Agreement Date <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" id="agreementDate" name="agreementDate" value="<?php echo htmlspecialchars($formData['agreementDate']); ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="contractorName" class="form-label">Name of Contractor <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="contractorName" name="contractorName" placeholder="Enter contractor name" value="<?php echo htmlspecialchars($formData['contractorName']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="trainNo" class="form-label">No. of Trains <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" id="trainNo" name="trainNo" placeholder="Enter number of trains" value="<?php echo htmlspecialchars($formData['trainNo']); ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="contractStation" class="form-label">Station <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="contractStation" name="contractStation" required>
                                                        <option value="">-- Select Station --</option>
                                                        <?php foreach ($stations as $station): ?>
                                                        <?php
                                                            $stationId = (int) $station['station_id'];
                                                            $selected = ((string) $stationId === $formData['contractStation']) ? 'selected' : '';
                                                            $label = $station['station_name'] . ' (' . $station['division_name'] . ' / ' . $station['zone_name'] . ')';
                                                        ?>
                                                        <option value="<?php echo $stationId; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($label); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                       
                                        </div>
                                         <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="amount" class="form-label">Amount<span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter amount" value="<?php echo htmlspecialchars($formData['amount']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="noOfYears" class="form-label">No. of Years <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" id="noOfYears" name="noOfYears" placeholder="Enter number of years" value="<?php echo htmlspecialchars($formData['noOfYears']); ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                       
                                    </div>

                                    <!-- Submit Buttons -->
                                    <div class="d-grid gap-2 d-sm-flex mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-check-circle"></i> <?php echo $isEditMode ? 'Update Organisation & Contract' : 'Create Organisation & Contract'; ?>
                                        </button>
                                        <button type="reset" class="btn btn-secondary btn-lg">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contracts List Section -->
            
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

        // Form now submits directly to PHP backend.
    </script>
</body>
</html>
