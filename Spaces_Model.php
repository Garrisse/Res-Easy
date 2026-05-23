<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the bookable spaces of the residence (study rooms, cinema, basement,...). -->

<?php
    class Spaces_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_spaces'");
            
            if (mysqli_num_rows($check) === 0){
                $this->connection->query("
                    CREATE TABLE reseasy_spaces (
                        space_id    INT AUTO_INCREMENT PRIMARY KEY,
                        name        VARCHAR(100) NOT NULL,
                        type        ENUM('study_room','cinema','basement','kitchen','gym','library','rooftop', 'other') NOT NULL,
                        capacity    INT NOT NULL DEFAULT 1,
                        description TEXT,
                        is_bookable TINYINT(1) DEFAULT 1,
                        floor        VARCHAR(10),
                        residence_id VARCHAR(20) NOT NULL,
                        FOREIGN KEY (residence_id) REFERENCES reseasy_residence(residence_code) ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllSpaces() {
            $spaces = [];
            
            $result = $this->connection->query("SELECT * FROM reseasy_spaces ORDER BY type, name");
            
            while ($row = $result->fetch_assoc()) { 
                $spaces[] = $row; 
            }
            
            return $spaces;
        }

        public function getSpaceById($id) {
            $id = intval($id);
            
            $result = $this->connection->query("SELECT * FROM reseasy_spaces WHERE space_id=$id");
            
            return mysqli_fetch_assoc($result);
        }

        public function getSpacesByBookable() {
            $spaces = [];
            
            $result = $this->connection->query("SELECT * FROM reseasy_spaces WHERE is_bookable=TRUE ORDER BY type, name");
            
            while ($row = $result->fetch_assoc()) {
                $spaces[] = $row; 
            }
            
            return $spaces;
        }
    }
?>