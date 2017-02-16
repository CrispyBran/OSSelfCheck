<?php
/**
* 	Self Check (UNOFFICIAL REVISION FOR TESTING)
*
* 	This application provides a method of checking out and renewing materials via a web interface using 3M's SIP2 standard
* 	Thanks to John Wohlers for his sip2 class -this application would not have come about without it
*	
*	If you make additions or other customizations that improve or extend the self check's 
*	functionality I would love to hear about them.
*
*	@author     	Eric Melton <ericmelton@gmail.com>
* 	@licence    	http://opensource.org/licenses/gpl-3.0.html
* 	@copyright  	Eric Melton <ericmelton@gmail.com>
*	@version    	1.31
*
* 	1.3 Enhancements by Christopher Brannon <brannon.chris@outlook.com>:
*	* Show what patron already has checked out.
*	* Improved customization of SIP Responses.
*	* Rebuilt renewal process to catch renewals for items checked out in previous sessions.
*	* Stop items from renewing that were checked out in current session.
*
* 	1.31 Enhancements by Christopher Brannon <brannon.chris@outlook.com>:
*	* Improvements to renewal process (checkout and renewal captures renewals with different responses).
*	* Dialed back customization of SIP for issues needing staff intervention (less details to patron).
*	* Adjustments to keypad.
*/

//========================== SIP2 =================================
$sip_hostname = 'ilssipserver.com';
$sip_port = "sipport"; 
$sip_login='username'; 	//if your SIP2 server does not require a username and password leave these empty
$sip_password='password';


//========================== Site Rules ==============================
$sc_location='Your Library';//enter a name for the self-check's location (e.g. 'East Branch') to track transactions in your SIP2 logs (in Polaris this is required and is the numeric organization ID)
$allow_manual_userid_entry=true;
$show_fines=true;
$fine_limit='3.00';
$show_available_holds=true;
$allow_email_receipts=true;
$allow_print_receipts=true;
$display_php_errors='off'; //off or on
$hide_cursor_pointer=false; //hides default cursor pointer -should probably set to true on live self check
$show_welcome_screen=false;


//========================== Logging =================================
/*	
	use the query below to setup the mysql table (if you change the table name set 
	the variable $log_table_name below equal to that new table name)
	
	CREATE TABLE `self_check_stats`
	(`id` int( 11 ) NOT NULL AUTO_INCREMENT ,
	`location` varchar( 50 ) DEFAULT NULL ,
	`count` int( 11 ) NOT NULL DEFAULT '0',
	`sessions` int( 11 ) NOT NULL DEFAULT '0',
	`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
	PRIMARY KEY ( `id` ));
	
*/
//====================================================================
$use_mysql_logging=false;	/* log your selfcheck checkout count by month? 
							use the query above to set up the table */
$log_table_name='self_check_stats';

//mysql connection info (ignore this if you're not using mysql logging)
$dbhostname = "localhost:3306";
$database = "";
$dbusername = "";
$dbpassword = "";

//====================== SIP2 Responses  ==============
/*
	GET YOUR SYSTEM'S RESPONSE MESSAGES BY ENTERING YOUR SIP2 CONNECTION INFO ABOVE THEN OPENING responses.php 
	IN YOUR BROWSER-THEY MUST BE KEPT UP TO DATE!
	These are case INsensitive. 
*/
//====================================================================
$SIP_already_ckdout_to_you='Item already checked out to you: renewing item.'; //item already out to this borrower response
$SIP_recheck_limit='Item has reached maximum renewals'; //SIP response when checking out and item has reached renewal limit
$SIP_renewal_limit='NO_MORE_RENEWALS: 1 ';  //SIP response when renewing and item has reached renewal limit (yes, they are different processes)

//====================== Custom Responses  ==============
/*
	SET YOUR OWN RESPONSES TO THE SIP RESPONSES 
*/
//====================================================================
$CUSTOM_renewal_limit="Sorry, this item cannot be renewed.  Please return item to a staff member.";
$CUSTOM_item_problems="There is an issue with this item.<br /><br />Please see staff for assistance."; //for generic numeric responses


//====================== Wording, SMTP, & Other Variables ==============
$currency_symbol='$';
$due_date_format='n/j/Y'; //see http://php.net/manual/en/function.date.php for information on formatting dates
$inactivity_timeout=40000; //time of inactivity before showing inactive prompt (in milliseconds)
$account_check_timeout=15000; //time of inactivity after patron card scan before showing out of order page (in milliseconds)
$patron_id_length=''; //length of patron barcode or other id (leave empty if this varies)
$online_catalog_url='http://youropac.com'; 	/*leave blank if you don't have one or if your catalog does
							not allow renewals (this is for printing on the paper receipt and 
							sending in the email receipt info about renewing online)*/
							
