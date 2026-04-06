<?php
include 'connection.php';

$saved = isset($_GET['saved']) && $_GET['saved'] === '1';

$rows = [];
$sql = "
	SELECT
		u.user_id,
		COALESCE(NULLIF(u.user_name, ''), u.username, u.full_name, CONCAT('User #', u.user_id)) AS organisation_name,
		u.email,
		u.status AS user_status,
		COUNT(DISTINCT r.report_id) AS total_reports,
		COUNT(p.parameter_id) AS total_parameters,
		MAX(r.created_at) AS last_report_on
	FROM Mcc_users u
	LEFT JOIN Mcc_reports r ON r.user_id = u.user_id
	LEFT JOIN Mcc_parameters p ON p.user_id = u.user_id
	GROUP BY u.user_id, organisation_name, u.email, u.status
	ORDER BY organisation_name
";

$result = $conn->query($sql);
if ($result) {
	while ($row = $result->fetch_assoc()) {
		$rows[] = $row;
	}
}

$currentEditId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Organisation List - MCC Railway Dashboard</title>
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
					<h1><i class="bi bi-clipboard-check"></i> Organisation List</h1>
					<p class="text-muted">Live view of organisations with report and parameter counts</p>
				</div>

				<?php if ($saved): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					Report configuration saved successfully.
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>
				<?php endif; ?>

				<div class="mb-3">
					<a href="organisation_reports.php" class="btn btn-primary">
						<i class="bi bi-journal-plus"></i> Add Report Configuration
					</a>
				</div>

				<?php if ($currentEditId > 0): ?>
				<div class="alert alert-info">
					Editing organisation information for record #<?php echo (int) $currentEditId; ?>. Open the edit form in <a href="organisation.php?edit_id=<?php echo (int) $currentEditId; ?>">organisation page</a>.
				</div>
				<?php endif; ?>

				<div class="card shadow-sm">
					<div class="card-header bg-light">
						<h5 class="mb-0">Organisation Summary</h5>
					</div>
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead class="table-light">
								<tr>
									<th>Organisation</th>
									<th>Email</th>
									<th>Reports</th>
									<th>Parameters</th>
									<th>Last Report On</th>
									<th>Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php if (count($rows) === 0): ?>
								<tr>
										<td colspan="7" class="text-center text-muted py-3">No organisation/user records found.</td>
								</tr>
								<?php else: ?>
								<?php foreach ($rows as $row): ?>
								<?php $statusClass = strtolower((string) $row['user_status']) === 'active' ? 'bg-success' : 'bg-secondary'; ?>
								<tr>
									<td><?php echo htmlspecialchars((string) $row['organisation_name']); ?></td>
									<td><?php echo htmlspecialchars((string) $row['email']); ?></td>
									<td><?php echo (int) $row['total_reports']; ?></td>
									<td><?php echo (int) $row['total_parameters']; ?></td>
									<td><?php echo $row['last_report_on'] ? htmlspecialchars((string) $row['last_report_on']) : '-'; ?></td>
									<td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars((string) $row['user_status']); ?></span></td>
									<td>
										<a class="btn btn-sm btn-warning" href="organisation.php?edit_id=<?php echo (int) $row['user_id']; ?>">
											<i class="bi bi-pencil"></i> Edit
										</a>
									</td>
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
	</script>
</body>
</html>
