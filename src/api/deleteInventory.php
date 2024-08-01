<?php
session_start();

// get post data
$_POST = json_decode(file_get_contents('php://input'), true);
if(!isset($_POST['id'])){
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

// only perm 1 can delete inventory
if($_SESSION['perm'] != 1){
    $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
    http_response_code(200);
    die(json_encode($response));
}


$name = $_POST['name'];
// delete the inventory
if($statement = $connection->prepare("DELETE FROM Inventory WHERE name = ?")){
    $statement->bind_param("i", $name);
    $statement->execute();
    $statement->store_result();

    if($statement->affected_rows == 1){
        $response = ['success'=>'true'];
        http_response_code(200);
        die(json_encode($response));
    }
}

$response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
http_response_code(500);
die(json_encode($response));