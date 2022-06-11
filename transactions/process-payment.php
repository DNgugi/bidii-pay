<?php

// header("Content-Type:application/json");
// session_start();
function bidii_pay_process_payment(){
     if($_POST['payment_method'] != 'bidii_pay_mpesa')
        return;

    if( !isset($_POST['mobile']) || empty($_POST['mobile']) )
        wc_add_notice( __( 'Please add your mobile number', $this->domain ), 'error' );


    $config = array(
        "env"              => "sandbox",
        "BusinessShortCode"=> "174379",
        "key"              => "", //Enter your consumer key here
        "secret"           => "", //Enter your consumer secret here
        "username"         => "apitest",
        "TransactionType"  => "CustomerPayBillOnline",
        "passkey"          => "", //Enter your passkey here
        "CallBackURL"     => "http://localhost/wordpress/wc-api/response/",
        "AccountReference" => "CompanyXLTD",
        "TransactionDesc"  => "Payment of X" ,
    );

    $mobile = $order['mobile'];
    $amount = $order['amount'];

    $mobile = (substr($mobile, 0, 1) == "+") ? str_replace("+", "", $mobile) : $mobile;
    $mobile = (substr($mobile, 0, 1) == "0") ? preg_replace("/^0/", "254", $mobile) : $mobile;
    $mobile = (substr($mobile, 0, 1) == "7") ? "254{$mobile}" : $mobile;

    $access_token = ($config['env']  == "live") ? "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials" : "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials"; 
    $credentials = base64_encode($config['key'] . ':' . $config['secret']); 
    
    $ch = curl_init($access_token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response); 
    $token = isset($result->{'access_token'}) ? $result->{'access_token'} : "N/A";

    $timestamp = date("YmdHis");
    $password  = base64_encode($config['BusinessShortCode'] . "" . $config['passkey'] ."". $timestamp);

    $curl_post_data = array( 
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
        "TransactionDesc" => $config['TransactionDesc'],
    ); 

    $data_string = json_encode($curl_post_data);

    $endpoint = ($config['env'] == "live") ? "https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest" : "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest"; 

    $ch = curl_init($endpoint );
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer '.$token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response     = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response); 
    
    $stkpushed = $result->{'ResponseCode'};


    if($stkpushed === "0"){
        echo $result->{'ResponseDescription'};
        // header("Location: confirmation-payment.php");
        return true;
    } else {
        echo $result->{'errorMessage'};
        return false;
    }
}
