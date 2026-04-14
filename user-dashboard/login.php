<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/includes/auth.php';

ud_auth_redirect_if_logged_in('index.php');

$errorMessage = '';
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginValue = trim((string) ($_POST['login'] ?? ''));
    $passwordValue = (string) ($_POST['password'] ?? '');

    if ($loginValue === '' || $passwordValue === '') {
        $errorMessage = 'Please enter username/email and password.';
    } else {
        $loginSql = '
            SELECT
                user_id,
                user_name,
                username,
                full_name,
                email,
                password_hash,
                role,
                status
            FROM Mcc_users
            WHERE username = ? OR email = ? OR user_name = ?
            LIMIT 1
        ';
        $loginStmt = $conn->prepare($loginSql);
        if (!$loginStmt) {
            $errorMessage = 'Unable to prepare login query.';
        } else {
            $loginStmt->bind_param('sss', $loginValue, $loginValue, $loginValue);
            $loginStmt->execute();
            $loginResult = $loginStmt->get_result();
            $loginUser = $loginResult ? $loginResult->fetch_assoc() : null;
            $loginStmt->close();

            if (!$loginUser || !isset($loginUser['password_hash']) || !password_verify($passwordValue, (string) $loginUser['password_hash'])) {
                $errorMessage = 'Invalid username or password.';
            } elseif (strtolower((string) ($loginUser['status'] ?? 'Active')) !== 'active') {
                $errorMessage = 'Your account is inactive.';
            } else {
                ud_login_user($loginUser);
                header('Location: index.php?user_id=' . (int) $loginUser['user_id']);
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCC User Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-shell">
        <div class="login-glow login-glow-left"></div>
        <div class="login-glow login-glow-right"></div>

        <div class="login-card reveal">
            <div class="login-brand">
                <div class="brand-mark">MCC</div>
                <div>
                    <div class="brand-name">BeatleBuddy Railway</div>
                    <div class="brand-subtitle">User Portal Login</div>
                </div>
            </div>

            <div class="login-copy">
                <span class="hero-chip">MCC Dashboard Access</span>
                <h1>Sign in to your user dashboard</h1>
                <p>Use your MCC username, email, or user name to access assigned reports and dashboard pages.</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger border-0"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <div class="mb-3">
                    <label for="login" class="form-label">Username / Email</label>
                    <input type="text" name="login" id="login" class="form-control form-control-lg" value="<?php echo htmlspecialchars($loginValue, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control form-control-lg" required>
                </div>
                <button type="submit" class="btn btn-primary btn-brand w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </button>
            </form>

            <div class="login-note">
                After login, you will be taken to your dashboard and can open each report page from the sidebar.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
