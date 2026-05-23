<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the Announcements table, being created by staff members and shown to all residents on their dashboard. -->

<?php
    class Announcements_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
            $this->createTableIfNotExists();
        }

        // ----- TABLE SETUP -----
        private function createTableIfNotExists() {
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_announcements'");

            if (mysqli_num_rows($check) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_announcements (
                        announcement_id INT AUTO_INCREMENT PRIMARY KEY,
                        author_id       INT NOT NULL,
                        location        VARCHAR(100),
                        title           VARCHAR(200) NOT NULL,
                        description     TEXT         NOT NULL,
                        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (author_id) REFERENCES reseasy_staff(staff_id) ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- GET METHODS -----
        public function getAllAnnouncements() {
            $rows = [];
            
            $result = $this->connection->query(
                "SELECT announcementsTable.*, staffTable.name as author_name, staffTable.surname as author_surname, staffTable.staff_type
                 FROM reseasy_announcements announcementsTable
                 JOIN reseasy_staff staffTable ON announcementsTable.author_id = staffTable.staff_id
                 ORDER BY announcementsTable.created_at DESC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        public function getRecentAnnouncements($limit = 5) {
            $limit = intval($limit);
            $rows  = [];
            
            $result = $this->connection->query(
                "SELECT announcementsTable.*, staffTable.name as author_name, staffTable.surname as author_surname
                 FROM reseasy_announcements announcementsTable
                 JOIN reseasy_staff staffTable ON announcementsTable.author_id = staffTable.staff_id
                 ORDER BY announcementsTable.created_at DESC
                 LIMIT $limit"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        public function getAnnouncementsById($id) {
            $id = intval($id);
            
            $result = $this->connection->query(
                "SELECT announcementsTable.*, staffTable.name as author_name, staffTable.surname as author_surname
                 FROM reseasy_announcements announcementsTable
                 JOIN reseasy_staff staffTable ON announcementsTable.author_id = staffTable.staff_id
                 WHERE announcementsTable.announcement_id=$id"
            );

            return mysqli_fetch_assoc($result);
        }

        public function getAnnouncementsByAuthor($staffId) {
            $staffId = intval($staffId);
            $rows    = [];
            
            $result  = $this->connection->query(
                "SELECT announcementsTable.* FROM reseasy_announcements announcementsTable
                 WHERE announcementsTable.author_id=$staffId
                 ORDER BY announcementsTable.created_at DESC"
            );

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        }

        public function countTotalAnnouncements() {
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_announcements");
            
            return $result->fetch_assoc()['cnt'];
        }

        // ----- ACTION METHODS -----
        public function create($authorId, $title, $description, $location = '') {
            $authorId    = intval($authorId);
            $title       = mysqli_real_escape_string($this->connection, $title);
            $description = mysqli_real_escape_string($this->connection, $description);
            $location    = mysqli_real_escape_string($this->connection, $location);

            $this->connection->query("INSERT INTO reseasy_announcements (author_id, title, description, location) VALUES ($authorId, '$title', '$description', '$location')");
            
            return $this->connection->insert_id;
        }

        public function delete($id) {
            $id = intval($id);
            
            $this->connection->query("DELETE FROM reseasy_announcements WHERE announcement_id=$id");
        }
    }
?>
