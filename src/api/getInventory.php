<?php
// connect to db
include "../api/database.php";
if(mysqli_connect_errno()) {
    $response = ['success'=>'false', 'reason'=>'Unable to connect to database. Contact system admin if problem persists.'];
    http_response_code(500);
    die(json_encode($response));
}

// get inventory
$result = mysqli_query($connection, "SELECT * FROM Inventory");
$inventory = mysqli_fetch_all($result, MYSQLI_ASSOC);