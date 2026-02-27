<?php
// config/database.php
require_once __DIR__ . '/Env.php';
Env::load(__DIR__ . '/../.env');

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'inventory_system';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
    }

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        }
        catch (PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            die("Internal Server Error: Could not connect to the database.");
        }
        return $this->conn;
    }
}
?>