<?php
// public/index.php — redirect ke dashboard
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

requireLogin();

header('Location: /siakad-mini/public/dashboard.php');
exit;
