<?php
require_once __DIR__ . '/includes/auth.php';

ud_logout_user();
header('Location: login.php');
exit;
