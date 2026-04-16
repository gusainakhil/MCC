<?php
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/sidebar.php';

session_start();

// Get user_id from URL parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    die('Invalid user ID');
}

// Fetch user details
$userQuery = "SELECT * FROM Mcc_users WHERE user_id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user) {
    die('User not found');
}

// Fetch reports assigned to this user
$reportsQuery = "SELECT * FROM Mcc_reports WHERE user_id = ? AND status = 'Active'";
$reportsStmt = $conn->prepare($reportsQuery);
$reportsStmt->bind_param("i", $user_id);
$reportsStmt->execute();
$reportsResult = $reportsStmt->get_result();
$reports = $reportsResult->fetch_all(MYSQLI_ASSOC);

$selectedReportId = null;
$selectedReport = null;
$existingMarks = [];

// Helper: Get value by rating name
function getMarkByRating($marks, $ratingName) {
    foreach ($marks as $m) {
        if ($m['rating'] === $ratingName) {
            return $m['value'];
        }
    }
    return '';
}

// Helper: Check if rating is selected
function isRatingSelected($marks, $ratingName) {
    foreach ($marks as $m) {
        if ($m['rating'] === $ratingName) {
            return 'selected';
        }
    }
    return '';
}

if (isset($_GET['report_id'])) {
    $selectedReportId = (int)$_GET['report_id'];
    foreach ($reports as $report) {
        if ($report['report_id'] == $selectedReportId) {
            $selectedReport = $report;
            break;
        }
    }
    
    // Fetch existing marks for this user-report
    if ($selectedReport) {
        $marksQuery = "SELECT * FROM mcc_marks WHERE user_id = ? AND report_id = ? ORDER BY marks_id";
        $marksStmt = $conn->prepare($marksQuery);
        $marksStmt->bind_param("ii", $user_id, $selectedReportId);
        $marksStmt->execute();
        $marksResult = $marksStmt->get_result();
        $existingMarks = $marksResult->fetch_all(MYSQLI_ASSOC);
        $marksStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Values & Marks | MCC Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .marks-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .reports-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .report-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .report-item:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
            transform: translateY(-2px);
        }

        .report-item.active {
            border-color: #0d6efd;
            background: #f0f6ff;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }

        .report-item-icon {
            font-size: 28px;
            margin-bottom: 10px;
            color: #0d6efd;
        }

        .report-item-title {
            font-size: 16px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 8px;
        }

        .report-item-type {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .report-item-weight {
            font-size: 13px;
            color: #495057;
            margin-bottom: 0;
        }

        .marks-form {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-top: 20px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group-marks {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #0d6efd;
        }

        .form-group-marks label {
            font-weight: 600;
            color: #212529;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .marks-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .marks-inputs .form-group {
            margin-bottom: 0;
        }

        .marks-inputs label {
            font-size: 13px;
            font-weight: 500;
            color: #495057;
            margin-bottom: 6px;
        }

        .marks-inputs input {
            font-size: 14px;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .marks-inputs input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .marks-inputs-3col {
            grid-template-columns: repeat(3, 1fr);
        }

        .rating-select {
            font-size: 14px;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .rating-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .submit-btn {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
            color: white;
            text-decoration: none;
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .page-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
        }

        .user-info {
            background: #f0f6ff;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }

        .user-info-name {
            font-weight: 600;
            color: #0d6efd;
            font-size: 15px;
        }

        .no-reports {
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
        }

        .no-reports i {
            font-size: 48px;
            color: #dee2e6;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="marks-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="bi bi-pen-fill" style="color: #0d6efd; margin-right: 10px;"></i>Create Values & Marks</h2>
            <p>Select a report and update marks for each rating category</p>
        </div>

        <!-- User Info Card -->
        <div class="user-info">
            <div class="user-info-name">
                <i class="bi bi-person-circle"></i> User: <?php echo htmlspecialchars($user['user_name'] ?? $user['username']); ?>
            </div>
        </div>

        <!-- Reports List -->
        <?php if (empty($reports)): ?>
            <div class="no-reports">
                <i class="bi bi-inbox"></i>
                <p>No reports assigned to this user.</p>
            </div>
        <?php else: ?>
            <div class="reports-list">
                <?php foreach ($reports as $report): ?>
                    <div class="report-item <?php echo ($selectedReportId == $report['report_id']) ? 'active' : ''; ?>" 
                         onclick="selectReport(<?php echo $report['report_id']; ?>, this)">
                        <div class="report-item-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="report-item-type"><?php echo htmlspecialchars($report['report_type']); ?></div>
                        <div class="report-item-title"><?php echo htmlspecialchars($report['report_name']); ?></div>
                        <div class="report-item-weight">
                            <i class="bi bi-percent"></i> Weight: <?php echo htmlspecialchars($report['weight_percent']); ?>%
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Marks Update Form -->
            <?php if ($selectedReport): ?>
                <div class="marks-form" id="marksForm">
                    <h4 style="margin-bottom: 20px; color: #212529;">
                        <i class="bi bi-pencil-square" style="margin-right: 8px; color: #0d6efd;"></i>
                        Update Marks - <?php echo htmlspecialchars($selectedReport['report_name']); ?>
                    </h4>

                    <form id="updateMarksForm" onsubmit="return handleSubmit(event)">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="report_id" value="<?php echo $selectedReport['report_id']; ?>">

                        <!-- Value 1: Excellent -->
                        <div class="form-group-marks">
                            <label>
                                <span class="badge bg-success">Value 1</span>
                                Excellent
                            </label>
                            <div class="marks-inputs">
                                <div class="form-group">
                                    <label>Enter marks (0-100)</label>
                                    <input type="number" class="form-control" name="value1_excellent" min="0" max="100" placeholder="0" value="<?php echo getMarkByRating($existingMarks, 'Excellent'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Select Rating</label>
                                    <select class="form-control rating-select" name="rating1_excellent">
                                        <option value="">Select Rating...</option>
                                        <option value="Excellent" <?php echo isRatingSelected($existingMarks, 'Excellent'); ?>>Excellent</option>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Very Good">Very Good</option>
                                        <option value="Good">Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Poor">Poor</option>
                                        <option value="Not Attended">Not Attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Value 2: Very Good -->
                        <div class="form-group-marks">
                            <label>
                                <span class="badge bg-info">Value 2</span>
                                Very Good
                            </label>
                            <div class="marks-inputs">
                                <div class="form-group">
                                    <label>Enter marks (0-100)</label>
                                    <input type="number" class="form-control" name="value2_verygood" min="0" max="100" placeholder="0" value="<?php echo getMarkByRating($existingMarks, 'Very Good'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Select Rating</label>
                                    <select class="form-control rating-select" name="rating2_verygood">
                                        <option value="">Select Rating...</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Very Good" <?php echo isRatingSelected($existingMarks, 'Very Good'); ?>>Very Good</option>
                                        <option value="Good">Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Poor">Poor</option>
                                        <option value="Not Attended">Not Attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Value 3: Good -->
                        <div class="form-group-marks">
                            <label>
                                <span class="badge bg-primary">Value 3</span>
                                Good
                            </label>
                            <div class="marks-inputs">
                                <div class="form-group">
                                    <label>Enter marks (0-100)</label>
                                    <input type="number" class="form-control" name="value3_good" min="0" max="100" placeholder="0" value="<?php echo getMarkByRating($existingMarks, 'Good'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Select Rating</label>
                                    <select class="form-control rating-select" name="rating3_good">
                                        <option value="">Select Rating...</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Very Good">Very Good</option>
                                        <option value="Good" <?php echo isRatingSelected($existingMarks, 'Good'); ?>>Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Poor">Poor</option>
                                        <option value="Not Attended">Not Attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Value 4: Average -->
                        <div class="form-group-marks">
                            <label>
                                <span class="badge bg-warning">Value 4</span>
                                Average
                            </label>
                            <div class="marks-inputs">
                                <div class="form-group">
                                    <label>Enter marks (0-100)</label>
                                    <input type="number" class="form-control" name="value4_average" min="0" max="100" placeholder="0" value="<?php echo getMarkByRating($existingMarks, 'Average'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Select Rating</label>
                                    <select class="form-control rating-select" name="rating4_average">
                                        <option value="">Select Rating...</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Very Good">Very Good</option>
                                        <option value="Good">Good</option>
                                        <option value="Average" <?php echo isRatingSelected($existingMarks, 'Average'); ?>>Average</option>
                                        <option value="Poor">Poor</option>
                                        <option value="Not Attended">Not Attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Value 5: Poor -->
                        <div class="form-group-marks">
                            <label>
                                <span class="badge bg-danger">Value 5</span>
                                Poor
                            </label>
                            <div class="marks-inputs">
                                <div class="form-group">
                                    <label>Enter marks (0-100)</label>
                                    <input type="number" class="form-control" name="value5_poor" min="0" max="100" placeholder="0" value="<?php echo getMarkByRating($existingMarks, 'Poor'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Select Rating</label>
                                    <select class="form-control rating-select" name="rating5_poor">
                                        <option value="">Select Rating...</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Very Good">Very Good</option>
                                        <option value="Good">Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Poor" <?php echo isRatingSelected($existingMarks, 'Poor'); ?>>Poor</option>
                                        <option value="Not Attended">Not Attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="submit-btn">
                            <i class="bi bi-check-circle" style="margin-right: 8px;"></i>
                            Submit Marks
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectReport(reportId, element) {
            // Remove active class from all report items
            document.querySelectorAll('.report-item').forEach(item => {
                item.classList.remove('active');
            });

            // Add active class to clicked item
            element.classList.add('active');

            // Redirect to this page with the selected report
            const userId = <?php echo $user_id; ?>;
            window.location.href = `?user_id=${userId}&report_id=${reportId}`;
        }

        function handleSubmit(event) {
            event.preventDefault();

            // Get form data
            const formData = new FormData(document.getElementById('updateMarksForm'));

            // Send to backend (create backend file next)
            fetch('update-marks.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Marks submitted successfully!');
                    // Optionally reset form or redirect
                    document.getElementById('updateMarksForm').reset();
                } else {
                    alert('Error: ' + (data.message || 'Failed to submit marks'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting marks');
            });

            return false;
        }
    </script>
</body>
</html>
