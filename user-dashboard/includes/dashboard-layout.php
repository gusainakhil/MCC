<?php
if (!function_exists('ud_page_link')) {
    function ud_page_link($page, $userId)
    {
        $separator = strpos($page, '?') === false ? '?' : '&';
        return $page . $separator . 'user_id=' . (int) $userId;
    }
}

if (!function_exists('ud_report_page_map')) {
    function ud_report_page_map()
    {
        return [
            'Normal Report' => 'normal-report.php',
            'Intensive Report' => 'intensive-report.php',
            'Chemical Report' => 'chemical-report.php',
            'Machine Report' => 'machine-report.php',
            'Attendance Report' => 'attendance-report.php',
        ];
    }
}

if (!function_exists('ud_sidebar_report_items')) {
    function ud_sidebar_report_items(array $reports)
    {
        $items = [];
        $pageMap = ud_report_page_map();

        foreach ($reports as $reportRow) {
            $reportType = (string) ($reportRow['report_type'] ?? '');
            $items[] = [
                'name' => (string) ($reportRow['report_name'] ?? $reportType),
                'type' => $reportType,
                'status' => (string) ($reportRow['status'] ?? 'Inactive'),
                'weight' => (float) ($reportRow['weight_percent'] ?? 0),
                'url' => $pageMap[$reportType] ?? 'index.php',
            ];
        }

        return $items;
    }
}

if (!function_exists('ud_render_dashboard_header')) {
    function ud_render_dashboard_header(array $context)
    {
        $selectedUserId = (int) ($context['selectedUserId'] ?? 0);
        $title = (string) ($context['pageTitle'] ?? 'Dashboard');
        $subtitle = (string) ($context['pageDescription'] ?? '');
        $icon = (string) ($context['pageIcon'] ?? 'bi-grid-1x2');
        $accent = (string) ($context['pageAccent'] ?? '#3c8dbc');
        $reportType = (string) ($context['reportType'] ?? 'Dashboard');
        $selectedUserName = (string) ($context['selectedUserName'] ?? 'User');
        $stationLabel = (string) ($context['stationLabel'] ?? '-');

        ?>
        <header class="dashboard-header reveal">
            <div class="header-copy">
                <div class="eyebrow"><?php echo ud_h($reportType); ?></div>
                <h1 class="dashboard-title mb-2"><i class="bi <?php echo ud_h($icon); ?> me-2" style="color: <?php echo ud_h($accent); ?>;"></i><?php echo ud_h($title); ?></h1>
                <p class="dashboard-subtitle mb-0"><?php echo ud_h($subtitle); ?></p>
                <div class="header-meta">
                    <span><?php echo ud_h($selectedUserName); ?></span>
                    <span><?php echo ud_h($stationLabel); ?></span>
                </div>
            </div>
            <div class="topbar-actions header-actions">
                <div class="action-buttons">
                    <a href="../organisation_pdf.php?user_id=<?php echo (int) $selectedUserId; ?>" class="btn btn-outline-primary btn-soft">
                        <i class="bi bi-printer me-1"></i> Profile PDF
                    </a>
                    <a href="../logout.php" class="btn btn-outline-danger btn-soft">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                    <a href="../index.php" class="btn btn-primary btn-brand">
                        <i class="bi bi-speedometer2 me-1"></i> Admin Home
                    </a>
                </div>
            </div>
        </header>
        <?php
    }
}

if (!function_exists('ud_render_dashboard_sidebar')) {
    function ud_render_dashboard_sidebar(array $context)
    {
        $selectedUserId = (int) ($context['selectedUserId'] ?? 0);
        $selectedUserName = (string) ($context['selectedUserName'] ?? 'User');
        $stationLabel = (string) ($context['stationLabel'] ?? '-');
        $contractLabel = (string) ($context['contractLabel'] ?? '-');
        $reports = $context['reports'] ?? [];
        $reportType = (string) ($context['reportType'] ?? 'Dashboard');
        $reportItems = ud_sidebar_report_items($reports);
        $reportPageMap = ud_report_page_map();
        $activePage = $context['activePage'] ?? 'dashboard';
        ?>
        <aside class="dashboard-sidebar reveal">
            <div class="sidebar-brand">
                <div class="brand-mark">MCC</div>
                <div>
                    <div class="brand-name">Railway Panel</div>
                    <div class="brand-subtitle">User dashboard</div>
                </div>
            </div>

            <div class="sidebar-user-card">
                <div class="sidebar-user-name"><?php echo ud_h($selectedUserName); ?></div>
                <div class="sidebar-user-meta"><?php echo ud_h($stationLabel); ?></div>
                <div class="sidebar-user-tag">Contract: <?php echo ud_h($contractLabel); ?></div>
            </div>

            <nav class="sidebar-nav">
                <a class="sidebar-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" href="index.php?user_id=<?php echo (int) $selectedUserId; ?>">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
                <a class="sidebar-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </nav>

            <div class="sidebar-section">
                <div class="sidebar-section__title">Assigned Reports</div>
                <?php if (count($reportItems) === 0): ?>
                    <div class="sidebar-empty">No reports assigned yet.</div>
                <?php else: ?>
                    <div class="sidebar-report-list">
                        <?php foreach ($reportItems as $item): ?>
                            <a class="sidebar-report-item" href="<?php echo ud_h($item['url']); ?>?user_id=<?php echo (int) $selectedUserId; ?>">
                                <div class="sidebar-report-item__title">
                                    <span><?php echo ud_h($item['name']); ?></span>
                                    <small><?php echo ud_h($item['type']); ?></small>
                                </div>
                                <div class="sidebar-report-item__badge <?php echo ud_report_status_badge($item['status']); ?>">
                                    <?php echo ud_h(number_format((float) $item['weight'], 0)); ?>%
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="sidebar-footer-card">
                <div>
                    <span>Mode</span>
                    <strong><?php echo ud_h($reportType); ?></strong>
                </div>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($reportPageMap as $label => $url): ?>
                    <a class="sidebar-link <?php echo $reportType === $label ? 'active' : ''; ?>" href="<?php echo ud_h($url); ?>?user_id=<?php echo (int) $selectedUserId; ?>">
                        <i class="bi bi-journal-text"></i>
                        <span><?php echo ud_h($label); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <?php
    }
}
