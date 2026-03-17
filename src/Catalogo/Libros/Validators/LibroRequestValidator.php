<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Validators;

use App\Shared\Exceptions\ValidationException;

class LibroRequestValidator
{
    private const ISBN_PATTERN = '/^\d{10}(\d{3})?$/';
    private const MAX_TEXT_LENGTH = 255;
    private const MIN_CDU = 0;
    private const MAX_CDU = 999;
    private const MIN_YEAR = 1000;

    /** @var array<int, string> */
    private const REQUIRED_FIELDS = [
        'isbn',
        'export_marc',
    ];

    /**
     * @param array<string, mixed> $data
     */
    public static function validate(array $data): void
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[$field] = ["El campo {$field} es requerido"];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if (!is_string($data['isbn'])) {
            $errors['isbn'] = ['El campo isbn debe ser un string'];
        } elseif (!preg_match(self::ISBN_PATTERN, $data['isbn'])) {
            $errors['isbn'] = ['El formato del ISBN no es válido. Debe tener 10 o 13 dígitos.'];
        }

        if (!is_string($data['export_marc'])) {
            $errors['export_marc'] = ['El campo export_marc debe ser un string'];
        } elseif (mb_strlen(trim($data['export_marc'])) > self::MAX_TEXT_LENGTH) {
            $errors['export_marc'] = ['El campo export_marc no puede tener más de ' . self::MAX_TEXT_LENGTH .
             ' caracteres'];
        }

        // Validaciones opcionales
        if (isset($data['autor']) && $data['autor'] !== null) {
            if (!is_string($data['autor'])) {
                $errors['autor'] = ['El campo autor debe ser un string'];
            } elseif (mb_strlen(trim($data['autor'])) > self::MAX_TEXT_LENGTH) {
                $errors['autor'] = ['El campo autor no puede tener más de ' . self::MAX_TEXT_LENGTH . ' caracteres'];
            }
        }

        if (isset($data['autores']) && $data['autores'] !== null) {
            if (!is_string($data['autores'])) {
                $errors['autores'] = ['El campo autores debe ser un string'];
            } elseif (mb_strlen(trim($data['autores'])) > self::MAX_TEXT_LENGTH) {
                $errors['autores'] = ['El campo autores no puede tener más de ' . self::MAX_TEXT_LENGTH .
                 ' caracteres'];
            }
        }

        if (isset($data['colaboradores']) && $data['colaboradores'] !== null) {
            if (!is_string($data['colaboradores'])) {
                $errors['colaboradores'] = ['El campo colaboradores debe ser un string'];
            } elseif (mb_strlen(trim($data['colaboradores'])) > self::MAX_TEXT_LENGTH) {
                $errors['colaboradores'] = ['El campo colaboradores no puede tener más de ' . self::MAX_TEXT_LENGTH .
                 ' caracteres'];
            }
        }

        if (isset($data['titulo_informativo']) && $data['titulo_informativo'] !== null) {
            if (!is_string($data['titulo_informativo'])) {
                $errors['titulo_informativo'] = ['El campo titulo_informativo debe ser un string'];
            } elseif (mb_strlen(trim($data['titulo_informativo'])) > self::MAX_TEXT_LENGTH) {
                $errors['titulo_informativo'] = ['El campo titulo_informativo no puede tener más de ' .
                 self::MAX_TEXT_LENGTH . ' caracteres'];
            }
        }

