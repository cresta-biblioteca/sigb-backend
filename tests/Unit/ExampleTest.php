<?php

use PHPUnit\Framework\TestCase;

/**
 * Test de ejemplo para demostrar el funcionamiento de PHPUnit/Pest
 *
 * Este archivo puede ser eliminado cuando se implementen los tests reales.
 */
test('ejemplo de test básico', function () {
    expect(true)->toBeTrue();
});

test('las operaciones matemáticas funcionan correctamente', function () {
    $result = 2 + 2;
    expect($result)->toBe(4);
});

test('los arrays pueden ser manipulados', function () {
    $array = [1, 2, 3, 4, 5];

    expect($array)->toHaveCount(5);
    expect($array)->toContain(3);
});
