<?php
// ─── Copy this file to config.php and fill in your real values ───────────────

define('DB_HOST', 'your_db_host');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('change_this_password', PASSWORD_BCRYPT));

define('APP_NAME', 'Bright Mind School');
define('SESSION_TIMEOUT', 1800);

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("DB Connection failed: " . $conn->connect_error);
            die("A database error occurred. Please try again later.");
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

function bootstrapDB(): void {
    $conn = getDB();
    $conn->query("CREATE TABLE IF NOT EXISTS registrations (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        full_name   VARCHAR(100)  NOT NULL,
        email       VARCHAR(100)  NOT NULL,
        gender      ENUM('male','female','other') NOT NULL,
        course      VARCHAR(100)  NOT NULL,
        phone       VARCHAR(20)   DEFAULT NULL,
        reg_date    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
    )");
}

bootstrapDB();