        if (isset($data['cdu']) && $data['cdu'] !== null) {
            if (!is_int($data['cdu']) && !is_numeric($data['cdu'])) {
                $errors['cdu'] = ['El campo cdu debe ser un número entero'];
            } else {
                $cdu = (int) $data['cdu'];
                if ($cdu < self::MIN_CDU || $cdu > self::MAX_CDU) {
                    $errors['cdu'] = ["El campo cdu debe estar entre " . self::MIN_CDU . " y " . self::MAX_CDU];
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new ValidationException(['id' => ['El ID debe ser un entero positivo']]);
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function validateSearchParams(array $params): void
    {
        $errors = [];
        $allowedParams = [
            'isbn',
            'autor',
            'autores',
            'colaboradores',
            'titulo_informativo',
            'cdu',
            'titulo',
            'anio_publicacion',
            'tipo_documento_id',
            'idioma',
            'tema_ids',
            'temas',
            'materia_ids',
            'materias',
            'page',
            'per_page'
        ];

        // Validar que solo se usen parámetros permitidos
        foreach (array_keys($params) as $param) {
            if (!in_array($param, $allowedParams, true)) {
                $errors[$param] = ["El parámetro '{$param}' no está permitido"];
            }
        }

        // Validar parámetros de Libro
        if (isset($params['isbn']) && $params['isbn'] !== '') {
            if (!is_string($params['isbn'])) {
                $errors['isbn'] = ['El campo isbn debe ser un string'];
            } elseif (!preg_match(self::ISBN_PATTERN, $params['isbn'])) {
                $errors['isbn'] = ['El formato del ISBN no es válido'];
            }
        }

        if (isset($params['cdu']) && $params['cdu'] !== '') {
            if (!is_numeric($params['cdu'])) {
                $errors['cdu'] = ['El campo cdu debe ser un número'];
            } else {
                $cdu = (int) $params['cdu'];
                if ($cdu < self::MIN_CDU || $cdu > self::MAX_CDU) {
                    $errors['cdu'] = ["El campo cdu debe estar entre " . self::MIN_CDU . " y " . self::MAX_CDU];
                }
            }
        }

        // Validar campos de texto de Libro
        $textFields = ['autor', 'autores', 'colaboradores', 'titulo_informativo', 'titulo'];
        foreach ($textFields as $field) {
            if (isset($params[$field]) && $params[$field] !== '') {
                if (!is_string($params[$field])) {
                    $errors[$field] = ["El campo {$field} debe ser un string"];
                } elseif (mb_strlen(trim($params[$field])) > self::MAX_TEXT_LENGTH) {
                    $errors[$field] = [
                        "El campo {$field} no puede tener más de " . self::MAX_TEXT_LENGTH .
                        " caracteres"
                    ];
                }
            }
        }

        // Validar parámetros de Articulo
        if (isset($params['anio_publicacion']) && $params['anio_publicacion'] !== '') {
            if (!is_numeric($params['anio_publicacion'])) {
                $errors['anio_publicacion'] = ['El campo anio_publicacion debe ser un número'];
            } else {
                $anio = (int) $params['anio_publicacion'];
                $currentYear = (int) date('Y');
                if ($anio < self::MIN_YEAR || $anio > $currentYear) {
                    $errors['anio_publicacion'] = ["El año debe estar entre " . self::MIN_YEAR . " y {$currentYear}"];
                }
            }
        }

        if (isset($params['tipo_documento_id']) && $params['tipo_documento_id'] !== '') {
            if (!is_numeric($params['tipo_documento_id'])) {
                $errors['tipo_documento_id'] = ['El campo tipo_documento_id debe ser un número'];
            } elseif ((int) $params['tipo_documento_id'] <= 0) {
                $errors['tipo_documento_id'] = ['El campo tipo_documento_id debe ser positivo'];
            }
        }

        if (isset($params['idioma']) && $params['idioma'] !== '') {
            if (!is_string($params['idioma'])) {
                $errors['idioma'] = ['El campo idioma debe ser un string'];
            } elseif (mb_strlen($params['idioma']) !== 2) {
                $errors['idioma'] = ['El idioma debe tener exactamente 2 caracteres'];
            }
        }

        // Validar arrays de IDs
        if (isset($params['tema_ids'])) {
            if (!is_array($params['tema_ids'])) {
                $errors['tema_ids'] = ['El campo tema_ids debe ser un array'];
            } elseif (count($params['tema_ids']) > 50) {
                $errors['tema_ids'] = ['El campo tema_ids no puede tener más de 50 elementos'];
            } else {
                foreach ($params['tema_ids'] as $id) {
                    if (!is_numeric($id) || (int) $id <= 0) {
                        $errors['tema_ids'] = ['Todos los elementos de tema_ids deben ser números positivos'];
                        break;
                    }
                }
            }
        }

        if (isset($params['temas'])) {
            $temas = is_array($params['temas']) ? $params['temas'] : [$params['temas']];

            if (count($temas) > 50) {
                $errors['temas'] = ['El campo temas no puede tener más de 50 elementos'];
            } else {
                foreach ($temas as $tema) {
                    if (!is_string($tema) || trim($tema) === '') {
                        $errors['temas'] = ['Todos los elementos de temas deben ser strings no vacíos'];
                        break;
                    }

                    if (mb_strlen(trim($tema)) > self::MAX_TEXT_LENGTH) {
                        $errors['temas'] = [
                            'Los elementos de temas no pueden superar ' . self::MAX_TEXT_LENGTH . ' caracteres'
                        ];
                        break;
                    }
                }
            }
        }

        if (isset($params['materia_ids'])) {
            if (!is_array($params['materia_ids'])) {
                $errors['materia_ids'] = ['El campo materia_ids debe ser un array'];
            } elseif (count($params['materia_ids']) > 50) {
                $errors['materia_ids'] = ['El campo materia_ids no puede tener más de 50 elementos'];
            } else {
                foreach ($params['materia_ids'] as $id) {
                    if (!is_numeric($id) || (int) $id <= 0) {
                        $errors['materia_ids'] = ['Todos los elementos de materia_ids deben ser números positivos'];
                        break;
                    }
                }
            }
        }

        if (isset($params['materias'])) {
            $materias = is_array($params['materias']) ? $params['materias'] : [$params['materias']];

            if (count($materias) > 50) {
                $errors['materias'] = ['El campo materias no puede tener más de 50 elementos'];
            } else {
                foreach ($materias as $materia) {
                    if (!is_string($materia) || trim($materia) === '') {
                        $errors['materias'] = ['Todos los elementos de materias deben ser strings no vacíos'];
                        break;
                    }

                    if (mb_strlen(trim($materia)) > self::MAX_TEXT_LENGTH) {
                        $errors['materias'] = [
                            'Los elementos de materias no pueden superar ' . self::MAX_TEXT_LENGTH . ' caracteres'
                        ];
                        break;
                    }
                }
            }
        }

        // Validar paginación
        if (isset($params['page'])) {
            if (!is_numeric($params['page'])) {
                $errors['page'] = ['El campo page debe ser un número'];
            } elseif ((int) $params['page'] < 1) {
                $errors['page'] = ['El campo page debe ser mayor a 0'];
            }
        }

        if (isset($params['per_page'])) {
            if (!is_numeric($params['per_page'])) {
                $errors['per_page'] = ['El campo per_page debe ser un número'];
            } else {
                $perPage = (int) $params['per_page'];
                if ($perPage < 1 || $perPage > 100) {
                    $errors['per_page'] = ['El campo per_page debe estar entre 1 y 100'];
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Valida datos para operaciones PATCH (actualización parcial)
     * Solo valida campos editables: autor, autores, colaboradores, titulo_informativo, cdu
     * ISBN y export_marc NO son modificables via PATCH
     * @param array<string, mixed> $data
     */
    public static function validatePatch(array $data): void
    {
        $errors = [];

        // Rechazar explícitamente campos no editables
        if (array_key_exists('isbn', $data)) {
            $errors['isbn'] = ['El ISBN no puede ser modificado'];
        }

        if (array_key_exists('export_marc', $data)) {
            $errors['export_marc'] = ['El export_marc se actualiza automáticamente, no puede ser modificado'];
        }

        // Validar campos opcionales solo si están presentes
        foreach (['autor', 'autores', 'colaboradores', 'titulo_informativo'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                if (!is_string($data[$field])) {
                    $errors[$field] = ["El campo {$field} debe ser un string"];
                } elseif (mb_strlen(trim($data[$field])) > self::MAX_TEXT_LENGTH) {
                    $errors[$field] = ["El campo {$field} no puede tener más de " . self::MAX_TEXT_LENGTH .
                     " caracteres"];
                }
            }
        }

        // Validar cdu solo si está presente
        if (array_key_exists('cdu', $data) && $data['cdu'] !== null) {
            if (!is_numeric($data['cdu'])) {
                $errors['cdu'] = ['El campo cdu debe ser numérico'];
            } else {
                $cdu = (int) $data['cdu'];
                if ($cdu < self::MIN_CDU || $cdu > self::MAX_CDU) {
                    $errors['cdu'] = ['El campo cdu debe estar entre ' . self::MIN_CDU . ' y ' . self::MAX_CDU];
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
