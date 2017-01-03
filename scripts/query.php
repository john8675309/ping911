<?php
require_once "System/Daemon.php";
System_Daemon::setOption("appName", "query.php");
System_Daemon::start();
$redis = new Redis();
$redis->connect('127.0.0.1');
while(true) {
  foreach ($redis->keys("*") as $key) {
    $latency = ping($key);
    if (!$latency) {
      $output = $redis->get($key);
      $json = json_decode($output,true);
      $date = date("Y-m-d H:i:s");
      $json["$date"]=0.00;
      $json["latency"] =0.00;
      $json["Reachable"] = 0;
      $json = json_encode($json);
      $redis->set($key, '{"Latency":"0.000","Reachable":"0"}');
    } else {
      //$latency = round(($latency * 1000000)/1000,4);
      $latency = round($latency,4);
      $output = $redis->get($key);
      $json = json_decode($output,true);
      $date = date("Y-m-d H:i:s");
      $json["$date"]=$latency;
      $json["latency"] = $latency;
      $json["Reachable"] = 1;
      $json = json_encode($json);
      $redis->set($key, $json);
    }
  }
  sleep(5);
}
System_Daemon::stop();
function ping($host) {
    $package = "\x08\x00\x19\x2f\x00\x00\x00\x00\x70\x69\x6e\x67";
    $socket = socket_create(AF_INET, SOCK_RAW, 1);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
    socket_connect($socket, $host, null);
    list($start_usec, $start_sec) = explode(" ", microtime());
    $start_time = ((float) $start_usec + (float) $start_sec);
    socket_send($socket, $package, strlen($package), 0);
    if(@socket_read($socket, 255)) {
        list($end_usec, $end_sec) = explode(" ", microtime());
        $end_time = ((float) $end_usec + (float) $end_sec);
        $total_time = $end_time - $start_time;
        $total_time = $total_time * 1000;
        return round($total_time,4);
    } else {
        return false;
    }
    socket_close($socket);
}
