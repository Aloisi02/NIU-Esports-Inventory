<?php

// checks if a character is a valid password character
function isValidPasswordChar($char){
    $passwordChars = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n','o', 'p', 'q', 
    'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9','!', 
    '(', ')', '-', '.', '?', '[', ']','_', '`', '~', ';', ':', '@', '#', '$', '%', '^', '&', '*', '+'];
    foreach ($passwordChars as $allowed) {
        if(strtolower($char) == $allowed){
            return true;
        }
    }
    return false;
}

function checkPassword($password){
    //check length of password
    if(strlen($password) < 8 || strlen($password) > 64){
        $response = ['success'=>'false', 'reason'=>'length'];
        http_response_code(200);
        die(json_encode($response));
    }

    //check allowed chars, num, and case.
    $numReqMet = false;
    $upperReqMet = false;
    $lowerReqMet = false;
    $spCharReqMet = false;

    $alphabet = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
        'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
    $digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $specialChars = ['!', '(', ')', '-', '.', '?', '[', ']','_', '`', '~', ';', ':', '@', 
        '#', '$', '%', '^', '&', '*', '+'];

    foreach(str_split($password) as $char){
        // check that all the characters are valid
        if(!isValidPasswordChar($char)){
            $response = ['success'=>'false', 'reason'=>'character'];
            http_response_code(200);
            die(json_encode($response));
        }

        //check if uppercase and lowercase letter
        foreach($alphabet as $alpha){
            if($char == strtoupper($alpha)){
                $upperReqMet = true;
            }
            if($char == $alpha){
                $lowerReqMet = true;
            }
            if($lowerReqMet && $upperReqMet){
                break;
            }
        }
        
        //check if digit
        foreach($digits as $digit){
            if($char == $digit){
                $numReqMet = true;
                break;
            }
        }

        //check if special char
        foreach($specialChars as $special){
            if($char == $special){
                $spCharReqMet = true;
                break;
            }
        }
    }
    if(!$upperReqMet){
        $response = ['success'=>'false', 'reason'=>'upper'];
        http_response_code(200);
        die(json_encode($response));
    } else if(!$lowerReqMet){
        $response = ['success'=>'false', 'reason'=>'lower'];
        http_response_code(200);
        die(json_encode($response));
    } else if(!$numReqMet){
        $response = ['success'=>'false', 'reason'=>'digit'];
        http_response_code(200);
        die(json_encode($response));
    } else if(!$spCharReqMet){
        $response = ['success'=>'false', 'reason'=>'special'];
        http_response_code(200);
        die(json_encode($response));
    }
}