<?php
require_once __DIR__ . '/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;

if ($user_id <= 0 || $report_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user_id or report_id']);
    exit;
}

// Collect marks data - ONLY save where value > 0 AND rating selected
$marksArray = [];

if ((int)$_POST['value1_excellent'] > 0 && $_POST['rating1_excellent'] !== '') {
    $marksArray[] = ['value' => (int)$_POST['value1_excellent'], 'rating' => trim($_POST['rating1_excellent'])];
}
if ((int)$_POST['value2_verygood'] > 0 && $_POST['rating2_verygood'] !== '') {
    $marksArray[] = ['value' => (int)$_POST['value2_verygood'], 'rating' => trim($_POST['rating2_verygood'])];
}
if ((int)$_POST['value3_good'] > 0 && $_POST['rating3_good'] !== '') {
    $marksArray[] = ['value' => (int)$_POST['value3_good'], 'rating' => trim($_POST['rating3_good'])];
}
if ((int)$_POST['value4_average'] > 0 && $_POST['rating4_average'] !== '') {
    $marksArray[] = ['value' => (int)$_POST['value4_average'], 'rating' => trim($_POST['rating4_average'])];
}
if ((int)$_POST['value5_poor'] > 0 && $_POST['rating5_poor'] !== '') {
    $marksArray[] = ['value' => (int)$_POST['value5_poor'], 'rating' => trim($_POST['rating5_poor'])];
}

if (empty($marksArray)) {
    echo json_encode(['success' => false, 'message' => 'Please fill at least one value and select its rating']);
    exit;
}

try {
    // First delete existing records for this user-report combo
    $deleteQuery = "DELETE FROM mcc_marks WHERE user_id = ? AND report_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $user_id, $report_id);
    $deleteStmt->execute();
    $deleteStmt->close();

    // Insert new records for all provided ratings
    $insertQuery = "INSERT INTO mcc_marks (user_id, report_id, value, rating, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
    $insertStmt = $conn->prepare($insertQuery);

    foreach ($marksArray as $mark) {
        $value = $mark['value'];
        $rating = $mark['rating'];
        
        $insertStmt->bind_param("iiss", $user_id, $report_id, $value, $rating);
        
        if (!$insertStmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error saving marks: ' . $insertStmt->error]);
            exit;
        }
    }

    $insertStmt->close();
    echo json_encode(['success' => true, 'message' => 'Marks saved successfully (' . count($marksArray) . ' entries)']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}

$conn->close();
?>
