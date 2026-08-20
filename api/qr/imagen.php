<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;


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

    /*
    |--------------------------------------------------------------------------
    | CONEXIÓN A MYSQL
    |--------------------------------------------------------------------------
    */

    $pdo = obtenerConexion();


    /*
    |--------------------------------------------------------------------------
    | VALIDAR TOKEN Y OBTENER INSCRIPCIÓN
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            q.id_qr,
            q.id_inscripcion,
            q.token,
            q.estado,
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


    /*
    |--------------------------------------------------------------------------
    | TOKEN NO VÁLIDO
    |--------------------------------------------------------------------------
    */

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
    | URL QUE SE GUARDARÁ DENTRO DEL QR
    |--------------------------------------------------------------------------
    |
    | Cuando el celular escanee el QR, abrirá consultar.php
    | utilizando el token de esta inscripción.
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
    |
    | Utilizamos SVG porque:
    |
    | - no necesitamos GD
    | - no necesitamos Imagick
    | - es ligero
    | - puede escanearse perfectamente con un celular
    |--------------------------------------------------------------------------
    */

    $opciones = new QROptions;

    $opciones->outputInterface =
        QRMarkupSVG::class;

    $opciones->outputBase64 =
        false;


    /*
    |--------------------------------------------------------------------------
    | GENERAR QR
    |--------------------------------------------------------------------------
    */

    $qr = new QRCode(
        $opciones
    );


    $imagen = $qr->render(
        $urlConsulta
    );


    /*
    |--------------------------------------------------------------------------
    | MOSTRAR QR COMO SVG
    |--------------------------------------------------------------------------
    */

    header(
        "Content-Type: image/svg+xml; charset=utf-8"
    );


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

    header(
        "Content-Type: application/json; charset=utf-8"
    );


    echo json_encode([
        "success" => false,
        "mensaje" => "No fue posible generar la imagen QR.",
        "detalle" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}