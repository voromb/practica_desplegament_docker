<?php
// config.php

// Leer variables de entorno definidas en docker-compose.yml
$host = getenv('DB_HOST') ?: 'mysql_contenidor';
$dbname = getenv('DB_NAME') ?: 'elementos';
$username = getenv('DB_USER') ?: 'usuario_db';
$password = getenv('DB_PASSWORD') ?: 'password_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}
