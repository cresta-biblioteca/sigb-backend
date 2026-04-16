<?php

declare(strict_types=1);

use App\Lectores\Dtos\Response\LectorPerfilResponse;
use App\Lectores\Exceptions\LectorCarreraAlreadyAssignedException;
use App\Lectores\Exceptions\LectorCarreraNotAssignedException;
use App\Lectores\Models\Carrera;
use App\Lectores\Models\Lector;
use App\Lectores\Repositories\CarreraRepository;
use App\Lectores\Repositories\LectorRepository;
use App\Lectores\Services\LectorService;
use App\Shared\Exceptions\NotFoundException;

beforeEach(function () {
    $this->lectorRepository = Mockery::mock(LectorRepository::class);
    $this->carreraRepository = Mockery::mock(CarreraRepository::class);
    $this->service = new LectorService($this->lectorRepository, $this->carreraRepository);
});

afterEach(function () {
    Mockery::close();
});

test('getPerfil devuelve el lector con carreras como arreglo de strings', function () {
    $lector = Lector::create(
        '123456',
        1,
        'Juan',
        'Perez',
        new DateTimeImmutable('2000-01-01'),
        '2616123456',
        'juan@example.com',
        'K1234',
        'M',
        null
    );
    $lector->setCarreras([
        Carrera::create('PSI', 'Psicologia'),
        Carrera::create('DER', 'Derecho'),
    ]);

    $this->lectorRepository
        ->shouldReceive('findByUserId')
        ->once()
        ->with(1)
        ->andReturn($lector);

    $response = $this->service->getPerfil(1, '12345678');

    expect($response)->toBeInstanceOf(LectorPerfilResponse::class)
        ->and($response->jsonSerialize()['carreras'])->toBe(['Psicologia', 'Derecho'])
        ->and($response->jsonSerialize()['dni'])->toBe('12345678');
});

test('getPerfil lanza NotFoundException si el lector no existe', function () {
    $this->lectorRepository
        ->shouldReceive('findByUserId')
        ->once()
        ->with(1)
        ->andReturnNull();

    expect(fn () => $this->service->getPerfil(1, '12345678'))
        ->toThrow(NotFoundException::class, 'Lector no encontrado');
});

test('assignCarrera asigna una carrera al lector', function () {
    $lector = Lector::create(
        '123456',
        1,
        'Juan',
        'Perez',
        new DateTimeImmutable('2000-01-01'),
        '2616123456',
        'juan@example.com'
    );
    $lector->setId(1);

    $carrera = Carrera::create('PSI', 'Psicologia');
    $carrera->setId(2);

    $this->lectorRepository
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($lector);

    $this->carreraRepository
        ->shouldReceive('findById')
        ->with(2)
        ->once()
        ->andReturn($carrera);

    $this->lectorRepository
        ->shouldReceive('hasCarrera')
        ->with(1, 2)
        ->once()
        ->andReturn(false);

    $this->lectorRepository
        ->shouldReceive('assignCarrera')
        ->with(1, 2)
        ->once()
        ->andReturnTrue();

    $this->service->assignCarrera(1, 2);

    expect(true)->toBeTrue();
});

test('assignCarrera lanza conflicto si la carrera ya estaba asignada', function () {
    $lector = Lector::create(
        '123456',
        1,
        'Juan',
        'Perez',
        new DateTimeImmutable('2000-01-01'),
        '2616123456',
        'juan@example.com'
    );
    $lector->setId(1);

    $carrera = Carrera::create('PSI', 'Psicologia');
    $carrera->setId(2);

    $this->lectorRepository
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($lector);

    $this->carreraRepository
        ->shouldReceive('findById')
        ->with(2)
        ->once()
        ->andReturn($carrera);

    $this->lectorRepository
        ->shouldReceive('hasCarrera')
        ->with(1, 2)
        ->once()
        ->andReturn(true);

    expect(fn () => $this->service->assignCarrera(1, 2))
        ->toThrow(LectorCarreraAlreadyAssignedException::class);
});

test('removeCarrera quita una carrera del lector', function () {
    $lector = Lector::create(
        '123456',
        1,
        'Juan',
        'Perez',
        new DateTimeImmutable('2000-01-01'),
        '2616123456',
        'juan@example.com'
    );
    $lector->setId(1);

    $carrera = Carrera::create('PSI', 'Psicologia');
    $carrera->setId(2);

    $this->lectorRepository
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($lector);

    $this->carreraRepository
        ->shouldReceive('findById')
        ->with(2)
        ->once()
        ->andReturn($carrera);

    $this->lectorRepository
        ->shouldReceive('hasCarrera')
        ->with(1, 2)
        ->once()
        ->andReturn(true);

    $this->lectorRepository
        ->shouldReceive('removeCarrera')
        ->with(1, 2)
        ->once()
        ->andReturnTrue();

    $this->service->removeCarrera(1, 2);

    expect(true)->toBeTrue();
});

test('removeCarrera lanza conflicto si la carrera no estaba asignada', function () {
    $lector = Lector::create(
        '123456',
        1,
        'Juan',
        'Perez',
        new DateTimeImmutable('2000-01-01'),
        '2616123456',
        'juan@example.com'
    );
    $lector->setId(1);

    $carrera = Carrera::create('PSI', 'Psicologia');
    $carrera->setId(2);

    $this->lectorRepository
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($lector);

    $this->carreraRepository
        ->shouldReceive('findById')
        ->with(2)
        ->once()
        ->andReturn($carrera);

    $this->lectorRepository
        ->shouldReceive('hasCarrera')
        ->with(1, 2)
        ->once()
        ->andReturn(false);

    expect(fn () => $this->service->removeCarrera(1, 2))
        ->toThrow(LectorCarreraNotAssignedException::class);
});
