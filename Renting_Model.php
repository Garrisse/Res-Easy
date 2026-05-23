<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the Renting Table, the middle table between rooms and residents (a resident rents a room).-->
 
<?php
    class Renting_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_renting'");

            if (mysqli_num_rows($check) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_renting (
                        rent_id           INT AUTO_INCREMENT PRIMARY KEY,
                        res_id            INT  NOT NULL,
                        room_id           INT  NOT NULL,
                        registration_date DATE NOT NULL,
                        contract_start    DATE NOT NULL,
                        contract_end      DATE NOT NULL,
                        FOREIGN KEY (res_id)  REFERENCES reseasy_residents(resident_id) ON DELETE CASCADE,
                        FOREIGN KEY (room_id) REFERENCES reseasy_rooms(room_id)         ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllRentals() {
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT rentingTable.*, roomsTable.name as room_name, roomsTable.type as room_type, residentsTable.name as resident_name, residentsTable.surname as resident_surname, usersTable.email
                 FROM reseasy_renting rentingTable
                 JOIN reseasy_rooms roomsTable ON rentingTable.room_id = roomsTable.room_id
                 JOIN reseasy_residents residentsTable ON rentingTable.res_id  = residentsTable.resident_id
                 JOIN reseasy_users usersTable ON residentsTable.user_id = usersTable.user_id
                 ORDER BY rentingTable.contract_end DESC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        public function getRentalByRoom($roomId) {
            $roomId = intval($roomId);
            $rows = [];

            $result = $this->connection->query(
                "SELECT rentingTable.*, residentsTable.name as resident_name, residentsTable.surname as resident_surname
                 FROM reseasy_renting rentingTable
                 JOIN reseasy_residents residentsTable ON rentingTable.res_id = residentsTable.resident_id
                 WHERE rentingTable.room_id=$roomId
                 ORDER BY rentingTable.contract_start DESC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        // History of all residents contracts of the residence
        public function getRentalByResident($residentId) {
            $residentId = intval($residentId);
            $rows = [];

            $result = $this->connection->query(
                "SELECT rentingTable.*, roomsTable.name as room_name, roomsTable.type as room_type
                 FROM reseasy_renting rentingTable
                 JOIN reseasy_rooms roomsTable ON rentingTable.room_id = roomsTable.room_id
                 WHERE rentingTable.res_id=$residentId
                 ORDER BY rentingTable.contract_start DESC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        // Contracts of residents that nowaday's are in the residence
        public function getActiveRentalByResident($residentId) {
            $residentId = intval($residentId);
            $today = date('Y-m-d');
            
            $result = $this->connection->query(
                "SELECT rentingTable.*, roomsTable.name as room_name, roomsTable.type as room_type, roomsTable.location
                 FROM reseasy_renting rentingTable
                 JOIN reseasy_rooms roomsTable ON rentingTable.room_id = roomsTable.room_id
                 WHERE rentingTable.res_id=$residentId AND rentingTable.contract_start <= '$today' AND rentingTable.contract_end   >= '$today'
                 LIMIT 1"
            );

            return mysqli_fetch_assoc($result);
        }

        // ----- ACTION METHODS -----
        public function create($resId, $roomId, $registrationDate, $contractStart, $contractEnd) {
            $resId            = intval($resId);
            $roomId           = intval($roomId);
            $registrationDate = mysqli_real_escape_string($this->connection, $registrationDate);
            $contractStart    = mysqli_real_escape_string($this->connection, $contractStart);
            $contractEnd      = mysqli_real_escape_string($this->connection, $contractEnd);

            $this->connection->query("INSERT INTO reseasy_renting (res_id, room_id, registration_date, contract_start, contract_end) VALUES ($resId, $roomId, '$registrationDate', '$contractStart', '$contractEnd')");

            return $this->connection->insert_id;
        }

        public function delete($rentId) {
            $rentId = intval($rentId);
            
            $this->connection->query("DELETE FROM reseasy_renting WHERE rent_id=$rentId");
        }
    }
?>
