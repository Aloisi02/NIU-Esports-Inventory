<?php
	session_start();

	// if user is logged in redirect to PCs or account page
	if(isset($_SESSION["loggedin"])){
		if($_SESSION["perm"] < 2){
			header("Location: devices.php?type=PC");
			exit;
		} else {
			header("Location: account.php");
			exit;
		}
	}
?>


<!DOCTYPE html>
<html>
	<head>
		<!-- Title -->
		<title>Esports PC Login</title>
		<meta charset="UTF-8"/>
		<style>
			body {
				background-color: black;
				color: white;
			}
			a {
				display: inline-block;
			}
			.center {
				text-align: center;
				margin: auto;
				padding: 10px;
			}
			.centertxt {
				text-align: center;
				margin: auto;
				padding: 10px;
			}
			.centerimg {
				display: block;
				margin-left: auto;
				margin-right: auto;
				padding: 10px;
			}
		</style>
	</head>
	
	
	<body>
		<!-- Heading -->
		<h1 class="centertxt">Esports PC Login</h1>
		<!-- Linked image -->
		<div class="center">
			<a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ">
				<img class="centerimg" src="../images/logo.png"/>
			</a>
		</div><br/>
		


		<!-- Login Form -->
		<form class="center" action="../api/authenticate.php" method="POST"> 
			<input id="username" type="text" placeholder="Username" name="username"/><br/><br/>
			<input id="password" type="password" placeholder="Password" name="password"/><br/><br/>
			<input type="submit" name="submit" value="Login"/>
		</form><br/>

		<div class="center"></divclass><a href="forgotPassword.php" style="color:aqua;">Forgot Password? Click Here</a></div><br/>

		<?php
			// error messages
			if(isset($_GET["login"])){
				echo "<div class='centertxt' style='color: red;'> Incorrect username/password, please try again. </div>";
			}
			if(isset($_GET["connection"])){
				echo "<div class='centertxt' style='color: red;'> Connection to database failed. If problem persists contact system admin. </div>";
			}
			if(isset($_GET["token"])){
				echo "<div class='centertxt' style='color: red;'> Error resetting password. If problem persists contact system admin. </div>";
			}
		?>

	</body>
</html>
