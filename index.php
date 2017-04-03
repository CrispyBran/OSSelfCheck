<?php

session_start();
include_once('config.php');
include_once('includes/sip2.php');

$formaction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $formaction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}



// Get page to be included below
if (!empty($_GET['page']) && file_exists('pages/'.$_GET['page'].'.php'))
  $page=$_GET['page'];
else
  // ...if no page was requested assume 'home'
  $page='home';



//header
include_once('includes/header.php');

//include page
include_once('pages/'.$page.'.php');

//footer
include_once('includes/footer.php');

