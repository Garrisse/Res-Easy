<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about both event participation tables (residents and staff). -->

<?php
    class EventParticipation_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTablesIfNotExist();
        }

        // ----- TABLE SETUP -----
        private function createTablesIfNotExist() {

            $checkResidentsTable = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_event_participation_res'");
            
            if (mysqli_num_rows($checkResidentsTable) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_event_participation_res (
                        event_part_r_id INT AUTO_INCREMENT PRIMARY KEY,
                        participant_id  INT NOT NULL,
                        event_id        INT NOT NULL,
                        signed_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_res_signup (participant_id, event_id),
                        FOREIGN KEY (participant_id) REFERENCES reseasy_residents(resident_id) ON DELETE CASCADE,
                        FOREIGN KEY (event_id)       REFERENCES reseasy_events(event_id)       ON DELETE CASCADE
                    );
                ");
            }

            $checkStaffTable = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_event_participation_staff'");
            
            if (mysqli_num_rows($checkStaffTable) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_event_participation_staff (
                        event_part_s_id INT AUTO_INCREMENT PRIMARY KEY,
                        participant_id  INT NOT NULL,
                        event_id        INT NOT NULL,
                        assigned_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_staff_signup (participant_id, event_id),
                        FOREIGN KEY (participant_id) REFERENCES reseasy_staff(staff_id)  ON DELETE CASCADE,
                        FOREIGN KEY (event_id)       REFERENCES reseasy_events(event_id) ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getParticipationsByEvent($eventId, $role) {
            $eventId = intval($eventId);
            $rows    = [];

            if ($role === 'resident') {
                $result = $this->connection->query(
                    "SELECT participantsTable.*, residentsTable.name, residentsTable.surname, residentsTable.room_number, usersTable.email
                     FROM reseasy_event_participation_res participantsTable
                     JOIN reseasy_residents residentsTable ON participantsTable.participant_id = residentsTable.resident_id
                     JOIN reseasy_users     usersTable     ON residentsTable.user_id = usersTable.user_id
                     WHERE participantsTable.event_id=$eventId ORDER BY participantsTable.signed_at ASC"
                );
            } else {
                $result = $this->connection->query(
                    "SELECT participantsTable.*, staffTable.name, staffTable.surname, staffTable.staff_type, staffTable.phone, usersTable.email
                     FROM reseasy_event_participation_staff participantsTable
                     JOIN reseasy_staff staffTable ON participantsTable.participant_id = staffTable.staff_id
                     JOIN reseasy_users usersTable ON staffTable.user_id = usersTable.user_id
                     WHERE participantsTable.event_id=$eventId ORDER BY participantsTable.assigned_at ASC"
                );
            }

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        }

        public function getParticipationsByParticipant($participantId, $role) {
            $participantId = intval($participantId);
            $now           = date('Y-m-d H:i:s');
            $table         = $this->usedTable($role);
            $rows          = [];

            $result = $this->connection->query(
                "SELECT participantTable.*, eventsTable.name as event_name, eventsTable.type, eventsTable.start_time, eventsTable.end_time, spacesTable.name as space_name
                 FROM $table participantTable
                 JOIN reseasy_events  eventsTable  ON participantTable.event_id  = eventsTable.event_id
                 LEFT JOIN reseasy_spaces spacesTable ON eventsTable.space_id = spacesTable.space_id
                 WHERE participantTable.participant_id=$participantId AND eventsTable.start_time >= '$now'
                 ORDER BY eventsTable.start_time ASC"
            );
            
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            
            return $rows;
        }

        public function countParticipationsByEvent($eventId, $role) {
            $eventId = intval($eventId);
            $table   = $this->usedTable($role);
            
            $result  = $this->connection->query("SELECT COUNT(*) as cnt FROM $table WHERE event_id=$eventId");
            
            return $result->fetch_assoc()['cnt'];
        }

        public function isParticipating($participantId, $eventId, $role) {
            $participantId = intval($participantId);
            $eventId       = intval($eventId);
            $table         = $this->usedTable($role);
            
            $result        = $this->connection->query("SELECT COUNT(*) as cnt FROM $table WHERE participant_id=$participantId AND event_id=$eventId");
            
            return $result->fetch_assoc()['cnt'] > 0;
        }

        private function usedTable($role) {
            return $role === 'staff' ? 'reseasy_event_participation_staff' : 'reseasy_event_participation_res';
        }

        // ----- ACTION METHODS -----
        public function add($participantId, $eventId, $role) {
            $participantId = intval($participantId);
            $eventId       = intval($eventId);
            $table         = $this->usedTable($role);
            
            $this->connection->query("INSERT IGNORE INTO $table (participant_id, event_id) VALUES ($participantId, $eventId)");
            
            return $this->connection->affected_rows > 0;
        }

        public function remove($participantId, $eventId, $role) {
            $participantId = intval($participantId);
            $eventId       = intval($eventId);
            $table         = $this->usedTable($role);
            
            $this->connection->query("DELETE FROM $table WHERE participant_id=$participantId AND event_id=$eventId");
        }

        // Called by Events_Model::delete() — cleans both tables for an event
        public function removeAllForEvent($eventId) {
            $eventId = intval($eventId);
            
            $this->connection->query("DELETE FROM reseasy_event_participation_res WHERE event_id=$eventId");
            $this->connection->query("DELETE FROM reseasy_event_participation_staff WHERE event_id=$eventId");
        }
    }
?>
