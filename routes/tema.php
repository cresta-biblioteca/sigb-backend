<?php
declare(strict_types=1);

use App\Catalogo\Articulos\Controllers\TemaController;
use App\Catalogo\Articulos\Repository\TemaRepository;
use App\Catalogo\Articulos\Services\TemaService;
use Bramus\Router\Router;

/**
 * @var Router $router
 */

$temaRepository = new TemaRepository();
$temaService = new TemaService($temaRepository);
$temaController = new TemaController($temaService);

$router->get("/temas", function() use($temaController) {
    $temaController->getAll();
});

$router->get("/temas/{id}", function($id) use($temaController) {
    $temaController->getById($id);
});

$router->post("/temas", function() use($temaController) {
    $temaController->createTema();
});

$router->put("/temas/{id}", function($id) use($temaController) {
    $temaController->updateTema($id);
});

$router->delete("/temas/{id}", function($id) use($temaController) {
    $temaController->deleteTema($id);
});