<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/conexion.php';

verificarApiKey();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderJson(405, [
        "success" => false,
        "mensaje" => "Método no permitido. Utiliza POST."
    ]);
}

$contenido = file_get_contents("php://input");
$datos = json_decode($contenido ?: "", true);

if (!is_array($datos)) {
    responderJson(400, [
        "success" => false,
        "mensaje" => "El cuerpo de la solicitud debe contener JSON válido."
    ]);
}

$nombre = trim((string) ($datos["nombre_responsable"] ?? ""));
$telefono = trim((string) ($datos["telefono"] ?? ""));

$cantidad = filter_var(
    $datos["cantidad_participantes"] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $nombre === "" ||
    $telefono === "" ||
    $cantidad === false ||
    $cantidad < 1
) {
    responderJson(422, [
        "success" => false,
        "mensaje" => "Nombre, teléfono y cantidad de participantes son obligatorios."
    ]);
}

try {

    $conexion = obtenerConexion();

    $sql = "
        INSERT INTO prueba_inscripciones
        (
            nombre_responsable,
            telefono,
            cantidad_participantes
        )
        VALUES
        (
            :nombre_responsable,
            :telefono,
            :cantidad_participantes
        )
    ";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":nombre_responsable" => $nombre,
        ":telefono" => $telefono,
        ":cantidad_participantes" => $cantidad
    ]);

    responderJson(201, [
        "success" => true,
        "mensaje" => "Inscripción registrada correctamente.",
        "id" => (int) $conexion->lastInsertId()
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    responderJson(500, [
        "success" => false,
        "mensaje" => "Error al guardar la inscripción en la base de datos."
    ]);
}