 <?php

  declare(strict_types=1);

  namespace App\Shared\Exceptions;

  use Exception;

  class EntityAlreadyExistsException extends Exception
  {
      public function __construct(string $entityType, string $field, mixed $value)
      {
          parent::__construct(
              sprintf('%s con %s "%s" ya existe', $entityType, $field, (string) $value)
          );
      }
  }