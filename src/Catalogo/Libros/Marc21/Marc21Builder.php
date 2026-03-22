<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Marc21;

use App\Catalogo\Libros\Models\Libro;
use File_MARC_Control_Field;
use File_MARC_Data_Field;
use File_MARC_Record;
use File_MARC_Subfield;
use Scriptotek\Marc\Record;

class Marc21Builder
{
    private const LANGUAGE_MAP = [
        'es' => 'spa',
        'en' => 'eng',
        'pt' => 'por',
        'fr' => 'fre',
        'de' => 'ger',
        'it' => 'ita',
    ];

    /**
     * Construye un Record MARC21 a partir de un Libro con su Articulo cargado.
     */
    public static function build(Libro $libro): Record
    {
        $articulo = $libro->getArticulo();
        $raw = new File_MARC_Record();

        // Leader: registro bibliográfico (tipo 'a'), nivel monografía ('m'), ISBD ('i')
        $raw->setLeader('00000nam a2200000 i 4500');

        // 001 - Identificador del registro
        $raw->appendField(new File_MARC_Control_Field('001', (string) $libro->getId()));

        // 003 - Código de organización MARC
        $raw->appendField(new File_MARC_Control_Field('003', 'AR-BuSIGB'));

        // 020 - ISBN
        if ($libro->getIsbn() !== null) {
            $raw->appendField(new File_MARC_Data_Field('020', [
                new File_MARC_Subfield('a', $libro->getIsbn()),
            ], ' ', ' '));
        }

        // 022 - ISSN (ind1: 0 = recurso de interés internacional)
        if ($libro->getIssn() !== null) {
            $raw->appendField(new File_MARC_Data_Field('022', [
                new File_MARC_Subfield('a', $libro->getIssn()),
            ], '0', ' '));
        }

        // 041 - Código de idioma (ind1: 0 = no es traducción)
        if ($articulo !== null) {
            $langCode = self::LANGUAGE_MAP[$articulo->getIdioma()] ?? $articulo->getIdioma();
            $raw->appendField(new File_MARC_Data_Field('041', [
                new File_MARC_Subfield('a', $langCode),
            ], '0', ' '));
        }

        // 080 - Clasificación Decimal Universal (CDU/UDC)
        if ($libro->getCdu() !== null) {
            $raw->appendField(new File_MARC_Data_Field('080', [
                new File_MARC_Subfield('a', (string) $libro->getCdu()),
            ], ' ', ' '));
        }

        // 100 - Autor principal
        if ($libro->getAutor() !== null) {
            $raw->appendField(new File_MARC_Data_Field('100', [
                new File_MARC_Subfield('a', $libro->getAutor()),
                new File_MARC_Subfield('e', 'autor'),
            ], '1', ' '));
        }

        // 245 - Mención de título
        if ($articulo !== null) {
            $subfields = [new File_MARC_Subfield('a', $articulo->getTitulo())];
            if ($libro->getTituloInformativo() !== null) {
                $subfields[] = new File_MARC_Subfield('b', $libro->getTituloInformativo());
            }
            if ($libro->getAutor() !== null) {
                $subfields[] = new File_MARC_Subfield('c', $libro->getAutor());
            }
            $raw->appendField(new File_MARC_Data_Field('245', $subfields, '1', '0'));
        }

        // 264 - Producción, publicación, distribución, fabricación
        if ($articulo !== null) {
            $subfields = [];
            if ($libro->getLugarDePublicacion() !== null) {
                $subfields[] = new File_MARC_Subfield('a', $libro->getLugarDePublicacion());
            }
            if ($libro->getEditorial() !== null) {
                $subfields[] = new File_MARC_Subfield('b', $libro->getEditorial());
            }
            $subfields[] = new File_MARC_Subfield('c', (string) $articulo->getAnioPublicacion());

            $raw->appendField(new File_MARC_Data_Field('264', $subfields, ' ', '1'));
        }

        // 300 - Descripción física (páginas)
        if ($libro->getPaginas() !== null) {
            $raw->appendField(new File_MARC_Data_Field('300', [
                new File_MARC_Subfield('a', $libro->getPaginas() . ' páginas'),
            ], ' ', ' '));
        }

        // 520 - Resumen
        if ($articulo !== null && $articulo->getDescripcion() !== null) {
            $raw->appendField(new File_MARC_Data_Field('520', [
                new File_MARC_Subfield('a', $articulo->getDescripcion()),
            ], ' ', ' '));
        }

        // 700 - Coautores (un campo 700 por cada persona)
        if ($libro->getAutores() !== null) {
            self::appendPersonFields($raw, $libro->getAutores(), 'coautor');
        }

        // 700 - Colaboradores (un campo 700 por cada persona)
        if ($libro->getColaboradores() !== null) {
            self::appendPersonFields($raw, $libro->getColaboradores(), 'colaborador');
        }

        return new Record($raw);
    }

    /**
     * Separa un string de nombres delimitado por comas y agrega un campo 700 por persona.
     */
    private static function appendPersonFields(
        File_MARC_Record $raw,
        string $names,
        string $relatorTerm
    ): void {
        $people = array_filter(array_map('trim', explode(',', $names)));

        foreach ($people as $name) {
            $raw->appendField(new File_MARC_Data_Field('700', [
                new File_MARC_Subfield('a', $name),
                new File_MARC_Subfield('e', $relatorTerm),
            ], '1', ' '));
        }
    }

    /**
     * Exporta el registro como MARCXML (ISO 25577).
     */
    public static function toMarcXml(Libro $libro): string
    {
        return self::suppressDeprecations(fn() => self::build($libro)->toXML());
    }

    /**
     * Exporta el registro en formato ISO 2709 binario (.mrc).
     */
    public static function toIso2709(Libro $libro): string
    {
        return self::suppressDeprecations(fn() => self::build($libro)->toRaw());
    }

    /**
     * Ejecuta un callable suprimiendo deprecation notices de pear/file_marc.
     */
    private static function suppressDeprecations(callable $fn): string
    {
        $previous = error_reporting(error_reporting() & ~E_DEPRECATED);
        try {
            return $fn();
        } finally {
            error_reporting($previous);
        }
    }
}
