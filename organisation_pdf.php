<?php
include 'connection.php';

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

$organisation = null;
$reports = [];
$parameters = [];

if ($userId > 0) {
    $orgSql = '
        SELECT
            u.user_id,
            COALESCE(NULLIF(u.user_name, \'\'), u.username, u.full_name, CONCAT(\'User #\', u.user_id)) AS organisation_name,
            u.email,
            u.username,
            u.role,
            u.status,
            u.start_date,
            u.end_date,
            s.station_name,
            d.division_name,
            z.zone_name,
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
        LEFT JOIN Mcc_stations s ON s.station_id = u.station_id
        LEFT JOIN Mcc_divisions d ON d.division_id = s.division_id
        LEFT JOIN Mcc_zones z ON z.zone_id = d.zone_id
        LEFT JOIN Mcc_contract_details c ON c.user_id = u.user_id
        WHERE u.user_id = ?
        LIMIT 1
    ';

    $orgStmt = $conn->prepare($orgSql);
    if ($orgStmt) {
        $orgStmt->bind_param('i', $userId);
        $orgStmt->execute();
        $orgResult = $orgStmt->get_result();
        $organisation = $orgResult ? $orgResult->fetch_assoc() : null;
        $orgStmt->close();
    }

    if ($organisation) {
        $reportSql = '
            SELECT
                report_id,
                report_name,
                report_type,
                weight_percent,
                page_url,
                status,
                created_at
            FROM Mcc_reports
            WHERE user_id = ?
            ORDER BY report_type, report_name
        ';

        $reportStmt = $conn->prepare($reportSql);
        if ($reportStmt) {
            $reportStmt->bind_param('i', $userId);
            $reportStmt->execute();
            $reportResult = $reportStmt->get_result();
            if ($reportResult) {
                while ($row = $reportResult->fetch_assoc()) {
                    $reports[] = $row;
                }
            }
            $reportStmt->close();
        }

        $parameterSql = '
            SELECT
                p.parameter_id,
                p.parameter_name,
                p.category,
                p.status,
                r.report_id,
                r.report_name,
                r.report_type
            FROM Mcc_parameters p
            INNER JOIN Mcc_reports r ON r.report_id = p.report_id
            WHERE p.user_id = ?
            ORDER BY r.report_type, r.report_name, p.parameter_name
        ';

        $parameterStmt = $conn->prepare($parameterSql);
        if ($parameterStmt) {
            $parameterStmt->bind_param('i', $userId);
            $parameterStmt->execute();
            $parameterResult = $parameterStmt->get_result();
            if ($parameterResult) {
                while ($row = $parameterResult->fetch_assoc()) {
                    $parameters[] = $row;
                }
            }
            $parameterStmt->close();
        }
    }
}

$parametersByReportId = [];
foreach ($parameters as $parameterRow) {
    $reportId = isset($parameterRow['report_id']) ? (int) $parameterRow['report_id'] : 0;
    if (!isset($parametersByReportId[$reportId])) {
        $parametersByReportId[$reportId] = [];
    }
    $parametersByReportId[$reportId][] = $parameterRow;
}

function showValue($value)
{
    return $value === null || $value === '' ? '-' : htmlspecialchars((string) $value);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organisation Detail PDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f8;
            font-size: 14px;
        }
        .page-wrap {
            max-width: 1000px;
            margin: 24px auto;
            background: #fff;
            padding: 28px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }
        .doc-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .doc-top-title {
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 10px;
            color: #0d3b66;
        }
        .doc-subtitle {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin: 18px 0 10px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 20px;
        }
        .meta-row {
            display: flex;
            gap: 8px;
        }
        .meta-label {
            font-weight: 600;
            min-width: 180px;
        }
        .print-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
        }
        @media print {
            body {
                background: #fff;
            }
            .page-wrap {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
                padding: 0;
            }
            .print-actions {
                display: none !important;
            }
            @page {
                size: A4;
                margin: 12mm;
            }
        }
    </style>
</head>
<body>
<div class="page-wrap">
    <div class="print-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save PDF</button>
        <a href="organisation_list.php" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="doc-top-title">Beatle Analytics MCC Document</div>
    <div class="doc-subtitle">Generated on <?php echo date('d M Y, h:i A'); ?></div>

    <?php if (!$organisation): ?>
        <div class="alert alert-danger mb-0">Organisation not found.</div>
    <?php else: ?>
        <div class="alert alert-light border mb-3">
            <strong>Organisation:</strong> <?php echo showValue($organisation['organisation_name']); ?><br>
            <strong>Default Password for App Or Dashboard:</strong> 123456
            <br>
            <strong>Login Link:</strong> <a href="http://mcc.beatlebuddy.com/" target="_blank" rel="noopener">http://mcc.beatlebuddy.com/</a>
        </div>

        <div class="section-title">Organisation Profile</div>
        <div class="meta-grid">
            <div class="meta-row"><div class="meta-label">Organisation Name:</div><div><?php echo showValue($organisation['organisation_name']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Email:</div><div><?php echo showValue($organisation['email']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Username:</div><div><?php echo showValue($organisation['username']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Role:</div><div><?php echo showValue($organisation['role']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Status:</div><div><?php echo showValue($organisation['status']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Start Date:</div><div><?php echo showValue($organisation['start_date']); ?></div></div>
            <div class="meta-row"><div class="meta-label">End Date:</div><div><?php echo showValue($organisation['end_date']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Station / Division / Zone:</div><div><?php echo showValue($organisation['station_name']); ?> / <?php echo showValue($organisation['division_name']); ?> / <?php echo showValue($organisation['zone_name']); ?></div></div>
        </div>

        <div class="section-title">Contract Details</div>
        <div class="meta-grid">
            <div class="meta-row"><div class="meta-label">Agreement No:</div><div><?php echo showValue($organisation['agreement_no']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Agreement Date:</div><div><?php echo showValue($organisation['agreement_date']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Contractor Name:</div><div><?php echo showValue($organisation['contractor_name']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Train Count:</div><div><?php echo showValue($organisation['train_no_count']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Amount:</div><div><?php echo showValue($organisation['amount']); ?></div></div>
            <div class="meta-row"><div class="meta-label">No. of Years:</div><div><?php echo showValue($organisation['no_of_years']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Contract Start Date:</div><div><?php echo showValue($organisation['contract_start_date']); ?></div></div>
            <div class="meta-row"><div class="meta-label">Contract End Date:</div><div><?php echo showValue($organisation['contract_end_date']); ?></div></div>
        </div>

        <div class="section-title">Reports</div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                <tr>
                    <th>Report Name</th>
                    <th>Type</th>
                    <th>Weight %</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($reports) === 0): ?>
                    <tr><td colspan="4" class="text-center text-muted">No reports found.</td></tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo showValue($report['report_name']); ?></td>
                            <td><?php echo showValue($report['report_type']); ?></td>
                            <td><?php echo $report['weight_percent'] === null ? '-' : htmlspecialchars((string) $report['weight_percent']) . '%'; ?></td>
                            <td><?php echo showValue($report['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-title">Reports Wise Parameters</div>
        <?php if (count($reports) === 0): ?>
            <div class="alert alert-light border text-muted mb-0">No reports available for parameter mapping.</div>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
                <?php
                    $reportId = isset($report['report_id']) ? (int) $report['report_id'] : 0;
                    $reportParameters = isset($parametersByReportId[$reportId]) ? $parametersByReportId[$reportId] : [];
                ?>
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div><strong><?php echo showValue($report['report_name']); ?></strong></div>
                        <span class="badge text-bg-secondary"><?php echo showValue($report['report_type']); ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Parameter</th>
                                <th>Category</th>
                                <th style="width: 120px;">Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($reportParameters) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted">No parameters configured for this report.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reportParameters as $index => $parameter): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo showValue($parameter['parameter_name']); ?></td>
                                        <td><?php echo showValue($parameter['category']); ?></td>
                                        <td><?php echo showValue($parameter['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
