<?php

declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

function responder(int $codigo, array $contenido): never
{
    http_response_code($codigo);
    echo json_encode(
        $contenido,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(405, [
        "success" => false,
        "mensaje" => "Método no permitido. Utiliza POST."
    ]);
}

/*
 * Validación de la clave que enviará Power Automate.
 */
$apiKeyConfigurada = getenv("API_KEY");
$apiKeyRecibida = $_SERVER["HTTP_X_API_KEY"] ?? "";

if (
    !$apiKeyConfigurada ||
    !hash_equals($apiKeyConfigurada, $apiKeyRecibida)
) {
    responder(401, [
        "success" => false,
        "mensaje" => "Acceso no autorizado."
    ]);
}

$contenido = file_get_contents("php://input");
$datos = json_decode($contenido, true);

if (!is_array($datos)) {
    responder(400, [
        "success" => false,
        "mensaje" => "El cuerpo de la solicitud no contiene JSON válido."
    ]);
}

$nombre = trim((string) ($datos["nombre_responsable"] ?? ""));
$telefono = trim((string) ($datos["telefono"] ?? ""));
$cantidad = filter_var(
    $datos["cantidad_participantes"] ?? null,
    FILTER_VALIDATE_INT
);

if ($nombre === "" || $telefono === "" || $cantidad === false) {
    responder(422, [
        "success" => false,
        "mensaje" => "Nombre, teléfono y cantidad son obligatorios."
    ]);
}

if ($cantidad < 1 || $cantidad > 10) {
    responder(422, [
        "success" => false,
        "mensaje" => "La cantidad de participantes debe estar entre 1 y 10."
    ]);
}

if (mb_strlen($nombre) > 150 || mb_strlen($telefono) > 20) {
    responder(422, [
        "success" => false,
        "mensaje" => "Uno de los datos supera la longitud permitida."
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

    responder(201, [
        "success" => true,
        "mensaje" => "Inscripción guardada correctamente.",
        "id" => (int) $conexion->lastInsertId()
    ]);
} catch (Throwable $e) {
    error_log("Error al registrar inscripción: " . $e->getMessage());

    responder(500, [
        "success" => false,
        "mensaje" => "Ocurrió un error al guardar la inscripción."
    ]);
}