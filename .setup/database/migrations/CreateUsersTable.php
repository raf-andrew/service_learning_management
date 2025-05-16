<?php

namespace Database\Migrations;

class CreateUsersTable {
    public function up(\PDO $connection): void {
        $connection->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
                status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
                email_verified_at TIMESTAMP NULL,
                remember_token VARCHAR(100) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(\PDO $connection): void {
        $connection->exec("DROP TABLE IF EXISTS users");
    }
} 