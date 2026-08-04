<?php

declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

echo json_encode([
    "success" => true,
    "servicio" => "API Carrera Benéfica",
    "estado" => "en línea"
], JSON_UNESCAPED_UNICODE);