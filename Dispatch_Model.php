<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the Dispatch Table, the middle table between staff and residences (a staff member is assigned to a residence in a relationship 1:N). -->

<?php
    class Dispatch_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_dispatch'");

            if (mysqli_num_rows($check) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_dispatch (
                        dispatch_id  INT AUTO_INCREMENT PRIMARY KEY,
                        residence_id VARCHAR(20) NOT NULL,
                        staff_id     INT         NOT NULL,
                        FOREIGN KEY (residence_id) REFERENCES reseasy_residence(residence_code) ON DELETE CASCADE,
                        FOREIGN KEY (staff_id)     REFERENCES reseasy_staff(staff_id)           ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllDispatches() {
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT dispatchTable.*, residenceTable.name as residence_name, staffTable.name as staff_name, staffTable.surname as staff_surname, staffTable.staff_type
                 FROM reseasy_dispatch dispatchTable
                 JOIN reseasy_residence residenceTable ON dispatchTable.residence_id = residenceTable.residence_code
                 JOIN reseasy_staff staffTable ON dispatchTable.staff_id = staffTable.staff_id
                 ORDER BY residenceTable.name ASC, staffTable.surname ASC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        public function getDispatchesByResidence($residenceCode) {
            $residenceCode = mysqli_real_escape_string($this->connection, $residenceCode);
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT dispatchTable.*, staffTable.name as staff_name, staffTable.surname as staff_surname, staffTable.staff_type, staffTable.phone, staffTable.emerg_callable, usersTable.email
                 FROM reseasy_dispatch dispatchTable
                 JOIN reseasy_staff staffTable ON dispatchTable.staff_id = staffTable.staff_id
                 JOIN reseasy_users usersTable ON staffTable.user_id = usersTable.user_id
                 WHERE dispatchTable.residence_id='$residenceCode'
                 ORDER BY staffTable.surname ASC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        public function getDispatchesByStaff($staffId) {
            $staffId = intval($staffId);
            
            $result  = $this->connection->query(
                "SELECT dispatchTable.*, residenceTable.name as residence_name 
                 FROM reseasy_dispatch dispatchTable
                 JOIN reseasy_residence residenceTable ON dispatchTable.residence_id = residenceTable.residence_code
                 WHERE dispatchTable.staff_id=$staffId
                 LIMIT 1"
            );

            return mysqli_fetch_assoc($result);
        }

        // ----- ACTION METHODS -----
        public function create($residenceId, $staffId) {
            $residenceId = mysqli_real_escape_string($this->connection, $residenceId);
            $staffId     = intval($staffId);

            $this->connection->query("INSERT INTO reseasy_dispatch (residence_id, staff_id) VALUES ('$residenceId', $staffId)");
            
            return $this->connection->insert_id;
        }

        public function delete($dispatchId) {
            $dispatchId = intval($dispatchId);
            
            $this->connection->query("DELETE FROM reseasy_dispatch WHERE dispatch_id=$dispatchId");
        }
    }
?>
