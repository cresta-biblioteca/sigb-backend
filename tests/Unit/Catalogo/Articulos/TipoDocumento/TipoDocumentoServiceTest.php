<?php

use App\Catalogo\Articulos\Dtos\Request\CreateTipoDocumentoRequest;
use App\Catalogo\Articulos\Dtos\Request\UpdateTipoDocumentoRequest;
use App\Catalogo\Articulos\Dtos\Response\TipoDocumentoResponse;
use App\Catalogo\Articulos\Exceptions\TipoDocumentoAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\TipoDocumentoNotFoundException;
use App\Catalogo\Articulos\Models\TipoDocumento;
use App\Catalogo\Articulos\Repository\TipoDocumentoRepository;
use App\Catalogo\Articulos\Services\TipoDocumentoService;
use App\Shared\Exceptions\BusinessRuleException;

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(TipoDocumentoRepository::class);
    $this->service = new TipoDocumentoService($this->repositoryMock);
});

afterEach(function () {
    Mockery::close();
});

test("getAll devuelve un array con tipoDocumentoResponse", function () {
    $tipoDoc1 = TipoDocumento::create(
        "EA",
        "Equipos Audiovisuales"
    );
    $tipoDoc1->setId(1);
    $tipoDoc2 = TipoDocumento::create(
        "LIB",
        "Libro",
        detalle: "Libro"
    );
    $tipoDoc2->setId(2);

    $this->repositoryMock
        ->shouldReceive("findAll")
        ->once()
        ->andReturn([$tipoDoc1, $tipoDoc2]);

    $result = $this->service->getAll();

    expect($result)
        ->toBeArray()
        ->toHaveCount(2);
    expect($result[0])
        ->toBeInstanceOf(TipoDocumentoResponse::class);
    expect($result[0]->jsonSerialize())
        ->toMatchArray(["id" => 1, "codigo" => "EA", "descripcion" => "Equipos Audiovisuales", "renovable" => true, "detalle" => null]);
    expect($result[1])
        ->toBeInstanceOf(TipoDocumentoResponse::class);
    expect($result[1]->jsonSerialize())
        ->toMatchArray(["id" => 2, "codigo" => "LIB", "descripcion" => "Libro", "renovable" => true, "detalle" => "Libro"]);
});

test("getAll devuelve un array vacio cuando no hay documentos", function() {
    $this->repositoryMock
        ->shouldReceive("findAll")
        ->once()
        ->andReturn([]);

    $result = $this->service->getAll();

    expect($result)
        ->toBeArray()
        ->toBeEmpty();
});

test("getById retorna un tipoDocumentoResponse cuando existe", function () {
    $tipoDoc1 = TipoDocumento::create(
        "ELE",
        "Documento Electronico",
        false,
        "Documento Electronico"
    );
    $tipoDoc1->setId(3);
    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(3)
        ->once()
        ->andReturn($tipoDoc1);


    $result = $this->service->getById(3);

    expect($result)
        ->toBeInstanceOf(TipoDocumentoResponse::class);
    expect($result->jsonSerialize())
        ->toMatchArray(["id" => 3, "codigo" => "ELE", "descripcion" => "Documento Electronico", "renovable" => false, "detalle" => "Documento Electronico"]);
});

test("getById retorna TipoDocumentoNotFoundException cuando no existe el tipo de documento", function () {
    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(4)
        ->once()
        ->andReturnNull();

    expect(fn() => $this->service->getById(4))
        ->toThrow(TipoDocumentoNotFoundException::class, 'TipoDocumento con identificador "4" no encontrado');
});

test("createTipoDocumento retorna tipoDocumentoResponse cuando se crea exitosamente", function () {
    $tipoDocRequest = new CreateTipoDocumentoRequest(
        "CDR",
        "CD-ROM",
        true,
        "CD-ROM"
    );

    $tipoDoc = TipoDocumento::create(
        codigo: "CDR",
        descripcion: "CD-ROM",
        detalle: "CD-ROM"
    );
    $tipoDoc->setId(5);

    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->with("CDR", "CD-ROM")
        ->once()
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive("insertTipoDocumento")
        ->once()
        ->withArgs(fn(TipoDocumento $tipoDoc) =>  $tipoDoc->getCodigo() === "CDR" && 
                                                        $tipoDoc->getDescripcion() === "CD-ROM" && 
                                                        $tipoDoc->isRenovable() === true && 
                                                        $tipoDoc->getDetalle() === "CD-ROM")
        ->andReturn($tipoDoc);

    $result = $this->service->createTipoDocumento($tipoDocRequest);

    expect($result)
        ->toBeInstanceOf(TipoDocumentoResponse::class);
    expect($result->jsonSerialize())
        ->toMatchArray(["id" => 5, "codigo" => "CDR", "descripcion" => "CD-ROM", "renovable" => true, "detalle" => "CD-ROM"]);
});

