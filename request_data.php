<?php
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

if ($_SERVER["REQUEST_METHOD"] != "GET"):
    $returnData = msg(0, 404, 'Page Not Found!');
else:
    if(isset($allHeaders['Authorization'])):
        $authorization = $allHeaders['Authorization'];
        $decode = JWT::decode(explode(" ", $authorization)[1], $allHeaders['secret'], array('HS256'));
        $agent = $decode->data->client;
    else:
        echo "";
    endif;

    try {
            //Save Panic Button Request
            $permitted_request_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            $request_id = substr(str_shuffle($permitted_request_chars), 0, 20);

            //Get the ID of the New Registered user and store them in User Access
            $get_requests = "select user_requests.id, request_id, first_name, last_name,system_countries.name as country, 
                                    user_requests.created_at, user_requests.status 
                                from user_requests, user_details, system_countries
                                where user_details.id = user_requests.user_id
                                and user_details.country_id = system_countries.id";
            $get_requests_stmt = $conn->prepare($get_requests);
            $get_requests_stmt->execute();

            $requests = [];
            $count = 0;
            foreach($get_requests_stmt as $single_request){
                $requests[] = [
                    'id'=>$single_request['id'],
                    'request'=>$single_request['request_id'],
                    'user'=>$single_request['first_name']." ".$single_request['last_name'],
                    'country'=>$single_request['country'],
                    'time'=>$single_request['created_at'],
                    'status'=>$single_request['status']
                ];
            }

            if ($get_requests_stmt):
                $returnData = $requests;
            else:
                $returnData = msg(0, 500, "Technical Error occurred, please try again.");
            endif;
    } catch (PDOException $e) {
        $returnData = msg(0, 500, $e->getMessage());
    }
endif;
echo json_encode($returnData);
