<?php

use App\Catalogo\Articulos\Controllers\MateriaController;
use App\Catalogo\Articulos\Repository\MateriaRepository;
use App\Catalogo\Articulos\Services\MateriaService;
use Tests\Helper\TestStreamWrapper;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Configurando el controlador con sus dependencias usando la BD de prueba
    $repository = new MateriaRepository($this->pdo);
    $service = new MateriaService($repository);
    $this->controller = new MateriaController($service);
});

test('getAll devuelve un array vacío cuando no hay materias', function () {
    // Capturando la salida del controlador
    ob_start();
    $this->controller->getAll();

    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)
        ->toHaveKey('error')
        ->toHaveKey('data')
        ->and($response['error'])->toBe('false')
        ->and($response['data'])->toBeArray()
        ->and($response['data'])->toBeEmpty();
});

test("getAll devuelve el listado de materias", function() {

    ob_start();
    $this->insertInto("materia", [
        "titulo" => "Matematica I"
    ]);
    $this->insertInto("materia", [
        "titulo" => "Matematica II"
    ]);
    $this->insertInto("materia", [
        "titulo" => "Filosofia"
    ]);

    $this->controller->getAll();

    $output = ob_get_clean();

    $response = json_decode($output, true);
    
    expect($response)
        ->toHaveKey("error")
        ->toHaveKey("data")
        ->and($response["error"])->toBe("false")
        ->and($response["data"])->toBeArray()
        ->and($response["data"])->toHaveCount(3);
});

test("createMateria crea una nueva materia correctamente", function() {
    $faker = Faker\Factory::create("es-ES");
    $titulo = $faker->words(3, true);

    $input = json_encode(["titulo" => $titulo]);

    stream_wrapper_unregister("php");
    stream_wrapper_register("php", TestStreamWrapper::class);
    TestStreamWrapper::$data = $input;

    ob_start();
    $this->controller->createMateria();
    $output = ob_get_clean();

    stream_wrapper_restore("php");

    $response = json_decode($output, true);

    expect($response)
        ->toHaveKey("error")
        ->toHaveKey("data")
        ->and($response["error"])->toBe(false)
        ->and($response["data"])->toBeArray()
        ->and($response["data"]["titulo"])->toBe($titulo);
});

test("createMateria falla con titulo vacio o no seteado", function() {
    $input = json_encode(["titulo" => ""]);

    stream_wrapper_unregister("php");
    stream_wrapper_register("php", TestStreamWrapper::class);
    TestStreamWrapper::$data = $input;

    ob_start();
    $this->controller->createMateria();
    $output = ob_get_clean();

    stream_wrapper_restore("php");

    $response = json_decode($output, true);

    expect($response)
        ->toHaveKey("error")
        ->toHaveKey("message")
        ->and($response["error"])->toBe(true);
});

test("createMateria falla con titulo de mas de 100 caracteres", function() {
    $input = json_encode(["titulo" => "El éxito no es el final, el fracaso no es fatal; lo que realmente cuenta es el valor para seguir adelante."]);

    stream_wrapper_unregister("php");
    stream_wrapper_register("php", TestStreamWrapper::class);
    TestStreamWrapper::$data = $input;

    ob_start();
    $this->controller->createMateria();
    $output = ob_get_clean();

    stream_wrapper_restore("php");

    $response = json_decode($output, true);

    expect($response)
        ->toHaveKey("error")
        ->toHaveKey("message")
        ->and($response["error"])->toBe(true);
});

test("createMateria falla cuando ya existe la materia", function() {
    ob_start();
    $this->insertInto("materia", [
        "titulo" => "Matematica I"
    ]);

    $input = json_encode(["titulo" => "Matematica I"]);

    stream_wrapper_unregister("php");
    stream_wrapper_register("php", TestStreamWrapper::class);
    TestStreamWrapper::$data = $input;

    $this->controller->createMateria();
    $output = ob_get_clean();

    stream_wrapper_restore("php");

    $response = json_decode($output, true);

    expect($response)
        ->toHaveKey("error")
        ->toHaveKey("message")
        ->and($response["error"])->toBe(true);
});