test("createTipoDocumento lanza TipoDocumentoAlreadyExistsException cuando ya existe", function() {
    $tipoDocRequest = new CreateTipoDocumentoRequest(
        "MAP",
        "Mapas",
        true,
        "Mapa"
    ); 

    $tipoDocExistente = TipoDocumento::create(
        codigo: "MAP",
        descripcion: "Mapa",
        detalle: "Mapa"
    );
    $tipoDocExistente->setId(6);

    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->with("MAP", "Mapas")
        ->andReturn($tipoDocExistente);

    expect(fn() => $this->service->createTipoDocumento($tipoDocRequest))
        ->toThrow(TipoDocumentoAlreadyExistsException::class, 'TipoDocumento con Codigo "MAP" ya existe');
});

test("createTipoDocumento lanza BusinessRuleException si el codigo excede 3 caracteres", function() {
    $codLargo = "ASDD";
    $tipoDocRequest = new CreateTipoDocumentoRequest(
        $codLargo,
        "asdf",
        true,
        "sadf"
    );

    $this->repositoryMock->shouldNotReceive("findCoincidence");
    $this->repositoryMock->shouldNotReceive("insertTipoDocumento");

    expect(fn() => $this->service->createTipoDocumento($tipoDocRequest))
        ->toThrow(BusinessRuleException::class, "El campo codigo no debe exceder 3 caracteres");
});

test("createTipoDocumento lanza BusinessRuleException si el codigo esta vacio", function() {
    $codVacio = "";
    $tipoDocRequest = new CreateTipoDocumentoRequest(
        $codVacio,
        "asdf",
        true,
        "sadf"
    );

    $this->repositoryMock->shouldNotReceive("findCoincidence");
    $this->repositoryMock->shouldNotReceive("insertTipoDocumento");

    expect(fn() => $this->service->createTipoDocumento($tipoDocRequest))
        ->toThrow(BusinessRuleException::class, "El campo codigo es requerido");
});

test("createTipoDocumento lanza BusinessRuleException si la descripcion excede los 100 caracteres", function() {
    $descripcionLarga = str_repeat("a", 101);
    $tipoDocRequest = new CreateTipoDocumentoRequest(
        "asd",
        $descripcionLarga
    );

    $this->repositoryMock->shouldNotReceive("findCoincidence");
    $this->repositoryMock->shouldNotReceive("insertTipoDocumento");

    expect(fn() => $this->service->createTipoDocumento($tipoDocRequest))
        ->toThrow(BusinessRuleException::class, "El campo descripcion no debe exceder 100 caracteres");
});

test("createTipoDocumento lanza BusinessRuleException si la descripcion esta vacia", function() {
    $tipoDocRequest = new CreateTipoDocumentoRequest(
        "asd",
        ""
    );

    $this->repositoryMock->shouldNotReceive("findCoincidence");
    $this->repositoryMock->shouldNotReceive("insertTipoDocumento");

    expect(fn() => $this->service->createTipoDocumento($tipoDocRequest))
        ->toThrow(BusinessRuleException::class, "El campo descripcion es requerido");
});

test("createTipoDocumento lanza BusinessRuleException si el detalle supera los 100 caracteres", function() {
    $detalleLargo = str_repeat("a", 101);
    $tipoDocRequest = new CreateTipoDocumentoRequest(
        "asd",
        "asds",
        detalle: $detalleLargo
    );

    $this->repositoryMock->shouldNotReceive("findCoincidence");
    $this->repositoryMock->shouldNotReceive("insertTipoDocumento");

    expect(fn() => $this->service->createTipoDocumento($tipoDocRequest))
        ->toThrow(BusinessRuleException::class, "El campo detalle no debe exceder 100 caracteres");
});

test("updateTipoDocumento retorna TipoDocumentoResponse cuando el documento es actualizado correctamente", function() {
    $request = new UpdateTipoDocumentoRequest(
        "LBS",
        "Libros",
        detalle: "Libros actualizados"
    );

    $tipoDocExistente = TipoDocumento::create(
        "LIB",
        "Libro",
    );
    $tipoDocExistente->setId(1);
    
    $tipoDocActualizado = TipoDocumento::create(
        "LBS",
        "Libros",
        detalle: "Libros actualizados"
    );
    $tipoDocActualizado->setId(1);
        
    $this->repositoryMock
        ->shouldReceive("findById")
        ->once()
        ->with(1)
        ->andReturn($tipoDocExistente);

    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->once()
        ->with("LBS", "Libros", 1)
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive("updateTipoDocumento")
        ->once()
        ->withArgs(fn(int $id, TipoDocumento $tipoDoc) => $id === 1 && $tipoDoc->getCodigo() === "LBS" 
                                                                          && $tipoDoc->getDescripcion() === "Libros" 
                                                                          && $tipoDoc->isRenovable() 
                                                                          && $tipoDoc->getDetalle() === "Libros actualizados")
        ->andReturn($tipoDocActualizado);

    $result = $this->service->updateTipoDocumento(1, $request);

    expect($result)
        ->toBeInstanceOf(TipoDocumentoResponse::class);
    expect($result->jsonSerialize())
        ->toMatchArray(["id" => 1, "codigo" => "LBS", "descripcion" => "Libros", "renovable" => true, "detalle" => "Libros actualizados"]);
});

