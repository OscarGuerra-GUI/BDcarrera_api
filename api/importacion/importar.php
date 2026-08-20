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
        "mensaje" => "El cuerpo debe contener JSON válido."
    ]);
}

$registros = $datos["registros"] ?? null;

if (!is_array($registros)) {
    responderJson(422, [
        "success" => false,
        "mensaje" => "No se recibió el arreglo de registros."
    ]);
}


try {
    $conexion = obtenerConexion();
} catch (Throwable $e) {
    responderJson(500, [
        "success" => false,
        "mensaje" => "No fue posible conectar con la base de datos."
    ]);
}


$total = count($registros);
$insertados = 0;
$omitidos = 0;
$errores = 0;

$resultados = [];

/*
|--------------------------------------------------------------------------
| URL BASE PARA LOS QR
|--------------------------------------------------------------------------
| Si después configuras APP_BASE_URL en Railway, se usará automáticamente.
| Mientras tanto utiliza tu dominio actual.
*/
$baseUrl = rtrim(
    getenv("APP_BASE_URL")
        ?: "https://bdcarreraapi-production.up.railway.app",
    "/"
);


foreach ($registros as $indice => $registro) {


    $responseId = trim(
        (string) ($registro["response_id_forms"] ?? "")
    );

    $responsable = $registro["responsable"] ?? [];
    $inscripcion = $registro["inscripcion"] ?? [];
    $participantes = $registro["participantes"] ?? [];
    $camisaExtra = $registro["camisa_extra"] ?? null;

    if ($responseId === "") {
        $errores++;

        $resultados[] = [
            "indice" => $indice,
            "estado" => "error",
            "mensaje" => "Falta response_id_forms."
        ];

        continue;
    }


    $sqlDuplicado = "
        SELECT
            i.id_inscripcion,
            i.folio,
            q.token
        FROM INSCRIPCION i
        LEFT JOIN CODIGO_QR q
            ON q.id_inscripcion = i.id_inscripcion
           AND q.estado = 'ACTIVO'
        WHERE i.response_id_forms = :response_id
        LIMIT 1
    ";

    $consultaDuplicado = $conexion->prepare($sqlDuplicado);

    $consultaDuplicado->execute([
        ":response_id" => $responseId
    ]);

    $existente = $consultaDuplicado->fetch();

    if ($existente) {
        $omitidos++;

        $tokenExistente = $existente["token"] ?? null;

        $resultados[] = [
            "response_id_forms" => $responseId,
            "estado" => "omitido",
            "mensaje" => "La respuesta ya había sido importada.",
            "id_inscripcion" => (int) $existente["id_inscripcion"],
            "folio" => $existente["folio"],
            "token_qr" => $tokenExistente,
            "url_qr" => $tokenExistente
                ? $baseUrl .
                    "/api/qr/imagen.php?token=" .
                    rawurlencode((string) $tokenExistente)
                : null
        ];

        continue;
    }


    $nombreResponsable = trim(
        (string) ($responsable["nombre_completo"] ?? "")
    );

    $telefonoResponsable = trim(
        (string) ($responsable["telefono"] ?? "")
    );

    $correoResponsable = trim(
        (string) ($responsable["correo"] ?? "")
    );

    if (
        $nombreResponsable === "" ||
        $telefonoResponsable === ""
    ) {
        $errores++;

        $resultados[] = [
            "response_id_forms" => $responseId,
            "estado" => "error",
            "mensaje" => "Nombre y teléfono del responsable son obligatorios."
        ];

        continue;
    }


    $idEvento = filter_var(
        $inscripcion["id_evento"] ?? null,
        FILTER_VALIDATE_INT
    );

    $opcionInscripcion = trim(
        (string) ($inscripcion["opcion_inscripcion"] ?? "")
    );

    if (
        $idEvento === false ||
        $idEvento < 1 ||
        $opcionInscripcion === ""
    ) {
        $errores++;

        $resultados[] = [
            "response_id_forms" => $responseId,
            "estado" => "error",
            "mensaje" => "Evento u opción de inscripción inválidos."
        ];

        continue;
    }

    if (!is_array($participantes) || count($participantes) < 1) {
        $errores++;

        $resultados[] = [
            "response_id_forms" => $responseId,
            "estado" => "error",
            "mensaje" => "La inscripción debe contener al menos un participante."
        ];

        continue;
    }

    try {


        $conexion->beginTransaction();


        $sqlPaquete = "
            SELECT
                id_paquete,
                cantidad_participantes
            FROM PAQUETE
            WHERE nombre = :nombre
              AND estado = 'ACTIVO'
            LIMIT 1
        ";

        $consultaPaquete = $conexion->prepare($sqlPaquete);

        $consultaPaquete->execute([
            ":nombre" => $opcionInscripcion
        ]);

        $paquete = $consultaPaquete->fetch();

        if (!$paquete) {
            throw new RuntimeException(
                "No existe un paquete activo llamado: " .
                $opcionInscripcion
            );
        }

        $idPaquete = (int) $paquete["id_paquete"];


        $sqlResponsable = "
            INSERT INTO RESPONSABLE
            (
                nombre_completo,
                telefono,
                correo
            )
            VALUES
            (
                :nombre,
                :telefono,
                :correo
            )
        ";

        $consultaResponsable =
            $conexion->prepare($sqlResponsable);

        $consultaResponsable->execute([
            ":nombre" => $nombreResponsable,
            ":telefono" => $telefonoResponsable,
            ":correo" =>
                $correoResponsable !== ""
                    ? $correoResponsable
                    : null
        ]);

        $idResponsable =
            (int) $conexion->lastInsertId();


        $folioInscripcion =
            "INS-" .
            date("Y") .
            "-" .
            strtoupper(bin2hex(random_bytes(4)));


        $sqlInscripcion = "
            INSERT INTO INSCRIPCION
            (
                id_responsable,
                id_evento,
                id_paquete,
                folio,
                cantidad_participantes,
                estado_inscripcion,
                estado_pago,
                response_id_forms
            )
            VALUES
            (
                :id_responsable,
                :id_evento,
                :id_paquete,
                :folio,
                :cantidad,
                :estado_inscripcion,
                :estado_pago,
                :response_id_forms
            )
        ";

        $consultaInscripcion =
            $conexion->prepare($sqlInscripcion);

        $consultaInscripcion->execute([
            ":id_responsable" => $idResponsable,
            ":id_evento" => $idEvento,
            ":id_paquete" => $idPaquete,
            ":folio" => $folioInscripcion,
            ":cantidad" => count($participantes),
            ":estado_inscripcion" => "REGISTRADA",
            ":estado_pago" => "PENDIENTE",
            ":response_id_forms" => $responseId
        ]);

        $idInscripcion =
            (int) $conexion->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | GENERAR TOKEN QR PARA LA INSCRIPCIÓN
        |--------------------------------------------------------------------------
        | Se crea dentro de la misma transacción. Si después falla un participante
        | o una camisa extra, el rollback también elimina este CODIGO_QR.
        */

        $tokenQr = bin2hex(random_bytes(32));

        $sqlQr = "
            INSERT INTO CODIGO_QR
            (
                id_inscripcion,
                token,
                ruta_archivo,
                estado
            )
            VALUES
            (
                :id_inscripcion,
                :token,
                NULL,
                'ACTIVO'
            )
        ";

        $consultaQr = $conexion->prepare($sqlQr);

        $consultaQr->execute([
            ":id_inscripcion" => $idInscripcion,
            ":token" => $tokenQr
        ]);

        $urlQr =
            $baseUrl .
            "/api/qr/imagen.php?token=" .
            rawurlencode($tokenQr);


        foreach ($participantes as $numero => $participante) {

            $nombreParticipante = trim(
                (string) ($participante["nombre_completo"] ?? "")
            );

            $sexo = trim(
                (string) ($participante["sexo"] ?? "")
            );

            $tipoPersona = trim(
                (string) ($participante["tipo_persona"] ?? "")
            );

            $categoriaNombre = trim(
                (string) ($participante["categoria"] ?? "")
            );

            $tallaNombre = trim(
                (string) ($participante["talla"] ?? "")
            );

            $tipoCamisaNombre = trim(
                (string) ($participante["tipo_camisa"] ?? "")
            );

            $codigoPatrocinador = trim(
                (string) (
                    $participante["codigo_patrocinador"] ?? ""
                )
            );

            if (
                $nombreParticipante === "" ||
                $categoriaNombre === "" ||
                $tallaNombre === "" ||
                $tipoCamisaNombre === ""
            ) {
                throw new RuntimeException(
                    "Datos incompletos en participante " .
                    ($numero + 1)
                );
            }


            $sqlCategoria = "
                SELECT id_categoria
                FROM CATEGORIA
                WHERE id_evento = :id_evento
                  AND nombre = :nombre
                  AND estado = 'ACTIVO'
                LIMIT 1
            ";

            $consultaCategoria =
                $conexion->prepare($sqlCategoria);

            $consultaCategoria->execute([
                ":id_evento" => $idEvento,
                ":nombre" => $categoriaNombre
            ]);

            $categoria = $consultaCategoria->fetch();

            if (!$categoria) {
                throw new RuntimeException(
                    "Categoría no encontrada: " .
                    $categoriaNombre
                );
            }

            $idCategoria =
                (int) $categoria["id_categoria"];


            $sqlTalla = "
                SELECT id_talla
                FROM TALLA
                WHERE nombre = :nombre
                  AND tipo_persona = :tipo_persona
                  AND estado = 'ACTIVO'
                LIMIT 1
            ";

            $consultaTalla =
                $conexion->prepare($sqlTalla);

            $consultaTalla->execute([
                ":nombre" => $tallaNombre,
                ":tipo_persona" => $tipoPersona
            ]);

            $talla = $consultaTalla->fetch();

            if (!$talla) {
                throw new RuntimeException(
                    "Talla no encontrada: " .
                    $tallaNombre .
                    " / " .
                    $tipoPersona
                );
            }

            $idTalla =
                (int) $talla["id_talla"];


            $sqlTipoCamisa = "
                SELECT id_tipo_camisa
                FROM TIPO_CAMISA
                WHERE nombre = :nombre
                  AND estado = 'ACTIVO'
                LIMIT 1
            ";

            $consultaTipoCamisa =
                $conexion->prepare($sqlTipoCamisa);

            $consultaTipoCamisa->execute([
                ":nombre" => $tipoCamisaNombre
            ]);

            $tipoCamisa =
                $consultaTipoCamisa->fetch();

            if (!$tipoCamisa) {
                throw new RuntimeException(
                    "Tipo de camisa no encontrado: " .
                    $tipoCamisaNombre
                );
            }

            $idTipoCamisa =
                (int) $tipoCamisa["id_tipo_camisa"];


            $idPatrocinador = null;

            if ($codigoPatrocinador !== "") {

                $sqlPatrocinador = "
                    SELECT id_patrocinador
                    FROM PATROCINADOR
                    WHERE codigo = :codigo
                      AND estado = 'ACTIVO'
                    LIMIT 1
                ";

                $consultaPatrocinador =
                    $conexion->prepare($sqlPatrocinador);

                $consultaPatrocinador->execute([
                    ":codigo" => $codigoPatrocinador
                ]);

                $patrocinador =
                    $consultaPatrocinador->fetch();

                if (!$patrocinador) {
                    throw new RuntimeException(
                        "Código de patrocinador no válido: " .
                        $codigoPatrocinador
                    );
                }

                $idPatrocinador =
                    (int) $patrocinador["id_patrocinador"];
            }



            $folioParticipante =
                "PAR-" .
                date("Y") .
                "-" .
                strtoupper(bin2hex(random_bytes(4)));


            $sqlParticipante = "
                INSERT INTO PARTICIPANTE
                (
                    id_inscripcion,
                    id_categoria,
                    id_talla,
                    id_tipo_camisa,
                    id_patrocinador,
                    folio,
                    nombre_completo,
                    sexo,
                    tipo_persona,
                    estado_participante
                )
                VALUES
                (
                    :id_inscripcion,
                    :id_categoria,
                    :id_talla,
                    :id_tipo_camisa,
                    :id_patrocinador,
                    :folio,
                    :nombre,
                    :sexo,
                    :tipo_persona,
                    :estado
                )
            ";

            $consultaParticipante =
                $conexion->prepare($sqlParticipante);

            $consultaParticipante->execute([
                ":id_inscripcion" => $idInscripcion,
                ":id_categoria" => $idCategoria,
                ":id_talla" => $idTalla,
                ":id_tipo_camisa" => $idTipoCamisa,
                ":id_patrocinador" => $idPatrocinador,
                ":folio" => $folioParticipante,
                ":nombre" => $nombreParticipante,
                ":sexo" => $sexo !== "" ? $sexo : null,
                ":tipo_persona" =>
                    $tipoPersona !== ""
                        ? $tipoPersona
                        : null,
                ":estado" => "REGISTRADO"
            ]);
        }


        if (is_array($camisaExtra)) {

            $tallaExtra = trim(
                (string) ($camisaExtra["talla"] ?? "")
            );

            $tipoCamisaExtra = trim(
                (string) ($camisaExtra["tipo_camisa"] ?? "")
            );

            $tipoPersonaExtra = trim(
                (string) (
                    $camisaExtra["tipo_persona"] ?? "Adulto"
                )
            );

            $cantidadExtra = (int) (
                $camisaExtra["cantidad"] ?? 1
            );

            $motivoExtra = trim(
                (string) ($camisaExtra["motivo"] ?? "")
            );


            $consultaTallaExtra = $conexion->prepare("
                SELECT id_talla
                FROM TALLA
                WHERE nombre = :nombre
                  AND tipo_persona = :tipo_persona
                  AND estado = 'ACTIVO'
                LIMIT 1
            ");

            $consultaTallaExtra->execute([
                ":nombre" => $tallaExtra,
                ":tipo_persona" => $tipoPersonaExtra
            ]);

            $filaTallaExtra =
                $consultaTallaExtra->fetch();

            if (!$filaTallaExtra) {
                throw new RuntimeException(
                    "Talla de camisa extra no encontrada."
                );
            }


            $consultaTipoExtra = $conexion->prepare("
                SELECT id_tipo_camisa
                FROM TIPO_CAMISA
                WHERE nombre = :nombre
                  AND estado = 'ACTIVO'
                LIMIT 1
            ");

            $consultaTipoExtra->execute([
                ":nombre" => $tipoCamisaExtra
            ]);

            $filaTipoExtra =
                $consultaTipoExtra->fetch();

            if (!$filaTipoExtra) {
                throw new RuntimeException(
                    "Tipo de camisa extra no encontrado."
                );
            }


            $sqlExtra = "
                INSERT INTO CAMISA_EXTRA
                (
                    id_inscripcion,
                    id_talla,
                    id_tipo_camisa,
                    cantidad,
                    motivo
                )
                VALUES
                (
                    :id_inscripcion,
                    :id_talla,
                    :id_tipo_camisa,
                    :cantidad,
                    :motivo
                )
            ";

            $consultaExtra =
                $conexion->prepare($sqlExtra);

            $consultaExtra->execute([
                ":id_inscripcion" => $idInscripcion,
                ":id_talla" =>
                    (int) $filaTallaExtra["id_talla"],
                ":id_tipo_camisa" =>
                    (int) $filaTipoExtra["id_tipo_camisa"],
                ":cantidad" => max(1, $cantidadExtra),
                ":motivo" =>
                    $motivoExtra !== ""
                        ? $motivoExtra
                        : null
            ]);
        }


        $conexion->commit();

        $insertados++;

        $resultados[] = [
            "response_id_forms" => $responseId,
            "estado" => "insertado",
            "id_responsable" => $idResponsable,
            "id_inscripcion" => $idInscripcion,
            "folio_inscripcion" => $folioInscripcion,
            "participantes" => count($participantes),
            "token_qr" => $tokenQr,
            "url_qr" => $urlQr
        ];

    } catch (Throwable $e) {

        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        $errores++;

        error_log(
            "Error importando response_id " .
            $responseId .
            ": " .
            $e->getMessage()
        );

        $resultados[] = [
            "response_id_forms" => $responseId,
            "estado" => "error",
            "mensaje" => $e->getMessage()
        ];
    }
}


responderJson(200, [
    "success" => $errores === 0,
    "total_recibidos" => $total,
    "insertados" => $insertados,
    "omitidos" => $omitidos,
    "errores" => $errores,
    "resultados" => $resultados
]);