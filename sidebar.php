        <?php include 'connection.php'; ?>
        <nav class="sidebar bg-dark">
            <div class="sidebar-header">
                <h4 class="text-white mb-0">MCC System</h4>
                <small class="text-muted">Railway Dashboard</small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="stations.php">
                        <i class="bi bi-building"></i> Stations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="organisation.php">
                        <i class="bi bi-diagram-3"></i> Create Organisation
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="organisation_list.php">
                        <i class="bi bi-clipboard-check"></i> organisation List
                    </a>
               
            </ul>
            <div class="sidebar-footer">
                <a href="#" class="btn btn-sm btn-outline-danger w-100">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </nav>