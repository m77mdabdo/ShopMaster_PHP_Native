<?php 

header("content-type:application/json");

require_once "../conn/db.php";
require_once "../vendor/autoload.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
$data = json_decode(file_get_contents("php://input"), true);

 if($_SERVER['REQUEST_METHOD'] === "POST") {
   
     http_response_code(200);
     echo json_encode(["message" => "Logged out successfully"]);
 } else {
     http_response_code(405);
     echo json_encode(["error" => "Method not allowed"]);
 }