<?php

use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
$url = 'localhost:8000/';
$emailuser = 'niuesportspassreset@gmail.com';
$mainAdmin = 'marioman2002@gmail.com';

session_start();



// connect to db
include "database.php";
if(mysqli_connect_errno()) {
    $response = ['success'=>'false', 'reason'=>'Unable to connect to database. Contact system admin if problem persists.'];
    http_response_code(500);
    die(json_encode($response));
}

// guard against spam/random password reset emails by non-admins and strangers stumbling onto the site
if(isset($_SESSION["loggedin"])){
    if($_SESSION["perm"] < 2){
        $response = ['success'=>'false','reason'=>'Nice try, nerd.'];
        http_response_code(200);
        die(json_encode($response));
    }
} else {
    $_POST = json_decode(file_get_contents('php://input'), true);
    if(!isset($_POST['email'])){
        header("Location: ../public/login.php");
        die();
    }

    $email = trim($_POST['email']);

    if(strtolower($email) != "marioman2002@gmail.com"){
        $response = ['success'=>'false','reason'=>'Unable to send password reset. Please double check your spelling.'];
        http_response_code(200);
        die(json_encode($response));
    }
}


// delete any old password reset entries
mysqli_query($connection, "DELETE FROM changepass WHERE time_created < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");

// gets existing token / password change requests if any
$result = mysqli_query($connection, "SELECT * FROM changepass WHERE time_created > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
// $token = mysqli_fetch_assoc($result);

// check for existing pass reset
if(mysqli_num_rows($result) > 0){
    $response = ['success'=>'false','reason'=>'A password reset for this account already exists, please check your email.'];
    http_response_code(200);
    die(json_encode($response));   
}

// generate a random token
$token = bin2hex(random_bytes(16));

// insert token into database
if($statement = $connection->prepare("INSERT INTO changepass (token) VALUES (?)")){
    $statement->bind_param("s", $token);
    $statement->execute();
    $statement->store_result();

    if($statement->affected_rows < 1){
        $response = ['success'=>'false','reason'=>'Unknown error, contact system admin if problem persists.'];
        http_response_code(400);
        die(json_encode($response));
    }
}


$mail = new PHPMailer(true);

try {
    // settings and login
    //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $emailuser;
    $mail->Password = $emailpass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // sender and recipient
    $mail->setFrom($emailuser, 'gmail');
    $mail->addAddress($mainAdmin);

    // content
    $mail->Subject = "Esports Inventory Password Reset";
    $mail->Body = "This link will expire after ten minutes.\n\n" .
                   $url . "resetPassword.php?token=" . $token;

    $mail->send();

    
    $response = ['success'=>'true'];
    http_response_code(200);
    die(json_encode($response));
} catch (Exception $e){
    // if error emailing delete entry from table
    mysqli_query($connection, "DELETE FROM changepass");
    $response = ['success'=>'false','reason'=>'Error sending password reset email. Contact system admin if problem persists.\nBe sure to check your spam folder.' ];
    http_response_code(400);
    die(json_encode($response));
}