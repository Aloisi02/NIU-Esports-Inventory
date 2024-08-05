<?php
session_start();

// get post data
$_POST = json_decode(file_get_contents('php://input'), true);
if(!isset($_POST['name'])){
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

// only perm 1 and 0 can create a new device and name won't submit if
// less than 2 or longer than 64 chars
if($_SESSION["perm"] > 1 || strlen($name) > 64 || strlen($name) < 2){
    $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
    http_response_code(200);
    die(json_encode($response));
} 
// check quantity is positive
if ($quantity < 0 || strlen($quantity) < 1){
    $response = ['success'=>'false', 'reason'=>'Quantity must be a positive number!'];
    http_response_code(200);
    die(json_encode($response));
}


// check if item already exists
$statement = $connection->prepare("SELECT IF (EXISTS (SELECT * FROM Inventory WHERE name = ?), 1, 0)");
$statement->bind_param("s", $name);
$statement->execute();
$res = $statement->get_result();
$itemExists = $res->fetch_row()[0];
// return if item exists
if($itemExists){
    $response = ['success'=>'false', 'reason'=>'Inventory with that name already exists.'];
    http_response_code(200);
    die(json_encode($response));
}
$statement->close();

// insert item to database
if($statement = $connection->prepare("INSERT INTO Inventory (name, quantity) VALUES (?,?)")){
    $statement->bind_param("si", $name, $quantity);
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