test("updateTipoDocumento lanza BusinessRuleException cuando el codigo supera los 3 caracteres", function() {
    $request = new UpdateTipoDocumentoRequest(
        "ASDF",
        "Descripcion"
    );

    $tipoDoc = TipoDocumento::create(
        "ASD",
        "Descripcion"
    );
    $tipoDoc->setId(1);

    $this->repositoryMock->shouldReceive("findById")
        ->once()
        ->with(1)
        ->andReturn($tipoDoc);
    $this->repositoryMock->shouldNotReceive("findCoincidence");

    $this->repositoryMock->shouldNotReceive("updateTipoDocumento");

    expect(fn() => $this->service->updateTipoDocumento(1, $request))
        ->toThrow(BusinessRuleException::class, "El campo codigo no debe exceder 3 caracteres");
});

test("updateTipoDocumento lanza BusinessRuleException cuando el codigo esta vacio", function() {
    $request = new UpdateTipoDocumentoRequest(
        "",
        "Descripcion"
    );
    $tipoDoc = TipoDocumento::create(
        "ASD",
        "Descripcion"
    );
    $tipoDoc->setId(1);

    $this->repositoryMock->shouldReceive("findById")
        ->once()
        ->with(1)
        ->andReturn($tipoDoc);

    $this->repositoryMock->shouldNotReceive("findCoincidence");

    $this->repositoryMock->shouldNotReceive("updateTipoDocumento");

    expect(fn() => $this->service->updateTipoDocumento(1, $request))
        ->toThrow(BusinessRuleException::class, "El campo codigo es requerido");
});

test("updateTipoDocumento lanza BusinessRuleException cuando la descripcion supera los 100 caracteres", function() {
    $descripcionLarga = str_repeat("a", 101);
    $request = new UpdateTipoDocumentoRequest(
        "asd",
        $descripcionLarga
    );

    $tipoDoc = TipoDocumento::create(
        "ASD",
        "Descripcion"
    );
    $tipoDoc->setId(1);

    $this->repositoryMock->shouldReceive("findById")
        ->once()
        ->with(1)
        ->andReturn($tipoDoc);

    $this->repositoryMock->shouldNotReceive("findCoincidence");

    $this->repositoryMock->shouldNotReceive("updateTipoDocumento");

    expect(fn() => $this->service->updateTipoDocumento(1, $request))
        ->toThrow(BusinessRuleException::class, "El campo descripcion no debe exceder 100 caracteres");
});

test("updateTipoDocumento lanza BusinessRuleException cuando la descripcion esta vacia", function() {
    $request = new UpdateTipoDocumentoRequest(
        "asd",
        ""
    );
    $tipoDoc = TipoDocumento::create(
        "ASD",
        "Descripcion"
    );
    $tipoDoc->setId(1);

    $this->repositoryMock->shouldReceive("findById")
        ->once()
        ->with(1)
        ->andReturn($tipoDoc);

    $this->repositoryMock->shouldNotReceive("findCoincidence");

    $this->repositoryMock->shouldNotReceive("updateTipoDocumento");

    expect(fn() => $this->service->updateTipoDocumento(1, $request))
        ->toThrow(BusinessRuleException::class, "El campo descripcion es requerido");
});

test("updateTipoDocumento lanza BusinessRuleException cuando el detalle supera los 100 caracteres", function() {
    $detalleLargo = str_repeat("a", 101);
    $request = new UpdateTipoDocumentoRequest(
        "asd",
        "asd",
        detalle: $detalleLargo
    );
    $tipoDoc = TipoDocumento::create(
        "ASD",
        "Descripcion"
    );
    $tipoDoc->setId(1);

    $this->repositoryMock->shouldReceive("findById")
        ->once()
        ->with(1)
        ->andReturn($tipoDoc);

    $this->repositoryMock->shouldNotReceive("findCoincidence");

    $this->repositoryMock->shouldNotReceive("updateTipoDocumento");

    expect(fn() => $this->service->updateTipoDocumento(1, $request))
        ->toThrow(BusinessRuleException::class, 'El campo detalle no debe exceder 100 caracteres');
});

