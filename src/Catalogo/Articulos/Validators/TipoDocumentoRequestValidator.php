<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Validators;

use App\Shared\Exceptions\ValidationException;

class TipoDocumentoRequestValidator
{
    private const REQUIRED_FIELDS = [
        "codigo",
        "descripcion"
    ];

    public static function validateInputCreate(array $input): void
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($input[$field]) || $input[$field] === "") {
                $errors[$field] = ["El campo {$field} es requerido"];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if (!is_string($input["codigo"])) {
            $errors["codigo"] = ["El codigo debe ser un string"];
        } elseif (!preg_match("/^[A-Za-z#&]+$/", trim($input["codigo"]))) {
            $errors["codigo"] = ["El codigo solo admite letras"];
        }

        if (!is_string($input["descripcion"])) {
            $errors["descripcion"] = ["La descripcion debe ser un string"];
        } elseif (!preg_match('/^[\p{L}\s -]+$/u', trim($input["descripcion"]))) {
            $errors["descripcion"] = ["La descripcion solo admite letras y '-'"];
        }

        if (isset($input["renovable"])) {
            if (!is_bool($input["renovable"])) {
                $errors["renovable"] = ["El campo renovable solo puede ser true o false"];
            }
        }

        if (isset($input["detalle"])) {
            if (!is_string($input["detalle"])) {
                $errors["detalle"] = ["El detalle debe ser un string"];
            } elseif (!preg_match('/^[\p{L}\s -]*$/u', trim($input["detalle"]))) {
                $errors["detalle"] = ["El detalle solo admite letras y '-'"];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateInputUpdate(array $input): void
    {
        $errors = [];

        if(isset($input["codigo"])) {
            if (!is_string($input["codigo"])) {
                $errors["codigo"] = ["El codigo debe ser un string"];
            } elseif (!preg_match("/^[A-Za-z#&]+$/", trim($input["codigo"]))) {
                $errors["codigo"] = ["El codigo solo admite letras"];
            }
        }

        if(isset($input["descripcion"])) {
            if (!is_string($input["descripcion"])) {
                $errors["descripcion"] = ["La descripcion debe ser un string"];
            } elseif (!preg_match('/^[\p{L}\s -]+$/u', trim($input["descripcion"]))) {
                $errors["descripcion"] = ["La descripcion solo admite letras y '-'"];
            }
        }

        if (isset($input["renovable"])) {
            if (!is_bool($input["renovable"])) {
                $errors["renovable"] = ["El campo renovable solo puede ser true o false"];
            }
        }

        if (isset($input["detalle"])) {
            if (!is_string($input["detalle"])) {
                $errors["detalle"] = ["El detalle debe ser un string"];
            } elseif (!preg_match('/^[\p{L}\s -]+$/u', trim($input["detalle"]))) {
                $errors["detalle"] = ["El detalle solo admite letras y '-'"];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateParams(array $params) {
        $errors = [];

        if(isset($params["codigo"])) {
            if(!is_string($params["codigo"])) {
                $errors["codigo"] = ["El codigo debe ser un string"];
            } elseif (!preg_match("/^[A-Za-z#&]+$/", $params["codigo"])) {
                $errors["codigo"] = ["El codigo solo admite letras"];
            }
        }

        if(isset($params["descripcion"])) {
            if(!is_string($params["descripcion"])) {
                $errors["descripcion"] = ["La descripcion debe ser un string"];
            } elseif (!preg_match('/^[\p{L}\s -]+$/u', $params["descripcion"])) {
                $errors["descripcion"] = ["La descripcion solo admite letras y '-'"];
            }
        }

        if(isset($params["renovable"])) {
            if($params["renovable"] !== "true" && $params["renovable"] !== "false") {
                $errors["renovable"] = ["Solo se admite true o false"];
            }
        }

        if(isset($params["detalle"])) {
            if(!is_string($params["detalle"])) {
                $errors["detalle"] = ["El detalle debe ser un string"];
            } elseif (!preg_match('/^[\p{L}\s -]+$/u', $params["detalle"])) {
                $errors["detalle"] = ["El detalle solo admite letras y '-'"];
            }
        }

        if(isset($params["order"])) {
            if(!is_string($params["order"])) {
                $errors["order"] = ["El orden debe ser un string"];
            } elseif(strtoupper($params["order"]) !== "ASC" && strtoupper($params["order"]) !== "DESC") {
                $errors["order"] = ["Solo se admite ASC o DESC como forma de ordenamiento"];
            }
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateId(string $id): void
    {
        $errors = [];

        if (!is_numeric($id)) {
            $errors["id"] = ["El id debe ser un numero"];
        } elseif ((int) $id < 1) {
            $errors["id"] = ["El id debe ser un numero entero positivo mayor que 0"];
        } elseif (!ctype_digit($id)) {
            $errors["id"] = ["El id debe ser un numero valido"];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}