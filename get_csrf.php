<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

session_init();
echo json_encode(['token' => csrf_token()]);
