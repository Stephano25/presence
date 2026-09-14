<?php
require_once __DIR__ . '/../config/database.php';

function authenticate($username, $password) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

function createUser($username, $password) {
    try {
        $pdo = getDBConnection();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        return $stmt->execute(['username' => $username, 'password' => $hashedPassword]);
    } catch(PDOException $e) {
        return false;
    }
}
?>