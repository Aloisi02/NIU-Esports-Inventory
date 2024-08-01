<?php
session_start();

// check that token is set
if(!isset($_GET["token"])){
    header("Location: login.php?token=error");
}
session_destroy();

// connect to db
try{
    $connection = mysqli_connect($host, $username, $password, $dbname);
}catch(mysqli_sql_exception $e){}

if(mysqli_connect_errno()) {
    header("Location: login.php?connection=failed");
}


$result = mysqli_query($connection, "SELECT token FROM changepass WHERE time_created > DATE_SUB(NOW(), INTERVAL 10 MINUTE) LIMIT 1");

// make sure token hasn't expired
if(mysqli_num_rows($result) < 1){
    header("Location: login.php?token=error");
}

$token = mysqli_fetch_assoc($result)["token"];

// make sure token matches
if($token != $_GET["token"]){
    session_destroy();
    header("Location: login.php?token=invalid");
}

// if all previous checks passed, tokens properly match and password change can be allowed

?>



<!DOCTYPE html>
<html>
	<head>
		<!-- Title -->
		<title>Password Reset</title>
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
            
			.conditionMet {
				color:rgb(0, 214, 0);
			}

			.conditionNotMet {
				color: red;
			}
		</style>

        <script>
            <?php
                echo "let token = '" . $_GET["token"] . "';";
            ?>

            function failedResponse(reason, failure){
				switch(reason){
						case "length":
							alert("Password must be between 8 and 64 characters long.");
							break;
						case "upper":
							alert("Password must have at least one uppercase character.");
							break;
						case "lower":
							alert("Password must have at least one lowercase character.");
							break;
						case "digit":
							alert("Password must have at least one digit.");
							break;
						case "special":
							output = "Password must have at least one special character. Allowed characters are: \n";
							for(let i = 0; i < specialChars.length; i++){
								output += specialChars[i] + " ";
							}
							alert(output);
							break;
						case "match":
							alert("Passwords must match.");
							break;
						case "character":
							output = "Password may only contain numbers, letters, and the following special characters: \n";
							for(let i = 0; i < specialChars.length; i++){
								output += specialChars[i] + " ";
							}
							alert(output);
							break;
						case "old":
							alert("Old password does not match. Please try again.")
							document.getElementById("current").value = "";
							break;
						default:
							alert(failure + "\nReason: " + reason);
					}
			}

            // changes password for admins and attendants (big admin must use email password reset)
			async function resetPassword(){
				let newp = document.getElementById("new").value;
				let retype = document.getElementById("retype").value;

				if(newp == "" || retype == ""){
					alert("Please fill in all the fields.");
					return;
				}

				// request
				const req = await fetch('../api/resetPassword.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						newPassword: newp,
						repeatPassword: retype,
                        token: token
					})
				});

				// response
				const res = await req.json();
				const success = res.success;
				if(success == "true"){
					document.getElementById("new").value = "";
					document.getElementById("retype").value = "";
					alert("Password change successful :)")
                    window.location.replace(window.location.protocol + '//' + window.location.host + '/login');
				} else {
					let output;
					failedResponse(res.reason, "Failed to update password.");
				}


			}
        </script>
	</head>
	
	
	<body>
		<!-- Heading -->
		<h1 class="centertxt">Password Reset</h1><br/>

        <div class="center">
            <!-- Reset Form -->
            <input id="new" type="password" placeholder="New Password" name="new"/><br/><br/>
            <input id="retype" type="password" placeholder="Retype Password" name="retype"/><br/><br/>
            <!-- Change -->
            <button onClick="resetPassword()">Change</button><br/><br/>

            <div>
                Password requirements:

                <br><br>
                <span id="passwordLength" class="conditionNotMet">
                    Between 8 and 64 characters long
                </span><br>
                <span id="passwordUpper" class="conditionNotMet">
                    At least one uppercase letter
                </span><br>
                <span id="passwordLower" class="conditionNotMet">
                    At least one lowercase letter
                </span><br>
                <span id="passwordNums" class="conditionNotMet">
                    At least one digit
                </span><br>
                <span id="passwordChars" class="conditionNotMet">
                    At least one special character
                </span><br>
                <span id="passwordMatch" class="conditionNotMet">
                    Passwords must match
                </span>
            </div>
                
        </div>
		

	</body>


	<script>
		// password and username checking code
		const newPassword = document.getElementById("new");
		const repeatPassword = document.getElementById("retype");

		const alphabet = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
		'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
		const digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
		const specialChars = ['!', '(', ')', '-', '.', '?', '[', ']','_', '`', '~', ';', ':', '@', 
		'#', '$', '%', '^', '&', '*', '+']
		//const usernameAllowedChars = alphabet + digits + [' '];
		//const passwordAllowedChars = alphabet + digits + specialChars;

		const passwordLength = document.getElementById("passwordLength");
		const passwordUpper = document.getElementById("passwordUpper");
		const passwordLower = document.getElementById("passwordLower");
		const passwordNums = document.getElementById("passwordNums");
		const passwordChars = document.getElementById("passwordChars");
		const passwordMatch = document.getElementById("passwordMatch");


		//check if password is allowed
		function checkPassword(){
			var passwordInput = newPassword.value;
			var confirmPasswordInput = repeatPassword.value;
			if(passwordInput.length < 1){
				passwordLength.className = 'conditionNotMet';
				passwordUpper.className = 'conditionNotMet';
				passwordLower.className = 'conditionNotMet';
				passwordNums.className = 'conditionNotMet';
				passwordChars.className = 'conditionNotMet';
				passwordMatch.className = 'conditionNotMet';
				return;
			}

			//check length of password
			if(passwordInput.length > 7 && passwordInput.length < 65){
				passwordLength.className = 'conditionMet';
			}
			else{
				passwordLength.className = 'conditionNotMet';
			}

			//check allowed chars, num, and uppercase.
			//var charReqMet = true;
			var numReqMet = false;
			var upperReqMet = false;
			var lowerReqMet = false;
			var spCharReqMet = false;
			for(let i = 0; i < passwordInput.length; i++){
				//check if uppercase letter
				for(let j = 0; !upperReqMet && j < alphabet.length; j++){
						if(passwordInput[i] == alphabet[j].toUpperCase()){
								upperReqMet = true;
						}
				}
				//check if lowercase letter
				for(let j = 0; !lowerReqMet && j < alphabet.length; j++){
						if(passwordInput[i] == alphabet[j].toLowerCase()){
								lowerReqMet = true;
						}
				}
				//check if digit
				for(let j = 0; !numReqMet && j < digits.length; j++){
						if(passwordInput[i] == digits[j]){
								numReqMet = true;
						}
				}
				//check if special char
				for(let j = 0; !spCharReqMet && j < specialChars.length; j++){
						if(passwordInput[i] == specialChars[j]){
								spCharReqMet = true;
						}
				}
			}

			//update conditions met
			if(spCharReqMet){
				passwordChars.className = 'conditionMet';
			}
			else{
				passwordChars.className = 'conditionNotMet';
			}
			if(numReqMet){
				passwordNums.className = 'conditionMet';
			}
			else{
				passwordNums.className = 'conditionNotMet';
			}
			if(upperReqMet){
				passwordUpper.className = 'conditionMet';
			}
			else{
				passwordUpper.className = 'conditionNotMet';
			}
			if(lowerReqMet){
				passwordLower.className = 'conditionMet';
			}
			else{
				passwordLower.className = 'conditionNotMet';
			}
			if(passwordInput == confirmPasswordInput){
				passwordMatch.className = 'conditionMet';
			}
			else{
				passwordMatch.className = 'conditionNotMet';
			}
		}

		
		// when password textboxes are changed check validity

		newPassword.addEventListener("input",
			()=>{checkPassword()}
		);

		repeatPassword.addEventListener("input", () => {
			var passwordInput = newPassword.value;
			var confirmPasswordInput = repeatPassword.value;
			if(passwordInput.length < 1){
				passwordMatch.className = 'conditionNotMet';
			}
			else if(passwordInput == confirmPasswordInput){
				passwordMatch.className = 'conditionMet';
			}
			else{
				passwordMatch.className = 'conditionNotMet';
			}
		});
	</script>
</html>
