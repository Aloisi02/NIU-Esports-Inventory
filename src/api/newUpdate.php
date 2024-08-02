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


$id = (int) $_POST['id'];
$status = (int) $_POST['status'];
$summary = trim($_POST['summary']);
$details = trim($_POST['details']);


// stop tomfoolery from inserting an invalid status or big admin somehow creating a ticket or too long strings
if(strlen($summary) > 48 || strlen($summary) < 5 || strlen($details) > 1024 || $_SESSION["perm"] > 1 || $_SESSION["perm"] < 0 || $status > 4){
    $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
    http_response_code(200);
    die(json_encode($response));
}
// check if the device is archived
if($statement = $connection->prepare("SELECT IF (EXISTS (SELECT * FROM Device WHERE id = ? AND archived = 1), 1, 0)")){
    $statement->bind_param("i", $id);
    $statement->execute();
    $res = $statement->get_result();
    $isArchived = $res->fetch_row()[0];
}
$statement->close();

// if archived and permission isn't 1 then not allowed to update
if($_SESSION["perm"] != 1 && $isArchived){
    $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
    http_response_code(200);
    die(json_encode($response));
}

// if update is for existing ticket then assign the ticket variable
if($_POST['ticket'] != false){
    $ticket = $_POST['ticket'];
    
    // status can't be less than 0 for existing tickets
    if($status < 0) {
        $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
        http_response_code(200);
        die(json_encode($response));
    }
} else { // update is for a new ticket
    // status can't be less than 1 for new tickets
    if($status < 1) {
        $response = ['success'=>'false', 'reason'=>'Nice try, nerd.'];
        http_response_code(200);
        die(json_encode($response));
    }

    // create ticket and get the ID
    if($statement = $connection->prepare("INSERT INTO Ticket (device) VALUES (?)")){
        $statement->bind_param("i", $id);
        $statement->execute();
        $statement->store_result();

        if($statement->affected_rows == 1){
            $ticket = $connection->insert_id; 
            $statement->close();
        } else {
            $response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
            http_response_code(500);
            die(json_encode($response));
        }
    }
}

// create update
if($statement = $connection->prepare("INSERT INTO Updates (ticket, summary, details, status, username) VALUES (?,?,?,?,?)")){
    $statement->bind_param("issis", $ticket, $summary, $details, $status, $_SESSION["username"]);
    $statement->execute();
    $statement->store_result();

    if($statement->affected_rows == 1){
        $response = ['success'=>'true'];
        http_response_code(200);
        die(json_encode($response));
    } else {
        // delete ticket if new ticket and update failed to insert
        if($_POST['ticket'] == false){
            mysqli_query($connection, "DELETE FROM ticket WHERE id = " + $ticket);
        }
    }
}

$response = ['success'=>'false', 'reason'=>'Unknown, contact system admin if problem persists.'];
http_response_code(500);
die(json_encode($response));