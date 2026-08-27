<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/seguridad.php';

header("Content-Type: application/json; charset=utf-8");

verificarApiKey();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderJson(405, [
        "success" => false,
        "mensaje" => "Método no permitido. Utiliza POST."
    ]);
}

$entrada = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($entrada)) {
    responderJson(400, [
        "success" => false,
        "mensaje" => "JSON inválido."
    ]);
}

$folio = trim(
    (string) ($entrada["folio"] ?? "")
);

$accion = trim(
    (string) ($entrada["accion"] ?? "")
);

if ($folio === "" || $accion === "") {
    responderJson(400, [
        "success" => false,
        "mensaje" => "Debes enviar folio y accion."
    ]);
}

try {

    $pdo = obtenerConexion();

    /*
    |--------------------------------------------------------------------------
    | VALIDAR INSCRIPCIÓN
    |--------------------------------------------------------------------------
    */

    $sqlBuscar = "
        SELECT
            id_inscripcion,
            estado_pago,
            estado_inscripcion
        FROM INSCRIPCION
        WHERE folio = ?
        LIMIT 1
    ";

    $stmtBuscar = $pdo->prepare($sqlBuscar);
    $stmtBuscar->execute([$folio]);

    $inscripcion = $stmtBuscar->fetch(PDO::FETCH_ASSOC);

    if (!$inscripcion) {
        responderJson(404, [
            "success" => false,
            "mensaje" => "Inscripción no encontrada."
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ACCIONES
    |--------------------------------------------------------------------------
    */

    switch ($accion) {

        case "PAGO":

            if ($inscripcion["estado_pago"] === "PAGADO") {
                responderJson(200, [
                    "success" => true,
                    "estado" => "sin_cambios",
                    "mensaje" => "La inscripción ya estaba marcada como pagada.",
                    "estado_pago" => "PAGADO"
                ]);
            }

            $sql = "
                UPDATE INSCRIPCION
                SET estado_pago = 'PAGADO'
                WHERE id_inscripcion = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $inscripcion["id_inscripcion"]
            ]);

            responderJson(200, [
                "success" => true,
                "estado" => "actualizado",
                "mensaje" => "Pago actualizado correctamente.",
                "estado_pago" => "PAGADO"
            ]);

        default:

            responderJson(400, [
                "success" => false,
                "mensaje" => "Acción no válida."
            ]);
    }

} catch (Throwable $e) {

    error_log(
        "Error actualizando estado: " .
        $e->getMessage()
    );

    responderJson(500, [
        "success" => false,
        "mensaje" => "No fue posible actualizar el estado.",
        "detalle" => $e->getMessage()
    ]);
}