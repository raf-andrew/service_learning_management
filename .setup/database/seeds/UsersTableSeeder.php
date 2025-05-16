<?php

namespace Database\Seeds;

class UsersTableSeeder {
    public function run(\PDO $connection): void {
        $stmt = $connection->prepare("
            INSERT INTO users (name, email, password, role, status)
            VALUES (?, ?, ?, ?, ?)
        ");

        // Create admin user
        $stmt->execute([
            'Admin User',
            'admin@example.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin',
            'active'
        ]);

        // Create teacher user
        $stmt->execute([
            'Teacher User',
            'teacher@example.com',
            password_hash('teacher123', PASSWORD_DEFAULT),
            'teacher',
            'active'
        ]);

        // Create student user
        $stmt->execute([
            'Student User',
            'student@example.com',
            password_hash('student123', PASSWORD_DEFAULT),
            'student',
            'active'
        ]);
    }
} 