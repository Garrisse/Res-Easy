<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle with SQL queries the database logic about three tables with inheritance between them: 
     
    - Users Table: main table with fields used in the authentication that are common between staff and residents (email, password, role, created_at)
     
    - Residents Table: more data about residents users joint to own fields like the "room_number". It is related with Renting, Booking, Laundry_usage and Event_participation_res tables.
    Otherwise, it has a foreign key "user_id" that points to the main table of the inheritance.
     
    - Staff Table: more data about staff users joint to own fields like the "staff_type", "company" or "emerg_callable". It is related with Dispatch, Announcement, Contact_logs and Event_participation_staff tables.
    Otherwise, it also uses a FK to relate to the main table of the inheritance.
-->

<?php
    class Users_Model {

        // ----- OBJECT CONSTRUCTOR -----
        private $connection;

        public function __construct() {
            require_once(__DIR__ . '/Connection.php');

            // Remember the difference to call an static and non-static method:
            // - In static method we put "ClassName::methodName()", like in Connection::bd_connect()
            // - In non-static method we put "$object->methodName()", such as, $this->createTable
            $this->connection = Connection::bd_connect();
            $this->createTablesIfNotExist();
        }

        // ----- TABLES SETUP -----
        private function createTablesIfNotExist() {
            // Allow UTF-8mb4 character set (accents, emojis, special characters) in the database. However it determines how to compare the texts (without distinguish texts with uppercase, accents,...).
            $this->connection->query("ALTER DATABASE `defaultdb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // 1. USERS MAIN TABLE (shared by all)
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_users'");
            
            if (mysqli_num_rows($check) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_users (
                        user_id    INT AUTO_INCREMENT PRIMARY KEY,
                        email      VARCHAR(100) NOT NULL UNIQUE,
                        password   VARCHAR(255) NOT NULL,
                        role       ENUM('resident','staff') DEFAULT 'resident',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                ");
            }

            // 2. RESIDENTS SUB-TABLE
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_residents'");

            if (mysqli_num_rows($check) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_residents (
                        resident_id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id     INT NOT NULL UNIQUE,
                        name        VARCHAR(50)  NOT NULL,
                        surname     VARCHAR(50)  NOT NULL,
                        phone       VARCHAR(20),
                        room_number VARCHAR(10),
                        FOREIGN KEY (user_id) REFERENCES reseasy_users(user_id) 
                        ON DELETE CASCADE
                    );
                ");
            }

            // 3. STAFF SUB-TABLE
            $check = mysqli_query($this->connection, "SHOW TABLES LIKE 'reseasy_staff'");

            if (mysqli_num_rows($check) === 0) {
                $this->connection->query("
                    CREATE TABLE reseasy_staff (
                        staff_id      INT AUTO_INCREMENT PRIMARY KEY,
                        user_id       INT NOT NULL UNIQUE,
                        name          VARCHAR(50)  NOT NULL,
                        surname       VARCHAR(50)  NOT NULL,
                        staff_type    VARCHAR(50),
                        company       VARCHAR(100),
                        phone         VARCHAR(20),
                        emerg_callable BOOLEAN DEFAULT TRUE,
                        FOREIGN KEY (user_id) REFERENCES reseasy_users(user_id) 
                        ON DELETE CASCADE
                    );
                ");
            }
        }

        // ----- MERGE PROFILE FROM ALL TABLES DATA -----
        private function mergeProfile(array $general_user_data): array {
            $user_id = intval($general_user_data['user_id']);

            if ($general_user_data['role'] === 'resident') {
                $resident_user_data = $this->connection->query("SELECT * FROM reseasy_residents WHERE user_id=$user_id");
                $profile = mysqli_fetch_assoc($resident_user_data);

                return array_merge($general_user_data, $profile ?? []);

            } elseif ($general_user_data['role'] === 'staff'){
                $staff_user_data = $this->connection->query("SELECT * FROM reseasy_staff WHERE user_id=$user_id");
                $profile = mysqli_fetch_assoc($staff_user_data);

                return array_merge($general_user_data, $profile ?? []);

            } else {
                return $general_user_data;
            }
        }

        // ----- GET METHODS -----
        // All users with their profiles merged (joined with both sub-tables)
        public function getAllUsers() {
            $users = [];
            
            $result = $this->connection->query(
                "SELECT usersTable.*, residentsTable.name, residentsTable.surname, residentsTable.phone, residentsTable.room_number, 
                 staffTable.name AS staff_name, staffTable.surname AS staff_surname, staffTable.staff_type, staffTable.company, staffTable.phone AS staff_phone, staffTable.emerg_callable 
                 FROM reseasy_users usersTable 
                 LEFT JOIN reseasy_residents residentsTable ON usersTable.user_id = residentsTable.user_id 
                 LEFT JOIN reseasy_staff  staffTable ON usersTable.user_id = staffTable.user_id 
                 ORDER BY usersTable.user_id ASC"
            );

            while ($row = $result->fetch_assoc()) {
                if ($row['role'] === 'staff') {                                                     // Normalizes the staff columns (staff_name/staff_surname) to have the same name as residents columns (name/surname)
                    $row['name']    = $row['staff_name'];
                    $row['surname'] = $row['staff_surname'];
                    $row['phone']   = $row['staff_phone'];
                }

                $users[] = $row;
            }
            
            return $users;
        }
 
        // Find a user profile by user_id (PK)
        public function getByUserId($id) {
            $id = intval($id);
 
            $result = $this->connection->query("SELECT * FROM reseasy_users WHERE user_id=$id");
            $user = mysqli_fetch_assoc($result);

            if (!$user){
                return null;
            } else{
                return $this->mergeProfile($user);
            }
        }

        // Find a user profile by email (used to login or to check if an email is already used when registering)
        public function getByEmail($email) {
            $email = mysqli_real_escape_string($this->connection, $email);

            $result = $this->connection->query("SELECT * FROM reseasy_users WHERE email='$email'");
            $user = mysqli_fetch_assoc($result);

            if (!$user){
                return null;
            } else{
                return $this->mergeProfile($user);
            }
        }

        // ----- MODIFYING METHODS -----
        // Insert a new user into both tables (main and sub-table), depending on the role.
        // To be able to use the same function to create both residents and staff, we put the parameters that are specific for each role as optional
        // This is made by puting the value '' as default, so if that fields are not filled in, the PHP won't return an error.
        public function create($name, $surname, $email, $password, $phone, $role = 'resident', $room = '', $staffType = '', $company = '', $emergCallable = '') {
            $name     = mysqli_real_escape_string($this->connection, $name);
            $surname  = mysqli_real_escape_string($this->connection, $surname);
            $email    = mysqli_real_escape_string($this->connection, $email);
            $phone    = mysqli_real_escape_string($this->connection, $phone);
            $role     = mysqli_real_escape_string($this->connection, $role);
            $hashed   = password_hash($password, PASSWORD_DEFAULT);

            $this->connection->query("INSERT INTO reseasy_users (email, password, role) VALUES ('$email','$hashed','$role')");
            $newUserId = $this->connection->insert_id;

            if ($role === 'resident') {
                $room = mysqli_real_escape_string($this->connection, $room);
                
                $this->connection->query("INSERT INTO reseasy_residents (user_id, name, surname, phone, room_number) VALUES ($newUserId,'$name','$surname','$phone','$room')");
            
            } elseif ($role === 'staff') {
                $staffType     = mysqli_real_escape_string($this->connection, $staffType);
                $company       = mysqli_real_escape_string($this->connection, $company);
                $emergCallable = mysqli_real_escape_string($this->connection, $emergCallable);
                
                $this->connection->query("INSERT INTO reseasy_staff (user_id, name, surname, staff_type, company, phone, emerg_callable) VALUES ($newUserId,'$name','$surname','$staffType','$company','$phone','$emergCallable')");
            }

            return $newUserId;
        }

        // Delete a user by id from both tables (main and sub-table), depending on the role. 
        public function delete($id) {
            $id = intval($id);

            $this->connection->query("DELETE FROM reseasy_users WHERE user_id=$id");                        // As we determined that sub-tables followed the CASCADE enforcing integrity strategy when we delete a user in the main table
                                                                                                            //  it is automatically deleted in the sub-table, because the foreign keys that the sub-table uses have been eliminated.
        }
    }
?>