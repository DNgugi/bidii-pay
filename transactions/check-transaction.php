<?php

function bidii_pay_check_transaction($fields, $entry, $form_data){


    //  if ( absint( $form_data[ 'id' ] ) !== 2014 ) {
    //     return $fields;
    // }
    // check the field ID 4 to see if it's empty and if it is, run the error    
    if(empty( $fields[1][ 'value' ]) ) {
            // Add to global errors. This will stop form entry from being saved to the database.
            // Uncomment the line below if you need to display the error above the form.
            // wpforms()->process->errors[ $form_data[ 'id' ] ][ 'header' ] = esc_html__( 'Some error occurred.', 'bidii_pay' );    
  
            // Check the field ID 4 and show error message at the top of form and under the specific field
               wpforms()->process->errors[ $form_data[ 'id' ] ] [ '1' ] = esc_html__( 'Some error occurred.', 'bidii_pay' );
                exit();

  
          // Add additional logic (what to do if error is not displayed)
    }



/*Call function with these configurations*/
    $env="sandbox";
    $type = 2;
    $shortcode = '600996'; 
    $key     = "6fSidiQK1v1f9sJG9m8Tzbs3SVTPgYfW";//Enter your consumer key here
    $secret= "3hosoK1vkgnXv80u"; //Enter your consumer secret here
    $initiatorName = "testapi";
    $initiatorPassword = "Safaricom999!*!";
    $results_url = "https://charityfarm.co.ke/wp-json/bidii-pay/v1/result/"; //Endpoint to receive results Body
    $timeout_url = "https://charityfarm.co.ke/wp-json/bidii-pay/v1/queue/"; //Endpoint to to go to on timeout
/*End  configurations*/

/*End transaction code validation*/

    $transactionID = $form_data['id'][1]; 
    //$transactionID = "OEI2AK4Q16";
    $command = "TransactionStatusQuery";
    $remarks = "Transaction Status Query"; 
    $occasion = "Transaction Status Query";
    $callback = null ;

    
    $access_token = ($env == "live") ? "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials" : "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials"; 
    $credentials = base64_encode($key . ':' . $secret); 
    
    $ch = curl_init($access_token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response); 

    // echo $result->{'access_token'};
    
    $token = isset($result->{'access_token'}) ? $result->{'access_token'} : "N/A";

    $publicKey = file_get_contents(__DIR__ . "/mpesa_public_cert.cer"); 
    $isvalid = openssl_public_encrypt($initiatorPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING); 
    $password = base64_encode($encrypted);

    //echo $token;


    $curl_post_data = array( 
        
    "Initiator" => "testapi",
    "SecurityCredential" => "P3N+gjF0LcMAr0HHhxFj5A0K/9/ARruktvA2WWJ6CNIHcaNBQDJ2CitI1ZchJXrcy5UnHrf8cPhhfXgX1Thn4SEGT80FdJfIWD1aO5arfVIqj+UNYlvfWWNGk6g5FSSyKiRCypdyOOoWeklvCpYWflNMWsqIlDGPGZQ14k7h82eTfj8uT8ys+4QuF8od3J1nzIu3Cot0l/1nV39ln8mgzMGmB0AJKOTalBWyBJ+9dl3AFUIk9eIe03dRRw1E7hZBZxlzSD5wJqKCOf1CRqirwK9cqrvHdW1QhTrXaMjtiB65YhSgGO91NNxFt+4l51AVHQIYoLTUXcp022jziCcYKg==",
    "CommandID" => "TransactionStatusQuery",
    "TransactionID" => "OEI2AK4Q16",
    "PartyA" => "600996",
    "IdentifierType" => "4",
    "ResultURL" => "https://mydomain.com/TransactionStatus/result/",
    "QueueTimeOutURL" => "https://mydomain.com/TransactionStatus/queue/",
    "Remarks" => "cinnMan",
    "Occassion" => "",
  
        // "Initiator" => $initiatorName, 
        // "SecurityCredential" => $password, 
        // "CommandID" => $command, 
        // "TransactionID" => $transactionID, 
        // "PartyA" => $shortcode, 
        // "IdentifierType" => $type, 
        // "ResultURL" => $results_url, 
        // "QueueTimeOutURL" => $timeout_url, 
        // "Remarks" => $remarks, 
        // "Occasion" => $occasion,
    ); 

    $data_string = json_encode($curl_post_data);

    //echo $data_string;

    $endpoint = ($env == "live") ? "https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query" : "https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query"; 

    $ch2 = curl_init($endpoint);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer '.$token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch2, CURLOPT_POST, 1);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    $response     = curl_exec($ch2);
    curl_close($ch2);

    echo $response;

    $result = json_decode($response); 
    
    $verified = $result -> {'ResponseCode'};
    if($verified === "0"){
        echo $verified;
        //make some changes to !!!the order!!!?
    }else{
        echo $result -> {'errorMessage'};
    }
        
}

add_filter( 'wpforms_process', 'bidii_pay_check_transaction', 10, 3 );
