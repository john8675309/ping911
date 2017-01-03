<?php
try {
  $db = new PDO('mysql:host=localhost;dbname=ping911;charset=utf8mb4', 'ping911', 'ping911');
} catch(PDOException $ex) {
    error_log($ex->getMessage());
}
