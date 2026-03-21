<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

class PatchLibroRequest
{
    private array $requiredFields;

    public function __construct(
        public readonly ?string $isbn = null,
        public readonly ?string $issn = null,
        public readonly ?int $paginas = null,
        public readonly ?string $autor = null,
        public readonly ?array $autores = null,
        public readonly ?array $colaboradores = null,
        public readonly ?string $tituloInformativo = null,
        public readonly ?string $cdu = null,
        public readonly ?string $editorial = null,
        public readonly ?string $lugarDePublicacion = null,
        public array $provided
    ) {
        $this->provided = $provided;
    }

    public static function fromRequest(mixed $data)
    {
        return new self(
            isbn: $data['isbn'],
            issn: $data['issn'],
            paginas: array_key_exists('paginas', $data) ? (int)$data['paginas'] : null,
            autor: $data['autor'] ?? null,
            autores: $data['autores'] ?? null,
            colaboradores: $data['colaboradores'] ?? null,
            tituloInformativo: $data['titulo_informativo'] ?? null,
            cdu: array_key_exists('cdu', $data) ? (int)$data['cdu'] : null,
            editorial: $data['editorial'] ?? null,
            lugarDePublicacion: $data['lugar_de_publicacion'] ?? null,
            provided: array_keys($data),
        );
    }

    // Para usar este metodo hacer:
    // paginas: $request->isProvided('paginas') ? $request->getPaginas() : $existing->getPaginas()
    public function isProvided(string $field): bool
    {
        return in_array($field, $this->provided);
    }
}
