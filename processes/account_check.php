<?php
/* 
	check and validate borrower's account
*/
session_start();
include_once('../config.php');
include_once('../includes/sip2.php');
include_once('../includes/json_encode.php');

if (!empty($_POST['barcode']) && (strlen($_POST['barcode'])==$patron_id_length OR empty($patron_id_length))){ //check that the barcode was posted and matches the length set in config.php 

	$mysip = new sip2;

	// Set host name
	$mysip->hostname = $sip_hostname;
	$mysip->port = $sip_port;
	
	// Identify a patron
	$mysip->patron = $_POST['barcode'];
	
	// connect to SIP server
	$connect = $mysip->connect();
	
	if(!$connect){ //if the connection failed go to the out of order page
		echo json_encode('out of order');
		exit;
	}
	
	if(!empty($sip_login)){
		$sc_login=$mysip->msgLogin($sip_login,$sip_password);
		$mysip->parseLoginResponse($mysip->get_message($sc_login));
	}

	// Get patron info response
	$ptrnmsg = $mysip->msgPatronInformation('charged');

	// parse the raw response into an array
	$patron_info = $mysip->parsePatronInfoResponse($mysip->get_message($ptrnmsg));

	//print_r($patron_info);
	
	$mysip->msgEndPatronSession();

	if (strpos($patron_info['fixed']['PatronStatus'],'Y')!== false OR (!empty($patron_info['variable']['BL'][0]) && $patron_info['variable']['BL'][0]!='Y')){ //blocked or non-existent account?
		session_regenerate_id();
		session_destroy();
		echo json_encode('blocked account');
		exit;
	}
	
	//extract and format account information and assign to session variables
	$_SESSION['patron_barcode']=$_POST['barcode'];
	
	$patron_name='';
	if (!empty($patron_info['variable']['AE'][0])){
		$patron_name=$patron_info['variable']['AE'][0]; //patron's unformatted name
		if (strpos($patron_name,',')!==false){
			$patron_last_name=substr($patron_name, 0, strpos($patron_name,',')); //last name
			$patron_first_name=substr($patron_name, strpos($patron_name,','),strlen($patron_name)); //first name
			$patron_name=$patron_first_name.' '.$patron_last_name;
		}
		$_SESSION['name']=trim(str_replace(',','',$patron_name)); //patron's formatted name
	} else {
		$_SESSION['name']='';
	}	
	
	if (!empty($patron_info['variable']['BE'][0])){
		$_SESSION['email']=trim($patron_info['variable']['BE'][0]); //patron's email
	} else {
		$_SESSION['email']='';
	}
	
	if (!empty($patron_info['fixed']['OverdueCount'])){
		$_SESSION['overdues']=$patron_info['fixed']['OverdueCount']; //overdues
	} else {
		$_SESSION['overdues']=0;
	}
	
	if (!empty($patron_info['fixed']['HoldCount'])){
		$_SESSION['available_holds']=$patron_info['fixed']['HoldCount']; //holds
	} else {
		$_SESSION['available_holds']=0;
	}
	
	if (!empty($patron_info['fixed']['ChargedCount'])){
		$_SESSION['checkouts']=$patron_info['fixed']['ChargedCount']; //checkouts
	} else {
		$_SESSION['checkouts']=0;
	}
	
	if (!empty($patron_info['variable']['BV'][0])){
		$_SESSION['fines']=$currency_symbol.trim($patron_info['variable']['BV'][0]); //fines
	} else {
		$_SESSION['fines']='';
	}

	$_SESSION['checkouts_this_session']=0;
	
	// gather and display items already checked out
	$chrgd = $mysip->msgPatronInformation('charged');
	$myCharged = $mysip->parsePatronInfoResponse( $mysip->get_message($chrgd));
	$response_message='';
	$prvcount=0;
	$_SESSION['pBarcode'] = array();
	$_SESSION['pTitle'] = array();
	$_SESSION['pDue'] = array();
	if ($_SESSION['checkouts']>0){
		$_SESSION['previousitems']='<tr><td colspan="3"><h2>Currently Checked Out</h2></td></tr>';
		// apply items to array for sorting by due date
		foreach ($myCharged['variable']['AU'] as $prevItemBarcode) {
			$prvitem = $mysip->msgItemInformation($prevItemBarcode);
			$previousItem = $mysip->parseItemInfoResponse( $mysip->get_message($prvitem));
			$time = strtotime(trim($previousItem['variable']['AH'][0]));
			$_SESSION['pBarcode'][$prvcount] = $prevItemBarcode;
			$_SESSION['pTitle'][$prvcount] = trim($previousItem['variable']['AJ'][0]);
			$_SESSION['pDue'][$prvcount] = date('m-d-Y',$time);
			$prvcount++;
		}
		array_multisort($_SESSION['pDue'],$_SESSION['pBarcode'],$_SESSION['pTitle']);
		// populate checkout table with the array items
		for ($i=0; $i <= $prvcount-1; $i++) {
			$j=$i+1;
			if ($_SESSION['pDue'][$i]<date("m-d-Y")) {
				$odstyle='style="color: red;"';
			} else {
				$odstyle='';
			}
			$_SESSION['previousitems']=$_SESSION['previousitems'].'<tr><td class="cko_item" style="color:#666;width:25px" id="item_left_'.$_SESSION['pBarcode'][$i].'_'.$j.'">'.$j.'.</td><td class="cko_item" style="width:80%;">'.$_SESSION['pTitle'][$i].'</td><td class="cko_item" id="item_right_'.$_SESSION['pBarcode'][$j].'_'.$j.'" '.$odstyle.'>'.$_SESSION['pDue'][$i].'</td></tr>';
		}
	} else {
		$_SESSION['previousitems']='';
	}
	if ($_SESSION['checkouts']>0){
		$_SESSION['previousitems']=$_SESSION['previousitems'].'<tr><td colspan="3"><hr></td></tr><tr><td colspan="3"><h2>Checking Out Now</h2></td></tr>';
	} else {
		$_SESSION['previousitems']=$_SESSION['previousitems'].'<tr><td colspan="3"><h2>Checking Out Now</h2></td></tr>';
	}

	session_write_close();

	//put include file into variable to dump as json back to the jquery script that initiated the call to this page
	ob_start();
	include_once( '../includes/welcome.php' );
	$response = ob_get_contents();
	ob_end_clean(); 
	
	echo json_encode($response);
	exit;

} else {

	echo json_encode('invalid account');
	exit;
	
}
?>