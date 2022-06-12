<?php
/**
 * Plugin Name:       Bidii Pay 
 * Plugin URI:        https://teambidii.co.ke/bidii-pay
 * Description:       Handle M-Pesa payments on WordPress with this plugin.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Team Bidii Consulting
 * Author URI:        https://teambidii.co.ke
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bidii-pay
 * Domain Path:       /languages
 */

 /**
  * TO-DO
  * Call process payment when woocommerce 'Place Order' button is clicked
  *   process payment triggers mpesa response to callback url provide in request handled by 'response.php'
  * Hook into 'order-received' page of website, mark order as pending payment and add "confirm payment" button
  * Call check transaction when confirm payment is clicked and mark the order as completed if payment worked
  * 
  */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function bidii_pay_custom_db_table() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'mpesa_transactions';

	$sql = "CREATE TABLE $table_name (
		MerchantRequestID text NOT NULL,
		CheckoutRequestID text NOT NULL,
		ResultCode smallint(1) NOT NULL,
		ResultDesc  text NOT NULL,
        MpesaCode varchar(20) NOT NULL,
        Amount decimal(19,2) NOT NULL,
        TransactionDate datetime NOT NULL,
        PhoneNumber varchar(12) NOT NULL,
        UNIQUE KEY MpesaCode (MpesaCode)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}
register_activation_hook( __FILE__, 'bidii_pay_custom_db_table' );

function bidii_pay_add_callback_url(){
    register_rest_route(
        'bidii-pay/v1/',
        'receive-callback',
        array(
            'methods' => 'POST',
            'callback' => 'bidii_pay_receive_callback'
        )
    );
}
add_action('rest_api_init', 'bidii_pay_add_callback_url');

function bidii_pay_receive_callback($request){
    global $wpdb;
    $data = array();
    $request = $request -> get_params();
     $MerchantRequestID = $request['MerchantRequestID'];
     $CheckoutRequestID = $request['CheckoutRequestID'];
     $ResultCode = $request['ResultCode'];
     $ResultDesc = $request['ResultDesc'];
     $MpesaCode = $request['CallbackMetadata']['Item'][1]['Value'];
     $Amount = $request['CallbackMetadata']['Item'][0]['Value'];
     $TransactionDate = $request['CallbackMetadata']['Item'][2]['Value'];
     $PhoneNumber = $request['CallbackMetadata']['Item'][3]['Value'];

    $table = $wpdb->prefix . 'mpesa_transactions';
 
    $sql = $wpdb->prepare("INSERT INTO `$table` (`MerchantRequestID`, `CheckoutRequestID`, `ResultCode`, `ResultDesc`, `MpesaCode`, `Amount`, `TransactionDate`, `PhoneNumber`) values (%s, %s, %d, %s, %s, %f, %s, %s)", $MerchantRequestID, $CheckoutRequestID, $ResultCode, $ResultDesc, $MpesaCode, $Amount, $TransactionDate, $PhoneNumber);

    $wpdb->query($sql);

    $data['status'] = 'OK';
    $data['message'] = 'Reached callback url';
    return $data;
    //store m-pesa data in a custom table 
}

include('bidii-pay-custom-gateway.php');

