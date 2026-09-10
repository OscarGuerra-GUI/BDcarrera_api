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

        $codigoPatrocinador = null;


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 1 PARTICIPANTE
        |--------------------------------------------------------------------------
        */

        if (
    $opcion === "Inscripción 1 participante" ||
    $opcion === "Paquete 1 participante"
) {

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "K", $fila)
        );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "J",
        "L",
        "M",
        "N"
    );
}


        /*
        |--------------------------------------------------------------------------
        | PAQUETE ESTUDIANTE INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        elseif (
    $opcion === "Inscripción Estudiante" ||
    $opcion === "Paquete Estudiante"
) {

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "P", $fila)
        );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "O",
        "Q",
        "R",
        "S"
    );
}


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 2 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif (
    $opcion === "Inscripción 2 participantes" ||
    $opcion === "Paquete 2 participantes"
) {

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "U", $fila)
        );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "T",
        "V",
        "W",
        "X"
    );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "Y",
        "Z",
        "AA",
        "AB"
    );
}


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 3 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif (
    $opcion === "Inscripción 3 participantes" ||
    $opcion === "Paquete 3 participantes"
) {

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "AD", $fila)
        );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "AC",
        "AE",
        "AF",
        "AG"
    );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "AH",
        "AI",
        "AJ",
        "AK"
    );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "AL",
        "AM",
        "AN",
        "AO"
    );
}


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 4 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif (
    $opcion === "Inscripción 4 participantes" ||
    $opcion === "Paquete 4 participantes"
) {

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "AQ", $fila)
        );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "AP",
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

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "BC",
        "BD",
        "BE",
        "BF"
    );
}


        /*
        |--------------------------------------------------------------------------
        | PAQUETE 5 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif (
    $opcion === "Inscripción 5 participantes" ||
    $opcion === "Paquete 5 participantes"
) {

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "BH", $fila)
        );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "BG",
        "BI",
        "BJ",
        "BK"
    );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "BL",
        "BM",
        "BN",
        "BO"
    );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "BP",
        "BQ",
        "BR",
        "BS"
    );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "BT",
        "BU",
        "BV",
        "BW"
    );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "BX",
        "BY",
        "BZ",
        "CA"
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

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "CC", $fila)
        );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "CB",
        "CD",
        "CE",
        "CF"
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

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "CH", $fila)
        );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "CG",
        "CI",
        "CJ",
        "CK"
    );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "CL",
        "CM",
        "CN",
        "CO"
    );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "CP",
        null,
        "CQ",
        "CR",
        true
    );
}


        /*
        |--------------------------------------------------------------------------
        | PAQUETE NIÑO
        |--------------------------------------------------------------------------
        */

        elseif (
    $opcion === "Inscripción 1 niño (Máximo 12 años)" ||
    $opcion === "Paquete 1 niño (Máximo 12 años)"
) {

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "CT", $fila)
        );

    agregarParticipante(
        $participantes,
        $hoja,
        $fila,
        "CS",
        null,
        "CU",
        "CV",
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

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "CX", $fila)
        );

    $grupos = [
        ["CW", "CY", "CZ", "DA"],
        ["DB", "DC", "DD", "DE"],
        ["DF", "DG", "DH", "DI"],
        ["DJ", "DK", "DL", "DM"],
        ["DN", "DO", "DP", "DQ"],
        ["DR", "DS", "DT", "DU"],
        ["DV", "DW", "DX", "DY"],
        ["DZ", "EA", "EB", "EC"],
        ["ED", "EE", "EF", "EG"],
        ["EH", "EI", "EJ", "EK"]
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
        "EL",
        $fila
    );

    $sexoKit = celda(
        $hoja,
        "EM",
        $fila
    );

    $camisaKit = celda(
        $hoja,
        "EN",
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

    $codigoPatrocinador =
        normalizarCodigoPatrocinador(
            celda($hoja, "EP", $fila)
        );

    $grupos = [
        ["EO", "EQ", "ER", "ES"],
        ["ET", "EU", "EV", "EW"],
        ["EX", "EY", "EZ", "FA"],
        ["FB", "FC", "FD", "FE"],
        ["FF", "FG", "FH", "FI"],
        ["FJ", "FK", "FL", "FM"],
        ["FN", "FO", "FP", "FQ"],
        ["FR", "FS", "FT", "FU"],
        ["FV", "FW", "FX", "FY"],
        ["FZ", "GA", "GB", "GC"]
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
        "GD",
        $fila
    );

    $sexoKit = celda(
        $hoja,
        "GE",
        $fila
    );

    $camisaKit = celda(
        $hoja,
        "GF",
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
                    $opcion,

                "codigo_patrocinador" =>
                    $codigoPatrocinador

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


function normalizarCodigoPatrocinador(
    string $codigo
): ?string {

    $codigo = trim($codigo);

    if ($codigo === "") {
        return null;
    }

    /*
     * En algunas respuestas de Forms,
     * la persona puede seleccionar/escribir "No".
     * Eso no es realmente un código.
     */

    if (
        strcasecmp($codigo, "No") === 0 ||
        strcasecmp($codigo, "N/A") === 0 ||
        strcasecmp($codigo, "NA") === 0
    ) {
        return null;
    }

    return $codigo;
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
    bool $forzarNino = false
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