<?php
include('includes/functions.php.inc');
if (!isset($_GET['address'])) {
	exit;
}
if (!isset($_GET['snmpVersion'])) {
	exit;
}
if (!isset($_GET['snmpCommunity'])) {
  echo 1;
	exit;
}
if ($_GET['snmpVersion'] != "1" && $_GET['snmpVersion'] !="2c" && $_GET['snmpVersion'] != "ping") {
	exit;
}

$snmpVersion = $_GET['snmpVersion'];
$snmpHost = $_GET['address'];
$snmpCommunity = $_GET['snmpCommunity'];

$entity = getEntity($snmpHost,$snmpCommunity,$snmpVersion);
$entity = str_replace("\r","",$entity);
$entity = str_replace("\n"," ",$entity);
$entity = str_replace("STRING: ","",$entity);
$image = lookupImage($snmpHost,$snmpCommunity,$snmpVersion);
error_log("*********" . $image);


$arr = getHost($snmpHost,$snmpCommunity,$snmpVersion);
$first = array("entity"=>$entity,"image"=>$image,"devices"=>$arr);
//error_log(print_r(json_encode($first),true));
echo json_encode($first);
