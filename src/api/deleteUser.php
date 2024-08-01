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

// get permission level of the user being deleted
if($statement = $connection->prepare("SELECT perm FROM Account WHERE username = ?")){
    $statement->bind_param("s", $_POST['username']);
    $statement->execute();
    $statement->store_result();

    if($statement->num_rows == 1){
        $statement->bind_result($perm);
        $statement->fetch();
    } else {
        $response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
        http_response_code(400);
        die(json_encode($response));
    }
    $statement->close();
}

// if the permission level of the current user is higher than the user being deleted, attempt deletion
if($_SESSION["perm"] > $perm){
    if($statement = $connection->prepare("DELETE FROM Account WHERE username = ?")){
        $statement->bind_param("s", $_POST['username']);
        $statement->execute();
        $statement->store_result();

        if($statement->affected_rows == 1){
            $response = ['success'=>'true', 'perm'=>$perm];
            http_response_code(200);
            die(json_encode($response));
        } else {
            $response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
            http_response_code(400);
            die(json_encode($response));
        }
    }
}

$response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
http_response_code(400);
die(json_encode($response));