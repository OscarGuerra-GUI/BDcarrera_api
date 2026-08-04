<?php

declare(strict_types=1);

/**
 * Devuelve una respuesta JSON y detiene la ejecución.
 */
function responderJson(int $codigo, array $contenido): never
{
    http_response_code($codigo);
    header("Content-Type: application/json; charset=utf-8");

    echo json_encode(
        $contenido,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/**
 * Verifica que la petición incluya la API_KEY correcta.
 */
function verificarApiKey(): void
{
    $apiKeyConfigurada = getenv("API_KEY");
    $apiKeyRecibida = $_SERVER["HTTP_X_API_KEY"] ?? "";

    if (!$apiKeyConfigurada) {
        responderJson(500, [
            "success" => false,
            "mensaje" => "La API_KEY no está configurada en el servidor."
        ]);
    }

    if (
        $apiKeyRecibida === "" ||
        !hash_equals($apiKeyConfigurada, $apiKeyRecibida)
    ) {
        responderJson(401, [
            "success" => false,
            "mensaje" => "Acceso no autorizado."
        ]);
    }
}