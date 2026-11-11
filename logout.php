<?php
require_once __DIR__ . '/config.php';

$wasAdmin = !empty($_SESSION['admin_id']);

$_SESSION = [];
session_destroy();

redirect($wasAdmin ? 'admin/login.php' : 'login.php');
