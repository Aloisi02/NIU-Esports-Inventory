<?php
// connect to db
include "../api/database.php";
if(mysqli_connect_errno()) {
    header("Location: login.php?connection=failed");
}

$result = mysqli_query($connection, "SELECT token FROM ChangePass WHERE time_created > DATE_SUB(NOW(), INTERVAL 20 MINUTE) LIMIT 1");

// make sure token hasn't expired
if(mysqli_num_rows($result) < 1){
    header("Location: login.php?token=error");
}

$token = mysqli_fetch_assoc($result)["token"];