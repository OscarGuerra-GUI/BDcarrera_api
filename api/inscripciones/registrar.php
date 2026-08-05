<?php

declare(strict_types=1);

require_once __DIR__ . "/../../config/seguridad.php";

verificarApiKey();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderJson(405, [
        "success" => false,
        "mensaje" => "Método no permitido. Utiliza POST."
    ]);
}

$contenido = file_get_contents("php://input");
$datos = json_decode($contenido, true);

if (!is_array($datos)) {
    responderJson(400, [
        "success" => false,
        "mensaje" => "El cuerpo de la solicitud no contiene un JSON válido."
    ]);
}

$nombre = trim((string) ($datos["nombre_responsable"] ?? ""));
$telefono = trim((string) ($datos["telefono"] ?? ""));

$cantidad = filter_var(
    $datos["cantidad_participantes"] ?? null,
    FILTER_VALIDATE_INT
);

if ($nombre === "" || $telefono === "" || $cantidad === false) {
    responderJson(422, [
        "success" => false,
        "mensaje" => "Nombre, teléfono y cantidad de participantes son obligatorios."
    ]);
}

if ($cantidad < 1 || $cantidad > 10) {
    responderJson(422, [
        "success" => false,
        "mensaje" => "La cantidad de participantes debe estar entre 1 y 10."
    ]);
}

if (mb_strlen($nombre) > 150) {
    responderJson(422, [
        "success" => false,
        "mensaje" => "El nombre del responsable supera los 150 caracteres."
    ]);
}

if (mb_strlen($telefono) > 20) {
    responderJson(422, [
        "success" => false,
        "mensaje" => "El teléfono supera los 20 caracteres."
    ]);
}

try {
    require_once __DIR__ . "/../../config/conexion.php";

    $sql = "
        INSERT INTO prueba_inscripciones
            (nombre_responsable, telefono, cantidad_participantes)
        VALUES
            (:nombre_responsable, :telefono, :cantidad_participantes)
    ";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":nombre_responsable" => $nombre,
        ":telefono" => $telefono,
        ":cantidad_participantes" => $cantidad
    ]);

    responderJson(201, [
        "success" => true,
        "mensaje" => "Inscripción guardada correctamente.",
        "data" => [
            "id" => (int) $conexion->lastInsertId(),
            "nombre_responsable" => $nombre,
            "telefono" => $telefono,
            "cantidad_participantes" => $cantidad
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error al registrar inscripción: " . $e->getMessage());

    responderJson(500, [
        "success" => false,
        "mensaje" => "Ocurrió un error al guardar la inscripción."
    ]);
}