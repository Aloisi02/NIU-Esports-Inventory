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
		<title>Forgot Password</title>
		<meta charset="UTF-8"/>
		<style>
			body {
				background-color: black;
				color: white;
				margin:20px;
			}
			a {
				display: inline-block;
			}
			.center {
				text-align: center;
				margin: auto;
				width: min-content;
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

		<script>
			// sends an email to reset password
			async function resetPassword(){
				// request
				const req = await fetch('../api/sendResetEmail.php', {
					method: 'POST',
					body: JSON.stringify({
						email: document.getElementById("email").value
					})
				});

				// response
				const res = await req.json();
				const success = res.success;
				const reason = res.reason;
				
				// alert response
				if(success == "true"){
					alert("Password reset email sent successfully. (It may take up to 10 minutes to appear in your inbox)");
					document.getElementById("email").value = "";
				} else {
					alert(reason);
				}
			}

		</script>
	</head>
	
	
	<body>
		<!-- back button -->
		<a href="login.php" style="float:left;position:absolute;">
			<button type="button">Back to Login</button>
		</a>
		<!-- header -->
		<div style="text-align:center;">
			<h1 class="centertxt" style="display:inline-block;">Forgot Password</h1>
		</div>

		<h3 class="centertxt">Enter the email to your account</h3>

		<!-- field and submit button -->
        <div class="center">
            <input id="email" type="email" placeholder="Email" name="email"/><br/><br/>
            <button onClick="resetPassword()">Send Email</button><br/><br/>
        </div>
		
		<h3 class="centertxt">If you are an attendant, speak with the director of Esports to reset your password.</h3>

	</body>
</html>
