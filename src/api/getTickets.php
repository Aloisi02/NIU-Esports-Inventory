<?php
session_start();

// get post data
$_POST = json_decode(file_get_contents('php://input'), true);
if(!isset($_POST['device'])){
    header("Location: ../public/devices.php?type=PC");
    die();
}

// connect to db
include "database.php";
if(mysqli_connect_errno()) {
    $response = ['success'=>'false', 'reason'=>'Unable to connect to database. Contact system admin if problem persists.'];
    http_response_code(500);
    die(json_encode($response));
}

// get the tickets for a specific device
if($statement = $connection->prepare("SELECT * FROM updates WHERE ticket IN (SELECT id FROM ticket WHERE device = ?) ORDER BY ticket DESC")){
    $statement->bind_param("i", $_POST['device']);
    $statement->execute();
    $result = $statement->get_result();
    $updates = $result->fetch_all();

    $response = ['success'=>'true', 'updates'=>$updates];
    
    http_response_code(200);
    die(json_encode($response));
}

$response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
http_response_code(500);
die(json_encode($response));
