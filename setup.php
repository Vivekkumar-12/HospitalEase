<?php
// Setup script for Hospital Management System
error_reporting(E_ALL);
ini_set('display_errors', 1);

class HMSSetup {
    private $db_server = 'localhost';
    private $db_user = 'root';
    private $db_pass = '';
    private $db_name = 'myhmsdb';
    private $conn;
    private $errors = [];
    private $success = [];

    public function __construct() {
        $this->checkRequirements();
    }

    private function checkRequirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.2.4', '<')) {
            $this->errors[] = "PHP version 7.2.4 or higher is required. Current version: " . PHP_VERSION;
        }

        // Check MySQL extension
        if (!extension_loaded('mysqli')) {
            $this->errors[] = "MySQLi extension is not enabled";
        }

        // Check if XAMPP is running
        if (!@fsockopen('localhost', 80)) {
            $this->errors[] = "Apache server is not running. Please start XAMPP";
        }

        // Check if MySQL is running
        if (!@fsockopen('localhost', 3306)) {
            $this->errors[] = "MySQL server is not running. Please start MySQL in XAMPP";
        }

        // Check directory permissions
        $directories = ['TCPDF', 'assets', 'images'];
        foreach ($directories as $dir) {
            if (!is_writable($dir)) {
                $this->errors[] = "Directory '$dir' is not writable";
            }
        }
    }

    private function connectDB() {
        $this->conn = new mysqli($this->db_server, $this->db_user, $this->db_pass);
        if ($this->conn->connect_error) {
            $this->errors[] = "Database connection failed: " . $this->conn->connect_error;
            return false;
        }
        return true;
    }

    public function setupDatabase() {
        if (!$this->connectDB()) {
            return;
        }

        // Create database
        $sql = "CREATE DATABASE IF NOT EXISTS " . $this->db_name;
        if ($this->conn->query($sql)) {
            $this->success[] = "Database created successfully";
        } else {
            $this->errors[] = "Error creating database: " . $this->conn->error;
            return;
        }

        // Select database
        $this->conn->select_db($this->db_name);

        // Import SQL file
        $sql_file = file_get_contents('myhmsdb.sql');
        if ($sql_file === false) {
            $this->errors[] = "Could not read SQL file";
            return;
        }

        // Execute SQL queries
        $queries = explode(';', $sql_file);
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if (!$this->conn->query($query)) {
                    $this->errors[] = "Error executing query: " . $this->conn->error;
                }
            }
        }

        // Create admin account if not exists
        $check_admin = "SELECT * FROM admintb WHERE username = 'admin'";
        $result = $this->conn->query($check_admin);
        if ($result->num_rows == 0) {
            $create_admin = "INSERT INTO admintb (username, password) VALUES ('admin', 'admin123')";
            if ($this->conn->query($create_admin)) {
                $this->success[] = "Admin account created successfully";
            } else {
                $this->errors[] = "Error creating admin account: " . $this->conn->error;
            }
        }
    }

    public function displayResults() {
        echo "<h2>Setup Results</h2>";
        
        if (!empty($this->success)) {
            echo "<div style='color: green;'>";
            echo "<h3>Success:</h3>";
            echo "<ul>";
            foreach ($this->success as $msg) {
                echo "<li>$msg</li>";
            }
            echo "</ul>";
            echo "</div>";
        }

        if (!empty($this->errors)) {
            echo "<div style='color: red;'>";
            echo "<h3>Errors:</h3>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
            echo "</div>";
        }

        if (empty($this->errors)) {
            echo "<p style='color: green; font-weight: bold;'>Setup completed successfully!</p>";
            echo "<p>You can now <a href='index.php'>access the application</a>.</p>";
            echo "<p>Default admin credentials:<br>Username: admin<br>Password: admin123</p>";
        }
    }
}

// Create HTML page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hospital Management System - Setup</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Hospital Management System Setup</h1>
        <?php
        $setup = new HMSSetup();
        $setup->setupDatabase();
        $setup->displayResults();
        ?>
    </div>
</body>
</html> 