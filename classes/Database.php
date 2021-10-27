<?php
class Database{
    // DATABASE CONNECTION
    private $db_host = 'aura-panic-services-db.cuvohhhemjmz.af-south-1.rds.amazonaws.com';
    private $db_name = 'aura_panic_services';
    private $db_username = 'aura_admin';
    private $db_password = 'Thatho_nul07';
    
    public function dbConnection(){
        
        try{
            $conn = new PDO('mysql:host='.$this->db_host.';dbname='.$this->db_name,$this->db_username,$this->db_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch(PDOException $e){
            echo "Connection error ".$e->getMessage(); 
            exit;
        }
          
    }
}
