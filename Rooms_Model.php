<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the Rooms table, entities that belong to a residence and are rented by residents via the Renting table. -->

<?php
    class Rooms_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_rooms'");

            if (mysqli_num_rows($check) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_rooms (
                        room_id        INT AUTO_INCREMENT PRIMARY KEY,
                        residence_id   VARCHAR(20)  NOT NULL,
                        name           VARCHAR(50)  NOT NULL,
                        type           ENUM('single','double','studio','suite','other') NOT NULL DEFAULT 'single',
                        location       VARCHAR(100),
                        capacity       INT NOT NULL DEFAULT 1,
                        is_rentable    BOOLEAN DEFAULT TRUE,
                        description    TEXT,
                        FOREIGN KEY (residence_id) REFERENCES reseasy_residence(residence_code) ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllRooms() {
            $rooms = [];

            $result = $this->connection->query(
                "SELECT roomsTable.*, residenceTable.name as residence_name
                 FROM reseasy_rooms roomsTable
                 JOIN reseasy_residence residenceTable ON roomsTable.residence_id = residenceTable.residence_code
                 ORDER BY roomsTable.name ASC"
            );

            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }

            return $rooms;
        }

        public function getRoomById($roomId) {
            $roomId = intval($roomId);
            
            $result = $this->connection->query("SELECT * FROM reseasy_rooms WHERE room_id=$roomId");
            
            return mysqli_fetch_assoc($result);
        }

        public function getRoomsByRentable() {
            $rooms = [];
            
            $result = $this->connection->query("SELECT * FROM reseasy_rooms WHERE is_rentable=TRUE ORDER BY name ASC");
            
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }

            return $rooms;
        }

        public function getRoomsByResidence($residenceCode) {
            $residenceCode = mysqli_real_escape_string($this->connection, $residenceCode);
            $rooms = [];
            
            $result = $this->connection->query("SELECT * FROM reseasy_rooms WHERE residence_id='$residenceCode' ORDER BY name ASC");

            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }

            return $rooms;
        }

        public function countTotalRooms() {
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_rooms");

            return $result->fetch_assoc()['cnt'];
        }

        // ----- ACTION METHODS -----
        public function create($residenceId, $name, $type, $location, $capacity, $description) {
            $residenceId = mysqli_real_escape_string($this->connection, $residenceId);
            $name        = mysqli_real_escape_string($this->connection, $name);
            $type        = mysqli_real_escape_string($this->connection, $type);
            $location    = mysqli_real_escape_string($this->connection, $location);
            $capacity    = intval($capacity);
            $description = mysqli_real_escape_string($this->connection, $description);

            $this->connection->query("INSERT INTO reseasy_rooms (residence_id, name, type, location, capacity, description) VALUES ('$residenceId','$name','$type','$location',$capacity,'$description')");
            
            return $this->connection->insert_id;
        }

        public function setRentable($roomId, $isRentable) {
            $roomId     = intval($roomId);
            $isRentable = $isRentable ? 'TRUE' : 'FALSE';
            
            $this->connection->query("UPDATE reseasy_rooms SET is_rentable=$isRentable WHERE room_id=$roomId");
        }

        public function delete($roomId) {
            $roomId = intval($roomId);
            
            $this->connection->query("DELETE FROM reseasy_rooms WHERE room_id=$roomId");
        }
    }
?>
