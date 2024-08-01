<?php
function populateInventoryTable($inventory){
	$perm = $_SESSION["perm"];
    // table head
    echo "<thead><tr style='background-color: black;'>
        <th>Item</th>
        <th>Quantity</th>
        <th>Functions</th>
    </tr></thead>";
    if(count($inventory) > 0){			
        // table body	
        echo "<tbody id='tableInventory'>";
        foreach($inventory as $item){
            $name = $item["name"];
            $quantity = $item["quantity"];
            

            // create row
            echo '<tr id="' . $name . '">
                <td>' . $name . '</td>
                <td id="' . $name . 'Quantity">' . $quantity . '</td>
                <td>
                    <div class="functions">
                        <span>
                            <input type="number" min="0" style="width: 100px;" value="' . $quantity . '" id="update' . $name . '"/>
                            <button onclick="updateQuantity(\'' . $name . '\')">&nbsp;Update Quantity&nbsp;</button>
                        </span>';
                    if($perm == 1){
                        echo '<button onclick="deleteItem(\'' . $name . '\')">&nbsp;Delete&nbsp;</button>';
                    }
                
                echo '</div></td>
            </tr>';
        }
        echo "</tbody>";
    } else {

    }
}