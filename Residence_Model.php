<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the Residence table, the main entity of the project from which all the others depend(rooms, spaces, laundry machines,...). -->

<?php
    class Residence_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_residence'");

            if (mysqli_num_rows($check) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_residence (
                        residence_code VARCHAR(20)  PRIMARY KEY,
                        name           VARCHAR(100) NOT NULL,
                        ownership      VARCHAR(100),
                        target         VARCHAR(100),
                        description    TEXT,
                        address        VARCHAR(200),
                        city           VARCHAR(100),
                        postal_code    VARCHAR(20),
                        country        VARCHAR(100),
                        capacity       INT NOT NULL DEFAULT 0
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllResidences() {
            $result = $this->connection->query("SELECT * FROM reseasy_residence LIMIT 1");
            
            return mysqli_fetch_assoc($result);
        }

        public function getResidenceByCode($code) {
            $code = mysqli_real_escape_string($this->connection, $code);
            
            $result = $this->connection->query("SELECT * FROM reseasy_residence WHERE residence_code='$code'");
            
            return mysqli_fetch_assoc($result);
        }

        // ----- ACTION METHODS -----
        public function update($code, $name, $ownership, $target, $description, $address, $city, $postalCode, $country, $capacity) {
            $code        = mysqli_real_escape_string($this->connection, $code);
            $name        = mysqli_real_escape_string($this->connection, $name);
            $ownership   = mysqli_real_escape_string($this->connection, $ownership);
            $target      = mysqli_real_escape_string($this->connection, $target);
            $description = mysqli_real_escape_string($this->connection, $description);
            $address     = mysqli_real_escape_string($this->connection, $address);
            $city        = mysqli_real_escape_string($this->connection, $city);
            $postalCode  = mysqli_real_escape_string($this->connection, $postalCode);
            $country     = mysqli_real_escape_string($this->connection, $country);
            $capacity    = intval($capacity);

            $this->connection->query("UPDATE reseasy_residence SET name='$name', ownership='$ownership', target='$target', description='$description', address='$address', city='$city',postal_code='$postalCode', country='$country', capacity=$capacity WHERE residence_code='$code'");
        }
    }
?>
