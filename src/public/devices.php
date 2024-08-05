<?php
	session_start();

	// if user isn't logged in redirect to logout (kills session and goes to login page)
	if(!isset($_SESSION["loggedin"])){
		header("Location: ../api/logout.php");
		exit;
	}

	// redirect if main admin is logged in
	$perm = $_SESSION["perm"];
	if($perm > 1){
		header("Location: account.php");
		exit;
	}

	include '../api/populateDeviceTable.php';
	// connect to db
	include "../api/database.php";
	if(mysqli_connect_errno()) {
		$response = ['success'=>'false', 'reason'=>'Unable to connect to database. Contact system admin if problem persists.'];
		http_response_code(500);
		die(json_encode($response));
	}

	// default type is PC
	if(!isset($_GET["type"])){
		$_GET["type"] = "PC";
	}

	// check device is valid
	$device = strtoupper($_GET["type"]);
	if($device != "PC" && $device != "CONSOLE" && $device != "VR"){
		header("Location: ../api/logout.php");
		exit;
	}

	// casing
	if($device == "CONSOLE"){
		$device = "Console";
	}

	
	// check which device page is being viewed and change sidebar html 
	$sidePC = 'href="devices.php?type=PC"';
	$sideConsole = 'href="devices.php?type=Console"';
	$sideVR = 'href="devices.php?type=VR"';
	if($device == "PC"){
		$sidePC = 'href="#" class="active" onclick="return false;"';
	} else if($device == "VR"){
		$sideVR = 'href="#" class="active" onclick="return false;"';
	} else {
		$sideConsole = 'href="#" class="active" onclick="return false;"';
	}


	// get devices and device types
	$result = mysqli_query($connection, "SELECT * FROM $device INNER JOIN Device ON $device.id = Device.id");
	$devices = mysqli_fetch_all($result, MYSQLI_ASSOC);

	$result = mysqli_query($connection, "SELECT * FROM " . $device . "Types");
	$types = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html>
	<head>
		<!-- Title -->
		<title>Esports <?php echo $device;?> Manager</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		
		<link rel="stylesheet" href="../styles/main.css">
		<link rel="stylesheet" href="../styles/contentPages.css">
		<link rel="stylesheet" href="../styles/sidebar.css">

		<script>
			// device types;
			let types = [<?php
				$i = 0;
				foreach($types as $type){
					$i++;
					echo "'" . $type["type"] . "'";
					if($i != count($types)){
						echo ", ";
					}
				}
			?>];
			// permission level
			const perm = <?php echo $perm;?>;
			// device type
			const device = <?php echo '"' . $device . '"';?>;

			// current archived status
			let showArchived = false;

			// updates and device for the current ticket being viewed
			let updates;
			let deviceID;
			// ticket for updates being viewed
			let ticketID;


			// sorting functions

			// gets numeric value based on text in column 2 for sorting
			function statusValue(msg){
				switch(msg.toLowerCase()){
					case "fully functional":
						return 4;
					case "mostly functional":
						return 3;
					case "repair - esports":
						return 2;
					case "repair - doit":
						return 1;
					
				}
				return 0;
			}


			// checks if the rows should be switched
			function shouldSwitch(column, reverse, v1, v2){
				v1 = v1.innerHTML;
				v2 = v2.innerHTML;
				if(column == 2){
					v1 = statusValue(v1);
					v2 = statusValue(v2);
				}

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


			// sorts the table based on column 0, 1, or 2 and if it should be reversed
			function sortTable(column, reverse){
				let table, rows, switching, i, r1, r2, shouldSw;
				table = document.getElementById("table");
				switching = true;

				while(switching){
					switching = false;
					rows = table.rows;
					// loop through rows looking for row that needs to be switched
					for(i = 1; i < rows.length - 1; i++){
						shouldSw = false;

						// get rows
						r1 = rows[i].getElementsByTagName("TD")[column];
						r2 = rows[i + 1].getElementsByTagName("TD")[column];

						// check if rows should be switched
						shouldSw = shouldSwitch(column, reverse, r1, r2)
						if(shouldSw){
							break;
						}
					}
					// perform the switch
					if(shouldSw){
						rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
						switching = true;
					}
				}
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


			// formats date from sql date 
			function formatDate(date){
				const hour = parseInt(date.substring(11, 13));
				const month = parseInt(date.substring(5, 7));
				const day = parseInt(date.substring(8, 10));
				const minutes = date.substring(14, 16);
				let newDate = month + "/" + day + "/" + date.substring(2, 4) + " ";

				if(hour > 12){
					newDate += (hour - 12) + ":" + minutes + " P.M.";
				} else {
					newDate += hour + ":" + minutes + " A.M.";
				}

				return newDate;
			}

			// gets color code and message for a device status
			function getColorStatus(status){
				let color, statusMsg;
				switch(status){
					case 0:
						color = "#00ff00";
						statusMsg = "Fully Functional";
						break;
					case 1:
						color = "#7fff00";
						statusMsg = "Mostly Functional";
						break;
					case 2:
						color = "#ffff00";
						statusMsg = "Repair - Esports";
						break;
					case 3:
						color = "#ff7f00";
						statusMsg = "Repair - DoIT";
						break;
					case 4:
						color = "#ff0000";
						statusMsg = "Broken";
						break;
				}
				return [color, statusMsg];
			}


			// opens the tickets modal
			async function openTickets(id){
				// request
				const req = await fetch('../api/getTickets.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						device: id
					})
				});

				// response
				const res = await req.json();
				const success = res.success;
				updates = res.updates;
				deviceID = id;

				if(success == "true"){
					// create tickets table 
					const table = document.getElementById("ticketsTable");
					document.getElementById("ticketsDevice").innerHTML = "Tickets " + id;
					if(updates.length > 0){
						// table header
						let tableContents = "<thead><tr style='background-color: black;'><th>Created" +
						"</th><th>Updated</th><th>Status</th><th>Primary Issue</th><th>Functions</th></tr></thead><tbody>";
						let prevTicket;
						let status;
						let msg;
						let initialUpdate;
						let latestUpdate;
						let i = 0;

						// loop through updates
						for(const update of updates){
							const ticket = update[1];
							// if this ticket is different from the previous ticket then use
							// data from the last update to create the row
							if(ticket != prevTicket){
								if(prevTicket != null){
									const colorStatus = getColorStatus(status);
									const color = colorStatus[0], statusMsg = colorStatus[1];

									tableContents += '<tr><td>' + initialUpdate + '</td><td>' + 
									latestUpdate + '</td><td style="background-color: ' + color +
									'; color: black;">' + statusMsg + '</td><td>' + msg + '</td>' +
									'<td><div><button onclick="openUpdates(' + prevTicket + 
									')">&nbsp;Updates&nbsp;</button></div></td></tr>';
								}
								initialUpdate = formatDate(update[6]);
							}
							// set variables to current update
							status = update[4];
							msg = update[2];
							latestUpdate = formatDate(update[6]);
							prevTicket = ticket;

							// if the date of the last update is the same 
							// as the first update then show a -
							if(latestUpdate == initialUpdate){
								latestUpdate = "-";
							}
							
							// if it's the last update then create the final row
							if(++i == updates.length){
								const colorStatus = getColorStatus(status);
								const color = colorStatus[0], statusMsg = colorStatus[1];

								tableContents += '<tr><td>' + initialUpdate + '</td><td>' + 
								latestUpdate + '</td><td style="background-color: ' + color +
								'; color: black;">' + statusMsg + '</td><td>' + msg + '</td>' +
								'<td><div><button onclick="openUpdates(' + ticket + 
								')">&nbsp;Updates&nbsp;</button></div></td></tr>';
							}
						};
						// show tickets table
						table.style.display = "";
						document.getElementById("noTickets").style.display = "none";
						table.innerHTML = tableContents + "</tbody>";
					} else { // if no tickets then show the no tickets display
						document.getElementById("noTickets").style.display = "";
						table.style.display = "none";
					}
					// clear the modal, show the tickets display, and open the modal
					clearModal();
					document.getElementById("tickets").style.display = "";
					openModal();
				}
			}


			// opens the updates of a ticket on the modal
			function openUpdates(id){
				ticketID = id;
				const table = document.getElementById("updatesTable");
				document.getElementById("updatesDevice").innerHTML = "Tickets " + deviceID;

				// table header
				let tableContents = "<thead><tr style='background-color: black;'><th>Time</th><th>Status</th><th>Summary</th>" +
				"<th>Details</th><th>User</th></tr></thead><tbody>";

				let i = 0;
				// loop through updates
				for(const update of updates) {
					// if the update belongs to the ticket then create a row
					if(update[1] == id){
						// get color and status message
						const colorStatus = getColorStatus(update[4]);
						const color = colorStatus[0], statusMsg = colorStatus[1];

						// if details are long then add a button to view separately
						let details = update[3];
						if(details.length > 48){
							details = "<button onclick='openDetails(" + i + ")'>&nbsp;Details&nbsp;</button>";
						}

						// add row
						tableContents += '<tr><td>' + formatDate(update[6]) + '</td>' + 
						'<td style="background-color: ' + color + '; color: black;">'
						+ statusMsg + '</td><td>' + update[2] + '</td><td>' + details +
						'</td><td>' + update[5] + '</td></tr>';
					}
					++i;
				};
				table.innerHTML = tableContents + "</tbody>";
				
				// clear the modal and show the updates view
				clearModal();
				document.getElementById("updates").style.display = "";
			}


			// opens the details for an update
			function openDetails(i){
				clearModal();
				document.getElementById("details").style.display = "";
				document.getElementById("detailsTxt").innerHTML = updates[i][3];
			}


			// submits an update or ticket for a device
			async function submitNewUpdate(isTicket){
				// get input data
				const updateStatus = document.getElementById("newUpdateStatus").value;
				const updateSummary = document.getElementById("newUpdateSummary").value.trim();
				const details = document.getElementById("newUpdateDetails").value.trim();

				// check summary length
				if(updateSummary.length < 5){
					alert("Please write a longer summary.");
					return;
				}

				// if it's an update to a ticket then ticket is the current ticket ID
				let ticket;
				if(!isTicket){
					ticket = ticketID;
				} else { // if it's a ticket then there's no ticket ID, set false
					ticket = false;
				}

				// request
				let req = await fetch('../api/newUpdate.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						ticket: ticket,
						id: deviceID,
						status: updateStatus,
						summary: updateSummary,
						details: details
					})
				});

				// response 
				const res = await req.json();
				const success = res.success;
				
				if(success == "true"){
					// reset inputs
					document.getElementById("newUpdateStatus").value = 1;
					document.getElementById("newUpdateSummary").value = "";
					document.getElementById("newUpdateDetails").value = "";

					let highestStatus = parseInt(updateStatus);
					let highestSummary = updateSummary;
					let prevTicket;
					let status;
					let summary;
					let i = 0;

					// loop through updates and find the highest status and summary
					for(const update of updates) {
						const ticket = update[1];
						if(ticket != prevTicket && prevTicket != null && status > highestStatus && prevTicket != ticketID) {
							highestStatus = status;
							highestSummary = summary;
						}

						status = update[4];
						prevTicket = ticket;
						summary = update[2];
						
						if(++i == updates.length && status > highestStatus && prevTicket != ticketID) {
							highestStatus = status;
							highestSummary = summary;
						}

					};

					// resort and apply any new message
					const colorStatus = getColorStatus(highestStatus);
					const color = colorStatus[0], statusMsg = colorStatus[1];

					// loop through the rows and update the status of the device being updated
					let rows = document.getElementById("table").rows;
					for(i = 1; i < rows.length; i++){
						row = rows[i].getElementsByTagName("TD");
						const id = row[0].innerHTML;
						// check matching device ID
						if (id == deviceID){
							// update the row
							row[2].innerHTML = statusMsg;
							row[2].style.backgroundColor = color;
							if(highestStatus != 0){
								row[3].innerHTML = highestSummary;
							} else {
								row[3].innerHTML = "none";
							}
						}
					}
					
					// apply filters and sort
					applyFilters();

					// close the modal
					closeModal();

					// alert that it was a success
					if(isTicket){
						alert("Ticket added successfully!");
					} else {
						alert("Update added successfully!");
					}
				} else {
					// alert the failure
					if(isTicket){
						alert("Failed to create ticket.\nReason: " + res.reason);
					} else {
						alert("Failed to create update.\nReason: " + res.reason);
					}
					
				}
			}


			// checks if the type is different from an existing type
			function isDifferentDeviceType(newDeviceType){
				for(const type of types){
					if(type.toUpperCase().replace(" ", "") == newDeviceType.replace(" ", "")){
						return false;
					}
				}
				return true;
			}


			// submits a new device
			async function submitNewDevice(){
				// get input data
				let newDeviceType = document.getElementById("createNewDeviceType").value.toUpperCase().trim();
				let newdeviceID = document.getElementById("newdeviceID").value.trim();
				let differentDeviceType = isDifferentDeviceType(newDeviceType);

				// check input ID length
				if(newdeviceID.length != 6 && device.toLowerCase() != "console"){
					alert("The ID should be 6 digits long!");
					return;
				} else if (newdeviceID.length != 3 && device.toLowerCase() == "console") {
					alert("The ID should be 3 digits long!");
					return;
				}

				// check what device type and if it's a new type
				if(newDeviceType.length == 0){
					newDeviceType = document.getElementById("newDeviceType").value;
					differentDeviceType = false;
				} else if (!differentDeviceType){
					alert("That " + device + " type already exists!");
					document.getElementById("createNewDeviceType").value = "";
					return;
				} else if (!confirm("Are you sure you want to create a new " + device + " type \"" + newDeviceType + "\"?")){
					return;
				}

				// request
				let req = await fetch('../api/newDevice.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						type: newDeviceType,
						id: newdeviceID,
						deviceType: device
					})
				});

				// response 
				const res = await req.json();
				const success = res.success;

				if(success == "true"){
					// build the new device row
					let output = '<tr id="' + newdeviceID + '"><td>' + newdeviceID + '</td><td>' + 
					newDeviceType + '</td><td style="background-color: #00ff00; color: black;">' + 
					'Fully Functional</td><td style="width:auto;white-space:wrap;">none</td><td><div class="functions">' +
                    '<button onclick="openTickets(' + newdeviceID + ')">&nbsp;Tickets&nbsp;</button>';
					if(perm == 1){ // if admin acct add archive and delete buttons
						output += '<button id="archiveButton' + newdeviceID + '" onclick="archiveDevice(' + 
						newdeviceID + ', 1)">&nbsp;Archive&nbsp;</button><button ' +
						'onclick="deleteDevice(' + newdeviceID + ')">&nbsp;Delete&nbsp;</button>';
					}
					output += '</div></td><td style="display:none;">0</td></tr>';
					// add the row
					document.getElementById("table").innerHTML += output;

					// if type is new add to types array and to filters options
					if(differentDeviceType){
						types.push(newDeviceType);
						document.getElementById("deviceTypeFilters").innerHTML += '<span id="filter' + newDeviceType.toUpperCase().replace(" ", "") +
							'"><input type="checkbox" id="' + newDeviceType.toUpperCase().replace(" ", "") + '"/> <label for="' + 
							newDeviceType.toUpperCase().replace(" ", "") + '">' + newDeviceType + '</label><br/></span>';
					}

					// clear inputs, close modal, alert success, and apply filters
					document.getElementById("newdeviceID").value = "";
					document.getElementById("createNewDeviceType").value = "";
					closeModal();
					alert(device + " added successfully!");
					applyFilters();
				} else { // alert failure
					alert("Failed to create " + device + ".\nReason: " + res.reason);
				}
			}

			
			// updates a device's archive status
			async function archiveDevice(id, archive) {
				// request
				let req = await fetch('../api/archiveDevice.php', {
					method: 'POST',
					headers: {},
					body: JSON.stringify({
						id: id,
						archive: archive
					})
				});

				// response 
				const res = await req.json();
				const success = res.success;
				
				if(success == "true"){
					// set archive button to the opposite of the current archive status
					let opposite;
					let archiveButton = document.getElementById("archiveButton" + id);
					if(parseInt(archive) == 1){
						opposite = 0;
						archiveButton.innerHTML = "&nbsp;Unarchive&nbsp;";
					} else {
						opposite = 1;
						archiveButton.innerHTML = "&nbsp;Archive&nbsp;";
					}
					document.getElementById(id).getElementsByTagName("TD")[5].innerHTML = archive;
					archiveButton.setAttribute("onclick", "archiveDevice(" + id + "," + opposite + ")");
					applyFilters();
				} else { // alert failure
					alert("Failed to archive " + device + ".\nReason: " + res.reason);
				}
			}
			

			// delete device with ID
			async function deleteDevice(id) {
				// confirm deletion
				if(confirm("Are you SURE you want to delete this " + device + "?\n" +
				"(This will delete all associated information such as tickets and updates! " + 
				"This action cannot be undone.)")){
					// request
					let req = await fetch('../api/deleteDevice.php', {
						method: 'POST',
						headers: {},
						body: JSON.stringify({
							id: id
						})
					});

					// response 
					const res = await req.json();
					const success = res.success;
					
					if(success == "true"){
						// get the deleted device and type
						const delDevice = document.getElementById(id);
						const type = delDevice.getElementsByTagName("TD")[1].innerHTML;

						// alert and remove device from DOM
						alert(device + " " + id + " deleted.");
						delDevice.remove();

						// apply colors (no need to filter)
						applyTableColors();

						// check if last device of type and ask if user would like to remove type
						if(isLastDeviceType(type) && confirm("That was the last " + device + " of type \"" + type + "\".\nWould you like to delete the type?")){
							// request
							let req = await fetch('../api/deleteDeviceType.php', {
								method: 'POST',
								headers: {},
								body: JSON.stringify({
									type: type,
									deviceType: device
								})
							});

							// response
							const res = await req.json();
							const success = res.success;

							if(success == "true"){
								// delete type from array and filters
								types.splice(types.indexOf(type), 1);
								document.getElementById("filter" + type.toUpperCase().replace(" ", "")).remove();
								alert("Type \"" + type + "\" has been removed from the database.");
							} else { // alert failure
								alert("Unable to delete type \"" + type + "\".\nReason: " + res.reason);
							}
						}
					} else { // alert failure
						alert("Unable to delete " + device + " " + id + ".\nReason: " + res.reason);
					}
				}
			}

			// deletes all of a device of a type and the type
			async function deleteDeviceType(){
				// confirm twice because this could be a big deal and potential mistake if not intentional
				if(confirm("This will delete EVERY update, ticket, and " + device + " with this type!!! Are you SURE you want to delete this type?") &&
				confirm("SERIOUSLY! This CANNOT be undone! Are you SURE you want to do this?")){
					// get the type to be deleted
					const type = document.getElementById("newDeviceType").value;
					// request
					let req = await fetch('../api/deleteDeviceType.php', {
						method: 'POST',
						headers: {},
						body: JSON.stringify({
							type: type,
							deviceType: device
						})
					});

					// response
					const res = await req.json();
					const success = res.success;

					if(success == "true"){
						// remove type from type array and filters
						types.splice(types.indexOf(type), 1);
						document.getElementById("filter" + type.toUpperCase().replace(" ", "")).remove();
						
						// loop through rows and remove anything with matching type
						let rows = document.getElementById("table").rows;
						for(let i = rows.length - 1; i > 0; i--){
							row = rows[i].getElementsByTagName("TD");
							if(row[1].innerHTML == type){
								document.getElementById(row[0].innerHTML).remove();
							}
						}
						
						// apply colors
						applyTableColors();
						closeModal();

						// alert success
						alert("Type \"" + type + "\" has been removed from the database.");
					} else { // alert failure
						alert("Unable to delete type \"" + type + "\".\nReason: " + res.reason);
					}
				}
			}


			// checks if there are any rows left of a given type
			function isLastDeviceType(type){
				let rows = document.getElementById("table").rows;
				for(i = 1; i < rows.length; i++){
					row = rows[i].getElementsByTagName("TD");
					if(type == row[1].innerHTML){
						return false;
					}
				}
				return true;
			}

			
			// toggles whether or not the page is showing archived devices
			// just toggles the var and certain DOM elements
			function toggleArchived(){
				if(showArchived){
					showArchived = false;
					document.getElementById("title").innerHTML = device + "s";
					document.getElementById("toggleArchived").innerHTML = "&nbsp;Archive&nbsp;";
					document.getElementById("toggleArchivedSpan").style.right = "0px";
					if(perm == 0){
						document.getElementById("newUpdateButton").style.display = "";
						document.getElementById("newTicketButton").style.display = "";
						document.getElementById("newTicketLink").style.display = "";
					}
				} else {
					showArchived = true;
					document.getElementById("title").innerHTML = "Archived " + device + "s";
					document.getElementById("toggleArchived").innerHTML = "&nbsp;" + device + "s&nbsp;";
					document.getElementById("toggleArchivedSpan").style.right = "10px";
					if(perm == 0){
						document.getElementById("newUpdateButton").style.display = "none";
						document.getElementById("newTicketButton").style.display = "none";
						document.getElementById("newTicketLink").style.display = "none";
					}
				}
				// apply filters
				applyFilters();
			}


			// opens the new ticket menu 
			function newTicket(){
				clearModal();
				document.getElementById("newUpdate").style.display = "";
				document.getElementById("createUpdate").style.display = "none";
				document.getElementById("createTicket").style.display = "";
				document.getElementById("newUpdateDevice").innerHTML = "New Ticket " + deviceID;
				document.getElementById("statusZero").style.display = "none";
				document.getElementById("statusOne").selected = "selected";
				document.getElementById("backTickets").style.display = "";
				document.getElementById("backUpdates").style.display = "none";
			}


			// opens the new update menu
			function newUpdate(){
				clearModal();
				document.getElementById("newUpdate").style.display = "";
				document.getElementById("createUpdate").style.display = "";
				document.getElementById("createTicket").style.display = "none";
				document.getElementById("newUpdateDevice").innerHTML = "New Update " + deviceID;
				document.getElementById("statusZero").style.display = "";
				document.getElementById("statusZero").selected = "selected";
				document.getElementById("backTickets").style.display = "none";
				document.getElementById("backUpdates").style.display = "";
			}

			// goes back to tickets modal display
			function backTickets(){
				clearModal();
				document.getElementById("tickets").style.display = "";
			}

			// goes back from create update to updates display
			function backUpdates(){
				clearModal();
				document.getElementById("updates").style.display = "";
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


			// turns off all modal content
			function clearModal(){
				document.getElementById("tickets").style.display = "none";
				document.getElementById("newUpdate").style.display = "none";
				document.getElementById("updates").style.display = "none";
				document.getElementById("details").style.display = "none";
				document.getElementById("newDevice").style.display = "none";
			}


			// opens the new Device modal
			function newDevice(){
				// clear modal and display view
				clearModal();
				document.getElementById("newDevice").style.display = "";
				
				// create elements of type options for the new device and add to DOM
				let options = "";
				for(const type of types){
					options += '<option value="' + type + '">' + type + '</option>';
				}
				document.getElementById("newDeviceType").innerHTML = options;

				// open modal
				openModal();
			}


			// applies the selected filters and sort
			function applyFilters(){
				// status checkboxes
				const fully = document.getElementById("fullyFunctional").checked;
				const mostly = document.getElementById("mostlyFunctional").checked;
				const esport = document.getElementById("repairEsports").checked;
				const doit = document.getElementById("repairDOIT").checked;
				const broken = document.getElementById("broken").checked;
				// whether or not a status is being filtered
				const isStatusFilter = (fully || mostly || esport || doit || broken);

				// whether or not a device type is being filtered
				let isDeviceFilter = false;
				for(const deviceType of types){
					const str = deviceType.toUpperCase().replace(" ", "");
					if(document.getElementById(str).checked) {
						isDeviceFilter = true;
						break;
					}
				};

				// loop through rows and check if they should be displayed
				let rows = document.getElementById("table").rows;
				for(i = 1; i < rows.length; i++){
					// get type, status, and archived status
					row = rows[i].getElementsByTagName("TD");
					const type = row[1].innerHTML;
					const status = statusValue(row[2].innerHTML);
					const archived = parseInt(row[5].innerHTML);


					if(isDeviceFilter){
						// if there's a type filter check if we should show the type
						let showType = false;
						for(const deviceType of types){ 
							const str = deviceType.toUpperCase().replace(" ", "");
							if(type == deviceType && document.getElementById(str).checked) showType = true;
						};
					}

					// finally make the decision
					if((!isDeviceFilter || showType) && (!isStatusFilter || ((status == 0 && broken) || 
					(status == 1 && doit) || (status == 2 && esport) || (status == 3 && mostly) || 
					(status == 4 && fully))) && ((archived && showArchived) || (!archived && !showArchived))){
						row[0].parentNode.style.display = "";
					} else {
						row[0].parentNode.style.display = "none";
					}
				}
			
				// apply sort by ID, then by type, then by the chosen method
				sortTable(0, false);
				sortTable(1, false);
				const sort = document.getElementById("sort").value;
				switch(sort){
					case "idlh":
						sortTable(0, false);
						break;
					case "idhl":
						sortTable(0, true);
						break;
					case "typeaz":
						break;
					case "typeza":
						sortTable(1, true);
						break;
					case "statusbg":
						sortTable(2, false);
						break;
					case "statusgb":
						sortTable(2, true);
					break;
				}
				// apply colors
				applyTableColors();
			}

			// loops through the rows and checks if they're being displayed
			// if being displayed then apply the alternating color
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


	

	<body onload="sortTable(2, false);applyTableColors();">

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
			<a <?php echo $sidePC;?>>
				<span>PCs</span>
			</a>
			<a  <?php echo $sideConsole;?>>
				<span>Consoles</span>
			</a>
			<a  <?php echo $sideVR;?>>
				<span>VR</span>
			</a>
			<a href="inventory.php">
				<span>Inventory</span>
			</a>
			<a href="account.php">
				<span>Account</span>
			</a>
			<a href="../api/logout.php" style="position:absolute;bottom: 0;">
				<span>Logout</span>
			</a>
		</div>


		<!-- Header with title and filters -->
		<div class="header" id='heading'>
			<span class="filters vertcenter">
				<span class="filtershover"></span>
				<span class="vertcenter">
					Filters&nbsp;
					<i class="arrow down vertcenter"></i>
				</span>
				<span class="dropdown">
					<!-- sort options -->
					<div>
						<label for="sort">Sort By: </label>
						<select name="sort" id="sort">
							<option value="idlh">ID: Low to High</option>
							<option value="idhl">ID: High to Low</option>
							<option value="typeaz">Type: A-Z</option>
							<option value="typeza">Type: Z-A</option>
							<option value="statusgb">Status: Good to Bad</option>
							<option value="statusbg" selected="selected">Status: Bad to Good</option>
						</select>
					</div>
					<!-- type filters -->
					<div style="margin-top: 8px;">
					<?php echo $device;?>s: <br/>
						<span id="deviceTypeFilters">
							<?php
							foreach($types as $type){
								echo '<span id="filter' . strtoupper(str_replace(" ", "", $type["type"])) . 
									'"><input type="checkbox" id="' . strtoupper(str_replace(" ", "", $type["type"])) . 
									'"/> <label for="' . strtoupper(str_replace(" ", "", $type["type"])) . '">' . 
									$type["type"] . '</label><br/></span>';
							}
							?>
						</span>
					</div>
					<!-- status filters -->
					<div style="margin-top: 8px;">
						Status: <br/>
						<input type="checkbox" id="fullyFunctional"/>
						<el for="fullyFunctional">Fully Funcional</label><br/>
						<input type="checkbox" id="mostlyFunctional"/>
						<label for="mostlyFunctional">Mostly Functional</label><br/>
						<input type="checkbox" id="repairEsports"/>
						<label for="repairEsports">Repair - Esports</label><br/>
						<input type="checkbox" id="repairDOIT"/>
						<label for="repairDOIT">Repair - DoIT</label><br/>
						<input type="checkbox" id="broken"/>
						<label for="broken">Broken</label><br/>
					</div>
					<div style="margin-top: 8px;">
						<button onclick="applyFilters()">&nbsp;Apply Filters&nbsp;</button>
					</div>
				</span>
			</span>
			<!-- Title of page and buttons at top right -->
			<span id="title" class="title vertcenter"><?php echo $device;?>s</span>
			<span class="vertcenter" style="right: 0;" id="toggleArchivedSpan"><button id="toggleArchived" onclick="toggleArchived()">&nbsp;Archive&nbsp;</button></span>
				<?php
					if($perm == 1){
						echo '<span class="vertcenter" style="right: 58px;"><button onclick="newDevice()">&nbsp;New ' . $device . '&nbsp;</button></span>';
					}
				?>
					
		</div>


		<!-- Main body of the page -->
		<div class="bodydiv">
			<table id="table">
				<?php
					populateDeviceTable($devices, $connection);
				?>
			</table>
		</div>


		<!-- modal -->
		<div class="modal" id="modal">
			<!-- tickets view -->
			<div id="tickets">
				<div style="position: fixed; width: 90%; height: 40px;text-align:center;">
					<img class="back vertcenter" src="../images/xb.png" onclick="closeModal()"/>
					<span id="ticketsDevice" class="title" style="font-size:35px;"></span>
					<button id="newTicketButton" class="vertcenter" style="right:-30px;position: absolute;" onclick="newTicket()">&nbsp;+ New Ticket&nbsp;</button>
				</div>
				<div style="top:35px;" class="bodydiv">
					<table id="ticketsTable"></table>
					<div style="margin-top: 30px;" id="noTickets">
						<div>No tickets yet</div>
						<div id="newTicketLink" style="margin-top:5px;">
							<a style="text-decoration:underline;color:aqua;" href="#" onclick="newTicket()">New ticket</a>
						</div>
					</div>
				</div>
			</div>
			<!-- new update/ticket view -->
			<div id="newUpdate">
				<div style="position: fixed; width: 90%; height: 40px;text-align:center;">
					<img id="backTickets" class="back vertcenter" src="../images/back.png" onclick="backTickets()"/>
					<img id="backUpdates" class="back vertcenter" src="../images/back.png" onclick="backUpdates()"/>
					<span id="newUpdateDevice" class="title" style="font-size:30px;"></span>
					<button id="createUpdate" class="vertcenter" style="right: -30px;" onclick="submitNewUpdate(false)">&nbsp;Create Update&nbsp;</button>
					<button id="createTicket" class="vertcenter" style="right: -30px;" onclick="submitNewUpdate(true)">&nbsp;Create Ticket&nbsp;</button>
				</div>
				<div style="top:25px;" class="bodydiv">
					<span><?php echo $device;?> Status: </span>
					<select name="status" id="newUpdateStatus">
						<option id="statusZero" value="0">Fully Functional</option>
						<option id="statusOne" value="1">Mostly Functional</option>
						<option value="2">Repair - Esports</option>
						<option value="3">Repair - DoIT</option>
						<option value="4">Broken</option>
					</select></br></br>
					<textarea id="newUpdateSummary" maxlength="48" wrap='off' style="overflow:hidden;resize:none;width:90%;height: 20px;padding:10px;font-size:18px;" 
					placeholder="Summarize the current issue here."></textarea>
					<span id="summaryLimit" style="position:absolute;color:red;right:5%;top:50px;">48</span>
					<textarea id="newUpdateDetails" maxlength="1024" style="resize:none;width:90%;height:calc(95% - 100px);padding:10px;font-size:18px;" 
					placeholder="Optionally, additional details and actions perfomed related to the issue can go here."></textarea>
					<span id="detailsLimit" style="position:absolute;color:red;right:5%;top:90px;">1024</span>
				</div>
			</div>
			<!-- updates view -->
			<div id="updates">
				<div style="position: fixed; width: 90%; height: 40px;text-align:center;">
					<img class="back vertcenter" src="../images/back.png" onclick="backTickets()"/>
					<span id="updatesDevice" class="title" style="font-size:35px;"></span>
					<button id="newUpdateButton" class="vertcenter" style="right:-30px;position: absolute;" onclick="newUpdate()">&nbsp;+ New Update&nbsp;</button>
				</div>
				<div style="top:25px;" class="bodydiv">
					<table id="updatesTable"></table>
				</div>
			</div>
			<!-- details view -->
			<div id="details">
				<div style="position: fixed; width: 90%; height: 40px;text-align:center;">
					<img class="back vertcenter" src="../images/back.png" onclick="backUpdates()"/>
				</div>
				<div style="top:35px;text-align:left;" class="bodydiv" id="detailsTxt"></div>
			</div>
			<!-- new device view -->
			<div id="newDevice">
				<div style="position: fixed; width: 90%; height: 40px;text-align:center;">
					<img class="back vertcenter" src="../images/xb.png" onclick="closeModal()"/>
					<span class="title" style="font-size:35px;">New <?php echo $device;?></span>
					<button class="vertcenter" style="right:-30px;position: absolute;" onclick="submitNewDevice()">&nbsp;Create <?php echo $device;?>&nbsp;</button>
				</div>
				<div style="top:35px;" class="bodydiv">
					<div>
						<span style="position:relative;bottom: 17px;"><?php echo $device;?> Type: </span>
						<span style="display:inline-block;">
							<select name="newDeviceType" id="newDeviceType"></select>
							<button onclick="deleteDeviceType()">&nbsp;Delete Type&nbsp;</button>
							<br/> or <br/>
							<input id="createNewDeviceType" type="text" maxlength="32" placeholder="Create New Type"/>
							<span id="newTypeLimit" style="position:absolute;color:black;right:calc(50% - 145px);top:39px;">32</span>
						</span>
					</div>
					</br></br>
					<span><?php echo $device;?> ID: </span>
					<input id="newdeviceID" type="number" maxlength="6" placeholder="Enter NIU <?php echo $device;?> ID"/></br></br>
				</div>
			</div>
		</div>


		<script>
			// event listeners for length counters

			document.getElementById("newUpdateSummary").addEventListener("input", ({currentTarget: target}) => {
				const maxLen = target.getAttribute("maxLength");
				curLen = target.value.length;

				document.getElementById("summaryLimit").innerHTML = (maxLen-curLen);
			});
			document.getElementById("createNewDeviceType").addEventListener("input", ({currentTarget: target}) => {
				const maxLen = target.getAttribute("maxLength");
				curLen = target.value.length;

				document.getElementById("newTypeLimit").innerHTML = (maxLen-curLen);
			});
			document.getElementById("newUpdateDetails").addEventListener("input", ({currentTarget: target}) => {
				const maxLen = target.getAttribute("maxLength");
				curLen = target.value.length;

				document.getElementById("detailsLimit").innerHTML = (maxLen-curLen);
			});
			
		</script>
	</body>
</html>
