<?php
// connect to db
include "database.php";
if(mysqli_connect_errno()) {
    $response = ['success'=>'false', 'reason'=>'Unable to connect to database. Contact system admin if problem persists.'];
    http_response_code(500);
    die(json_encode($response));
}


// get devices and device types
$result = mysqli_query($connection, "SELECT * FROM $device INNER JOIN Device ON $device.id = Device.id");
$devices = mysqli_fetch_all($result, MYSQLI_ASSOC);

$result = mysqli_query($connection, "SELECT * FROM " . $device . "Types");
$types = mysqli_fetch_all($result, MYSQLI_ASSOC);