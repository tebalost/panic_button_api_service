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

    $authorization = $allHeaders['Authorization'];

    $decode = JWT::decode(explode(" ", $authorization)[1], $allHeaders['secret'], array('HS256'));
    $agent = $decode->data->client;

    $email = trim($data->email);
    $username = $data->username;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = msg('Invalid Email Address!');

    else:
        try {
            if (isset($decode->data->username)):

                $check_email = "SELECT * FROM `user_details` WHERE `email`=:email";
                $check_email_stmt = $conn->prepare($check_email);
                $check_email_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $check_email_stmt->execute();

                $check_mobile_number = "SELECT * FROM `user_details` WHERE `mobile`=:mobile_number";
                $check_mobile_number_stmt = $conn->prepare($check_mobile_number);
                $check_mobile_number_stmt->bindValue(':mobile_number', $data->mobile_number, PDO::PARAM_STR);
                $check_mobile_number_stmt->execute();

                $check_username = "SELECT * FROM user_access WHERE `username`=:username";
                $check_username_stmt = $conn->prepare($check_username);
                $check_username_stmt->bindValue(':username', $data->username, PDO::PARAM_STR);
                $check_username_stmt->execute();

                if ($check_email_stmt->rowCount() || $check_mobile_number_stmt->rowCount() || $check_username_stmt->rowCount()):
                    $returnData = msg(0, 422, 'User already exists!');
                else:
                    $save_user = "INSERT INTO `user_details` (`first_name`, `last_name`, `email`, `date_of_birth`, `gender`, `date_of_joining`,
                            `mobile`,`emergency_mobile`,`marital_status`,`user_photo`,`current_address`,`location`,
                            `active_status`,`country_id`, `state`,`zip`,`role_id`, `user_type`)
                                VALUES(:first_name,:last_name,:email,:date_of_birth,:gender,:date_of_joining,
                                       :mobile,:emergency_mobile,:marital_status,:user_photo,
                                       :current_address,:location,:active_status,:country,:province,:zip,
                                       :role_id,:user_type)";

                    $insert_stmt = $conn->prepare($save_user);

                    $username = $data->username;
                    $password = password_hash($data->password, PASSWORD_DEFAULT);

                    $email = trim($data->email);
                    $mobile_number = trim($data->mobile_number);
                    $status = 1;

                    // DATA BINDING
                    $insert_stmt->bindValue(':first_name', htmlspecialchars(strip_tags($data->name)), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':last_name', htmlspecialchars(strip_tags($data->surname)), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':email', trim($data->email), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':mobile', $data->mobile_number, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':emergency_mobile', $data->emergency_number, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':country', $data->country, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':active_status', $status, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':date_of_birth', $data->date_of_birth, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':date_of_joining', date('Y-m-d H:i:s'), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':gender', $data->gender, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':province', $data->city, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':current_address', $data->address_line_1, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':zip', $data->zip_code, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':location', $data->location, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':marital_status', $data->marital_status, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':role_id', $data->role_id, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':user_type', $data->user_type, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':user_photo', $data->user_photo, PDO::PARAM_STR);
                    $insert_stmt->execute();

                    //Get the ID of the New Registered user and store them in User Access
                    $get_user_id = "select max(id) from user_details where email='$email' ";
                    $get_user_id_stmt = $conn->prepare($get_user_id);
                    $get_user_id_stmt->execute();
                    $user_id = $get_user_id_stmt->fetch(PDO::FETCH_ASSOC)['max(id)'];

                    //Save User Login and Access Details
                    $save_login_info = "INSERT INTO `user_access` (`user_id`, `username`, `email`, `password`, `active_status`, `url`)
                                VALUES(:user_id,:username,:email,:password,:active_status,:url)";

                    $save_login_stmt = $conn->prepare($save_login_info);
                    $save_login_stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                    $save_login_stmt->bindValue(':username', $username, PDO::PARAM_STR);
                    $save_login_stmt->bindValue(':email', $data->email, PDO::PARAM_STR);
                    $save_login_stmt->bindValue(':password', $password, PDO::PARAM_STR);
                    $save_login_stmt->bindValue(':active_status', $status, PDO::PARAM_STR);
                    $save_login_stmt->bindValue(':url', $data->third_party_url, PDO::PARAM_STR);
                    $save_login_stmt->execute();

                    if ($save_login_stmt && $insert_stmt):
                        $returnData = msg(1, 200, 'You have successfully registered.');
                    else:
                        $returnData = msg(0, 500, "Technical Error occurred, please try again.");
                    endif;
                endif;
            endif;
        } catch (PDOException $e) {
            $returnData = msg(0, 500, "$authorization"."$data");
        }
    endif;
endif;
echo json_encode($returnData);
