<?php
	session_start();

	// if user isn't logged in redirect to logout (kills session and goes to login page)
	if(!isset($_SESSION["loggedin"])){
		header("Location: ../api/logout.php");
		exit;
	}

	// get user lists based on permission level
	$perm = $_SESSION["perm"];
	if($perm > 0){
		include "../api/getUsers.php";
	}
?>


<!DOCTYPE html>
<html>
	<head>
		<!-- Title -->
		<title>Account Settings</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<link rel="stylesheet" href="../styles/sidebar.css">
		<link rel="stylesheet" href="../styles/main.css">
		<style>
			#heading {
				margin: 0;
				padding: 0;
			}
			
			.userRow {
				display:flex; 
				padding:5px; 
				justify-content: space-between; 
				border: red; 
				border-style:solid solid none solid;
			}

			.flex {
				display: flex;
			}
			
			.conditionMet {
				color:rgb(0, 214, 0);
			}

			.conditionNotMet {
				color: red;
			}

			<?php echo '
			.flex > div {
				padding: 10px;
				max-width: 500px;
				width: calc(100%/' . ($perm + 1) . '); /* Setting the width of columns at one third each */
			}
			'; ?>
		</style>
		
		<script>
			<?php 
				// count variables
				if($perm > 0){
					echo "let attendantCount = " . $attendantCount . ";";
				} 
				if($perm > 1){
					echo "let adminCount = " . $adminCount . ";";
				}
			?>

			// allowed characters variables
			let alphabet = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
			'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
			let digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
			let specialChars = ['!', '(', ')', '-', '.', '?', '[', ']','_', '`', '~', ';', ':', '@', 
			'#', '$', '%', '^', '&', '*', '+'];
			let passwordCharsAllowed = alphabet.concat(digits, specialChars);
			let userCharsAllowed = alphabet.concat(digits, [" "]);


			// checks character
			function isValidChar(char, allowedCharList){
				for(const allowed of allowedCharList) {
					if(char.toLowerCase() == allowed){
						return true;
					}
				}
				return false;
			}

			// failed password responses
			function failedResponse(reason, failure){
				switch(reason){
					case "length":
					case 0:
						alert("Password must be between 8 and 64 characters long.");
						break;
					case "upper":
					case 1:
						alert("Password must have at least one uppercase character.");
						break;
					case "lower":
					case 2:
						alert("Password must have at least one lowercase character.");
						break;
					case "digit":
					case 3:
						alert("Password must have at least one digit.");
						break;
					case "special":
					case 4:
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

			// sends request to remove user from database
			async function deleteUser(username){
				// request
				const req = await fetch('../api/deleteUser.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						username: username
					})
				});

				// response
				const res = await req.json();
				const success = res.success;
				const perm = res.perm;
				// on successful delete remove user from the DOM
				if(success == "true"){
					const user = document.getElementById(username);
					user.remove();
					// reduce user count and update DOM if count is 0
					if(perm == "0"){
						attendantCount--;
						if(attendantCount == 0){
							const attendants = document.getElementById("attendants");
							attendants.innerHTML = "None, add more below.";
						}
					} else {
						adminCount--;
						if(adminCount == 0){
							const admins = document.getElementById("admins");
							admins.innerHTML = "None, add more below.";
						}
					}
				} else {
					alert("Failed to delete user.");
				}
			}

			// sends request to add user to the database
			async function createUser(type){
				// get the respective username and password
				let usernameBox, passwordBox;
				if(type == 0){
					usernameBox = document.getElementById("username");
					passwordBox = document.getElementById("password");
				} else {
					usernameBox = document.getElementById("adminUsername");
					passwordBox = document.getElementById("adminPassword");
				}
				let username = usernameBox.value.trim(), password = passwordBox.value.trim();

				// check fields are filled
				if(username.length < 1 || password.length < 1){
					alert("Please fill in both username and password fields.");
					return;
				}

				// check username length
				if(username.length > 64){
					alert("Username must be between 1 and 64 characters.")
					return;
				}

				// check characters
				for(let i = 0; i < username.length; i++){
					if(!isValidChar(username[i], userCharsAllowed)){
						alert("Username can only contain letters, numbers, and spaces.");
						return;
					}
				}

				// check password
				if(!checkSubmittedPassword(password)) return;


				// request
				const req = await fetch('../api/createUser.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						type: type,
						username: username,
						password: password
					})
				});

				// response
				const res = await req.json();
				const success = res.success;
				const reason = res.reason;

				if(success == "true"){
					// clear input boxes
					usernameBox.value = "";
					passwordBox.value = "";

					// I'm so sorry for this insanely long line, it's just the html of the new entry
					const newEntry = '<div id="' + username + '" class="userRow"><div style="display: inline-block; font-size:150%; font-weight: bold;">' + username + '</div><span style="display: inline-block; text-align: right; align-self: center;"><button onClick="deleteUser(\'' + username + '\')" style="float: right;">Delete</button></span></div>';
					if(type == 0){
						if(attendantCount == 0){ // add back in table
							const attendants = document.getElementById("attendants");
							attendants.innerHTML = '<div id="attendantTable" style="border: red; border-style:none none solid none; width:100%;"></div>';
						}
						// add new attendant entry
						const attendantTable = document.getElementById("attendantTable");
						attendantTable.innerHTML += newEntry;
						attendantCount++;
					} else {
						if(adminCount == 0){ // add back in table
							const admnins = document.getElementById("admnins");
							admins.innerHTML = '<div id="adminTable" style="border: red; border-style:none none solid none; width:100%;"></div>';
						}
						// add new admin entry
						const adminTable = document.getElementById("adminTable");
						adminTable.innerHTML += newEntry;
						adminCount++;
					}
				} else {
					failedResponse(res.reason, "Failed to add user.");
				}
			}

			// changes password for admins and attendants (big admin must use email password reset)
			async function changePassword(){
				let current = document.getElementById("current").value;
				let newp = document.getElementById("new").value;
				let retype = document.getElementById("retype").value;

				// check fields are filled
				if(current == "" || newp == "" || retype == ""){
					alert("Please fill in all the fields.");
					return;
				}

				// check new password
				if(!checkSubmittedPassword(newp)) return;

				// check match
				if(newp != retype) {
					failedResponse("match");
					return;
				}

				// request
				const req = await fetch('../api/changePassword.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						oldPassword: current,
						newPassword: newp,
						repeatPassword: retype
					})
				});

				// response
				const res = await req.json();
				const success = res.success;
				if(success == "true"){
					document.getElementById("current").value = "";
					document.getElementById("new").value = "";
					document.getElementById("retype").value = "";
					alert("Password change successful :)")
				} else {
					let output;
					failedResponse(res.reason, "Failed to update password.");
				}
			}

			// sends password reset email
			async function resetPassword(){
				// request
				const req = await fetch('../api/sendResetEmail.php', {
					method: 'POST'
				});

				// response
				const res = await req.json();
				const success = res.success;
				const reason = res.reason;
				
				if(success == "true"){
					alert("Password reset email sent successfully. (It may take up to 10 minutes to appear in your inbox)");
				} else {
					alert(reason);
				}
			}

			// uses checkPassword and says whether or not to cancel the request
			function checkSubmittedPassword(password){
				// [length, upper, lower, digit, special]
				const flags = checkPassword(password);

				for(let i = 0; i < flags.length; i++){
					if(!flags[i]) {
						failedResponse(i);
						return 0;
					}
				}

				// check characters
				for(let i = 0; i < password.length; i++){
					if(!isValidChar(password[i], passwordCharsAllowed)){
						failedResponse("character");
						return 0;
					}
				}

				return 1;
			}


			// checks the password
			function checkPassword(password){
				// length, upper, lower, digit, special, match
				let flags = [false, false, false, false, false];

				//check length of password
				if(password.length > 7 && password.length < 65){
					flags[0] = true;
				}

				// check num, uppercase, and lowercase.
				for(let i = 0; i < password.length; i++){
					// check if uppercase letter
					for(let j = 0; !flags[1] && j < alphabet.length; j++){
						if(password[i] == alphabet[j].toUpperCase()){
							flags[1] = true;
						}
					}
					//check if lowercase letter
					for(let j = 0; !flags[2] && j < alphabet.length; j++){
						if(password[i] == alphabet[j].toLowerCase()){
							flags[2] = true;
						}
					}
					//check if digit
					for(let j = 0; !flags[3] && j < digits.length; j++){
						if(password[i] == digits[j]){
							flags[3] = true;
						}
					}
					//check if special char
					for(let j = 0; !flags[4] && j < specialChars.length; j++){
						if(password[i] == specialChars[j]){
							flags[4] = true;
						}
					}
				}
				
				return flags;
			}

			// opens the navigation bar on the left
			function openNav(){
				document.getElementById("overlay").style.transition = "all .5s ease";
				document.getElementById("overlay").style.opacity = 1;
				document.getElementById("overlay").style.visibility = "visible";
				document.getElementById("sidebar").style.left = "0";
				document.getElementById("btn").style.left = "245px";
				document.getElementById("btn").style.opacity = 0;
				document.getElementById("btn").style.visibility = "hidden";
				document.getElementById("cancel").style.left = "245px";
				document.getElementById("cancel").style.opacity = 1;
				document.getElementById("cancel").style.visibility = "visible";
			}

			// closes the navigation bar
			function closeNav(){
				document.getElementById("overlay").style.transition = "all .5s ease";
				document.getElementById("overlay").style.opacity = 0;
				document.getElementById("overlay").style.visibility = "hidden";
				document.getElementById("sidebar").style.left = "-242px";
				document.getElementById("btn").style.left = 0;
				document.getElementById("btn").style.opacity = 1;
				document.getElementById("btn").style.visibility = "visible";
				document.getElementById("cancel").style.left = 0;
				document.getElementById("cancel").style.opacity = 0;
				document.getElementById("cancel").style.visibility = "hidden";
			}
		</script>
	</head>

	

	<!-- Body of page for database tables -->
	<body>
		<!-- Overlay for when side menu is open -->
		<div id="overlay" onclick="closeNav()"></div>

		<!-- Side Navbar -->
		<input type="checkbox" id="check"/>
		<label for="check">
			<i style="height:50px;" id="btn" onclick="openNav()"><image width="40px" src="../images/menu.png" class="vertcenter"/></i>
			<i style="height:50px;" id="cancel" onclick="closeNav()"><image width="30px" src="../images/xg.png" class="vertcenter"/></i>
		</label>
	
		<div class="sidebar" id="sidebar">
			<header>Menu</header>
			<?php
				if($perm < 2){
					echo '<a href="devices.php?type=PC"><span>PCs</span></a><a href="devices.php?type=Console">' . 
					'<span>Consoles</span></a><a href="devices.php?type=VR"><span>VR</span></a>' . 
					'<a href="inventory.php"><span>Inventory</span></a>';
				}
			?>
			<a href="#" class="active" onclick="return false;">
				<span>Account</span>
			</a>
			<a href="../api/logout.php" style="position:absolute;bottom: 0;">
				<span>Logout</span>
			</a>
		</div>

		<!-- Header with title and filters -->
		<div class="header" id='heading'>
			<span id="title" class="title vertcenter">Account</span>
		</div>

		<!-- Body -->
		<div class="flex bodydiv">
			<?php
				// for big admin only
				if($perm == 2){
					// list of admins
					echo '
					<div>
						<h1>Admins</h1>
							<div id="admins">';
							if($adminCount > 0){
								echo '<div id="adminTable" style="border: red; border-style:none none solid none; width:100%;">';
							
								foreach($admins as $admin){
									echo '
									<div id="' . $admin['username'] . '" class="userRow">
										<div style="display: inline-block; font-size:150%; font-weight: bold;">' .
											$admin['username']. '
										</div>
										<span style="display: inline-block; text-align: right; align-self: center;">
											<button onClick="deleteUser(\'' . $admin['username'] . '\')" style="float: right;">Delete</button>
										</span>
									</div>';
								}

								echo '</div>';
							} else {
								echo 'None, add more below.';
							}
							echo '</div>';
					
					// add new admin form

					echo '
					<h1>New Admin</h1>
					
					<input id="adminUsername" type="text" placeholder="Username" maxlength="64" name="username"/><br/><br/>
					<input id="adminPassword" type="password" placeholder="Password" maxlength="64" name="password"/><br/><br/>
					<!-- Just do it. submit button -->
					<button name="submit" onClick="createUser(1)">Create</button><br/><br/>
						<div>
							Password requirements:

							<br/><br/>
							<span id="passwordLengthAdmin" class="conditionNotMet">
								Between 8 and 64 characters long
							</span><br/>
							<span id="passwordUpperAdmin" class="conditionNotMet">
								At least one uppercase letter
							</span><br/>
							<span id="passwordLowerAdmin" class="conditionNotMet">
								At least one lowercase letter
							</span><br/>
							<span id="passwordNumsAdmin" class="conditionNotMet">
								At least one digit
							</span><br/>
							<span id="passwordCharsAdmin" class="conditionNotMet">
								At least one special character
							</span>
						</div>
					
					';

					echo '</div>';
				}

				// for normal admins
				if($perm > 0){
					// list of attendants
					echo '
					<div>
						<h1>Attendants</h1>
							<div id="attendants">';
							if($attendantCount > 0){
								echo '<div id="attendantTable" style="border: red; border-style:none none solid none; width:100%;">';
						
								foreach($attendants as $attendant){
									echo '
									<div id="' . $attendant['username'] . '" class="userRow">
										<div style="display: inline-block; font-size:150%; font-weight: bold;">' .
											$attendant['username']. '
										</div>
										<span style="display: inline-block; text-align: right; align-self: center;">
											<button onClick="deleteUser(\'' . $attendant['username'] . '\')" style="float: right;">Delete</button>
										</span>
									</div>';
								}

								echo '</div>';
							} else {
								echo 'None, add more below.';
							}

					echo '</div>';

					// add new attendant form

					echo '
					<h1>New Attendant</h1>
					
					<input id="username" type="text" placeholder="Username" maxlength="64" name="username"/><br/><br/>
					<input id="password" type="password" placeholder="Password" maxlength="64" name="password"/><br/><br/>
					<!-- Just do it. submit button -->
					<button name="submit" onClick="createUser(0)">Create</button><br/><br/>
						<div>
							Password requirements:

							<br/><br/>
							<span id="passwordLengthAttendant" class="conditionNotMet">
								Between 8 and 64 characters long
							</span><br/>
							<span id="passwordUpperAttendant" class="conditionNotMet">
								At least one uppercase letter
							</span><br/>
							<span id="passwordLowerAttendant" class="conditionNotMet">
								At least one lowercase letter
							</span><br/>
							<span id="passwordNumsAttendant" class="conditionNotMet">
								At least one digit
							</span><br/>
							<span id="passwordCharsAttendant" class="conditionNotMet">
								At least one special character
							</span>
						</div>
					
					';

					echo '</div>';
				}
			?>
				
				
			<div>
				<h1>Change password</h1>
			
				<?php

					// for not the big admin
					if($perm < 2){
						// password reset form
						echo '
						<input id="current" type="password" placeholder="Current Password" maxlength="64" name="current"/><br/><br/>
						<input id="new" type="password" placeholder="New Password" maxlength="64" name="new"/><br/><br/>
						<input id="retype" type="password" placeholder="Retype Password" maxlength="64" name="retype"/><br/><br/>
						<!-- Just do it. submit button -->
						<button onClick="changePassword()">Change</button><br/><br/>
						<div>
							Password requirements:

							<br/><br/>
							<span id="passwordLength" class="conditionNotMet">
								Between 8 and 64 characters long
							</span><br/>
							<span id="passwordUpper" class="conditionNotMet">
								At least one uppercase letter
							</span><br/>
							<span id="passwordLower" class="conditionNotMet">
								At least one lowercase letter
							</span><br/>
							<span id="passwordNums" class="conditionNotMet">
								At least one digit
							</span><br/>
							<span id="passwordChars" class="conditionNotMet">
								At least one special character
							</span><br/>
							<span id="passwordMatch" class="conditionNotMet">
								Passwords must match
							</span>
						</div>
					
						';
					} // for the big admin 
					else {
						// password reset email
						echo '<button onClick="resetPassword()">Send password reset email</button>';
					}
				?>
			</div>
		</div>
		

	</body>

	<?php
	// not for big admin
	if($perm < 2){
		// password checking code
		echo "
			<script>
				const newPassword = document.getElementById('new');
				const repeatPassword = document.getElementById('retype');

				const passwordLength = document.getElementById('passwordLength');
				const passwordUpper = document.getElementById('passwordUpper');
				const passwordLower = document.getElementById('passwordLower');
				const passwordNums = document.getElementById('passwordNums');
				const passwordChars = document.getElementById('passwordChars');
				const passwordMatch = document.getElementById('passwordMatch');

				newPassword.addEventListener('input',
					()=>{
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

						
						// length, upper, lower, digit, special
						const flags = checkPassword(passwordInput);

						//check length of password
						if(flags[0]){
							passwordLength.className = 'conditionMet';
						}
						else{
							passwordLength.className = 'conditionNotMet';
						}
						

						//update conditions met
						if(flags[4]){
							passwordChars.className = 'conditionMet';
						}
						else{
							passwordChars.className = 'conditionNotMet';
						}
						if(flags[3]){
							passwordNums.className = 'conditionMet';
						}
						else{
							passwordNums.className = 'conditionNotMet';
						}
						if(flags[1]){
							passwordUpper.className = 'conditionMet';
						}
						else{
							passwordUpper.className = 'conditionNotMet';
						}
						if(flags[2]){
							passwordLower.className = 'conditionMet';
						}
						else{
							passwordLower.className = 'conditionNotMet';
						}
						if(passwordInput.length > 0 && passwordInput == confirmPasswordInput){
							passwordMatch.className = 'conditionMet';
						}
						else{
							passwordMatch.className = 'conditionNotMet';
						}
					}
				);

				repeatPassword.addEventListener('input', () => {
					var passwordInput = newPassword.value;
					var confirmPasswordInput = repeatPassword.value;
					if(passwordInput.length > 0 && passwordInput == confirmPasswordInput){
						passwordMatch.className = 'conditionMet';
					}
					else{
						passwordMatch.className = 'conditionNotMet';
					}
				});



			</script>
		";
	}
	// only for big admin
	if($perm == 2){
		// password checking code
		echo "
			<script>
				const adminPassword = document.getElementById('adminPassword');

				const passwordLengthAdmin = document.getElementById('passwordLengthAdmin');
				const passwordUpperAdmin = document.getElementById('passwordUpperAdmin');
				const passwordLowerAdmin = document.getElementById('passwordLowerAdmin');
				const passwordNumsAdmin = document.getElementById('passwordNumsAdmin');
				const passwordCharsAdmin = document.getElementById('passwordCharsAdmin');

				// when password textboxes are changed check validity
				adminPassword.addEventListener('input',
					()=>{
						var passwordInput = adminPassword.value;
						if(passwordInput.length < 1){
							passwordLengthAdmin.className = 'conditionNotMet';
							passwordUpperAdmin.className = 'conditionNotMet';
							passwordLowerAdmin.className = 'conditionNotMet';
							passwordNumsAdmin.className = 'conditionNotMet';
							passwordCharsAdmin.className = 'conditionNotMet';
							return;
						}

						
						// length, upper, lower, digit, special
						const flags = checkPassword(passwordInput);

						//check length of password
						if(flags[0]){
							passwordLengthAdmin.className = 'conditionMet';
						}
						else{
							passwordLengthAdmin.className = 'conditionNotMet';
						}

						//update conditions met
						if(flags[4]){
							passwordCharsAdmin.className = 'conditionMet';
						}
						else{
							passwordCharsAdmin.className = 'conditionNotMet';
						}
						if(flags[3]){
							passwordNumsAdmin.className = 'conditionMet';
						}
						else{
							passwordNumsAdmin.className = 'conditionNotMet';
						}
						if(flags[1]){
							passwordUpperAdmin.className = 'conditionMet';
						}
						else{
							passwordUpperAdmin.className = 'conditionNotMet';
						}
						if(flags[2]){
							passwordLowerAdmin.className = 'conditionMet';
						}
						else{
							passwordLowerAdmin.className = 'conditionNotMet';
						}
					}
				);
			</script>
		";
	}
	// only for admins
	if($perm > 0){
		// password checking code
		echo "
			<script>
				const attendantPassword = document.getElementById('password');

				const passwordLengthAttendant = document.getElementById('passwordLengthAttendant');
				const passwordUpperAttendant = document.getElementById('passwordUpperAttendant');
				const passwordLowerAttendant = document.getElementById('passwordLowerAttendant');
				const passwordNumsAttendant = document.getElementById('passwordNumsAttendant');
				const passwordCharsAttendant = document.getElementById('passwordCharsAttendant');

				// when password textboxes are changed check validity
				attendantPassword.addEventListener('input',
					()=>{
						var passwordInput = attendantPassword.value;
						if(passwordInput.length < 1){
							passwordLengthAttendant.className = 'conditionNotMet';
							passwordUpperAttendant.className = 'conditionNotMet';
							passwordLowerAttendant.className = 'conditionNotMet';
							passwordNumsAttendant.className = 'conditionNotMet';
							passwordCharsAttendant.className = 'conditionNotMet';
							return;
						}
						
						// length, upper, lower, digit, special
						const flags = checkPassword(passwordInput);

						//check length of password
						if(flags[0]){
							passwordLengthAttendant.className = 'conditionMet';
						}
						else{
							passwordLengthAttendant.className = 'conditionNotMet';
						}


						//update conditions met
						if(flags[4]){
							passwordCharsAttendant.className = 'conditionMet';
						}
						else{
							passwordCharsAttendant.className = 'conditionNotMet';
						}
						if(flags[3]){
							passwordNumsAttendant.className = 'conditionMet';
						}
						else{
							passwordNumsAttendant.className = 'conditionNotMet';
						}
						if(flags[1]){
							passwordUpperAttendant.className = 'conditionMet';
						}
						else{
							passwordUpperAttendant.className = 'conditionNotMet';
						}
						if(flags[2]){
							passwordLowerAttendant.className = 'conditionMet';
						}
						else{
							passwordLowerAttendant.className = 'conditionNotMet';
						}
					}
				);
			</script>
		";
	}
	?>
</html>
