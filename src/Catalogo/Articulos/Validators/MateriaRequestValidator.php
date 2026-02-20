<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Validators;

use App\Shared\Exceptions\ValidationException;

class MateriaRequestValidator {
    private const REQUIRED_FIELDS = [
        "titulo"
    ];

    public static function validate(array $data): void {
        $errors = [];

        foreach(self::REQUIRED_FIELDS as $field) {
            if(!isset($data[$field]) || $data[$field] === '') {
                $errors[$field] = ["el campo {$field} es requerido"];
            }
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }
        
        if(!is_string($data["titulo"])) {
            $errors["titulo"] = ["el campo titulo tiene que ser un string"]; 
        }
            
        if(!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}