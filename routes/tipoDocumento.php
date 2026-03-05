<?php

use App\Catalogo\Articulos\Controllers\TipoDocumentoController;
use App\Catalogo\Articulos\Repository\TipoDocumentoRepository;
use App\Catalogo\Articulos\Services\TipoDocumentoService;

$tipoDocRepository = new TipoDocumentoRepository();
$tipoDocService = new TipoDocumentoService($tipoDocRepository);
$tipoDocController = new TipoDocumentoController($tipoDocService);

$router->get("/documentos", function() use($tipoDocController) {
    $tipoDocController->getAll();
});

$router->get("/documentos/{id}", function($id) use($tipoDocController) {
    $tipoDocController->getById($id);
});

$router->post("/documentos", function() use($tipoDocController) {
    $tipoDocController->createTipoDocumento();
});

$router->put("/documentos/{id}", function($id) use($tipoDocController) {
    $tipoDocController->updateTipoDocumento($id);
});

$router->delete("/documentos/{id}", function($id) use($tipoDocController) {
    $tipoDocController->deleteTipoDocumento($id);
});