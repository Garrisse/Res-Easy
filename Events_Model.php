<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the events that can be organized in the residence. -->

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
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_events'");
            
            if (mysqli_num_rows($check) === 0) {
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
                        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (organiser_id) REFERENCES reseasy_users(user_id)   ON DELETE CASCADE,
                        FOREIGN KEY (space_id)     REFERENCES reseasy_spaces(space_id) ON DELETE SET NULL
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllEvents() {
            $rows = [];

            // Due to having the name and surname of the organiser user in the sub-tables, we use the "COALESCE" command to show the one that is not null.
            $result = $this->connection->query(
                "SELECT eventsTable.*, spacesTable.name as space_name, COALESCE(organizationRes.name, organizationSta.name) as organiser_name, COALESCE(organizationRes.surname, organizationSta.surname) as organiser_surname,
                    (SELECT COUNT(*) FROM reseasy_event_participation_res participationResTable WHERE participationResTable.event_id=eventsTable.event_id) as signup_count
                FROM reseasy_events eventsTable
                LEFT JOIN reseasy_spaces spacesTable ON eventsTable.space_id = spacesTable.space_id
                JOIN reseasy_users usersTable ON eventsTable.organiser_id = usersTable.user_id
                LEFT JOIN reseasy_residents organizationRes ON usersTable.user_id = organizationRes.user_id
                LEFT JOIN reseasy_staff     organizationSta ON usersTable.user_id = organizationSta.user_id
                ORDER BY eventsTable.start_time ASC
            ");

            while ($row = $result->fetch_assoc()) { 
                $rows[] = $row; 
            }
            
            return $rows;
        }

        public function getEventsById($id) {
            $id = intval($id);

            $result = $this->connection->query(
                "SELECT eventsTable.*, spacesTable.name as space_name, COALESCE(organizationRes.name, organizationSta.name) as organiser_name, COALESCE(organizationRes.surname, organizationSta.surname) as organiser_surname
                FROM reseasy_events eventsTable
                LEFT JOIN reseasy_spaces spacesTable ON eventsTable.space_id = spacesTable.space_id
                JOIN reseasy_users usersTable ON eventsTable.organiser_id = usersTable.user_id
                LEFT JOIN reseasy_residents organizationRes ON usersTable.user_id = organizationRes.user_id
                LEFT JOIN reseasy_staff     organizationSta ON usersTable.user_id = organizationSta.user_id
                WHERE eventsTable.event_id=$id
            ");
            

            return $result->fetch_assoc();
        }

        public function getEventsByUser($userId) {
            $userId = intval($userId);
            $now = date('Y-m-d H:i:s');
            
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT eventsTable.*, spacesTable.name as space_name
                FROM reseasy_event_participation_res participationResTable
                JOIN reseasy_events eventsTable ON participationResTable.event_id = eventsTable.event_id
                LEFT JOIN reseasy_spaces spacesTable ON eventsTable.space_id = spacesTable.space_id
                JOIN reseasy_residents residentsTable ON participationResTable.participant_id = residentsTable.resident_id
                WHERE residentsTable.user_id=$userId AND eventsTable.start_time >= '$now'
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
                "SELECT eventsTable.*, spacesTable.name as space_name, COALESCE(organizationRes.name, organizationSta.name) as organiser_name, COALESCE(organizationRes.surname, organizationSta.surname) as organiser_surname,
                    (SELECT COUNT(*) FROM reseasy_event_participation_res participationResTable WHERE participationResTable.event_id=eventsTable.event_id) as signup_count
                FROM reseasy_events eventsTable
                LEFT JOIN reseasy_spaces spacesTable ON eventsTable.space_id = spacesTable.space_id
                JOIN reseasy_users usersTable ON eventsTable.organiser_id = usersTable.user_id
                LEFT JOIN reseasy_residents organizationRes ON usersTable.user_id = organizationRes.user_id
                LEFT JOIN reseasy_staff     organizationSta ON usersTable.user_id = organizationSta.user_id
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

        public function delete($eventId) {
            $eventId = intval($eventId);
            
            require_once(__DIR__ . '/EventParticipation_Model.php');
            (new EventParticipation_Model())->removeAllForEvent($eventId);
            $this->connection->query("DELETE FROM reseasy_events WHERE event_id=$eventId");
        }
    }
?>