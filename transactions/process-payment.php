<?php

function bidii_pay_process_payment($order_id){
    
$order = wc_get_order( $order_id );

     if($_POST['payment_method'] != 'bidii_pay_mpesa'){
        return;
     }

    if( !isset($_POST['mobile']) || empty($_POST['mobile']) ){
        wc_add_notice( __( 'Please add your mobile number', 'bidii_pay' ), 'error' );
    }
    
            $amount = $order -> {'total'};
            $mobile = $order -> {'mobile'};
    $mobile = (substr($mobile, 0, 1) == "+") ? str_replace("+", "", $mobile) : $mobile;
    $mobile = (substr($mobile, 0, 1) == "0") ? preg_replace("/^0/", "254", $mobile) : $mobile;
    $mobile = (substr($mobile, 0, 1) == "7") ? "254{$mobile}" : $mobile;

    $config = array(
        "env"              => "sandbox",
        "BusinessShortCode"=> "174379",
        "key"              => "6fSidiQK1v1f9sJG9m8Tzbs3SVTPgYfW", //Enter your consumer key here
        "secret"           => "3hosoK1vkgnXv80u", //Enter your consumer secret here
        "username"         => "testapi",
        "TransactionType"  => "CustomerPayBillOnline",
        "passkey"          => "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919", 
        "CallBackURL"     => "https://charityfarm.co.ke/wp-json/bidii-pay/v1/receive-callback",
        "AccountReference" => "CompanyXLTD",
        "TransactionDesc"  => "Payment of X" ,
    );
    $access_token = ($config['env']  == "live") ? "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials" : "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials"; 

    $credentials = base64_encode($config['key'] . ':' . $config['secret']); 

    $ch = curl_init($access_token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic '.$credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    $json_response = json_decode($response);
    // echo $response;
    $token = isset( $json_response -> access_token) ?  $json_response -> access_token  : '';
    $timestamp = date("YmdHis");
    $password  = base64_encode($config['BusinessShortCode'] . "" . $config['passkey'] ."". $timestamp);


    //Start structuring call to express api
    $request_data = json_encode(array( 
        "BusinessShortCode" => $config['BusinessShortCode'],
        "Password" => $password,
        "Timestamp" => $timestamp,
        "TransactionType" => $config['TransactionType'],
        "Amount" => $amount,
        "PartyA" => $mobile,
        "PartyB" => $config['BusinessShortCode'],
        "PhoneNumber" => $mobile,
        "CallBackURL" => $config['CallBackURL'],
        "AccountReference" => $config['AccountReference'],
        "TransactionDesc" => $config['TransactionDesc']

    //     "BusinessShortCode" => "174379",
    // "Password" => "MTc0Mzc5YmZiMjc5ZjlhYTliZGJjZjE1OGU5N2RkNzFhNDY3Y2QyZTBjODkzMDU5YjEwZjc4ZTZiNzJhZGExZWQyYzkxOTIwMjIwNjEyMjIxMDM3",
    // "Timestamp" => "20220612221037",
    // "TransactionType" => "CustomerPayBillOnline",
    // "Amount" => "1",
    // "PartyA" => "254713749580",
    // "PartyB" => "174379",
    // "PhoneNumber" => "254713749580",
    // "CallBackURL" => "https://charityfarm.co.ke/wp-json/bidii-pay/v1/receive-callback",
    // "AccountReference" => "CompanyXLTD",
    // "TransactionDesc" => "Payment of X" 
    )); 

    $endpoint = ($config['env'] == "live") ? "https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest" : "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest"; 
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer '.$token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $mpesa_response     = curl_exec($ch);
    curl_close($ch);
    $mpesa_json = json_decode($mpesa_response);
    $stk = $mpesa_json -> {'ResponseCode'};

    if($stk === '0'){
        echo $mpesa_response;
        echo $mpesa_json -> {'CustomerMessage'};
    } else {
        echo $mpesa_json -> {'errorMessage'};
    }
    
    //as this is a callback function to an action hook, don't return anything
    //add filter hook that calls check transaction?
    //apply filter calls it and returns somwthing
}

add_action('woocommerce_checkout_process', 'bidii_pay_process_payment');
// add_filter( 'bidii_pay_process_payment', 'bidii_pay_check_transaction', 10, 1);

include('check-transaction.php');