<!-- MAIN PROJECT  -  INTERACTIVE WEB TECHNOLOGY  -  KAUNAS UNIVERSITY OF TECHNOLOGY
     Made by Garris Cauet and José Ramis de Ayreflor Moragues -->

<!-- In this file we handle the database connection (reusable across all Models). -->

<!-- Unlike previous projects that we have both made by our one, we have to do two new things:
    
    1. Make an abstraction step by creating a file with all the DB configuration data to protect the user names, passwords or ports.
    It is because here we use the GitHub open platform to share the codes between both developers and, as we don't want to share all that sensible data with all GitHub platform, we store it in a ".gitignore" file.

    2. Add a SSL certificate to guarantee a secure connection to the database. It is because here we use a cloud server (Aiven) to store the database and it is a main requirement. 
    The SSL certificate is stored in another ".gitignore" file to avoid sharing it with all GitHub users.
 -->

<?php
    class Connection {
        public static function bd_connect() {
            require_once "private/config.local.php";

            $tunnel = mysqli_init();
            mysqli_ssl_set($tunnel, NULL, NULL, "private/ca.pem", NULL, NULL);

            $success = mysqli_real_connect($tunnel, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, NULL, MYSQLI_CLIENT_SSL);

            if (!$success) {
                die('ERROR connecting to the database: ' . mysqli_connect_error());
            }

            mysqli_query($tunnel, 'SET NAMES utf8mb4;');
            
            return $tunnel;
        }
    }
?>