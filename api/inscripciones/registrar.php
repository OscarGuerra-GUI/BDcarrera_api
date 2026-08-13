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


$responsable = $datos["responsable"] ?? [];

$nombre = trim((string) ($responsable["nombre_completo"] ?? ""));
$telefono = trim((string) ($responsable["telefono"] ?? ""));
$correo = trim((string) ($responsable["correo"] ?? ""));

$opcionInscripcion = trim(
    (string) ($datos["opcion_inscripcion"] ?? "")
);


if ($nombre === "" || $telefono === "") {
    responderJson(422, [
        "success" => false,
        "mensaje" => "Nombre y teléfono del responsable son obligatorios."
    ]);
}


$idEvento = 1;
$idPaquete = 1;
$cantidadParticipantes = 1;

$estadoInscripcion = "REGISTRADA";
$estadoPago = "PENDIENTE";


try {

    $conexion = obtenerConexion();


    $conexion->beginTransaction();


    $sqlResponsable = "
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

    $consultaResponsable = $conexion->prepare($sqlResponsable);

    $consultaResponsable->execute([
        ":nombre_completo" => $nombre,
        ":telefono" => $telefono,
        ":correo" => $correo !== "" ? $correo : null
    ]);


    $idResponsable = (int) $conexion->lastInsertId();


    $folio = "CB2026-" . str_pad(
        (string) $idResponsable,
        5,
        "0",
        STR_PAD_LEFT
    );


    $sqlInscripcion = "
        INSERT INTO INSCRIPCION
        (
            id_responsable,
            id_evento,
            id_paquete,
            folio,
            cantidad_participantes,
            estado_inscripcion,
            estado_pago
        )
        VALUES
        (
            :id_responsable,
            :id_evento,
            :id_paquete,
            :folio,
            :cantidad_participantes,
            :estado_inscripcion,
            :estado_pago
        )
    ";

    $consultaInscripcion = $conexion->prepare($sqlInscripcion);

    $consultaInscripcion->execute([
        ":id_responsable" => $idResponsable,
        ":id_evento" => $idEvento,
        ":id_paquete" => $idPaquete,
        ":folio" => $folio,
        ":cantidad_participantes" => $cantidadParticipantes,
        ":estado_inscripcion" => $estadoInscripcion,
        ":estado_pago" => $estadoPago
    ]);

    $idInscripcion = (int) $conexion->lastInsertId();


    $conexion->commit();


    responderJson(201, [
        "success" => true,
        "mensaje" => "Responsable e inscripción registrados correctamente.",

        "id_responsable" => $idResponsable,
        "id_inscripcion" => $idInscripcion,
        "folio" => $folio,

        "opcion_inscripcion_recibida" => $opcionInscripcion,

        "valores_temporales" => [
            "id_evento" => $idEvento,
            "id_paquete" => $idPaquete,
            "cantidad_participantes" => $cantidadParticipantes
        ]
    ]);

} catch (PDOException $e) {


    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log(
        "Error al registrar RESPONSABLE + INSCRIPCION: " .
        $e->getMessage()
    );

    responderJson(500, [
        "success" => false,
        "mensaje" => "Error al guardar el responsable y la inscripción."
    ]);
}