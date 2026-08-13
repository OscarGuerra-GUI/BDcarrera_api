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


$responsable = $datos["responsable"] ?? null;

if (!is_array($responsable)) {
    responderJson(422, [
        "success" => false,
        "mensaje" => "No se recibieron correctamente los datos del responsable."
    ]);
}

$nombre = trim(
    (string) ($responsable["nombre_completo"] ?? "")
);

$telefono = trim(
    (string) ($responsable["telefono"] ?? "")
);

$correo = trim(
    (string) ($responsable["correo"] ?? "")
);



$opcionInscripcion = trim(
    (string) ($datos["opcion_inscripcion"] ?? "")
);



if ($nombre === "") {
    responderJson(422, [
        "success" => false,
        "mensaje" => "El nombre completo del responsable es obligatorio."
    ]);
}

if ($telefono === "") {
    responderJson(422, [
        "success" => false,
        "mensaje" => "El teléfono del responsable es obligatorio."
    ]);
}


try {

    $conexion = obtenerConexion();

    $sql = "
        INSERT INTO RESPONSABLE
        (
            nombre_completo,
            telefono,
            correo
        )
        VALUES
        (
            :nombre_completo,
            :telefono,
            :correo
        )
    ";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":nombre_completo" => $nombre,
        ":telefono" => $telefono,

        // Si Forms no envía correo, guardamos NULL.
        ":correo" => $correo !== "" ? $correo : null
    ]);

    $idResponsable = (int) $conexion->lastInsertId();


    responderJson(201, [
        "success" => true,
        "mensaje" => "Responsable registrado correctamente.",
        "id_responsable" => $idResponsable,

        // Solo para comprobar que Power Automate manda esta respuesta.
        "opcion_inscripcion_recibida" => $opcionInscripcion
    ]);

} catch (PDOException $e) {

    error_log(
        "Error al registrar RESPONSABLE: " . $e->getMessage()
    );

    responderJson(500, [
        "success" => false,
        "mensaje" => "Error al registrar al responsable en la base de datos."
    ]);
}