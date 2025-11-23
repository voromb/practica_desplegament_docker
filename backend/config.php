 <?php
// config.php

$host = 'mysql_contenidor'; # host docker
#$host = 'localhost'; # host localhost
$dbname = 'elementos';
$username = 'usuario_db';
$password = 'password_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}
