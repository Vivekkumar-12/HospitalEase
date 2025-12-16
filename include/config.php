<?php
// Configuration for PostgreSQL on Render with fallback to MySQL locally
// To use MySQL locally, set USE_MYSQL=true in your environment
$use_mysql = getenv('USE_MYSQL') === 'true';

if ($use_mysql) {
    // MySQL Connection (for local development)
    define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'myhmsdb');
    
    $con = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
    
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }
} else {
    // PostgreSQL Connection (for Render/Production)
    $db_url = getenv('DATABASE_URL');
    
    if ($db_url) {
        // Parse DATABASE_URL provided by Render
        $db_parts = parse_url($db_url);
        define('DB_SERVER', $db_parts['host']);
        define('DB_USER', $db_parts['user']);
        define('DB_PASS', $db_parts['pass']);
        define('DB_NAME', ltrim($db_parts['path'], '/'));
        $db_port = $db_parts['port'] ?? 5432;
    } else {
        // Fallback to individual env variables
        define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');
        define('DB_USER', getenv('DB_USER') ?: 'postgres');
        define('DB_PASS', getenv('DB_PASS') ?: '');
        define('DB_NAME', getenv('DB_NAME') ?: 'myhmsdb');
        $db_port = getenv('DB_PORT') ?: 5432;
    }
    
    // PDO Connection for PostgreSQL
    try {
        $dsn = "pgsql:host=" . DB_SERVER . ";port=" . $db_port . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Create mysqli-compatible wrapper for PostgreSQL
        $con = new PostgreSQLWrapper($pdo);
    } catch (PDOException $e) {
        echo "Failed to connect to PostgreSQL: " . $e->getMessage();
        exit();
    }
}

// PostgreSQL Wrapper class to provide mysqli-like interface
class PostgreSQLWrapper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function query($sql) {
        try {
            // Convert MySQL syntax to PostgreSQL
            $sql = $this->convertSQLSyntax($sql);
            return new PostgreSQLResultWrapper($this->pdo->query($sql));
        } catch (PDOException $e) {
            trigger_error("Query Error: " . $e->getMessage(), E_USER_WARNING);
            return false;
        }
    }
    
    public function real_escape_string($string) {
        return addslashes($string);
    }
    
    public function prepare($sql) {
        $sql = $this->convertSQLSyntax($sql);
        return new PostgreSQLStatementWrapper($this->pdo->prepare($sql));
    }
    
    public function error() {
        $errorInfo = $this->pdo->errorInfo();
        return $errorInfo[2] ?? '';
    }
    
    private function convertSQLSyntax($sql) {
        // Convert LIMIT syntax
        $sql = preg_replace('/LIMIT (\d+)\s*,\s*(\d+)/i', 'LIMIT $2 OFFSET $1', $sql);
        return $sql;
    }
}

class PostgreSQLResultWrapper {
    private $stmt;
    private $data = [];
    private $position = 0;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
        $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function fetch_array() {
        if ($this->position >= count($this->data)) {
            return null;
        }
        return $this->data[$this->position++];
    }
    
    public function fetch_assoc() {
        return $this->fetch_array();
    }
    
    public function num_rows() {
        return count($this->data);
    }
}

class PostgreSQLStatementWrapper {
    private $stmt;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function bind_param($types, ...$vars) {
        foreach ($vars as $i => $var) {
            $this->stmt->bindValue($i + 1, $var);
        }
        return true;
    }
    
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            trigger_error("Execute Error: " . $e->getMessage(), E_USER_WARNING);
            return false;
        }
    }
    
    public function close() {
        $this->stmt = null;
        return true;
    }
    
    public function error() {
        $errorInfo = $this->stmt->errorInfo();
        return $errorInfo[2] ?? '';
    }
}

// Helper functions for backward compatibility
function mysqli_real_escape_string($con, $string) {
    if (is_object($con) && method_exists($con, 'real_escape_string')) {
        return $con->real_escape_string($string);
    }
    return addslashes($string);
}

function mysqli_query($con, $sql) {
    if (is_object($con) && method_exists($con, 'query')) {
        return $con->query($sql);
    }
    return false;
}

function mysqli_num_rows($result) {
    if (is_object($result) && method_exists($result, 'num_rows')) {
        return $result->num_rows();
    }
    return 0;
}

function mysqli_fetch_array($result) {
    if (is_object($result) && method_exists($result, 'fetch_array')) {
        return $result->fetch_array();
    }
    return false;
}

function mysqli_fetch_assoc($result) {
    if (is_object($result) && method_exists($result, 'fetch_assoc')) {
        return $result->fetch_assoc();
    }
    return false;
}

function mysqli_prepare($con, $sql) {
    if (is_object($con) && method_exists($con, 'prepare')) {
        return $con->prepare($sql);
    }
    return false;
}

function mysqli_stmt_bind_param($stmt, $types, ...$vars) {
    if (is_object($stmt) && method_exists($stmt, 'bind_param')) {
        return $stmt->bind_param($types, ...$vars);
    }
    return false;
}

function mysqli_stmt_execute($stmt) {
    if (is_object($stmt) && method_exists($stmt, 'execute')) {
        return $stmt->execute();
    }
    return false;
}

function mysqli_stmt_close($stmt) {
    if (is_object($stmt) && method_exists($stmt, 'close')) {
        return $stmt->close();
    }
    return false;
}

function mysqli_stmt_error($stmt) {
    if (is_object($stmt) && method_exists($stmt, 'error')) {
        return $stmt->error();
    }
    return '';
}

function mysqli_error($con) {
    if (is_object($con) && method_exists($con, 'error')) {
        return $con->error();
    }
    return '';
}
?>