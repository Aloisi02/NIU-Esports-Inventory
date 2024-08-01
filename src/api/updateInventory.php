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

$name = $_POST['name'];
$quantity = (int) $_POST['quantity'];

// only perm 1 and 0 can update and name must be between 2 and 64 chars
if($_SESSION["perm"] > 1 || strlen($name) > 64 || strlen($name) < 2){
    $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
    http_response_code(200);
    die(json_encode($response));
} 
// make sure quantity is a positive number
if ($quantity < 0 || strlen($quantity) < 1){
    $response = ['success'=>'false', 'reason'=>'Quantity must be a positive number!'];
    http_response_code(200);
    die(json_encode($response));
}


// check if type already exists
$statement = $connection->prepare("SELECT IF (EXISTS (SELECT * FROM Inventory WHERE name = ?), 1, 0)");
$statement->bind_param("s", $name);
$statement->execute();
$res = $statement->get_result();
$itemExists = $res->fetch_row()[0];
if(!$itemExists){
    $response = ['success'=>'false', 'reason'=>'Inventory with that name doesn\'t exist.'];
    http_response_code(200);
    die(json_encode($response));
}
$statement->close();

// update inventory quantity
if($statement = $connection->prepare("UPDATE Inventory SET quantity = ? WHERE name = ?")){
    $statement->bind_param("is", $quantity, $name);
    $statement->execute();
    $statement->store_result();

    if($statement->affected_rows == 1){
        $response = ['success'=>'true'];
        http_response_code(200);
        die(json_encode($response));
    }
}
$statement->close();

$response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
http_response_code(400);
die(json_encode($response));