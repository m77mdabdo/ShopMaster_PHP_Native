<?php


function sendResponse($status = true, $message = "", $data = null, $code = 200)
{
    http_response_code($code);
    $response = [
        "status" => $status,
        "message" => $message
    ];

    if ($data !== null) {
        $response["data"] = $data;
    }

    echo json_encode($response);
    exit;
}


function sendError($message = "An error occurred", $code = 400)
{
    sendResponse(false, $message, null, $code);
}
