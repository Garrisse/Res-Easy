<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about payments linked with renting contracts like the monthly fees. -->

<?php
    class Payments_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_payments'");
            
            if (mysqli_num_rows($check) == 0){
                $this->connection->query("
                    CREATE TABLE reseasy_payments (
                        payment_id   INT AUTO_INCREMENT PRIMARY KEY,
                        rent_id      INT NOT NULL,
                        amount       DECIMAL(8,2) NOT NULL,
                        period_start DATE NOT NULL,
                        period_end   DATE NOT NULL,
                        status       ENUM('paid','pending','overdue') DEFAULT 'pending',
                        paid_at      DATETIME DEFAULT NULL,
                        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (rent_id) REFERENCES reseasy_renting(rent_id) ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllPayments() {
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT paymentsTable.*, residentsTable.name as name, esidentsTable.surname as surname, residentsTable.room_number as room_number, roomsTable.name as room_name, usersTable.email
                FROM reseasy_payments paymentsTable
                JOIN reseasy_renting   rentingTable  ON paymentsTable.rent_id    = rentingTable.rent_id
                JOIN reseasy_rooms     roomsTable     ON rentingTable.room_id   = roomsTable.room_id
                JOIN reseasy_residents residentsTable ON rentingTable.res_id    = residentsTable.resident_id
                JOIN reseasy_users     usersTable     ON residentsTable.user_id  = usersTable.user_id
                ORDER BY paymentsTable.period_start DESC
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getPaymentsByUser($userId) {
            $userId = intval($userId);
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT paymentsTable.*, roomsTable.name as room_name, rentingTable.contract_start, rentingTable.contract_end
                FROM reseasy_payments paymentsTable
                JOIN reseasy_renting   rentingTable  ON paymentsTable.rent_id    = rentingTable.rent_id
                JOIN reseasy_rooms     roomsTable     ON rentingTable.room_id   = roomsTable.room_id
                JOIN reseasy_residents residentsTable ON rentingTable.res_id    = residentsTable.resident_id
                WHERE residentsTable.user_id=$userId
                ORDER BY paymentsTable.period_start DESC
            "); 
               
            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getPendingPaymentsByUser($userId) {
            $userId = intval($userId);
            $rows = [];

            $result = $this->connection->query(
                "SELECT paymentsTable.*, roomsTable.name as room_name
                FROM reseasy_payments paymentsTable
                JOIN reseasy_renting   rentingTable  ON paymentsTable.rent_id    = rentingTable.rent_id
                JOIN reseasy_rooms     roomsTable     ON rentingTable.room_id   = roomsTable.room_id
                JOIN reseasy_residents residentsTable ON rentingTable.res_id    = residentsTable.resident_id
                WHERE residentsTable.user_id=$userId AND paymentsTable.status IN ('pending','overdue')
                ORDER BY paymentsTable.period_start ASC
            ");
            
            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }

            return $rows;
        }

        public function countTotalPendingPayments() {
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_payments WHERE status IN ('pending','overdue')");
            $row = $result->fetch_assoc(); 

            return $row['cnt'];
        }

        // ----- ACTION METHODS -----
        // Mark a payment record as paid
        public function pay($paymentId, $userId) {
            $paymentId = intval($paymentId);
            $userId    = intval($userId);
            $now       = date('Y-m-d H:i:s');
            
            $this->connection->query(
                "UPDATE reseasy_payments paymentsTable
                JOIN reseasy_renting   rentingTable  ON paymentsTable.rent_id  = rentingTable.rent_id
                JOIN reseasy_residents residentsTable ON rentingTable.res_id  = residentsTable.resident_id
                SET paymentsTable.status='paid', paymentsTable.paid_at='$now'
                WHERE paymentsTable.payment_id=$paymentId AND residentsTable.user_id=$userId
            ");

            return $this->connection->affected_rows > 0;
        }

        // Create a payment record for a renting contract
        public function create($rentId, $amount, $periodStart, $periodEnd) {
            $rentId      = intval($rentId);
            $amount      = floatval($amount);
            $periodStart = mysqli_real_escape_string($this->connection, $periodStart);
            $periodEnd   = mysqli_real_escape_string($this->connection, $periodEnd);
            
            $this->connection->query("INSERT INTO reseasy_payments (rent_id, amount, period_start, period_end) VALUES ($rentId, $amount, '$periodStart', '$periodEnd')");
            
            return $this->connection->insert_id;
        }
    }
?>