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

$type = trim(strtoupper($_POST['type']));
$id = $_POST['id'];
$device = $_POST['deviceType'];
$deviceType = $device . "Types";


// only perm 1 can create a new device and type won't submit if empty or longer than 32 chars
if($_SESSION["perm"] != 1 || strlen($type) > 32 || strlen($type) == 0){
    $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
    http_response_code(200);
    die(json_encode($response));
}

// check ID
if(!is_numeric($id)){
    $response = ['success'=>'false', 'reason'=>'ID must be a number!'];
    http_response_code(200);
    die(json_encode($response));
}
if(strlen($id) != 6 && strtolower($device) != "console"){
    $response = ['success'=>'false', 'reason'=>'ID must be 6 digits long!'];
    http_response_code(200);
    die(json_encode($response));
} else if(strlen($id) != 3 && strtolower($device) == "console"){
    $response = ['success'=>'false', 'reason'=>'ID must be 3 digits long!'];
    http_response_code(200);
    die(json_encode($response));
}



$id = (int) $id;


// check if type already exists
$statement = $connection->prepare("SELECT IF (EXISTS (SELECT * FROM " . $deviceType . " WHERE type = ?), 1, 0)");
$statement->bind_param("s", $type);
$statement->execute();
$res = $statement->get_result();
$typeExists = $res->fetch_row()[0];
$statement->close();

// check if ID already exists
$statement = $connection->prepare("SELECT IF (EXISTS (SELECT * FROM Device WHERE id = ?), 1, 0)");
$statement->bind_param("i", $id);
$statement->execute();
$res = $statement->get_result();
$idExists = $res->fetch_row()[0];
if($idExists){
    $response = ['success'=>'false', 'reason'=>'That ID already exists!'];
    http_response_code(200);
    die(json_encode($response));
}
$statement->close();

$typeInserted = false;
$deviceInserted = false;

// if type doesn't exist create it
if(!$typeExists){
    if($statement = $connection->prepare("INSERT INTO " . $deviceType . " (type) VALUES (?)")){
        $statement->bind_param("s", $type);
        $statement->execute();
        $statement->store_result();

        if($statement->affected_rows == 1){
            $typeInserted = true;
        } else {
            $response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
            http_response_code(400);
            die(json_encode($response));
        }
    }
    $statement->close();
}
// insert device
if($statement = $connection->prepare("INSERT INTO Device (id, archived) VALUES (?,false)")){
    $statement->bind_param("i", $id);
    $statement->execute();
    $statement->store_result();

    if($statement->affected_rows == 1){
        $deviceInserted = true;
    } else {
        if($typeInserted){
            mysqli_query($connection, "DELETE FROM " . $deviceType . " WHERE type = " . $type);
        }
    }
}

// if device was inserted successfully into device table then insert into specific type table
if($deviceInserted && $statement = $connection->prepare("INSERT INTO " . $device . " (id, type) VALUES (?,?)")){
    $statement->bind_param("is", $id, $type);
    $statement->execute();
    $statement->store_result();

    // if successful return
    if($statement->affected_rows == 1){
        $response = ['success'=>'true'];
        http_response_code(200);
        die(json_encode($response));
    }
}

// if this code is reached then unsuccessful
// remove type and device

if($typeInserted){
    mysqli_query($connection, "DELETE FROM " . $deviceType . " WHERE type = " . $type);
}
if($deviceInserted){
    mysqli_query($connection, "DELETE FROM Device WHERE id = " . $id);
}

$response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
http_response_code(500);
die(json_encode($response));