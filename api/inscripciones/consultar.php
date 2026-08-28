<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/seguridad.php';

header("Content-Type: application/json; charset=utf-8");

$pdo = obtenerConexion();


/*
|--------------------------------------------------------------------------
| SOLO GET
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    responderJson(405, [
        "success" => false,
        "mensaje" => "Método no permitido. Utiliza GET."
    ]);
}


/*
|--------------------------------------------------------------------------
| OBTENER TOKEN
|--------------------------------------------------------------------------
*/

$token = trim(
    (string) ($_GET["token"] ?? "")
);

$folio = trim(
    (string) ($_GET["folio"] ?? "")
);


/*
|--------------------------------------------------------------------------
| DEBE RECIBIR TOKEN O FOLIO
|--------------------------------------------------------------------------
*/

if ($token === "" && $folio === "") {

    responderJson(400, [
        "success" => false,
        "mensaje" => "Debes proporcionar un token QR o un folio."
    ]);
}


/*
|--------------------------------------------------------------------------
| SI BUSCA POR FOLIO, EXIGIMOS API KEY
|--------------------------------------------------------------------------
*/

if ($folio !== "") {

    verificarApiKey();
}


try {


$sql = "
    SELECT
        q.id_qr,
        q.token,
        q.estado AS estado_qr,

        i.id_inscripcion,
        i.folio,
        i.cantidad_participantes,
        i.estado_inscripcion,
        i.estado_pago,
        i.estado_entrega,
        i.estado_entrada,
        i.fecha_inscripcion,

        r.id_responsable,
        r.nombre_completo AS responsable_nombre,

        pa.id_paquete,
        pa.nombre AS paquete_nombre

    FROM INSCRIPCION i

    INNER JOIN RESPONSABLE r
        ON r.id_responsable = i.id_responsable

    INNER JOIN PAQUETE pa
        ON pa.id_paquete = i.id_paquete

    LEFT JOIN CODIGO_QR q
        ON q.id_inscripcion = i.id_inscripcion
";


/*
|--------------------------------------------------------------------------
| DETERMINAR SI BUSCAMOS POR TOKEN O POR FOLIO
|--------------------------------------------------------------------------
*/

if ($token !== "") {

    $sql .= "
        WHERE q.token = ?
          AND q.estado = 'ACTIVO'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $token
    ]);

} else {

    $sql .= "
        WHERE i.folio = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $folio
    ]);
}


$inscripcion =
    $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | QR NO ENCONTRADO
    |--------------------------------------------------------------------------
    */

    if (!$inscripcion) {

        responderJson(404, [
            "success" => false,
            "mensaje" => "Inscripción no encontrada."
        ]);
    }


    $idInscripcion =
        (int) $inscripcion["id_inscripcion"];


    /*
    |--------------------------------------------------------------------------
    | 2. OBTENER PARTICIPANTES
    |--------------------------------------------------------------------------
    */

    $sqlParticipantes = "
        SELECT
            p.id_participante,
            p.folio,
            p.nombre_completo,
            p.sexo,
            p.tipo_persona,
            p.estado_participante,

            c.nombre AS categoria,
            c.distancia,

            t.nombre AS talla,

            tc.nombre AS tipo_camisa,

            pat.codigo AS codigo_patrocinador,
            pat.nombre AS patrocinador

        FROM PARTICIPANTE p

        INNER JOIN CATEGORIA c
            ON c.id_categoria = p.id_categoria

        INNER JOIN TALLA t
            ON t.id_talla = p.id_talla

        INNER JOIN TIPO_CAMISA tc
            ON tc.id_tipo_camisa = p.id_tipo_camisa

        LEFT JOIN PATROCINADOR pat
            ON pat.id_patrocinador = p.id_patrocinador

        WHERE p.id_inscripcion = ?

        ORDER BY p.id_participante ASC
    ";


    $stmtParticipantes =
        $pdo->prepare($sqlParticipantes);


    $stmtParticipantes->execute([
        $idInscripcion
    ]);


    $participantes =
        $stmtParticipantes->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | 3. OBTENER CAMISA / KIT EXTRA SI EXISTE
    |--------------------------------------------------------------------------
    */

    $sqlCamisaExtra = "
        SELECT
            ce.id_camisa_extra,
            ce.cantidad,
            ce.motivo,

            t.nombre AS talla,
            t.tipo_persona,

            tc.nombre AS tipo_camisa

        FROM CAMISA_EXTRA ce

        INNER JOIN TALLA t
            ON t.id_talla = ce.id_talla

        INNER JOIN TIPO_CAMISA tc
            ON tc.id_tipo_camisa = ce.id_tipo_camisa

        WHERE ce.id_inscripcion = ?

        ORDER BY ce.id_camisa_extra ASC
    ";


    $stmtCamisaExtra =
        $pdo->prepare($sqlCamisaExtra);


    $stmtCamisaExtra->execute([
        $idInscripcion
    ]);


    $camisasExtra =
        $stmtCamisaExtra->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | 4. CONSTRUIR RESPUESTA
    |--------------------------------------------------------------------------
    */

    responderJson(200, [

        "success" => true,

        "inscripcion" => [

            "id_inscripcion" =>
                (int) $inscripcion["id_inscripcion"],

            "folio" =>
                $inscripcion["folio"],

            "estado" =>
                $inscripcion["estado_inscripcion"],

            "estado_pago" =>
                $inscripcion["estado_pago"],

            "estado_entrega" =>
                $inscripcion["estado_entrega"],

            "estado_entrada" =>
                $inscripcion["estado_entrada"],

            "cantidad_participantes" =>
                (int) $inscripcion["cantidad_participantes"],

            "fecha_inscripcion" =>
                $inscripcion["fecha_inscripcion"]

        ],

        "responsable" => [

            "id_responsable" =>
                (int) $inscripcion["id_responsable"],

            "nombre_completo" =>
                $inscripcion["responsable_nombre"]

        ],

        "paquete" => [

            "id_paquete" =>
                (int) $inscripcion["id_paquete"],

            "nombre" =>
                $inscripcion["paquete_nombre"]

        ],

        "participantes" =>
            $participantes,

        "camisas_extra" =>
            $camisasExtra

    ]);


} catch (Throwable $e) {

    error_log(
        "Error consultar inscripción QR: " .
        $e->getMessage()
    );


    responderJson(500, [
        "success" => false,
        "mensaje" => "No fue posible consultar la inscripción.",
        "detalle" => $e->getMessage()
    ]);
}