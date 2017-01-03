<?php
snmp_set_quick_print(false);
function addInterface($device_id,$snmp_id,$interface_name,$interface_port) {
  include('includes/sql.php.inc');
  try {
    $stmt = $db->prepare("INSERT into interfaces (device_id,snmp_id,interface_name,interface_port) values (:device_id,:snmp_id,:interface_name,:interface_port)");
    $stmt->bindValue(':device_id', $device_id, PDO::PARAM_INT);
    $stmt->bindValue(':snmp_id', $snmp_id, PDO::PARAM_INT);
    $stmt->bindValue(':interface_name', $interface_name, PDO::PARAM_STR);
    $stmt->bindValue(':interface_port', $interface_port, PDO::PARAM_STR);
    $stmt->execute();
  } catch (PDOException $ex) {
    error_log($ex->getMessage());
  }

}
function updateDevice($ip_address,$snmpCommunity,$snmpVersion) {
  include('includes/sql.php.inc');
  try {
    $stmt = $db->prepare("UPDATE devices set snmpCommunity=:snmpCommunity,snmpVersion=:snmpVersion where ip_address=:ip_address");
    $stmt->bindValue(':snmpCommunity', $snmpCommunity, PDO::PARAM_STR);
    $stmt->bindValue(':snmpVersion', $snmpVersion, PDO::PARAM_STR);
    $stmt->bindValue(':ip_address', $ip_address, PDO::PARAM_STR);
    $stmt->execute();
  } catch (PDOException $ex) {
    error_log($ex->getMessage());
  }
  try {
    $stmt = $db->prepare("SELECT id from devices where ip_address=:ip_address");
    $stmt->bindValue(':ip_address', $ip_address, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $rows[0]['id'];
  } catch (PDOException $ex) {
    error_log($ex->getMessage());
  }
}
function addDevice($ip_address,$snmpCommunity,$snmpVersion) {
  include('includes/sql.php.inc');
  $redis = new Redis();
  $redis->connect('127.0.0.1', 6379);
  try {
    $stmt = $db->prepare("INSERT into devices (device_ip,snmpCommunity,snmpVersion) values (:device_ip,:snmpCommunity,:snmpVersion)");
    $stmt->bindValue(':device_ip', $ip_address, PDO::PARAM_STR);
    $stmt->bindValue(':snmpCommunity', $snmpCommunity, PDO::PARAM_STR);
    $stmt->bindValue(':snmpVersion', $snmpVersion, PDO::PARAM_STR);
    $stmt->execute();
    $redis->set($ip_address, '{"Latency":"0.00","Reachable":"0"}');
  } catch (PDOException $ex) {
    error_log($ex->getMessage());
  }
  return $db->lastInsertId();
}
function lookupimage($snmpHost,$snmpCommunity,$snmpVersion) {
  //we can do this by vendor specific OID's For example Ubiquiti is 10002, Cisco SNMPv2-SMI::enterprises.9.1.1041, HP SNMPv2-SMI::enterprises.11.2.3.7.11.72
  $known_oids = array("SNMPv2-SMI::enterprises.10002.1","SNMPv2-SMI::enterprises.9.1.1041","SNMPv2-SMI::enterprises.11.2.3.7.11.72");
  $known_oids_image = array("img/ubnt.png","img/cisco.png","img/hp.png");
  if ($snmpVersion == "1") {
    //$output = snmpwalk($snmpHost,$snmpCommunity,".1.3.6.1.2.1.1.2.0");
    $output = @snmpget($snmpHost,$snmpCommunity,"SNMPv2-MIB::sysObjectID.0");
    error_log(print_r($output,true));
  } else if ($snmpVersion == "2c") {
    $output = @snmp2_get($snmpHost,$snmpCommunity,".1.3.6.1.2.1.1.2.0");    
  }
  $output = str_replace("OID: ","",$output);
  $key = array_search($output,$known_oids);
  if (!is_numeric($key)) {
    return "img/nologo.png";    
  }
  return $known_oids_image[$key];
}


function getEntity($snmpHost,$snmpCommunity,$snmpVersion) {
  if ($snmpVersion == "1") {
    return snmpget($snmpHost,$snmpCommunity,"1.3.6.1.2.1.1.1.0");        
  } else if ($snmpVersion == "2c") {
    return snmp2_get($snmpHost,$snmpCommunity,"1.3.6.1.2.1.1.1.0");    
  }
}

function getHost($snmpHost,$snmpCommunity,$snmpVersion) {
  if ($snmpVersion == "1") {
    $interfaces = snmpwalk ($snmpHost,$snmpCommunity,"1.3.6.1.2.1.2.2.1.1");
    $interfaceNames = array();
    foreach($interfaces as $interface) {
      $interface = explode(': ', $interface, 2);
      $interface = $interface[1];
      $interfaceName = "";
      $interfacePort = "";
      $interfaceName = getInterfaceName($interface,$snmpHost,$snmpCommunity,$snmpVersion);
      if ($interfaceName != "") {
        $interfaceName = explode(': ', $interfaceName, 2);
        $interfaceName = $interfaceName[1];
        $interfaceName = stripslashes($interfaceName);
      }
      $interfacePort = getInterfacePort($interface,$snmpHost,$snmpCommunity,$snmpVersion); 
      if ($interfacePort != "") {
        $interfacePort = explode(': ', $interfacePort, 2);
        $interfacePort = $interfacePort[1];
        $interfacePort = stripslashes($interfacePort);
      }
      $interfaceStatus = getInterfaceStatus($interface,$snmpHost,$snmpCommunity,$snmpVersion);
      $int = array("interface" => $interface,"interfaceName" => $interfaceName,"interfacePort"=>$interfacePort,"interfaceStatus"=>$interfaceStatus);
      array_push($interfaceNames,$int);
    }
    return $interfaceNames;
  } else if ($snmpVersion == "2c") {
    $interfaces = snmp2_walk ($snmpHost,$snmpCommunity,"1.3.6.1.2.1.2.2.1.1");

    $interfaceNames = array();
    foreach($interfaces as $interface) {
      $interface = explode(': ', $interface, 2);
      $interface = $interface[1];
      $interfaceName = getInterfaceName($interface,$snmpHost,$snmpCommunity,$snmpVersion);
      $interfaceName = explode(': ', $interfaceName, 2);
      $interfaceName = $interfaceName[1];
      $interfaceName = stripslashes($interfaceName);
      $interfacePort = getInterfacePort($interface,$snmpHost,$snmpCommunity,$snmpVersion); 
      $interfacePort = explode(': ', $interfacePort, 2);
      $interfacePort = $interfacePort[1];
      $interfacePort = stripslashes($interfacePort);

      $interfaceStatus = getInterfaceStatus($interface,$snmpHost,$snmpCommunity,$snmpVersion);
    

      $int = array("interface" => $interface,"interfaceName" => $interfaceName,"interfacePort"=>$interfacePort,"interfaceStatus"=>$interfaceStatus);

      array_push($interfaceNames,$int);
    }
    return $interfaceNames;
  } else if ($snmpVersion == "ping") {

  } else {
    exit;
  }
}

function getInterfaceStatus($id,$snmpHost,$snmpCommunity,$snmpVersion) {
  if ($snmpVersion == "2c") {
    $output = snmp2_get($snmpHost,$snmpCommunity,"1.3.6.1.2.1.2.2.1.8.".$id);
    $output = str_replace("INTEGER: ","",$output);
    if (strstr($output,"down") || $output == 2) {
      return 0;
    } else if (strstr($output,"up") || $output == 1) {
      return 1;
    }
  } else if ($snmpVersion == "1") {
    $output = snmpget($snmpHost,$snmpCommunity,".1.3.6.1.2.1.2.2.1.8.".$id);
    $output = str_replace("INTEGER: ","",$output);
    //error_log(print_r($output));
    if (strstr($output,"down")) {
      return 0;
    } else if (strstr($output,"up")) {
      return 1;
    }
  }
}

function getInterfacePort($id,$snmpHost,$snmpCommunity,$snmpVersion) {
  if ($snmpVersion == "2c") {
    return snmp2_get($snmpHost,$snmpCommunity,"1.3.6.1.2.1.2.2.1.2.".$id);
  } else if ($snmpVersion == "1") {
    return snmpget($snmpHost,$snmpCommunity,".1.3.6.1.2.1.2.2.1.2.".$id);
  }
}

function getInterfaceName($id,$snmpHost,$snmpCommunity,$snmpVersion) {
  if ($snmpVersion == "2c") {
    return snmp2_get($snmpHost,$snmpCommunity,"1.3.6.1.2.1.31.1.1.1.18.".$id);
  } else if ($snmpVersion == "1") {
    return @snmpget($snmpHost,$snmpCommunity,".1.3.6.1.2.1.31.1.1.1.18.".$id);
    //return snmpget($snmpHost,$snmpCommunity,".1.3.6.1.2.1.2.2.1.2.".$id);
  }
}
