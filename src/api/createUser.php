<?php
// checks if a character is a valid username character
function isValidUsernameChar($char){
    $usernameChars = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n','o', 'p', 'q', 
    'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ' '];

    foreach ($usernameChars as $allowed) {
        if(strtolower($char) == $allowed){
            return true;
        }
    }
    return false;
}

include "checkPassword.php";
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


// get data from request
$type = (int) $_POST['type'];
$username = trim($_POST['username']);
$password = trim($_POST['password']);


if(strlen($username) > 64 || strlen($username) == 0){
    $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
    http_response_code(400);
    die(json_encode($response));
}
// check that username only includes allowed characters
foreach (str_split($username) as $char) {
    if(!isValidUsernameChar($char)){
        $response = ['success'=>'false', 'reason'=>'Username can only contain letters, numbers, and spaces.'];
        http_response_code(200);
        die(json_encode($response));
    }
}

// check password
checkPassword($password);



$password = password_hash($password, PASSWORD_DEFAULT);


// check that account with username doesn't already exist
$statement = $connection->prepare("SELECT IF (EXISTS (SELECT * FROM Account WHERE username = ?), 1, 0)");
$statement->bind_param("s", $username);
$statement->execute();
$res = $statement->get_result();
$exists = $res->fetch_row()[0];

// check that the user's permission level is higher than the user being inserted
if(!$exists){
    if($_SESSION["perm"] > $type && $_SESSION["perm"] < 3){
        if($statement = $connection->prepare("INSERT INTO Account (username, password, perm) VALUES (?, ?, ?)")){
            $statement->bind_param("ssi", $username, $password, $type);
            $statement->execute();
            $statement->store_result();

            if($statement->affected_rows == 1){
                $response = ['success'=>'true'];
                http_response_code(200);
                die(json_encode($response));
            } else {
                $response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
                http_response_code(400);
                die(json_encode($response));
            }

        }
    } else {// if someone tries deleting an account without the proper permissions (somehow)
        $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
        http_response_code(200);
        die(json_encode($response));
    }
} else {// if username already exists
    $response = ['success'=>'false', 'reason'=>'Account already exists with that username.'];
    http_response_code(200);
    die(json_encode($response));
}

$response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
http_response_code(400);
die(json_encode($response));