<!-- PHP to verify the user is logged in -->

<?php
	session_start();

	// if user isn't logged in redirect to logout (kills session and goes to login page)
	if(!isset($_SESSION["loggedin"])){
		header("Location: ../api/logout.php");
		exit;
	}

	// only lower perm users
	if($_SESSION["perm"] > 1){
		header("Location: account.php");
		exit;
	}

	include '../api/populateInventoryTable.php';
	// connect to db
	include "../api/database.php";
	if(mysqli_connect_errno()) {
		$response = ['success'=>'false', 'reason'=>'Unable to connect to database. Contact system admin if problem persists.'];
		http_response_code(500);
		die(json_encode($response));
	}

	// get inventory
	$result = mysqli_query($connection, "SELECT * FROM Inventory");
	$inventory = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>


<!DOCTYPE html>
<html>
	<head>
		<!-- Title -->
		<title>Esports Inventory Manager</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<link rel="stylesheet" href="../styles/main.css">
		<link rel="stylesheet" href="../styles/contentPages.css">
		<link rel="stylesheet" href="../styles/sidebar.css">


		<script>
			// user perm level
			const perm = <?php echo $_SESSION["perm"];?>;


			// sorting functions
			// checks if the rows should be switched
			function shouldSwitch(column, reverse, v1, v2){
				v1 = v1.innerHTML;
				v2 = v2.innerHTML;

				if(!reverse){
					if(v1 > v2){
						return true;
					} else {
						return false;
					}
				}
				
				if(v1 < v2){
					return true;
				} else {
					return false;
				}
			}


			// sorts the table based on column 0 or 1 and if it should be reversed
			function sortTable(){
				let column;
				let reverse;
				switch(document.getElementById("sort").value){
					case "nameaz":
						column = 0;
						reverse = false;
						break;
					case "nameza":
						column = 0;
						reverse = true;
						break;
					case "quantityhl":
						column = 1;
						reverse = true;
						break;
					case "quantitylh":
						column = 1;
						reverse = false;
						break;
				}

				let table, rows, switching, i, r1, r2, shouldSw;
				table = document.getElementById("table");
				switching = true;

				while(switching){
					switching = false;
					rows = table.rows;
					// loop through rows and check if there are any that should be swapped
					for(i = 1; i < rows.length - 1; i++){
						shouldSw = false;

						r1 = rows[i].getElementsByTagName("TD")[column];
						r2 = rows[i + 1].getElementsByTagName("TD")[column];

						// check if switch
						shouldSw = shouldSwitch(column, reverse, r1, r2)
						if(shouldSw){
							break;
						}

					}
					// perform switch
					if(shouldSw){
						rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
						switching = true;
					}
				}
				// apply colors
				applyTableColors();
			}


			// closes any potentially open overlays
			function closeOverlays(){
				closeNav();
				closeModal();
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


			// submits a new item to the inventory database
			async function submitNewItem(){
				// get inputs
				let name = document.getElementById("newItemName").value;
				let quantity = document.getElementById("newItemQuantity").value;

				// check inputs
				if(name.length < 2){
					alert("Please use a longer item name.");
					return;
				}
				if(quantity.length < 0 || quantity < 0){
					alert("Please enter a positive quantity.");
					return;
				}


				// request
				let req = await fetch('../api/newInventory.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						name: name,
						quantity: quantity
					})
				});

				// response 
				const res = await req.json();
				const success = res.success;

				if(success == "true"){
					// create table row
					let output = '<tr id="' + name + '"><td>' + name + '</td><td id="' + name + 
					'Quantity">' + quantity + '</td><td><div class="functions"><span><input ' + 
					'type="number" min="0" style="width: 100px;" value="' + quantity + 
					'" id="update' + name + '"/><button onclick="updateQuantity(\'' + name + 
					'\')">&nbsp;Update Quantity&nbsp;</button></span>';
					if(perm == 1){ // only admin can delete
                        output += '<button onclick="deleteItem(\'' + name + '\')">&nbsp;Delete&nbsp;</button>';
                    }
					output += '</div></td></tr>'
					document.getElementById("table").innerHTML += output;

					// alery success and clear inputs
					alert(quantity + " " + name + " added to inventory.");
					document.getElementById("newItemName").value = "";
					document.getElementById("newItemQuantity").value = "";

					// sort table and close modal
					sortTable();
					closeModal();
				} else { // alert failure
					alert("Failed to create item.\nReason: " + res.reason);
				}
			}


			// updates the quantity of an item in inventory
			async function updateQuantity(name){
				// check input quantity
				const newQuantity = document.getElementById("update" + name).value;
				if(newQuantity.length < 0 || newQuantity < 0){
					alert("Please enter a positive quantity.");
					return;
				}

				// request
				let req = await fetch('../api/updateInventory.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						name: name,
						quantity: newQuantity 
					})
				});

				// response 
				const res = await req.json();
				const success = res.success;

				if(success == "true"){ // update quantity and sort
					document.getElementById(name + "Quantity").innerHTML = newQuantity;
					sortTable();
					alert("Quantity updated.")
				} else { // alert failure
					alert("Failed to update quantity.\nReason: " + res.reason);
				}
			}


			// deletes item from inventory
			async function deleteItem(name){
				// request
				let req = await fetch('../api/deleteInventory.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						name: name
					})
				});

				// response 
				const res = await req.json();
				const success = res.success;

				// remove element from DOM, apply colors, and alert success
				if(success == "true"){ 
					document.getElementById(name).remove();
					applyTableColors();
					alert("Item deleted.")
				} else { // alert failure
					alert("Failed to delete item.\nReason: " + res.reason);
				}
			}


			// opens the modal
			function openModal(){
				document.getElementById("modal").style.transition = "all .5s ease";
				document.getElementById("modal").style.opacity = 1;
				document.getElementById("modal").style.visibility = "visible";
				document.getElementById("overlay").style.transition = "all .5s ease";
				document.getElementById("overlay").style.opacity = 1;
				document.getElementById("overlay").style.visibility = "visible";
			}


			// closes the modal
			function closeModal(){
				document.getElementById("modal").style.transition = "0s";
				document.getElementById("modal").style.opacity = 0;
				document.getElementById("modal").style.visibility = "hidden";
				document.getElementById("overlay").style.transition = "0s";
				document.getElementById("overlay").style.opacity = 0;
				document.getElementById("overlay").style.visibility = "hidden";
			}


			// applies colors to visible rows
			function applyTableColors(){
				let rows = document.getElementById("table").rows;
				let j = 0;
				for(let i = 1; i < rows.length; i++){
					if(rows[i].style.display != "none"){
						j++;
						if(j % 2 == 0){
							rows[i].style.backgroundColor = "black";
						} else {
							rows[i].style.backgroundColor = "#c8102e";
						}
					}
				}
			}
			
		</script>
	</head>


	

	<body onload="sortTable(0, false);applyTableColors();">

		<!-- Overlay for dimming the background -->
		<div id="overlay" onclick="closeOverlays()"></div>


		<!-- Side Navbar -->
		<input type="checkbox" id="check"/>
		<label for="check">
			<i style="height:50px;" id="btn" onclick="openNav()"><image width="40px" src="../images/menu.png" class="vertcenter"/></i>
			<i style="height:50px;" id="cancel" onclick="closeNav()"><image width="30px" src="../images/xg.png" class="vertcenter"/></i>
		</label>
		<div class="sidebar" id="sidebar">
			<header>Menu</header>
			<a href="devices.php?type=PC">
				<span>PCs</span>
			</a>
			<a href="devices.php?type=Console">
				<span>Consoles</span>
			</a>
			<a href="devices.php?type=VR">
				<span>VR</span>
			</a>
			<a href="#" class="active" onclick="return false;">
				<span>Inventory</span>
			</a>
			<a href="account.php">
				<span>Account</span>
			</a>
			<a href="../api/logout.php" style="position:absolute;bottom: 0;">
				<span>Logout</span>
			</a>
		</div>


		<!-- Header with title and sort -->
		<div class="header" id='heading'>
			<span class="filters vertcenter">
				<span class="filtershover"></span>
				<span class="vertcenter">
					Sort&nbsp;
					<i class="arrow down vertcenter"></i>
				</span>
				<span class="dropdown">
					<div>
						<label for="sort">Sort By: </label>
						<select name="sort" id="sort">
							<option value="nameaz">Name: A-Z</option>
							<option value="nameza">Name: Z-A</option>
							<option value="quantityhl">Quantity: High to Low</option>
							<option value="quantitylh">Quantity: Low to High</option>
						</select>
					</div>
					<div style="margin-top: 8px;">
						<button onclick="sortTable()">&nbsp;Apply Sort&nbsp;</button>
					</div>
				</span>
			</span>
			<span id="title" class="title vertcenter">Inventory</span>
			<span class="vertcenter" style="right: 0;"><button onclick="openModal()">&nbsp;New Item&nbsp;</button></span>
		</div>


		<!-- Main body of the page -->
		<div class="bodydiv">
			<table id="table">
				<?php
					populateInventoryTable($inventory);
				?>
				
			</table>
		</div>


		<!-- modal -->
		<div class="modal" id="modal">
			<div id="newItem">
				<div style="position: fixed; width: 90%; height: 40px;text-align:center;">
					<img class="back vertcenter" src="../images/xb.png" onclick="closeModal()"/>
					<span class="title" style="font-size:35px;">New Item</span>
					<button class="vertcenter" style="right:-30px;position: absolute;" onclick="submitNewItem()">&nbsp;Create Item&nbsp;</button>
				</div>
				<div style="top:35px;" class="bodydiv">
					<span>Item name: </span>
					<input id="newItemName" type="text" maxlength="64" placeholder="Enter name"/>
					<span id="nameLimit" style="color:white;">64</span></br></br>
					
					<span>Quantity: </span>
					<input style="width:173px;" id="newItemQuantity" type="number" min="0" max="1000" placeholder="Enter quantity"/></br></br>
				</div>
			</div>
		</div>


		<script>
			// input length event listener
			document.getElementById("newItemName").addEventListener("input", ({currentTarget: target}) => {
				const maxLen = target.getAttribute("maxLength");
				curLen = target.value.length;

				document.getElementById("nameLimit").innerHTML = (maxLen-curLen);
			});
			
		</script>
	</body>
</html>