<?php

require_once __DIR__. '/../config/database.php';
try{

        $stmt = $pdo->query("SELECT 1 AS test");

        $result = $stmt->fetch();
        if($result['test'] == 1) {

        echo "Database connected success";

        }



}catch(PDOException $e){

    echo "database qyuery failed" . $e->getMessage();

}