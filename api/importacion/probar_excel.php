<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header("Content-Type: application/json; charset=utf-8");

echo json_encode([
    "success" => true,
    "mensaje" => "PhpSpreadsheet cargado correctamente."
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);