<?php
session_start();

// connect to db
include "database.php";
if(mysqli_connect_errno()) {
    $response = ['success'=>'false', 'reason'=>'Unable to connect to database. Contact system admin if problem persists.'];
    http_response_code(500);
    die(json_encode($response));
}

// return if username or password are empty
if(!isset($_POST["username"], $_POST["password"])) {
    header("Location: ../public/login.php?login=failed");
    exit;
}

// get hashed password for the given username
if($statement = $connection->prepare("SELECT password, perm FROM Account WHERE username = ?")){
    $statement->bind_param("s", $_POST["username"]);
    $statement->execute();
    $statement->store_result();

    if($statement->num_rows > 0){
        $statement->bind_result($password, $perm);
        $statement->fetch();
        
        // verify inputted password matches hashed password
        if(password_verify($_POST["password"], $password)){
            // generate the session and set session variables
            session_regenerate_id();
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $_POST["username"];
            $_SESSION["perm"] = $perm;

            // redirect to page
            if($perm == 2){
                header("Location: ../public/account.php");
            } else {
                header("Location: ../public/devices.php?type=PC");
            }
        } else { // mismatching password
            header("Location: ../public/login.php?login=failed");
            session_destroy();
            exit;
        }
    } else { // no username found
        header("Location: ../public/login.php?login=failed");
        session_destroy();
        exit;
    }


    $statement->close();
}
