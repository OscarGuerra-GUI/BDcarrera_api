<?php

declare(strict_types=1);

$host = getenv("DB_HOST");
$port = getenv("DB_PORT");
$dbname = getenv("DB_NAME");
$username = getenv("DB_USER");
$password = getenv("DB_PASSWORD");

if (!$host || !$port || !$dbname || !$username || !$password) {
    throw new RuntimeException(
        "Faltan variables de entorno para conectar con la base de datos."
    );
}

try {
    $conexion = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Error de conexión MySQL: " . $e->getMessage());

    throw new RuntimeException(
        "No fue posible conectarse con la base de datos."
    );
}