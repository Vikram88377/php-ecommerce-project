<?php

require_once __DIR__ . '/../config/database.php';

try {

    $sql = "SELECT id, name, email FROM users";

    $stmt = $pdo->query($sql);

    $users = $stmt->fetchAll();

    echo "<pre>";
    print_r($users);
    echo "</pre>";

} catch (PDOException $e) {

    echo "Unable to fetch users.";
}