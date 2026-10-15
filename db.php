<?php
define("DB_HOST", "127.0.0.1");
define("DB_PORT", 3306);
define("DB_NAME", "tra_sua_db");
define("DB_USER", "root");
define("DB_PASS", "");

function ketNoiDatabase()
{
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Không kết nối được database: " . $e->getMessage() .
            "\nHãy kiểm tra: MySQL đã bật chưa? Đã chạy file database.sql chưa? Mật khẩu đúng chưa?");
    }
}

$conn = ketNoiDatabase();
