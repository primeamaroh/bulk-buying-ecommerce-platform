<?php
class Database {
    private $db_file;
    private $conn;

    public function __construct() {
        $this->db_file = __DIR__ . '/database.sqlite';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("sqlite:" . $this->db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables if they don't exist
            $this->initializeTables();
            
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }

    private function initializeTables() {
        // Users table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Products table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                quantity INTEGER NOT NULL,
                price REAL NOT NULL,
                variations TEXT,
                duration DATETIME NOT NULL,
                source_website TEXT,
                dimensions TEXT,
                weight REAL,
                image_path TEXT,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Orders table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER,
                user_id INTEGER,
                quantity INTEGER NOT NULL,
                status TEXT DEFAULT 'pending',
                shipping_fee REAL,
                admin_fee REAL,
                total_amount REAL,
                cancellation_fee REAL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // User Posts table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS user_posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                title TEXT NOT NULL,
                description TEXT,
                image_path TEXT,
                website TEXT,
                company TEXT,
                votes INTEGER DEFAULT 0,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // Votes table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS votes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER,
                user_id INTEGER,
                potential_quantity INTEGER,
                deposit_amount REAL NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES user_posts(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // Comments table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER,
                user_id INTEGER,
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES user_posts(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // Admin Settings table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS admin_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_fee_percentage REAL DEFAULT 5.00,
                shipping_fee_per_kg REAL DEFAULT 0.00,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insert default admin settings if not exists
        $stmt = $this->conn->query("SELECT COUNT(*) FROM admin_settings");
        if ($stmt->fetchColumn() == 0) {
            $this->conn->exec("
                INSERT INTO admin_settings (admin_fee_percentage, shipping_fee_per_kg) 
                VALUES (5.00, 0.00)
            ");
        }
    }
}
?>
