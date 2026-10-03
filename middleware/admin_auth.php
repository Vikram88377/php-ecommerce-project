<?php

require_once __DIR__. '/../includes/security.php';

if(empty($_SESSION['user_id'])){

header("Location: ../public/login.php");

exit;
}

if($_SESSION['user_role'] !== 'admin'){

http_response_code(403);
exit("access denied");

}