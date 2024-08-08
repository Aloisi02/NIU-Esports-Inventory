<?php
// connect to db
include "database.php";
if(mysqli_connect_errno()) {
    $response = ['success'=>'false', 'reason'=>'Unable to connect to database. Contact system admin if problem persists.'];
    http_response_code(500);
    die(json_encode($response));
}

// get admins
if($perm > 1){
    $result = mysqli_query($connection, "SELECT username FROM Account WHERE perm = 1");
    $admins = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $adminCount = count($admins);
}	

// get attendants
$result = mysqli_query($connection, "SELECT username FROM Account WHERE perm = 0");
$attendants = mysqli_fetch_all($result, MYSQLI_ASSOC);
$attendantCount = count($attendants);