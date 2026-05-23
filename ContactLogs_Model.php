<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the Contact Logs table, the middle table that records calls between staff and residents. -->

<?php
    class ContactLogs_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_contact_logs'");

            if (mysqli_num_rows($check) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_contact_logs (
                        log_id      INT AUTO_INCREMENT PRIMARY KEY,
                        caller_id   INT      NOT NULL,
                        called_id   INT      NOT NULL,
                        timestamp   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        description TEXT,
                        FOREIGN KEY (caller_id) REFERENCES reseasy_staff(staff_id)         ON DELETE CASCADE,
                        FOREIGN KEY (called_id) REFERENCES reseasy_residents(resident_id)  ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllLogs() {
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT clogsTable.*, staffTable.name as caller_name, staffTable.surname as caller_surname, residentsTable.name as called_name, residentsTable.surname as called_surname, usersTable.email  as called_email
                 FROM reseasy_contact_logs clogsTable
                 JOIN reseasy_staff staffTable ON clogsTable.caller_id = staffTable.staff_id
                 JOIN reseasy_residents residentsTable ON clogsTable.called_id = residentsTable.resident_id
                 JOIN reseasy_users usersTable ON residentsTable.user_id = usersTable.user_id
                 ORDER BY clogsTable.timestamp DESC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        // Logs made by a staff member
        public function getLogsByCaller($staffId) {
            $staffId = intval($staffId);
            $rows    = [];

            $result  = $this->connection->query(
                "SELECT clogsTable.*, residentsTable.name as called_name, residentsTable.surname as called_surname
                 FROM reseasy_contact_logs clogsTable
                 JOIN reseasy_residents residentsTable ON clogsTable.called_id = residentsTable.resident_id
                 WHERE clogsTable.caller_id=$staffId
                 ORDER BY clogsTable.timestamp DESC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        // Logs received by a resident
        public function getLogsByCalled($residentId) {
            $residentId = intval($residentId);
            $rows       = [];
            
            $result     = $this->connection->query(
                "SELECT clogsTable.*, staffTable.name as caller_name, staffTable.surname as caller_surname, staffTable.staff_type
                 FROM reseasy_contact_logs clogsTable
                 JOIN reseasy_staff staffTable ON clogsTable.caller_id = staffTable.staff_id
                 WHERE clogsTable.called_id=$residentId
                 ORDER BY clogsTable.timestamp DESC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        public function getRecentLogs($limit = 10) {
            $limit = intval($limit);
            $rows  = [];
            
            $result = $this->connection->query(
                "SELECT clogsTable.*, staffTable.name as caller_name, staffTable.surname as caller_surname, residentsTable.name as called_name, residentsTable.surname as called_surname
                 FROM reseasy_contact_logs clogsTable
                 JOIN reseasy_staff     staffTable ON clogsTable.caller_id = staffTable.staff_id
                 JOIN reseasy_residents residentsTable ON clogsTable.called_id = residentsTable.resident_id
                 ORDER BY clogsTable.timestamp DESC
                 LIMIT $limit"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        // ----- ACTION METHODS -----
        public function create($callerId, $calledId, $description, $timestamp = null) {
            $callerId    = intval($callerId);
            $calledId    = intval($calledId);
            $description = mysqli_real_escape_string($this->connection, $description);
            $ts          = $timestamp
                ? "'" . mysqli_real_escape_string($this->connection, $timestamp) . "'"
                : 'NOW()';

            $this->connection->query("INSERT INTO reseasy_contact_logs (caller_id, called_id, timestamp, description) VALUES ($callerId, $calledId, $ts, '$description')");
            
            return $this->connection->insert_id;
        }

        public function delete($logId) {
            $logId = intval($logId);
            
            $this->connection->query("DELETE FROM reseasy_contact_logs WHERE log_id=$logId");
        }
    }
?>
