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
    $authorization = $allHeaders['Authorization'];
    $decode = JWT::decode(explode(" ", $authorization)[1], $allHeaders['secret'], array('HS256'));
    $agent = $decode->data->client;
    try {
        if (isset($decode->data->username)):
            //Save Panic Button Request
            $permitted_request_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            $request_id = substr(str_shuffle($permitted_request_chars), 0, 20);

            //Get the ID of the New Registered user and store them in User Access
            $get_countries = "select * from system_countries";
            $get_countries_stmt = $conn->prepare($get_countries);
            $get_countries_stmt->execute();

            $countries = [];
            $count = 0;
            foreach($get_countries_stmt as $single_country){
                $countries[] = [
                    'id'=>$single_country['id'],
                    'code'=>$single_country['code'],
                    'name'=>$single_country['name'],
                    'phone'=>$single_country['phone'],
                    'capital'=>$single_country['capital']
                ];
            }

            $all_countries = [
                'countries'=>$countries
            ];

            if ($get_countries_stmt):
                $returnData = msg(1, 200, $all_countries);
            else:
                $returnData = msg(0, 500, "Technical Error occurred, please try again.");
            endif;
        endif;
    } catch (PDOException $e) {
        $returnData = msg(0, 500, $e->getMessage());
    }
endif;
echo json_encode($returnData);
