
<?php
require __DIR__.'/../jwt/JWT.php';
require __DIR__.'/../jwt/ExpiredException.php';
require __DIR__.'/../jwt/SignatureInvalidException.php';
require __DIR__.'/../jwt/BeforeValidException.php';
require __DIR__.'/Database.php';


use \Firebase\JWT\JWT;

class JwtHandler {
    protected $jwt_secrect;
    protected $token;
    protected $issuedAt;
    protected $expire;
    protected $jwt;
    protected $user;


    public function __construct()
    {
        $db_connection = new Database();
        $conn = $db_connection->dbConnection();
        // set your default time-zone
        $this->issuedAt = time();
        
        // Token Validity
        $email=$_SESSION['email'];
        $fetch_user_by_email = "SELECT * FROM `user_access` where email='$email'";
        $query_stmt = $conn->prepare($fetch_user_by_email);
        $query_stmt->execute();
        $user_details =  $query_stmt->fetch(PDO::FETCH_ASSOC);
        $expire_at =$user_details['token_expire_time'];
        $this->expire = $this->issuedAt + $expire_at;

        // Set secret or signature
        $this->jwt_secrect = $user_details['random_code'];
    }

    // ENCODING THE TOKEN
    public function _jwt_encode_data($iss,$data){

        $this->token = array(
            //Adding the identifier to the token (who issue the token)
            "iss" => $iss,
            // Adding the current timestamp to the token, for identifying that when the token was issued.
            "iat" => $this->issuedAt,
            // Token expiration
            "exp" => $this->expire,
            // Payload
            "data"=> $data
        );

        $this->jwt = JWT::encode($this->token, $this->jwt_secrect);
        return $this->jwt;

    }

    protected function _errMsg($msg){
        return [
            "auth" => 0,
            "message" => $msg
        ];
    }
    
    //DECODING THE TOKEN
    public function _jwt_decode_data($jwt_token){
        try{
            $decode = JWT::decode($jwt_token, $this->jwt_secrect, array('HS256'));
            return [
                "auth" => 1,
                "data" => $decode->data
            ];
        }
        catch(\Firebase\JWT\ExpiredException $e){
            return $this->_errMsg($e->getMessage());
        }
        catch(\Firebase\JWT\SignatureInvalidException $e){
            return $this->_errMsg($e->getMessage());
        }
        catch(\Firebase\JWT\BeforeValidException $e){
            return $this->_errMsg($e->getMessage());
        }
        catch(\DomainException $e){
            return $this->_errMsg($e->getMessage());
        }
        catch(\InvalidArgumentException $e){
            return $this->_errMsg($e->getMessage());
        }
        catch(\UnexpectedValueException $e){
            return $this->_errMsg($e->getMessage());
        }

    }
}
