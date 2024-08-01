<?php
include '../../secrets.php';
$connection = mysqli_connect($host, $username, $password, $dbname) OR die('Unable to connect to database. Contact system admin if problem persists.');