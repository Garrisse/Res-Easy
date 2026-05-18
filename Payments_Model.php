<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about payments like the monthly fees. -->

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
                        user_id      INT NOT NULL,
                        amount       DECIMAL(8,2) NOT NULL,
                        period_start DATE NOT NULL,
                        period_end   DATE NOT NULL,
                        status       ENUM('paid','pending','overdue') DEFAULT 'pending',
                        paid_at      DATETIME DEFAULT NULL,
                        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAll() {
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT paymentsTable.*, COALESCE(pmRes.name, pmSta.name) as name, COALESCE(pmRes.surname, pmSta.surname) as surname, pmRes.room_number as room_number
                FROM reseasy_payments paymentsTable
                JOIN reseasy_users usersTable ON paymentsTable.user_id = usersTable.user_id
                LEFT JOIN reseasy_residents pmRes ON usersTable.user_id = pmRes.user_id
                LEFT JOIN reseasy_staff     pmSta ON usersTable.user_id = pmSta.user_id
                ORDER BY paymentsTable.period_start DESC
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getByUser($userId) {
            $userId = intval($userId);
            $rows = [];
            
            $result = $this->connection->query("SELECT * FROM reseasy_payments WHERE user_id=$userId ORDER BY period_start DESC");
            
            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getPendingByUser($userId) {
            $userId = intval($userId);
            $rows = [];

            $result = $this->connection->query("SELECT * FROM reseasy_payments WHERE user_id=$userId AND status IN ('pending','overdue') ORDER BY period_start ASC");
            
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
        public function pay($paymentId, $userId) {
            $paymentId = intval($paymentId);
            $userId    = intval($userId);
            $now = date('Y-m-d H:i:s');
            
            $this->connection->query("UPDATE reseasy_payments SET status='paid', paid_at='$now' WHERE payment_id=$paymentId AND user_id=$userId");
            
            return $this->connection->affected_rows > 0;
        }
    }
?>