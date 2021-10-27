<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Secret, Authorization, X-Requested-With");

function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}
require __DIR__ . '/classes/JwtHandler.php';

$db_connection = new Database();
$conn = $db_connection->dbConnection();

$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT EQUAL TO POST
if ($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0, 404, 'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif (!isset($data->email)
    || !isset($data->password)
    || empty(trim($data->email))
    || empty(trim($data->password))
):

    $fields = ['fields' => ['email', 'password']];
    $returnData = [
        'access_token' => '',
        'status' => 'Error',
        'message' => 'Invalid Credentials'
    ];

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $email = trim($data->email);
    $_SESSION['email']=$email;
    $password = $data->password;
    $issuedAt = date('Y-m-d H:i:s');

    try {
        $fetch_user_by_email = "SELECT * FROM `user_access` WHERE `email`=:email";
        $query_stmt = $conn->prepare($fetch_user_by_email);
        $query_stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $query_stmt->execute();


        // IF THE USER IS FOUNDED BY EMAIL
        if ($query_stmt->rowCount()):
            $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
            $check_password = password_verify($password, $row['password']);
            $iss = $row['url'];
            $secret_key = $row['random_code'];


            // VERIFYING THE PASSWORD (IS CORRECT OR NOT?)
            // IF PASSWORD IS CORRECT THEN SEND THE LOGIN TOKEN
            if ($check_password):

                $jwt = new JwtHandler();
                $token = $jwt->_jwt_encode_data(
                    "$iss",
                    array("user_id" => $row['id'], "username" => $row['email'], "client"=>$row['username'], "random_code"=>$secret_key)
                );

                $returnData = [
                    'access_token' => $token,
                    'message'=> 'Successful'
                ];

            // IF INVALID PASSWORD
            else:
                $returnData = [
                    'access_token' => '',
                    'message'=> 'invalid'
                ];

            endif;

        // IF THE USER IS NOT FOUNDED BY EMAIL THEN SHOW THE FOLLOWING ERROR
        else:
            $returnData = [
                'access_token' => '',
                'message'=> 'Invalid Credentials Provided.'
            ];

        endif;
    } catch (PDOException $e) {
        $returnData = msg(0, 500, $e->getMessage());
    }

    // endif;

endif;

echo json_encode($returnData);
