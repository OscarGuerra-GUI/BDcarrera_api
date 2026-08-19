<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "mensaje" => "Utiliza POST y envía un archivo Excel en el campo archivo."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

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

    $spreadsheet = IOFactory::load($rutaTemporal);
    $hoja = $spreadsheet->getActiveSheet();

    $ultimaFila = $hoja->getHighestDataRow();

    $registros = [];

    for ($fila = 2; $fila <= $ultimaFila; $fila++) {

        $responseId = trim(
            (string) $hoja->getCell("A{$fila}")->getValue()
        );

        if ($responseId === "") {
            continue;
        }

        $nombreResponsable = trim(
            (string) $hoja->getCell("F{$fila}")->getValue()
        );

        $telefono = trim(
            (string) $hoja->getCell("G{$fila}")->getValue()
        );

        $correo = trim(
            (string) $hoja->getCell("H{$fila}")->getValue()
        );

        $opcion = trim(
            (string) $hoja->getCell("I{$fila}")->getValue()
        );

        $participantes = [];
        $camisaExtra = null;

        /*
        |--------------------------------------------------------------------------
        | PAQUETE 1 PARTICIPANTE
        |--------------------------------------------------------------------------
        */

        if (
            stripos($opcion, "1 participante") !== false ||
            stripos($opcion, "1 persona") !== false
        ) {

            agregarParticipante(
                $participantes,
                $hoja,
                $fila,
                "J",
                "L",
                "M",
                "N",
                false
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAQUETE 2 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif (
            stripos($opcion, "2 particip") !== false ||
            stripos($opcion, "2 persona") !== false
        ) {

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

        elseif (
            stripos($opcion, "3 particip") !== false ||
            stripos($opcion, "3 persona") !== false
        ) {

            agregarParticipante($participantes, $hoja, $fila, "AA", "AB", "AC", "AD");
            agregarParticipante($participantes, $hoja, $fila, "AE", "AF", "AG", "AH");
            agregarParticipante($participantes, $hoja, $fila, "AI", "AJ", "AK", "AL");
        }

        /*
        |--------------------------------------------------------------------------
        | PAQUETE 4 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif (
            stripos($opcion, "4 particip") !== false ||
            stripos($opcion, "4 persona") !== false
        ) {

            agregarParticipante($participantes, $hoja, $fila, "AM", "AN", "AO", "AP");
            agregarParticipante($participantes, $hoja, $fila, "AQ", "AR", "AS", "AT");
            agregarParticipante($participantes, $hoja, $fila, "AU", "AV", "AW", "AX");
            agregarParticipante($participantes, $hoja, $fila, "AY", "AZ", "BA", "BB");
        }

        /*
        |--------------------------------------------------------------------------
        | PAQUETE 5 PARTICIPANTES
        |--------------------------------------------------------------------------
        */

        elseif (
            stripos($opcion, "5 particip") !== false ||
            stripos($opcion, "5 persona") !== false
        ) {

            agregarParticipante($participantes, $hoja, $fila, "BC", "BD", "BE", "BF");
            agregarParticipante($participantes, $hoja, $fila, "BG", "BH", "BI", "BJ");
            agregarParticipante($participantes, $hoja, $fila, "BK", "BL", "BM", "BN");
            agregarParticipante($participantes, $hoja, $fila, "BO", "BP", "BQ", "BR");
            agregarParticipante($participantes, $hoja, $fila, "BS", "BT", "BU", "BV");
        }

        /*
        |--------------------------------------------------------------------------
        | INDIVIDUAL ESPECIAL
        | Niño / discapacidad / adulto mayor / proceso oncológico
        |--------------------------------------------------------------------------
        */

        elseif (
            stripos($opcion, "niñ") !== false ||
            stripos($opcion, "discapacidad") !== false ||
            stripos($opcion, "adulto mayor") !== false ||
            stripos($opcion, "oncol") !== false
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

        elseif (stripos($opcion, "familiar") !== false) {

            agregarParticipante($participantes, $hoja, $fila, "CA", "CB", "CC", "CD");
            agregarParticipante($participantes, $hoja, $fila, "CE", "CF", "CG", "CH");

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

        elseif (stripos($opcion, "colaborativo") !== false) {

            $columnas = [
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

            foreach ($columnas as $grupo) {
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

            $nombreKit = trim(
                (string) $hoja->getCell("EC{$fila}")->getValue()
            );

            $sexoKit = trim(
                (string) $hoja->getCell("ED{$fila}")->getValue()
            );

            $camisaKit = trim(
                (string) $hoja->getCell("EE{$fila}")->getValue()
            );

            if ($nombreKit !== "" || $camisaKit !== "") {

                [$tipoPersonaKit, $tallaKit] =
                    separarCamisa($camisaKit);

                $camisaExtra = [
                    "nombre" => $nombreKit,
                    "sexo" => $sexoKit,
                    "tipo_persona" => $tipoPersonaKit,
                    "talla" => $tallaKit,
                    "tipo_camisa" => $tipoPersonaKit,
                    "cantidad" => 1,
                    "motivo" => "Kit de regalo paquete colaborativo"
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PAQUETE ESTUDIANTES
        |--------------------------------------------------------------------------
        */

        elseif (stripos($opcion, "estudiante") !== false) {

            $columnas = [
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

            foreach ($columnas as $grupo) {

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

            $camisaKit = trim(
                (string) $hoja->getCell("FV{$fila}")->getValue()
            );

            if ($camisaKit !== "") {

                [$tipoPersonaKit, $tallaKit] =
                    separarCamisa($camisaKit);

                $camisaExtra = [
                    "nombre" => trim(
                        (string) $hoja->getCell("FT{$fila}")->getValue()
                    ),
                    "sexo" => trim(
                        (string) $hoja->getCell("FU{$fila}")->getValue()
                    ),
                    "tipo_persona" => $tipoPersonaKit,
                    "talla" => $tallaKit,
                    "tipo_camisa" => $tipoPersonaKit,
                    "cantidad" => 1,
                    "motivo" => "Kit de regalo paquete estudiantes"
                ];
            }
        }

        $registros[] = [
            "response_id_forms" => $responseId,

            "responsable" => [
                "nombre_completo" => $nombreResponsable,
                "telefono" => $telefono,
                "correo" => $correo
            ],

            "inscripcion" => [
                "id_evento" => 1,
                "opcion_inscripcion" => $opcion
            ],

            "participantes" => $participantes,

            "camisa_extra" => $camisaExtra
        ];
    }

    echo json_encode([
        "success" => true,
        "total" => count($registros),
        "registros" => $registros
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


/*
|--------------------------------------------------------------------------
| FUNCIONES
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
    bool $esNino = false
): void {

    $nombre = trim(
        (string) $hoja->getCell($colNombre . $fila)->getValue()
    );

    if ($nombre === "") {
        return;
    }

    $sexo = "";

    if ($colSexo !== null) {
        $sexo = trim(
            (string) $hoja->getCell($colSexo . $fila)->getValue()
        );
    }

    $camisa = trim(
        (string) $hoja->getCell($colCamisa . $fila)->getValue()
    );

    $categoria = trim(
        (string) $hoja->getCell($colCategoria . $fila)->getValue()
    );

    [$tipoPersona, $talla] = separarCamisa($camisa);

    if ($esNino) {
        $tipoPersona = "Niño";
    }

    $participantes[] = [
        "nombre_completo" => $nombre,
        "sexo" => $sexo,
        "tipo_persona" => $tipoPersona,
        "categoria" => $categoria,
        "talla" => $talla,
        "tipo_camisa" => $tipoPersona,
        "codigo_patrocinador" => null
    ];
}


function separarCamisa(string $texto): array
{
    $texto = trim(
        str_replace("\xc2\xa0", " ", $texto)
    );

    if ($texto === "") {
        return ["", ""];
    }

    if (
        preg_match(
            '/^(Adulto|Niñ[oa]|Niño|Niña)\s+Talla\s+(.+)$/iu',
            $texto,
            $coincidencias
        )
    ) {
        return [
            trim($coincidencias[1]),
            trim($coincidencias[2])
        ];
    }

    return ["Adulto", trim($texto)];
}