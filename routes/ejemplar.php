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

$router->get('/ejemplares', function () use ($ejemplarController) {
    $ejemplarController->getAll();
});

$router->get('/ejemplares/{id}', function ($id) use ($ejemplarController) {
    $ejemplarController->getById((int) $id);
});

$router->post('/ejemplares', function () use ($ejemplarController) {
    $ejemplarController->createEjemplar();
});

$router->put('/ejemplares/{id}', function ($id) use ($ejemplarController) {
    $ejemplarController->updateEjemplar((int) $id);
});

$router->delete('/ejemplares/{id}', function ($id) use ($ejemplarController) {
    $ejemplarController->deleteEjemplar((int) $id);
});

$router->patch('/ejemplares/{id}/habilitar', function ($id) use ($ejemplarController) {
    $ejemplarController->habilitar((int) $id);
});

$router->patch('/ejemplares/{id}/deshabilitar', function ($id) use ($ejemplarController) {
    $ejemplarController->deshabilitar((int) $id);
});

$router->get('/articulos/{articuloId}/ejemplares', function ($articuloId) use ($ejemplarController) {
    $ejemplarController->getByArticuloId((int) $articuloId);
});

$router->get('/articulos/{articuloId}/ejemplares/habilitados', function ($articuloId) use ($ejemplarController) {
    $ejemplarController->getHabilitadosByArticuloId((int) $articuloId);
});
