<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Secret, Authorization, X-Requested-With");

// INCLUDING DATABASE AND MAKING OBJECT
require __DIR__ . '/middlewares/Auth.php';
$db_connection = new Database();

use \Firebase\JWT\JWT;

function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}


$conn = $db_connection->dbConnection();
$allHeaders = getallheaders();

// GET DATA FORM REQUEST
$data = json_decode(file_get_contents("php://input"));

// IF REQUEST METHOD IS NOT POST

$returnData = [
    "success" => 0,
    "status" => 401,
    "message" => "Unauthorized"
];

if ($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0, 404, 'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif
(isset($data)):


        try {
            //Save Panic Button Request
            $permitted_request_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            $request_id = substr(str_shuffle($permitted_request_chars), 0, 20);

            $save_panic_info = "INSERT INTO `user_requests` (`user_id`, `user_latitude`, `user_longitude`, `user_address`,`request_id`)
                                VALUES(:user_id,:user_latitude,:user_longitude,:user_map_location,:request_id)";


            $save_panic_info = $conn->prepare($save_panic_info);
            $save_panic_info->bindValue(':user_id', $data->request_details->user_id, PDO::PARAM_STR);
            $save_panic_info->bindValue(':user_latitude', explode( ",",$data->request_details->coordinates)[0], PDO::PARAM_STR);
            $save_panic_info->bindValue(':user_longitude', explode(",",$data->request_details->coordinates)[1], PDO::PARAM_STR);
            $save_panic_info->bindValue(':user_map_location', $data->request_details->user_map_location, PDO::PARAM_STR);
            $save_panic_info->bindValue(':request_id', $request_id, PDO::PARAM_STR);
            $save_panic_info->execute();

            if ($save_panic_info):
                $returnData = msg(1, 200, 'Request successfully submitted, awaiting providers. Ref: '.$request_id);
            else:
                $returnData = msg(0, 500, "Technical Error occurred, please try again.");
            endif;
        } catch (PDOException $e) {
            $returnData = msg(0, 500, "$data");
        }
endif;
echo json_encode($returnData);

