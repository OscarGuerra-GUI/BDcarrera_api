<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/seguridad.php';

verificarApiKey();

responderJson(200, [
    "success" => true,
    "mensaje" => "API key correcta. Acceso autorizado."
]);