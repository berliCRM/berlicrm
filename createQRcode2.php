<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endPointUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    $xml
);
// Receive server response ...
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec($ch);

$datei = fopen("test/server_outputFromSimulator.txt","a+");
fwrite($datei, print_r($server_output, TRUE));
fclose($datei);

// echo PHP_EOL . __FILE__ . PHP_EOL . print_r($server_output, true) . PHP_EOL;
echo "New Order simulated!";
die();


$defaults = array(
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => array('Expect:', 'Cache-Control: no-cache', 'Content-Type: application/xml'),
            // CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0
            );

$curl_handler = curl_init();
$options = array(CURLOPT_URL => $endPointUrl, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => 'palle=1');
// $options = array(CURLOPT_URL => $endPointUrl, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $xml);
// echo PHP_EOL . __FILE__ . PHP_EOL . print_r($options, true) . PHP_EOL;
// die();
curl_setopt_array($curl_handler, ($defaults + $options));

$result = curl_exec($curl_handler);
echo PHP_EOL . 'result: ' . PHP_EOL . print_r($result, true) . PHP_EOL;
die();
if (!$result) {
    var_dump(curl_errno($curl_handler));
    var_dump(curl_error($curl_handler));
    curl_close($curl_handler);
    die();
}
echo "<pre>";
var_dump($result);
echo "</pre>";

curl_close($curl_handler);

function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>