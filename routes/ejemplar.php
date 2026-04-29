<?php

declare(strict_types=1);

use App\Catalogo\Ejemplares\Controllers\EjemplarController;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Catalogo\Ejemplares\Services\EjemplarService;

/**
 * @var \Bramus\Router\Router $router
 */

$ejemplarRepository = new EjemplarRepository();
$ejemplarService = new EjemplarService($ejemplarRepository);
$ejemplarController = new EjemplarController($ejemplarService);

$router->get('/ejemplares', withRole(['admin', 'auxiliar', 'lector'], function () use ($ejemplarController) {
    $ejemplarController->getAll();
}));

$router->get('/ejemplares/{id}', withRole(['admin', 'auxiliar', 'lector'], function ($id) use ($ejemplarController) {
    $ejemplarController->getById($id);
}));

$router->post('/ejemplares', withRole(['admin'], function () use ($ejemplarController) {
    $ejemplarController->createEjemplar();
}));

$router->put('/ejemplares/{id}', withRole(['admin'], function ($id) use ($ejemplarController) {
    $ejemplarController->updateEjemplar($id);
}));

$router->delete('/ejemplares/{id}', withRole(['admin'], function ($id) use ($ejemplarController) {
    $ejemplarController->deleteEjemplar($id);
}));

$router->get('/articulos/{articuloId}/ejemplares', withRole(['admin', 'auxiliar', 'lector'], function ($articuloId) use ($ejemplarController) {
    $ejemplarController->getByArticuloId($articuloId);
}));
