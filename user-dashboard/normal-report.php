<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dashboard-data.php';
require_once __DIR__ . '/includes/dashboard-layout.php';

ud_require_auth('../login.php');
ud_require_org_admin_dashboard('../index.php');

$selectedUserId = ud_authenticated_user_id();
$context = ud_load_dashboard_context($conn, $selectedUserId, 'Normal Report');
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
	'reportType' => 'Normal Report',
	'pageTitle' => 'Normal Report',
	'pageDescription' => 'Static score card preview with top date filter.',
	'pageIcon' => 'bi-journal-text',
	'pageAccent' => '#3c8dbc',
	'activePage' => 'normal-report',
];

$coachPositionCount = 24;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Normal Report | MCC User Dashboard</title>
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
							<p class="panel-subtitle mb-0">Date filter UI is available on top. Values are static for now.</p>
						</div>
					</div>
					<div class="panel-card__body">
						<div class="row g-3 align-items-end">
							<div class="col-12 col-md-6 col-xl-3">
								<label class="form-label">From Date</label>
								<input type="date" class="form-control" value="2026-04-01">
							</div>
							<div class="col-12 col-md-6 col-xl-3">
								<label class="form-label">To Date</label>
								<input type="date" class="form-control" value="2026-04-14">
							</div>
							<div class="col-12 col-md-6 col-xl-3">
								<label class="form-label">Train No</label>
								<input type="text" class="form-control" value="12345">
							</div>
							<div class="col-12 col-md-6 col-xl-3 d-flex gap-2 justify-content-end">
								<button type="button" class="btn btn-soft">Reset</button>
								<button type="button" class="btn btn-brand">Apply Filter</button>
								<button type="button" class="btn btn-outline-primary btn-soft" onclick="window.print();">
									<i class="bi bi-printer me-1"></i> Print
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="panel-card reveal mt-3">
					<div class="panel-card__body">
						<div class="normal-score-sheet">
							<div class="normal-score-sheet__head">
								<span>Annexure A-1</span>
								<h3>Score card for Normal cleaning</h3>
							</div>
						

							<div class="normal-score-sheet__meta">
								<div>Agreement No &amp; date: ..................................</div>
								<div>Name of Contractor: ..................................</div>
								<div>Date of Inspection: ..............</div>
								<div>Name of Depot: <?php echo ud_h($userProfile['station_name'] ?? '..............'); ?></div>
								<div>Name of Supervisor: ..................................</div>
								<div>Train No: ........................</div>
								<div>Time Work Started: ..............</div>
								<div>Time Work Completed: ..............</div>
								<div>No. of Coaches in rake: ..............</div>
								<div>No. of Coaches attended: ..............</div>
							</div>

							<div class="table-responsive">
								<table class="table table-bordered normal-score-table mb-0">
									<thead>
										<tr>
											<th>Sr</th>
											<th>Coach position</th>
											<?php for ($coachPosition = 1; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
												<th><?php echo (int) $coachPosition; ?></th>
											<?php endfor; ?>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td></td>
											<td>Coach No</td>
											<?php for ($coachPosition = 1; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
												<td></td>
											<?php endfor; ?>
										</tr>
										<tr class="section-row">
											<td>(A)</td>
											<td colspan="<?php echo (int) (1 + $coachPositionCount); ?>">Coach Interior</td>
										</tr>
										<tr>
											<td>1</td>
											<td>Cleaning and wiping of toilet area and fittings including wash basins, mirrors, mugs in AC coaches etc.</td>
											<td>8</td>
											<?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
												<td></td>
											<?php endfor; ?>
										</tr>
										<tr>
											<td>2</td>
											<td>Interior cleaning doorways, gangways, vestibules, window glass, window shutter etc.</td>
											<td>8</td>
											<?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
												<td></td>
											<?php endfor; ?>
										</tr>
										<tr>
											<td>3</td>
											<td>Cleaning and wiping of all berths, panels, rexene and amenity fittings.</td>
											<td>8</td>
											<?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
												<td></td>
											<?php endfor; ?>
										</tr>
										<tr>
											<td>4</td>
											<td>Floor including area under seats and berths etc.</td>
											<td>8</td>
											<?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
												<td></td>
											<?php endfor; ?>
										</tr>
										<tr class="section-row">
											<td>(B)</td>
											<td>Coach Exterior</td>
											<td>8</td>
											<?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
												<td></td>
											<?php endfor; ?>
										</tr>
										<tr>
											<td></td>
											<td>Exterior cleaning and washing including end panel</td>
											<td>8</td>
											<?php for ($coachPosition = 2; $coachPosition <= $coachPositionCount; $coachPosition++): ?>
												<td></td>
											<?php endfor; ?>
										</tr>
										<tr class="section-row">
											<td>(C)</td>
											<td colspan="<?php echo (int) (1 + $coachPositionCount); ?>">Watering (Please mention Yes/No)</td>
										</tr>
									</tbody>
								</table>
							</div>

							<ul class="normal-score-notes mb-0">
								<li>Maximum marks for internal cleaning: 40</li>
								<li>Rating band: Excellent 10, Very Good 8-9, Satisfactory 6-7, Poor 5 and below, Not attended 0</li>
								<li>Example score: 32/40 x 100 = 80%</li>
								<li>Maximum marks for exterior cleaning and washing: 10</li>
								<li>Exterior example score: 8/10 x 100 = 80%</li>
							</ul>

							<div class="normal-score-sign">
								<span>Signature of Contractor's Supervisor</span>
								<span>Signature of Auth. Rep. of Sr.DME/CDO</span>
							</div>
                            <br><br>
                            		<div class="normal-score-sheet__meta">
									<div>Date: .......................</div>
									<div>Train No: ............................</div>
								</div>

							<div class="normal-score-sheet__footer-note">Payment cum Penalty Schedule for normal coach cleaning:</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script src="assets/js/script.js"></script>
</body>
</html>
 