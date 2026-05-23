<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the Machines Table and its usage Table (middle one between residents and machines). -->

<?php
    class Laundry_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $checkMachines = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_machines'");
            
            if (mysqli_num_rows($checkMachines) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_machines (
                        machine_id      INT AUTO_INCREMENT PRIMARY KEY,
                        name            VARCHAR(50) NOT NULL,
                        type            ENUM('washer','dryer') NOT NULL,
                        status          ENUM('available','in_use','broken') DEFAULT 'available',
                        current_user_id INT DEFAULT NULL,
                        cycle_end_time  DATETIME DEFAULT NULL,
                        floor           VARCHAR(10),
                        residence_id    VARCHAR(20) NOT NULL,
                        FOREIGN KEY (residence_id)    REFERENCES reseasy_residence(residence_code) ON DELETE CASCADE,
                        FOREIGN KEY (current_user_id) REFERENCES reseasy_users(user_id)            ON DELETE SET NULL
                    );
                ");
            }

            $checkUsage = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_laundry_usage'");
            
            if (mysqli_num_rows($checkUsage) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_laundry_usage (
                        cycle_id    INT AUTO_INCREMENT PRIMARY KEY,
                        user_id     INT NOT NULL,
                        machine_id  INT NOT NULL,
                        start_time  DATETIME NOT NULL,
                        end_time    DATETIME NOT NULL,
                        duration_min INT NOT NULL DEFAULT 45,
                        FOREIGN KEY (user_id)    REFERENCES reseasy_users(user_id)        ON DELETE CASCADE,
                        FOREIGN KEY (machine_id) REFERENCES reseasy_machines(machine_id) ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllMachines() {
            $rows = [];

            $result = $this->connection->query("SELECT * FROM reseasy_machines ORDER BY type, machine_id");
            
            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getMachineById($id) {
            $id = intval($id);
            
            $result = $this->connection->query("SELECT * FROM reseasy_machines WHERE machine_id=$id");
            
            return $result->fetch_assoc();
        }
        public function getActiveMachineByUser($userId) {
            $userId = intval($userId);
            $rows = [];

            $result = $this->connection->query("SELECT * FROM reseasy_machines WHERE current_user_id=$userId AND status='in_use'");
            
            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getUsageHistoryByUser($userId) {
            $userId = intval($userId);
            $rows = [];

            $result = $this->connection->query(
                "SELECT laundryUsageTable.*, machinesTable.name as machine_name, machinesTable.type as machine_type
                FROM reseasy_laundry_usage laundryUsageTable
                JOIN reseasy_machines machinesTable ON laundryUsageTable.machine_id = machinesTable.machine_id
                WHERE laundryUsageTable.user_id=$userId ORDER BY laundryUsageTable.start_time DESC LIMIT 10
            ");
            
            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }

            return $rows;
        }

        public function countActiveMachines() {
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_machines WHERE status='in_use'");
            $row = $result->fetch_assoc(); return $row['cnt'];
        }

        public function countBrokenMachines() {
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_machines WHERE status='broken'");
            $row = $result->fetch_assoc(); return $row['cnt'];
        }

        public function countTotalMachines() {
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_machines");
            $row = $result->fetch_assoc(); return $row['cnt'];
        }

        // ----- ACTION METHODS -----
        public function startCycle($machineId, $userId, $durationMin) {
            $machineId   = intval($machineId);
            $userId      = intval($userId);
            $durationMin = intval($durationMin);
            $start   = date('Y-m-d H:i:s');
            $endTime = date('Y-m-d H:i:s', strtotime("+$durationMin minutes"));                                                             // Determines the end time of the cycle by adding the duration to the current time

            $this->connection->query("UPDATE reseasy_machines SET status='in_use', current_user_id=$userId, cycle_end_time='$endTime' WHERE machine_id=$machineId AND status='available'"); // Only start the cycle if the machine is in the "available" status
            
            if ($this->connection->affected_rows > 0) {                                                                                     // Checks if the previous command made any change (if the machine was in use before, it won't enter in this if)
                                                                                                                                            // and if the cycle was successfully started, it registers the usage in the Laundry Usage Table
                $this->connection->query("INSERT INTO reseasy_laundry_usage (user_id, machine_id, start_time, end_time, duration_min)
                    VALUES ($userId, $machineId, '$start', '$endTime', $durationMin)");
                
                return true;
            }

            return false;
        }

        public function manuallyFinishCycle($machineId) {
            $machineId = intval($machineId);
            
            $this->connection->query("UPDATE reseasy_machines SET status='available', current_user_id=NULL, cycle_end_time=NULL WHERE machine_id=$machineId");
        }

        // Automated method to be executed minute by minute until it is the end_time that was determined when the cycle was started. 
        public function autoFinishCycle() {
            $now = date('Y-m-d H:i:s');

            $this->connection->query("UPDATE reseasy_machines SET status='available', current_user_id=NULL, cycle_end_time=NULL WHERE status='in_use' AND cycle_end_time <= '$now'");
        }
    }
?>