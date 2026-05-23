<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about the Staff Sub-table, which is heir to the Users Table.
     While making in the previous table all the authentication logic, here we manage (read/update) the staff profile.
-->

<?php
    class Staff_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');
            
            $this->connection = Connection::bd_connect();
        }

        // ----- GET METHODS -----
        // All staff users (joined with the main table to show all the info)
        public function getAllStaff() {
            $staff = [];
            
            $result = $this->connection->query(
                "SELECT staffTable.*, usersTable.email, usersTable.role, usersTable.created_at
                 FROM reseasy_staff staffTable
                 INNER JOIN reseasy_users usersTable ON staffTable.user_id = usersTable.user_id
                 ORDER BY staffTable.surname ASC"
            );

            while ($row = $result->fetch_assoc()) {
                $staff[] = $row;
            }

            return $staff;
        }
        
        // Find a staff profile by user_id (FK)
        public function getStaffByUserId($userId) {
            $userId = intval($userId);

            $result = $this->connection->query(
                "SELECT staffTable.*, usersTable.email, usersTable.role, usersTable.created_at
                 FROM reseasy_staff staffTable
                 INNER JOIN reseasy_users usersTable ON staffTable.user_id = usersTable.user_id
                 WHERE staffTable.user_id = $userId"
            );

            return mysqli_fetch_assoc($result);
        }

        // Find a staff profile by staff_id (PK)
        public function getStaffByStaffId($id) {
            $id = intval($id);

            $result = $this->connection->query(
                "SELECT staffTable.*, usersTable.email, usersTable.role, usersTable.created_at
                 FROM reseasy_staff staffTable
                 INNER JOIN reseasy_users usersTable ON staffTable.user_id = usersTable.user_id
                 WHERE staffTable.staff_id = $id"
            );

            return mysqli_fetch_assoc($result);
        }

        // Find a staff profile by type (maintenance, reception, ...)
        public function getStaffByType($type) {
            $type = mysqli_real_escape_string($this->connection, $type);
            
            $result = $this->connection->query(
                "SELECT staffTable.*, usersTable.email
                 FROM reseasy_staff staffTable
                 INNER JOIN reseasy_users usersTable ON staffTable.user_id = usersTable.user_id
                 WHERE staffTable.staff_type = '$type'
                 ORDER BY staffTable.surname ASC"
            );

            $staff = [];

            while ($row = $result->fetch_assoc()) {
                $staff[] = $row;
            }

            return $staff;
        }

        // To show the total number of staff in the Admin dashboard
        public function countTotalStaff() {
            $result = $this->connection->query("SELECT COUNT(*) as cnt FROM reseasy_staff");
            $row = $result->fetch_assoc();
            
            return $row['cnt'];
        }

        // ----- ACTION METHODS -----
        // Update the profile data that is stored in the sub-table (not allowed to modify the email or password because they are stored in the main table)
        public function update($userId, $name, $surname, $staffType, $company, $phone, $emergCallable) {
            $userId        = intval($userId);
            $name          = mysqli_real_escape_string($this->connection, $name);
            $surname       = mysqli_real_escape_string($this->connection, $surname);
            $staffType     = mysqli_real_escape_string($this->connection, $staffType);
            $company       = mysqli_real_escape_string($this->connection, $company);
            $phone         = mysqli_real_escape_string($this->connection, $phone);
            $emergCallable = mysqli_real_escape_string($this->connection, $emergCallable);

            $this->connection->query(
                "UPDATE reseasy_staff
                 SET name='$name', surname='$surname', staff_type='$staffType', company='$company', phone='$phone', emerg_callable='$emergCallable'
                 WHERE user_id=$userId"
            );
        }
    }
?>
