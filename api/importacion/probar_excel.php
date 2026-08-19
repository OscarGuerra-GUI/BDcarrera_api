<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header("Content-Type: application/json; charset=utf-8");


/*
|--------------------------------------------------------------------------
| VALIDAR MÉTODO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "mensaje" => "Utiliza POST y envía un archivo Excel en el campo archivo."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDAR ARCHIVO
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES["archivo"]) ||
    $_FILES["archivo"]["error"] !== UPLOAD_ERR_OK
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "mensaje" => "No se recibió correctamente el archivo Excel."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


$rutaTemporal = $_FILES["archivo"]["tmp_name"];


try {

    /*
    |--------------------------------------------------------------------------
    | ABRIR EXCEL
    |--------------------------------------------------------------------------
    */

    $spreadsheet = IOFactory::load($rutaTemporal);

    $hoja = $spreadsheet->getActiveSheet();

    $ultimaFila = $hoja->getHighestDataRow();

    $registros = [];


    /*
    |--------------------------------------------------------------------------
    | RECORRER RESPUESTAS
    |--------------------------------------------------------------------------
    */

    for ($fila = 2; $fila <= $ultimaFila; $fila++) {

        $responseId = celda(
            $hoja,
            "A",
            $fila
        );


        if ($responseId === "") {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSABLE
        |--------------------------------------------------------------------------
        */

        $nombreResponsable = celda(
            $hoja,
            "F",
            $fila
        );

        $telefono = celda(
            $hoja,
            "G",
            $fila
        );

        $correo = celda(
            $hoja,
            "H",
            $fila
        );


        /*
        |--------------------------------------------------------------------------
        | OPCIÓN DE INSCRIPCIÓN
        |--------------------------------------------------------------------------
        */

        $opcion = celda(
            $hoja,
            "I",
            $fila
        );


        $participantes = [];

        $camisaExtra = null;


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 1 PARTICIPANTE
        |--------------------------------------------------------------------------
        */

        if ($opcion === "Paquete 1 participante") {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "J",
                "L",
                "M",
                "N",
                false,
                celda($hoja, "K", $fila)
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PAQUETE ESTUDIANTE INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        elseif ($opcion === "Paquete Estudiante") {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "O",
                "P",
                "Q",
                "R"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 2 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif ($opcion === "Paquete 2 participantes") {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "S",
                "T",
                "U",
                "V"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "W",
                "X",
                "Y",
                "Z"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 3 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif ($opcion === "Paquete 3 participantes") {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "AA",
                "AB",
                "AC",
                "AD"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "AE",
                "AF",
                "AG",
                "AH"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "AI",
                "AJ",
                "AK",
                "AL"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 4 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif ($opcion === "Paquete 4 participantes") {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "AM",
                "AN",
                "AO",
                "AP"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "AQ",
                "AR",
                "AS",
                "AT"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "AU",
                "AV",
                "AW",
                "AX"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "AY",
                "AZ",
                "BA",
                "BB"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 5 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif ($opcion === "Paquete 5 participantes") {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "BC",
                "BD",
                "BE",
                "BF"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "BG",
                "BH",
                "BI",
                "BJ"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "BK",
                "BL",
                "BM",
                "BN"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "BO",
                "BP",
                "BQ",
                "BR"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "BS",
                "BT",
                "BU",
                "BV"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PERSONA CON DISCAPACIDAD / ADULTO MAYOR / PROCESO ONCOLÓGICO
        |--------------------------------------------------------------------------
        */

        elseif (
            $opcion ===
            "Persona con Discapacidad, Adulto mayor o en Proceso Oncológico"
        ) {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "BW",
                "BX",
                "BY",
                "BZ"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PAQUETE FAMILIAR
        |--------------------------------------------------------------------------
        */

        elseif (
            $opcion ===
            "Paquete Familiar (2 adultos y 1 niño)"
        ) {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "CA",
                "CB",
                "CC",
                "CD"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "CE",
                "CF",
                "CG",
                "CH"
            );

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "CI",
                null,
                "CJ",
                "CK",
                true
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PAQUETE NIÑO
        |--------------------------------------------------------------------------
        */

        elseif ($opcion === "Paquete niño") {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "CL",
                null,
                "CM",
                "CN",
                true
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PAQUETE COLABORATIVO
        |--------------------------------------------------------------------------
        */

        elseif (
            $opcion ===
            "Paquete Colaborativo (10 personas + 1 Kit de regalo)"
        ) {

            $grupos = [
                ["CO", "CP", "CQ", "CR"],
                ["CS", "CT", "CU", "CV"],
                ["CW", "CX", "CY", "CZ"],
                ["DA", "DB", "DC", "DD"],
                ["DE", "DF", "DG", "DH"],
                ["DI", "DJ", "DK", "DL"],
                ["DM", "DN", "DO", "DP"],
                ["DQ", "DR", "DS", "DT"],
                ["DU", "DV", "DW", "DX"],
                ["DY", "DZ", "EA", "EB"]
            ];


            foreach ($grupos as $grupo) {

                agregarParticipante(
                    $participantes,
                    $hoja,
                    $fila,
                    $grupo[0],
                    $grupo[1],
                    $grupo[2],
                    $grupo[3]
                );
            }


            $datoKit = celda(
                $hoja,
                "EC",
                $fila
            );

            $sexoKit = celda(
                $hoja,
                "ED",
                $fila
            );

            $camisaKit = celda(
                $hoja,
                "EE",
                $fila
            );


            if ($camisaKit !== "") {

                [
                    $tipoPersonaKit,
                    $tallaKit
                ] = separarCamisa($camisaKit);


                $camisaExtra = [

                    "nombre" =>
                        $datoKit,

                    "sexo" =>
                        $sexoKit,

                    "tipo_persona" =>
                        $tipoPersonaKit,

                    "talla" =>
                        $tallaKit,

                    "tipo_camisa" =>
                        $tipoPersonaKit,

                    "cantidad" =>
                        1,

                    "motivo" =>
                        "Kit de regalo paquete colaborativo"

                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PAQUETE ESTUDIANTES
        |--------------------------------------------------------------------------
        */

        elseif (
            $opcion ===
            "Paquete estudiantes (10 estudiantes + 1 Kit de regalo)"
        ) {

            $grupos = [
                ["EF", "EG", "EH", "EI"],
                ["EJ", "EK", "EL", "EM"],
                ["EN", "EO", "EP", "EQ"],
                ["ER", "ES", "ET", "EU"],
                ["EV", "EW", "EX", "EY"],
                ["EZ", "FA", "FB", "FC"],
                ["FD", "FE", "FF", "FG"],
                ["FH", "FI", "FJ", "FK"],
                ["FL", "FM", "FN", "FO"],
                ["FP", "FQ", "FR", "FS"]
            ];


            foreach ($grupos as $grupo) {

                agregarParticipante(
                    $participantes,
                    $hoja,
                    $fila,
                    $grupo[0],
                    $grupo[1],
                    $grupo[2],
                    $grupo[3]
                );
            }


            $datoKit = celda(
                $hoja,
                "FT",
                $fila
            );

            $sexoKit = celda(
                $hoja,
                "FU",
                $fila
            );

            $camisaKit = celda(
                $hoja,
                "FV",
                $fila
            );


            if ($camisaKit !== "") {

                [
                    $tipoPersonaKit,
                    $tallaKit
                ] = separarCamisa($camisaKit);


                $camisaExtra = [

                    "nombre" =>
                        $datoKit,

                    "sexo" =>
                        $sexoKit,

                    "tipo_persona" =>
                        $tipoPersonaKit,

                    "talla" =>
                        $tallaKit,

                    "tipo_camisa" =>
                        $tipoPersonaKit,

                    "cantidad" =>
                        1,

                    "motivo" =>
                        "Kit de regalo paquete estudiantes"

                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | OPCIÓN NO RECONOCIDA
        |--------------------------------------------------------------------------
        */

        else {

            throw new RuntimeException(
                "Opción de inscripción no reconocida en la respuesta " .
                $responseId .
                ": " .
                $opcion
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CREAR REGISTRO FINAL
        |--------------------------------------------------------------------------
        */

        $registros[] = [

            "response_id_forms" =>
                $responseId,

            "responsable" => [

                "nombre_completo" =>
                    $nombreResponsable,

                "telefono" =>
                    $telefono,

                "correo" =>
                    $correo

            ],

            "inscripcion" => [

                "id_evento" =>
                    1,

                "opcion_inscripcion" =>
                    $opcion

            ],

            "participantes" =>
                $participantes,

            "camisa_extra" =>
                $camisaExtra

        ];
    }


    echo json_encode(
        [
            "success" => true,
            "total" => count($registros),
            "registros" => $registros
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PRETTY_PRINT
    );


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode(
        [
            "success" => false,
            "mensaje" => $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}


/*
|--------------------------------------------------------------------------
| LEER CELDA
|--------------------------------------------------------------------------
*/

function celda(
    $hoja,
    string $columna,
    int $fila
): string {

    $valor = $hoja
        ->getCell($columna . $fila)
        ->getValue();

    return trim(
        (string) ($valor ?? "")
    );
}


/*
|--------------------------------------------------------------------------
| AGREGAR PARTICIPANTE
|--------------------------------------------------------------------------
*/

function agregarParticipante(
    array &$participantes,
    $hoja,
    int $fila,
    string $colNombre,
    ?string $colSexo,
    string $colCamisa,
    string $colCategoria,
    bool $forzarNino = false,
    ?string $codigoPatrocinador = null
): void {

    $nombre = celda(
        $hoja,
        $colNombre,
        $fila
    );


    if ($nombre === "") {
        return;
    }


    $sexo = "";

    if ($colSexo !== null) {

        $sexo = celda(
            $hoja,
            $colSexo,
            $fila
        );
    }


    $camisa = celda(
        $hoja,
        $colCamisa,
        $fila
    );


    $categoria = celda(
        $hoja,
        $colCategoria,
        $fila
    );


    [
        $tipoPersona,
        $talla
    ] = separarCamisa($camisa);


    if ($forzarNino) {

        $tipoPersona = "Niño";
    }


    $codigoPatrocinador =
        trim((string) $codigoPatrocinador);


    if ($codigoPatrocinador === "") {

        $codigoPatrocinador = null;
    }


    $participantes[] = [

        "nombre_completo" =>
            $nombre,

        "sexo" =>
            $sexo,

        "tipo_persona" =>
            $tipoPersona,

        "categoria" =>
            $categoria,

        "talla" =>
            $talla,

        "tipo_camisa" =>
            $tipoPersona,

        "codigo_patrocinador" =>
            $codigoPatrocinador

    ];
}


/*
|--------------------------------------------------------------------------
| SEPARAR CAMISA
|--------------------------------------------------------------------------
*/

function separarCamisa(
    string $texto
): array {

    $texto = trim(
        str_replace(
            "\xc2\xa0",
            " ",
            $texto
        )
    );


    if ($texto === "") {

        return [
            "",
            ""
        ];
    }


    if (
        preg_match(
            '/^(Adulto|Niño|Niña|Niñ@)\s+Talla\s+(.+)$/ui',
            $texto,
            $coincidencias
        )
    ) {

        $tipoPersona =
            trim($coincidencias[1]);

        $talla =
            trim($coincidencias[2]);


        if (
            $tipoPersona === "Niña" ||
            $tipoPersona === "Niñ@"
        ) {

            $tipoPersona = "Niño";
        }


        return [
            $tipoPersona,
            $talla
        ];
    }


    throw new RuntimeException(
        "No fue posible interpretar la talla/camisa: " .
        $texto
    );
}