//smtp (for emailing receipts)
$smtp_host="mail.host.com"; 
$smtp_authentication=false;
$smtp_username='';
$smtp_pwd='';

//wording
$library_name= "Your Library";
$library_phone= '555-555-5555';
$module_name='Self-Check Station'; //shows on pages/home.php and pages/checkout.php
$email_from_name="Your Library"; //library's email name
$email_from_address="someone@yourlibrary.org"; //library's email address
$admin_emails='someone@yourlibrary.org'; //comma delimted list of email addresses that should be notified should the self-check go out of order
$email_subject='Your Library Self-Checkout Receipt'; //subject of email receipt
$intro_screen_text="Scan or type your card number to begin"; //shown on pages/home.php
$intro_screen_subtext="(touch keypad in upper-right corner to type)";
$welcome_screen_text="Scan an item's barcode to continue";	//shown on includes/welcome.php
$welcome_screen_subtext="(located on the front cover or inside the front cover)";
$renewal_prompt_text='is already checked out to your account.<br />Would you like to try to renew it?';
$out_of_order_head='Out of Service'; //shown on pages/out_of_order.php
$out_of_order_text='We are working to fix the problem'; //shown on pages/out_of_order.php

//====================== Paper & Email Receipts ==============
/* add elements to or remove elements from the header & footer arrays below to manipulate that piece of the receipt.
the elements will appear on separate lines of the receipt in the order that you place them below */ 
$receipt_header[]='Checkout Receipt';
$receipt_header[]=$library_name;
$receipt_header[]='Phone: '.$library_phone;
$receipt_footer[]='Renew your items online:';
$receipt_footer[]=$online_catalog_url;

/*place the following in the order you want the elements to appear in the item list on the 
paper and email receipts. remove (or comment out) any elements you don't want included.
element options include item_barcode, title, due_date, and call_number */
$receipt_item_list_elements[]='title';
/*$receipt_item_list_elements[]='call_number';*/
$receipt_item_list_elements[]='item_barcode';
$receipt_item_list_elements[]='due_date';

//========================= Sounds & Images ==========================
	//sounds
$error_sound="sounds/error.mp3";
$welcome_sound="sounds/welcome.mp3";
$note_sound="sounds/note.mp3";

	//images  (you need to uncomment one -and only one- line from each group). 
/*
	Keep in mind these are not the image files names -they are just meant to trigger the showing 
	of the types of images listed here. For further customization, images are loaded in the following files: 
	pages/checkout.php , pages/home.php, and includes/welcome.php 
*/

	//======= group 1: home page images of library card =======
//$card_image='kpl';
$card_image='barcoded';
//$card_image='magnetic';

	//======= group 2: home and checkout page images of book ==
$item_image='barcoded';
//$item_image='nonbarcoded';


//======================= Action Balloons =======================
/*

The following settings determine what types of materials will prompt the self check to issue an
action message (a short message accompanied by a beep sound) upon checkout. You may want borrowers to unlock the cases of 
or desensitize certain types of items, for example, or give a reminder that a particular type of item has a 
shorter checkout period than other items like it. 

Each item that requires an action can have its action message triggered by 1) its item type OR 2) its permanent location.

Each action balloon requires 2 variables set up in the following format:

1) $action_balloon[item type OR permanent location]['action_message']=action message;
2) $action_balloon[item type OR permanent location]['trigger']='item type' OR 'permanent location' OR 'call number prefix';

2 examples:
$action_balloon['CD']['action_message']='Please place your CDs inside one of the plastic bags near this station';
$action_balloon['CD']['trigger']='permanent location';

$action_balloon['EXPRESS DVDS']['action_message']='Express DVDs checkout for 3 days';
$action_balloon['EXPRESS DVDS']['trigger']='item type';

*/
//======================================================================
$action_balloon_bg_color='#f1cae1'; //background color for action balloons
//$action_balloon['WII']['action_message']='Please ask at Check Out Desk for disc(s).';
//$action_balloon['WII']['trigger']='call number prefix';


//==================================== Allowed IPs =======================
/*
	list each allowed ip on a new line as $allowed_ip[]='IP'; 
	example: $allowed_ip[]='192.168.0.2';
		   $allowed_ip[]='192.168.0.4';
*/
$allowed_ip[]=''; //leave empty if you've already limited access to the self check via your server (Apache, IIS, etc.)

//==================================== Don't edit below this line =======================
if (!in_array($_SERVER['REMOTE_ADDR'],$allowed_ip) && !empty($allowed_ip[0])){ 
	exit;
}
ini_set('display_errors', $display_php_errors);
?>