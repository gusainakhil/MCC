<?php
require_once __DIR__ . '/user-dashboard/includes/auth.php';

ud_logout_user();
header('Location: login.php');
exit;
