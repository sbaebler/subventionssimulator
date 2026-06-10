<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

auth_abmelden();
header('Location: /login.php');
exit;
