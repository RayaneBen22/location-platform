<?php
require_once __DIR__ . '/bootstrap.php';
$agent = requireAgent();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') validateCsrf();
