<?php
require_once __DIR__ . '/config.php';
session_init();
session_unset();
session_destroy();
header('Location: auth.php');
exit;
