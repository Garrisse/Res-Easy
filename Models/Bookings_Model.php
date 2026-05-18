<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the bookings of spaces of the residence. -->

<?php
    class Bookings_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_bookings'");
            
            if (mysqli_num_rows($check) == 0){
                $this->connection->query("
                    CREATE TABLE reseasy_bookings (
                        booking_id  INT AUTO_INCREMENT PRIMARY KEY,
                        user_id     INT NOT NULL,
                        space_id    INT NOT NULL,
                        book_date   DATE NOT NULL,
                        start_time  TIME NOT NULL,
                        end_time    TIME NOT NULL,
                        status      ENUM('confirmed','cancelled','pending') DEFAULT 'confirmed',
                        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAll() {
            $rows = [];

            $result = $this->connection->query(
                "SELECT bookingsTable.*, spacesTable.name as space_name, COALESCE(bkRes.name, bkSta.name) as user_name, COALESCE(bkRes.surname, bkSta.surname) as surname, bkRes.room_number as room_number
                FROM reseasy_bookings bookingsTable
                JOIN reseasy_spaces spacesTable ON bookingsTable.space_id = spacesTable.space_id
                JOIN reseasy_users usersTable ON bookingsTable.user_id = usersTable.user_id
                LEFT JOIN reseasy_residents bkRes ON usersTable.user_id = bkRes.user_id
                LEFT JOIN reseasy_staff     bkSta ON usersTable.user_id = bkSta.user_id
                ORDER BY bookingsTable.book_date DESC, bookingsTable.start_time DESC
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getByUser($userId) {
            $userId = intval($userId);
            
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT bookingsTable.*, spacesTable.name as space_name, spacesTable.type as space_type, spacesTable.floor
                FROM reseasy_bookings bookingsTable
                JOIN reseasy_spaces spacesTable ON bookingsTable.space_id = spacesTable.space_id
                WHERE bookingsTable.user_id=$userId
                ORDER BY bookingsTable.book_date DESC, bookingsTable.start_time DESC
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }

            return $rows;
        }

        public function getUpcomingBookings($userId) {
            $userId = intval($userId);
            $now = date('Y-m-d');
            
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT bookingsTable.*, spacesTable.name as space_name, spacesTable.type as space_type
                FROM reseasy_bookings bookingsTable
                JOIN reseasy_spaces spacesTable ON bookingsTable.space_id = spacesTable.space_id
                WHERE bookingsTable.user_id=$userId AND bookingsTable.book_date >= '$now' AND bookingsTable.status='confirmed'
                ORDER BY bookingsTable.book_date ASC, bookingsTable.start_time ASC
                LIMIT 5
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }

            return $rows;
        }

        public function getByDate($date) {
            $date = mysqli_real_escape_string($this->connection, $date);
            
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT bookingsTable.*, spacesTable.name as space_name, COALESCE(bkRes.name, bkSta.name) as user_name, COALESCE(bkRes.surname, bkSta.surname) as surname
                FROM reseasy_bookings bookingsTable
                JOIN reseasy_spaces spacesTable ON bookingsTable.space_id = spacesTable.space_id
                JOIN reseasy_users usersTable ON bookingsTable.user_id = usersTable.user_id
                LEFT JOIN reseasy_residents bkRes ON usersTable.user_id = bkRes.user_id
                LEFT JOIN reseasy_staff     bkSta ON usersTable.user_id = bkSta.user_id
                WHERE bookingsTable.book_date='$date' AND bookingsTable.status='confirmed'
                ORDER BY bookingsTable.start_time ASC
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }

            return $rows;
        }

        public function countTodaysBookings() {
            $today = date('Y-m-d');
            
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_bookings WHERE book_date='$today' AND status='confirmed'");
            $row = $result->fetch_assoc();
            
            return $row['cnt'];
        }

        // ----- ACTION METHODS -----
        public function create($userId, $spaceId, $date, $startTime, $endTime) {
            $userId    = intval($userId);
            $spaceId   = intval($spaceId);
            $date      = mysqli_real_escape_string($this->connection, $date);
            $startTime = mysqli_real_escape_string($this->connection, $startTime);
            $endTime   = mysqli_real_escape_string($this->connection, $endTime);
            
            $this->connection->query("INSERT INTO reseasy_bookings (user_id, space_id, book_date, start_time, end_time) VALUES ($userId, $spaceId, '$date', '$startTime', '$endTime')");
            
            return $this->connection->insert_id;
        }

        public function hasConflict($spaceId, $date, $startTime, $endTime, $excludeId = 0) {
            $spaceId   = intval($spaceId);
            $date      = mysqli_real_escape_string($this->connection, $date);
            $startTime = mysqli_real_escape_string($this->connection, $startTime);
            $endTime   = mysqli_real_escape_string($this->connection, $endTime);
            $excludeId = intval($excludeId);
            
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_bookings WHERE space_id=$spaceId AND book_date='$date' AND status='confirmed' AND booking_id != $excludeId AND start_time < '$endTime' AND end_time > '$startTime'");
            $row = $result->fetch_assoc();
            
            return $row['cnt'] > 0;
        }

        public function cancel($bookingId, $userId) {
            $bookingId = intval($bookingId);
            $userId    = intval($userId);
            
            $this->connection->query("UPDATE reseasy_bookings SET status='cancelled' WHERE booking_id=$bookingId AND user_id=$userId");
        }
    }
?>