<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/seguridad.php';

header("Content-Type: application/json; charset=utf-8");

verificarApiKey();

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    responderJson(405, [
        "success" => false,
        "mensaje" => "Método no permitido. Utiliza GET."
    ]);
}

try {

    $pdo = obtenerConexion();


    /*
    |--------------------------------------------------------------------------
    | ADULTOS - PARTICIPANTES PENDIENTES
    |--------------------------------------------------------------------------
    |
    | Separamos por:
    | - talla
    | - sexo
    |
    */

    $sqlAdultos = "
        SELECT
            t.nombre AS talla,
            p.sexo,
            COUNT(*) AS cantidad

        FROM PARTICIPANTE p

        INNER JOIN INSCRIPCION i
            ON i.id_inscripcion = p.id_inscripcion

        INNER JOIN TALLA t
            ON t.id_talla = p.id_talla

        WHERE i.estado_entrega = 'PENDIENTE'
          AND (
                p.tipo_persona = 'Adulto'
                OR t.tipo_persona = 'Adulto'
              )

        GROUP BY
            t.nombre,
            p.sexo
    ";


    $stmtAdultos =
        $pdo->prepare($sqlAdultos);

    $stmtAdultos->execute();

    $adultosParticipantes =
        $stmtAdultos->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | NIÑOS - PARTICIPANTES PENDIENTES
    |--------------------------------------------------------------------------
    |
    | En niños no separamos por sexo.
    |
    */

    $sqlNinos = "
        SELECT
            t.nombre AS talla,
            COUNT(*) AS cantidad

        FROM PARTICIPANTE p

        INNER JOIN INSCRIPCION i
            ON i.id_inscripcion = p.id_inscripcion

        INNER JOIN TALLA t
            ON t.id_talla = p.id_talla

        WHERE i.estado_entrega = 'PENDIENTE'
          AND (
                p.tipo_persona = 'Niño'
                OR t.tipo_persona = 'Niño'
              )

        GROUP BY
            t.nombre
    ";


    $stmtNinos =
        $pdo->prepare($sqlNinos);

    $stmtNinos->execute();

    $ninosParticipantes =
        $stmtNinos->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | CAMISAS EXTRA
    |--------------------------------------------------------------------------
    |
    | Las camisas extra no necesariamente tienen sexo.
    | Por eso:
    |
    | - Si son Adulto, se suman al total de la talla.
    | - Si son Niño, se suman normalmente a la talla.
    |
    */

    $sqlExtras = "
        SELECT
            t.nombre AS talla,
            t.tipo_persona,
            SUM(ce.cantidad) AS cantidad

        FROM CAMISA_EXTRA ce

        INNER JOIN INSCRIPCION i
            ON i.id_inscripcion = ce.id_inscripcion

        INNER JOIN TALLA t
            ON t.id_talla = ce.id_talla

        WHERE i.estado_entrega = 'PENDIENTE'

        GROUP BY
            t.nombre,
            t.tipo_persona
    ";


    $stmtExtras =
        $pdo->prepare($sqlExtras);

    $stmtExtras->execute();

    $camisasExtra =
        $stmtExtras->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | ARMAR ADULTOS
    |--------------------------------------------------------------------------
    */

    $adultos = [];


    foreach ($adultosParticipantes as $fila) {

        $talla =
            trim((string) $fila["talla"]);

        $sexo =
            trim((string) ($fila["sexo"] ?? ""));

        $cantidad =
            (int) $fila["cantidad"];


        if (!isset($adultos[$talla])) {

            $adultos[$talla] = [
                "talla" => $talla,
                "masculino" => 0,
                "femenino" => 0,
                "sin_especificar" => 0,
                "extras" => 0,
                "total" => 0
            ];
        }


        if (strcasecmp($sexo, "Masculino") === 0) {

            $adultos[$talla]["masculino"] +=
                $cantidad;

        } elseif (
            strcasecmp($sexo, "Femenino") === 0
        ) {

            $adultos[$talla]["femenino"] +=
                $cantidad;

        } else {

            $adultos[$talla]["sin_especificar"] +=
                $cantidad;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ARMAR NIÑOS
    |--------------------------------------------------------------------------
    */

    $ninos = [];


    foreach ($ninosParticipantes as $fila) {

        $talla =
            trim((string) $fila["talla"]);

        $cantidad =
            (int) $fila["cantidad"];


        if (!isset($ninos[$talla])) {

            $ninos[$talla] = [
                "talla" => $talla,
                "cantidad" => 0
            ];
        }


        $ninos[$talla]["cantidad"] +=
            $cantidad;
    }


    /*
    |--------------------------------------------------------------------------
    | SUMAR CAMISAS EXTRA
    |--------------------------------------------------------------------------
    */

    foreach ($camisasExtra as $fila) {

        $talla =
            trim((string) $fila["talla"]);

        $tipoPersona =
            trim((string) $fila["tipo_persona"]);

        $cantidad =
            (int) $fila["cantidad"];


        if (
            strcasecmp(
                $tipoPersona,
                "Adulto"
            ) === 0
        ) {

            if (!isset($adultos[$talla])) {

                $adultos[$talla] = [
                    "talla" => $talla,
                    "masculino" => 0,
                    "femenino" => 0,
                    "sin_especificar" => 0,
                    "extras" => 0,
                    "total" => 0
                ];
            }


            $adultos[$talla]["extras"] +=
                $cantidad;


        } elseif (
            strcasecmp(
                $tipoPersona,
                "Niño"
            ) === 0
        ) {

            if (!isset($ninos[$talla])) {

                $ninos[$talla] = [
                    "talla" => $talla,
                    "cantidad" => 0
                ];
            }


            $ninos[$talla]["cantidad"] +=
                $cantidad;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CALCULAR TOTALES DE ADULTO
    |--------------------------------------------------------------------------
    */

    $totalAdultos = 0;


    foreach ($adultos as $talla => $datos) {

        $adultos[$talla]["total"] =
            $datos["masculino"]
            + $datos["femenino"]
            + $datos["sin_especificar"]
            + $datos["extras"];


        $totalAdultos +=
            $adultos[$talla]["total"];
    }


    /*
    |--------------------------------------------------------------------------
    | CALCULAR TOTAL DE NIÑOS
    |--------------------------------------------------------------------------
    */

    $totalNinos = 0;


    foreach ($ninos as $datos) {

        $totalNinos +=
            (int) $datos["cantidad"];
    }


    /*
    |--------------------------------------------------------------------------
    | ORDENAR TALLAS
    |--------------------------------------------------------------------------
    */

    $ordenAdultos = [
        "CH",
        "M",
        "G",
        "XG",
        "XXG"
    ];


    $ordenNinos = [
        "6-8",
        "8-10",
        "10-12"
    ];


    $adultosOrdenados = [];


    foreach ($ordenAdultos as $talla) {

        if (isset($adultos[$talla])) {

            $adultosOrdenados[] =
                $adultos[$talla];
        }
    }


    /*
    | Por si en un futuro aparece una talla
    | distinta a las conocidas.
    */

    foreach ($adultos as $talla => $datos) {

        if (!in_array(
            $talla,
            $ordenAdultos,
            true
        )) {

            $adultosOrdenados[] =
                $datos;
        }
    }


    $ninosOrdenados = [];


    foreach ($ordenNinos as $talla) {

        if (isset($ninos[$talla])) {

            $ninosOrdenados[] =
                $ninos[$talla];
        }
    }


    foreach ($ninos as $talla => $datos) {

        if (!in_array(
            $talla,
            $ordenNinos,
            true
        )) {

            $ninosOrdenados[] =
                $datos;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA
    |--------------------------------------------------------------------------
    */

    responderJson(200, [

        "success" => true,

        "total_pendientes" =>
            $totalAdultos + $totalNinos,

        "total_adultos" =>
            $totalAdultos,

        "total_ninos" =>
            $totalNinos,

        "adultos" =>
            $adultosOrdenados,

        "ninos" =>
            $ninosOrdenados
    ]);


} catch (Throwable $e) {

    error_log(
        "Error reporte camisetas: "
        . $e->getMessage()
    );


    responderJson(500, [
        "success" => false,
        "mensaje" =>
            "No fue posible generar el reporte de camisetas.",
        "detalle" =>
            $e->getMessage()
    ]);
}