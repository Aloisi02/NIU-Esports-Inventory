<?php
include "checkPassword.php";
session_start();
session_destroy();

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

$new = trim($_POST['newPassword']);
$repeat = trim($_POST['repeatPassword']);
$token1 = trim($_POST['token']);


// check that the password is valid
checkPassword($new);

// check match
if($new != $repeat){
    $response = ['success'=>'false', 'reason'=>'match'];
    http_response_code(200);
    die(json_encode($response));
}

$new = password_hash($new, PASSWORD_DEFAULT);

// check that the token matches, prevents tomfoolery from 
// anyone who may try to bypass the site and just hits the API

$result = mysqli_query($connection, "SELECT token FROM changepass WHERE time_created > DATE_SUB(NOW(), INTERVAL 10 MINUTE) LIMIT 1");

// make sure token hasn't expired
if(mysqli_num_rows($result) < 1){
    header("Location: login.php?token=error");
}

$token2 = mysqli_fetch_assoc($result)["token"];

// make sure token matches
if($token1 != $token2){
    header("Location: login.php?token=invalid");
}

// checks have passed and password can be updated
if($statement = $connection->prepare("UPDATE Account SET password = ? WHERE username = ?")){
    $statement->bind_param("ss", $new, $mainAdmin);
    $statement->execute();
    $statement->store_result();

    if($statement->affected_rows == 1){
        mysqli_query($connection, "DELETE FROM changepass");
        $response = ['success'=>'true'];
        http_response_code(200);
        die(json_encode($response));
    }
}

$response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
http_response_code(500);
die(json_encode($response));
