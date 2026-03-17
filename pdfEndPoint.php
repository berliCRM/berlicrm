<?php
include('config.inc.php');
require_once($root_directory.'swisspdf/createpng.php');
$data = json_decode(file_get_contents('php://input'), true);

// Process the received array
if ($data) {

    // Perform any necessary operations with the received data
    $url = createpng($data);
    // Prepare the response
    $response = array('success' => true, 'pictureURL' => $url);
    // Send the response back to Server A
    echo json_encode($response);
} else {
    echo 'Error: Invalid data received';
}
?>