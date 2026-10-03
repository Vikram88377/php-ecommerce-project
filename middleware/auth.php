<?php

require_once __DIR__ . '/../includes/security.php';

if (empty($_SESSION['user_id'])) {

    header("Location: ../public/login.php");
    exit;
}