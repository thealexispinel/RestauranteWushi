<?php
// Usaremos la extensión PDO (PHP Data Objects) por ser más moderna y segura.
$host = 'db'; // Nombre del servicio del contenedor MySQL
$db   = 'ProyectoFinalCS';
$user = 'root'; // Usuario root dentro del contenedor
$pass = 'admin123'; // La contraseña que definiste
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>