<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the Residents Sub-table, which is heir to the Users Table.
     While making in the previous table all the authentication logic, here we manage (read/update) the resident profile.
-->

<?php
    class Residents_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
        }

        // ----- GET METHODS -----
        // All residents users (joined with the main table to show all the info)
        public function getAllResidents() {
            $residents = [];

            $result = $this->connection->query(
                "SELECT residentsTable.*, usersTable.email, usersTable.role, usersTable.created_at
                 FROM reseasy_residents residentsTable
                 INNER JOIN reseasy_users usersTable ON residentsTable.user_id = usersTable.user_id
                 ORDER BY residentsTable.surname ASC"
            );

            while ($row = $result->fetch_assoc()) {
                $residents[] = $row;
            }

            return $residents;
        }

        // Find a resident profile by resident_id (PK)
        public function getByResidentId($id) {
            $id = intval($id);
            
            $result = $this->connection->query(
                "SELECT residentsTable.*, usersTable.email, usersTable.role, usersTable.created_at
                 FROM reseasy_residents residentsTable
                 INNER JOIN reseasy_users usersTable ON residentsTable.user_id = usersTable.user_id
                 WHERE residentsTable.resident_id = $id"
            );

            return mysqli_fetch_assoc($result);
        }

        // Find a resident profile by a specific room
        public function getByRoom($room) {
            $room = mysqli_real_escape_string($this->connection, $room);
            
            $result = $this->connection->query(
                "SELECT residentsTable.*, usersTable.email
                 FROM reseasy_residents residentsTable
                 INNER JOIN reseasy_users usersTable ON residentsTable.user_id = usersTable.user_id
                 WHERE residentsTable.room_number = '$room'
                 ORDER BY residentsTable.surname ASC"
            );

            $residents = [];

            while ($row = $result->fetch_assoc()) {
                $residents[] = $row;
            }

            return $residents;
        }

        // To show the total number of residents in the Admin dashboard
        public function countTotalResidents() {
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_residents");
            
            return $result->fetch_assoc()['cnt'];
        }

        // ----- MODIFYING METHODS -----
        // Update the profile data that is stored in the sub-table (not allowed to modify the email or password because they are stored in the main table)
        public function update($userId, $name, $surname, $phone, $room) {
            $userId  = intval($userId);
            $name    = mysqli_real_escape_string($this->connection, $name);
            $surname = mysqli_real_escape_string($this->connection, $surname);
            $phone   = mysqli_real_escape_string($this->connection, $phone);
            $room    = mysqli_real_escape_string($this->connection, $room);

            $this->connection->query(
                "UPDATE reseasy_residents
                 SET name='$name', surname='$surname', phone='$phone', room_number='$room'
                 WHERE user_id=$userId"
            );
        }
    }
?>
