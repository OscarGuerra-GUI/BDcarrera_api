<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;


/*
|--------------------------------------------------------------------------
| SOLO GET
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    header("Content-Type: application/json; charset=utf-8");

    echo json_encode([
        "success" => false,
        "mensaje" => "Método no permitido. Utiliza GET."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER TOKEN
|--------------------------------------------------------------------------
*/

$token = trim(
    (string) ($_GET["token"] ?? "")
);


if ($token === "") {

    http_response_code(400);

    header("Content-Type: application/json; charset=utf-8");

    echo json_encode([
        "success" => false,
        "mensaje" => "Debes proporcionar el token del QR."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


try {

    $pdo = obtenerConexion();


    /*
    |--------------------------------------------------------------------------
    | COMPROBAR QUE EL TOKEN EXISTE Y ESTÁ ACTIVO
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            q.id_qr,
            q.id_inscripcion,
            q.token,
            i.folio

        FROM CODIGO_QR q

        INNER JOIN INSCRIPCION i
            ON i.id_inscripcion = q.id_inscripcion

        WHERE q.token = ?
          AND q.estado = 'ACTIVO'

        LIMIT 1
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $token
    ]);


    $registro = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$registro) {

        http_response_code(404);

        header("Content-Type: application/json; charset=utf-8");

        echo json_encode([
            "success" => false,
            "mensaje" => "Token QR no válido, inactivo o inexistente."
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | URL QUE CONTENDRÁ EL QR
    |--------------------------------------------------------------------------
    |
    | Al escanearlo, el celular abrirá consultar.php con el token.
    |--------------------------------------------------------------------------
    */

    $urlConsulta =
        "https://bdcarreraapi-production.up.railway.app" .
        "/api/inscripciones/consultar.php?token=" .
        urlencode($token);


    /*
    |--------------------------------------------------------------------------
    | CONFIGURACIÓN DEL QR
    |--------------------------------------------------------------------------
    */

    $opciones = new QROptions([
        "outputType" => QRCode::OUTPUT_MARKUP_SVG,
        "eccLevel"   => QRCode::ECC_M,
        "scale"      => 8
    ]);


    /*
    |--------------------------------------------------------------------------
    | GENERAR QR
    |--------------------------------------------------------------------------
    */

    $qr = new QRCode($opciones);

    $imagen = $qr->render($urlConsulta);


    /*
    |--------------------------------------------------------------------------
    | DEVOLVER COMO IMAGEN SVG
    |--------------------------------------------------------------------------
    */

    header("Content-Type: image/svg+xml; charset=utf-8");

    header(
        'Content-Disposition: inline; filename="QR-' .
        $registro["folio"] .
        '.svg"'
    );

    echo $imagen;


} catch (Throwable $e) {

    error_log(
        "Error generando imagen QR: " .
        $e->getMessage()
    );

    http_response_code(500);

    header("Content-Type: application/json; charset=utf-8");

    echo json_encode([
        "success" => false,
        "mensaje" => "No fue posible generar la imagen QR.",
        "detalle" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}