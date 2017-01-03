<?php
include('includes/sql.php.inc');
include('includes/functions.php.inc');
$data = file_get_contents('php://input');
error_log($data);
$arr = json_decode($data);
try {
  $stmt = $db->prepare("SELECT count(*) FROM devices WHERE device_ip=:device_ip");
  $stmt->bindValue(':device_ip', $arr->ip_address, PDO::PARAM_STR);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if ($rows[0]["count(*)"] == 0) {
    $id = addDevice($arr->ip_address,$arr->snmpCommunity,$arr->snmpVersion);
  } else {
    $id = updateDevice($arr->ip_address,$arr->snmpCommunity,$arr->snmpVersion);
  }
  foreach($arr->interfaces as $interface) {
    if ($interface->snmp_id != "") {
      addInterface($id,$interface->snmp_id,$interface->interface_name,$interface->interface_port);
    }
  }
} catch(PDOException $ex) {
  error_log($ex->getMessage());
}


