<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/seguridad.php';

header("Content-Type: application/json; charset=utf-8");

$pdo = obtenerConexion();


/*
|--------------------------------------------------------------------------
| SEGURIDAD
|--------------------------------------------------------------------------
*/

validarApiKey();


/*
|--------------------------------------------------------------------------
| SOLO POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "mensaje" => "Método no permitido. Utiliza POST."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


/*
|--------------------------------------------------------------------------
| LEER JSON
|--------------------------------------------------------------------------
*/

$entrada = json_decode(
    file_get_contents("php://input"),
    true
);


if (!is_array($entrada)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "mensaje" => "El cuerpo debe ser JSON válido."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


$folio = trim(
    (string) ($entrada["folio"] ?? "")
);


if ($folio === "") {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "mensaje" => "Debes enviar el folio de inscripción."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | BUSCAR INSCRIPCIÓN
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            id_inscripcion,
            folio
        FROM INSCRIPCION
        WHERE folio = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $folio
    ]);

    $inscripcion = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$inscripcion) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "mensaje" => "No existe una inscripción con ese folio."
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }


    $idInscripcion =
        (int) $inscripcion["id_inscripcion"];


    /*
    |--------------------------------------------------------------------------
    | VER SI YA TIENE QR
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            id_qr,
            token,
            ruta_archivo,
            fecha_generacion,
            estado
        FROM CODIGO_QR
        WHERE id_inscripcion = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $idInscripcion
    ]);

    $qrExistente = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($qrExistente) {

        echo json_encode([
            "success" => true,
            "estado" => "existente",
            "mensaje" => "La inscripción ya tiene un QR generado.",
            "folio" => $folio,
            "id_inscripcion" => $idInscripcion,
            "token" => $qrExistente["token"],
            "ruta_archivo" => $qrExistente["ruta_archivo"],
            "fecha_generacion" => $qrExistente["fecha_generacion"]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | GENERAR TOKEN
    |--------------------------------------------------------------------------
    */

    $token = bin2hex(
        random_bytes(32)
    );


    /*
    |--------------------------------------------------------------------------
    | INSERTAR QR
    |--------------------------------------------------------------------------
    */

    $sql = "
        INSERT INTO CODIGO_QR
        (
            id_inscripcion,
            token,
            ruta_archivo,
            estado
        )
        VALUES
        (
            ?,
            ?,
            NULL,
            'ACTIVO'
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $idInscripcion,
        $token
    ]);


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "estado" => "generado",
        "mensaje" => "QR registrado correctamente.",
        "id_qr" => (int) $pdo->lastInsertId(),
        "id_inscripcion" => $idInscripcion,
        "folio" => $folio,
        "token" => $token
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}