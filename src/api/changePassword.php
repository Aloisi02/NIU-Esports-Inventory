<?php
include "checkPassword.php";
session_start();


// get post data
$_POST = json_decode(file_get_contents('php://input'), true);
if(!isset($_POST['oldPassword'])){
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


// basically prevents tomfoolery with big admin changing password
// without using a secure email token password reset
if($_SESSION["perm"] > 1 || $_SESSION["perm"] < 0){
    $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
    http_response_code(200);
    die(json_encode($response));
}


$old = trim($_POST['oldPassword']);
$new = trim($_POST['newPassword']);
$repeat = trim($_POST['repeatPassword']);



// check match
if($new != $repeat){
    $response = ['success'=>'false', 'reason'=>'match'];
    http_response_code(200);
    die(json_encode($response));
}


// verify that the "old" password matches the currently set password
if($statement = $connection->prepare("SELECT password FROM Account WHERE username = ?")){
    $statement->bind_param("s", $_SESSION["username"]);
    $statement->execute();
    $statement->store_result();

    if($statement->num_rows > 0){
        $statement->bind_result($password);
        $statement->fetch();

        // if password matches update to the new password
        if(password_verify($old, $password)){

            // check that the new password is valid
            checkPassword($new);

            // hash and store the password
            $new = password_hash($new, PASSWORD_DEFAULT);
            if($statement = $connection->prepare("UPDATE Account SET password = ? WHERE username = ?")){
                $statement->bind_param("ss", $new, $_SESSION["username"]);
                $statement->execute();
                $statement->store_result();

                if($statement->affected_rows == 1){
                    $response = ['success'=>'true'];
                    http_response_code(200);
                    die(json_encode($response));
                } else {
                    $response = ['success'=>'false', 'reason'=>'Unknown error. Contact system admin if problem persists.'];
                    http_response_code(400);
                    die(json_encode($response));
                }
            }
        } else {
            $response = ['success'=>'false', 'reason'=>'old'];
            http_response_code(200);
            die(json_encode($response));
        }
    } else {
        $response = ['success'=>'false', 'reason'=>'Unknown error. Contact system admin if problem persists.'];
        http_response_code(400);
        die(json_encode($response));
    }
}

$response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
http_response_code(400);
die(json_encode($response));