test("updateTipoDocumento lanza TipoDocumentoNotFoundException cuando no existe el documento a actualizar", function() {
    $request = new UpdateTipoDocumentoRequest(
        "LIB",
        "Libros",
        detalle: "Libros tapa dura" 
    );

    $this->repositoryMock
        ->shouldReceive("findById")
        ->once()
        ->with(999)
        ->andReturnNull();
    
    expect(fn() => $this->service->updateTipoDocumento(999, $request))
        ->toThrow(TipoDocumentoNotFoundException::class, 'TipoDocumento con identificador "999" no encontrado');
});

test("updateTipoDocumento lanza TipoDocumentoAlreadyExistsException cuando existe otro registro con el mismo codigo o descripcion",
 function() {
    $request = new UpdateTipoDocumentoRequest(
        "CD",
        "CD",
        detalle: "CD Fisico"
    );

    $tipoDocExistente = TipoDocumento::create(
        "COD",
        "Codigo",
        detalle: "Codigo binario"
    );
    $tipoDocExistente->setId(1);

    $tipoDocDuplicado = TipoDocumento::create(
        "CD",
        "Combinado",
        detalle: "Combinado"
    );
    $tipoDocDuplicado->setId(3);


    $this->repositoryMock
        ->shouldReceive("findById")
        ->once()
        ->with(1)
        ->andReturn($tipoDocExistente);
    
    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->once()
        ->with("CD", "CD", 1)
        ->andReturn($tipoDocDuplicado);
    
    expect(fn() => $this->service->updateTipoDocumento(1, $request))
        ->toThrow(TipoDocumentoAlreadyExistsException::class, 'TipoDocumento con Codigo "CD" ya existe');
});

test("deleteTipoDocumento borra un documento exitosamente", function() {
    $tipoDocExistente = TipoDocumento::create(
        "ASD",
        "AASDIOJ"
    );
    $tipoDocExistente->setId(1);
    
    $this->repositoryMock
        ->shouldReceive("findById")
        ->once()
        ->with(1)
        ->andReturn($tipoDocExistente);
    $this->repositoryMock
        ->shouldReceive("delete")
        ->once()
        ->with(1)
        ->andReturn(true);
    
    $this->service->deleteTipoDocumento(1);
});

test("deleteTipoDocumento lanza TipoDocumentoNotFoundException cuando no existe el documento", function() {
    $this->repositoryMock
        ->shouldReceive("findById")
        ->once()
        ->with(999)
        ->andReturnNull();

    $this->repositoryMock->shouldNotReceive("delete");
    
    expect(fn() => $this->service->deleteTipoDocumento(999))
        ->toThrow(TipoDocumentoNotFoundException::class, 'TipoDocumento con identificador "999" no encontrado');
});

test("deleteTipoDocumento lanza TipoDocumentoNotFoundException cuando delete retorna false", function() {
    $tipoDocExistente = TipoDocumento::create(
        "ASD",
        "sakjdh"
    );
    $tipoDocExistente->setId(5);

    $this->repositoryMock
        ->shouldReceive("findById")
        ->once()
        ->with(5)
        ->andReturn($tipoDocExistente);

    $this->repositoryMock
        ->shouldReceive("delete")
        ->once()
        ->with(5)
        ->andReturn(false);
    
    expect(fn() => $this->service->deleteTipoDocumento(5))
        ->toThrow(TipoDocumentoNotFoundException::class, 'TipoDocumento con identificador "5" no encontrado');
});

test("getByParams retorna TipoDocumentoResponse correctamente con busqueda por codigo", function() {
    $tipoDoc1 = TipoDocumento::create(
        "ASD",
        "Descripcion 1"
    );
    $tipoDoc1->setId(1);
    $tipoDoc2 = TipoDocumento::create(
        "ASF",
        "Descripcion 2",
        detalle: "Detalle 2"
    );
    $tipoDoc2->setId(2);
    $params = ["codigo" => "as"];
    $this->repositoryMock
        ->shouldReceive("findByParams")
        ->once()
        ->with($params)
        ->andReturn([$tipoDoc1, $tipoDoc2]);
    
    $result = $this->service->getByParams($params);

    expect($result)
        ->toBeArray()
        ->toHaveCount(2);
    expect($result[0])
        ->toBeInstanceOf(TipoDocumentoResponse::class);
    expect($result[0]->jsonSerialize())
        ->toMatchArray(["id" => 1, "codigo" => "ASD", "descripcion" => "Descripcion 1", "renovable" => true, "detalle" => null]);
    expect($result[1])
        ->toBeInstanceOf(TipoDocumentoResponse::class);
    expect($result[1]->jsonSerialize())
        ->toMatchArray(["id" => 2, "codigo" => "ASF", "descripcion" => "Descripcion 2", "renovable" => true, "detalle" => "Detalle 2"]);
});