<?php

class Database {
    private $host = "localhost";
    private $db_name = "idealogy_test";
    private $username = "root";
    private $password = "";
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            return null;
        }
        
        return $this->conn;
    }
    
    public function execute($query, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            return false;
        }
    }
    
    public function fetchAll($query, $params = []) {
        $stmt = $this->execute($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
    
    public function fetchOne($query, $params = []) {
        $stmt = $this->execute($query, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
?>

