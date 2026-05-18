<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the events that can be organized in the residence . -->

<?php
    class Events_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $checkEvents = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_events'");
            
            if (mysqli_num_rows($checkEvents) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_events (
                        event_id     INT AUTO_INCREMENT PRIMARY KEY,
                        organiser_id INT NOT NULL,
                        space_id     INT DEFAULT NULL,
                        name         VARCHAR(150) NOT NULL,
                        type         ENUM('social','study','sports','cultural','party','other') DEFAULT 'social',
                        description  TEXT,
                        start_time   DATETIME NOT NULL,
                        end_time     DATETIME NOT NULL,
                        capacity     INT DEFAULT 20,
                        location_txt VARCHAR(100) DEFAULT NULL,
                        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                ");
            }

            $checkSignups = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_event_signups'");
            
            if (mysqli_num_rows($checkSignups) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_event_signups (
                        signup_id  INT AUTO_INCREMENT PRIMARY KEY,
                        user_id    INT NOT NULL,
                        event_id   INT NOT NULL,
                        signed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_signup (user_id, event_id)
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAll() {
            $rows = [];

            // Due to having the name and surname of the organiser user in the sub-tables, we use the "COALESCE" command to show the one that is not null.
            $result = $this->connection->query(
                "SELECT eventsTable.*, spacesTable.name as space_name, COALESCE(orgRes.name, orgSta.name) as organiser_name, COALESCE(orgRes.surname, orgSta.surname) as organiser_surname,
                    (SELECT COUNT(*) FROM reseasy_event_signups sg WHERE sg.event_id=eventsTable.event_id) as signup_count
                FROM reseasy_events eventsTable
                LEFT JOIN reseasy_spaces spacesTable ON eventsTable.space_id = spacesTable.space_id
                JOIN reseasy_users usersTable ON eventsTable.organiser_id = usersTable.user_id
                LEFT JOIN reseasy_residents orgRes ON usersTable.user_id = orgRes.user_id
                LEFT JOIN reseasy_staff     orgSta ON usersTable.user_id = orgSta.user_id
                ORDER BY eventsTable.start_time ASC
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getById($id) {
            $id = intval($id);

            $result = $this->connection->query(
                "SELECT eventsTable.*, spacesTable.name as space_name, COALESCE(orgRes.name, orgSta.name) as organiser_name, COALESCE(orgRes.surname, orgSta.surname) as organiser_surname
                FROM reseasy_events eventsTable
                LEFT JOIN reseasy_spaces spacesTable ON eventsTable.space_id = spacesTable.space_id
                JOIN reseasy_users usersTable ON eventsTable.organiser_id = usersTable.user_id
                LEFT JOIN reseasy_residents orgRes ON usersTable.user_id = orgRes.user_id
                LEFT JOIN reseasy_staff     orgSta ON usersTable.user_id = orgSta.user_id
                WHERE eventsTable.event_id=$id");
            

            return $result->fetch_assoc();
        }

        public function getByUser($userId) {
            $userId = intval($userId);
            $now = date('Y-m-d H:i:s');
            
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT eventsTable.*, spacesTable.name as space_name
                FROM reseasy_event_signups signupsTable
                JOIN reseasy_events eventsTable ON signupsTable.event_id = eventsTable.event_id
                LEFT JOIN reseasy_spaces spacesTable ON eventsTable.space_id = spacesTable.space_id
                WHERE signupsTable.user_id=$userId AND eventsTable.start_time >= '$now'
                ORDER BY eventsTable.start_time ASC
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getUpcomingEvents($limit = 10) {
            $now = date('Y-m-d H:i:s');
            $limit = intval($limit);
            
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT eventsTable.*, spacesTable.name as space_name, COALESCE(orgRes.name, orgSta.name) as organiser_name, COALESCE(orgRes.surname, orgSta.surname) as organiser_surname,
                    (SELECT COUNT(*) FROM reseasy_event_signups sg WHERE sg.event_id=eventsTable.event_id) as signup_count
                FROM reseasy_events eventsTable
                LEFT JOIN reseasy_spaces spacesTable ON eventsTable.space_id = spacesTable.space_id
                JOIN reseasy_users usersTable ON eventsTable.organiser_id = usersTable.user_id
                LEFT JOIN reseasy_residents orgRes ON usersTable.user_id = orgRes.user_id
                LEFT JOIN reseasy_staff     orgSta ON usersTable.user_id = orgSta.user_id
                WHERE eventsTable.start_time >= '$now'
                ORDER BY eventsTable.start_time ASC
                LIMIT $limit
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function countTotalUpcomingEvents() {
            $now = date('Y-m-d H:i:s');
            
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_events WHERE start_time >= '$now'");
            $row = $result->fetch_assoc(); return $row['cnt'];
        }

        // ----- ACTION METHODS -----
        public function create($organiserId, $spaceId, $name, $type, $desc, $start, $end, $capacity, $locationTxt = '') {
            $organiserId = intval($organiserId);
            $spaceId     = $spaceId ? intval($spaceId) : 'NULL';
            $name        = $this->connection->real_escape_string($name);
            $desc        = $this->connection->real_escape_string($desc);
            $start       = $this->connection->real_escape_string($start);
            $end         = $this->connection->real_escape_string($end);
            $capacity    = intval($capacity);
            $locationTxt = $this->connection->real_escape_string($locationTxt);
            $spaceVal    = ($spaceId === 'NULL') ? 'NULL' : $spaceId;

            $this->connection->query("INSERT INTO reseasy_events (organiser_id, space_id, name, type, description, start_time, end_time, capacity, location_txt)
                VALUES ($organiserId, $spaceVal, '$name', '$type', '$desc', '$start', '$end', $capacity, '$locationTxt')");
            
            return $this->connection->insert_id;
        }

        public function signUp($userId, $eventId) {
            $userId  = intval($userId);
            $eventId = intval($eventId);
            
            $this->connection->query("INSERT IGNORE INTO reseasy_event_signups (user_id, event_id) VALUES ($userId, $eventId)");
            
            return $this->connection->affected_rows > 0;
        }

        public function cancelSignUp($userId, $eventId) {
            $userId  = intval($userId);
            $eventId = intval($eventId);
            
            $this->connection->query("DELETE FROM reseasy_event_signups WHERE user_id=$userId AND event_id=$eventId");
        }

        public function isSignedUp($userId, $eventId) {
            $userId  = intval($userId);
            $eventId = intval($eventId);
            
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_event_signups WHERE user_id=$userId AND event_id=$eventId");
            $row = $result->fetch_assoc();
            
            return $row['cnt'] > 0;
        }

        public function delete($eventId) {
            $eventId = intval($eventId);
            
            $this->connection->query("DELETE FROM reseasy_event_signups WHERE event_id=$eventId");
            $this->connection->query("DELETE FROM reseasy_events WHERE event_id=$eventId");
        }
    }
?>