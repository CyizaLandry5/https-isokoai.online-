<?php
// api/test.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

echo json_encode([
    'status' => 'online',
    'message' => 'API is working',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'],
    'php_version' => phpversion(),
    'api_version' => '1.0.0'
]);
?>