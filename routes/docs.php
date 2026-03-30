<?php

declare(strict_types=1);

use OpenApi\Generator;

$router->get('/docs/openapi.json', function () {
    $openapi = (new Generator())->generate([__DIR__ . '/../src']);
    header('Content-Type: application/json');
    echo $openapi->toJson();
});
