<?php
function populateDeviceTable($devices, $connection){
	$perm = $_SESSION["perm"];
    // table head
    echo "<thead><tr style='background-color: black;'>
        <th>ID</th>
        <th>Type</th>
        <th>Status</th>
        <th>Issue</th>
        <th>Functions</th>
    </tr></thead>";
    if(count($devices) > 0){		
        // table body		
        echo "<tbody id='tableDevices'>";
        foreach($devices as $device){
            $id = $device["id"];
            $type = $device["type"];
            $archived = $device["archived"];

            // id of row is id of device
            echo '<tr id="' . $id . '"';
            
            // if archived don't show yet
            if($device["archived"]) echo 'style="display: none;"';

            // id and type columns
            echo '>
                <td>' . $id . '</td>
                <td>' . $type . '</td>';

                $highestStatus = 0;
                $issue = "none";

                // get the highest status and issue for the device for status and issues columns
                if($statement = $connection->prepare("WITH subq1 as (SELECT * FROM Updates WHERE ticket IN (SELECT id FROM Ticket WHERE device = ?)), subq2 AS (SELECT * FROM subq1 INNER JOIN (SELECT MAX(time) AS maxTime FROM subq1 GROUP BY ticket) AS tmp ON subq1.time = tmp.maxTime) SELECT summary, MAX(status) as status FROM subq2 GROUP BY summary ORDER BY status DESC LIMIT 1")){
                    $statement->bind_param("i", $id);
                    $statement->execute();
                    $statement->store_result();
                    if($statement->num_rows > 0){
                        $statement->bind_result($issue,$highestStatus);
                        $statement->fetch();
                        if($highestStatus == 0){
                            $issue = "none";
                        }
                    }
                }
                
                // get color and message based on status
                switch($highestStatus){
                    case 0:
                        $color = "00ff00";
                        $msg = "Fully Functional";
                        $highestStatus = 0;
                        break;
                    case 1:
                        $color = "7fff00";
                        $msg = "Mostly Functional";
                        break;
                    case 2:
                        $color = "ffff00";
                        $msg = "Repair - Esports";
                        break;
                    case 3:
                        $color = "ff7f00";
                        $msg = "Repair - DoIT";
                        break;
                    case 4:
                        $color = "ff0000";
                        $msg = "Broken";
                        break;
                }


                // status and issue columns
                echo '<td style="background-color: #' . $color . '; color: black;">' . $msg . '</td>';
                echo '<td style="width:auto;white-space:wrap;">' . $issue . '</td>';

                // functions column, all can see tickets
                echo '<td><div class="functions">
                    <button onclick="openTickets(' . $id . ')">&nbsp;Tickets&nbsp;</button>';
                    
                    // only perm 1 can archive and delete
                    if($perm == 1){
                        if($archived == 1){
                            echo '<button id="archiveButton' . $id . '" onclick="archiveDevice(' . $id . ', 0)">&nbsp;Unarchive&nbsp;</button>';
                        } else {
                            echo '<button id="archiveButton' . $id . '" onclick="archiveDevice(' . $id . ', 1)">&nbsp;Archive&nbsp;</button>';
                        }
                        echo '<button onclick="deleteDevice(' . $id . ')">&nbsp;Delete&nbsp;</button>';
                    }
                
                    // hidden archived column
                echo '</div></td>
                      <td style="display:none;">' . $archived . '</td>
            </tr>';
        }
        echo "</tbody>";
    } else {

    }
}