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

    private const COUNTRY_MAP = [
        'ar' => 'ag ',
        'us' => 'xxu',
        'mx' => 'mx ',
        'es' => 'sp ',
        'gb' => 'xxk',
        'br' => 'bl ',
        'cl' => 'cl ',
        'co' => 'ck ',
        'pe' => 'pe ',
        'uy' => 'uy ',
        'fr' => 'fr ',
        'de' => 'gw ',
        'it' => 'it ',
        'pt' => 'po ',
    ];

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

        // 008 - Campo de datos fijos
        if ($articulo !== null) {
            $raw->appendField(new File_MARC_Control_Field('008', self::build008($libro)));
        }

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

        // 100 - Autor principal (desde personas)
        $autorPrincipal = $libro->getAutorPrincipal();
        if ($autorPrincipal !== null) {
            $raw->appendField(new File_MARC_Data_Field('100', [
                new File_MARC_Subfield('a', $autorPrincipal->getNombreCompleto()),
                new File_MARC_Subfield('e', 'autor'),
            ], '1', ' '));
        }

        // 245 - Mención de título
        if ($articulo !== null) {
            $subfields = [new File_MARC_Subfield('a', $articulo->getTitulo())];
            if ($libro->getTituloInformativo() !== null) {
                $subfields[] = new File_MARC_Subfield('b', $libro->getTituloInformativo());
            }
            if ($autorPrincipal !== null) {
                $subfields[] = new File_MARC_Subfield('c', $autorPrincipal->getNombreCompleto());
            }
            $raw->appendField(new File_MARC_Data_Field('245', $subfields, '1', '0'));
        }

        // 250 - Edición
        if ($libro->getEdicion() !== null) {
            $raw->appendField(new File_MARC_Data_Field('250', [
                new File_MARC_Subfield('a', $libro->getEdicion()),
            ], ' ', ' '));
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

        // 300 - Descripción física
        if ($libro->getPaginas() !== null) {
            $subfields = [new File_MARC_Subfield('a', $libro->getPaginas() . ' páginas')];
            if ($libro->getIlustraciones() !== null) {
                $subfields[] = new File_MARC_Subfield('b', $libro->getIlustraciones());
            }
            if ($libro->getDimensiones() !== null) {
                $subfields[] = new File_MARC_Subfield('c', $libro->getDimensiones());
            }
            $raw->appendField(new File_MARC_Data_Field('300', $subfields, ' ', ' '));
        }

        // 490 - Serie (ind1='0' = serie no trazada)
        if ($libro->getSerie() !== null) {
            $subfields = [new File_MARC_Subfield('a', $libro->getSerie())];
            if ($libro->getNumeroSerie() !== null) {
                $subfields[] = new File_MARC_Subfield('v', $libro->getNumeroSerie());
            }
            $raw->appendField(new File_MARC_Data_Field('490', $subfields, '0', ' '));
        }

        // 500 - Notas
        if ($libro->getNotas() !== null) {
            $raw->appendField(new File_MARC_Data_Field('500', [
                new File_MARC_Subfield('a', $libro->getNotas()),
            ], ' ', ' '));
        }

        // 520 - Resumen
        if ($articulo !== null && $articulo->getDescripcion() !== null) {
            $raw->appendField(new File_MARC_Data_Field('520', [
                new File_MARC_Subfield('a', $articulo->getDescripcion()),
            ], ' ', ' '));
        }

        // 653 - Temas (términos de índice no controlados)
        if ($articulo !== null) {
            foreach ($articulo->getTemas() as $tema) {
                $raw->appendField(new File_MARC_Data_Field('653', [
                    new File_MARC_Subfield('a', $tema->getTitulo()),
                ], ' ', ' '));
            }
        }

        // 700 - Personas adicionales (excluyendo autor principal)
        $autorPrincipalId = $autorPrincipal?->getId();
        foreach ($libro->getPersonas() as $libroPersona) {
            // Saltar el autor principal (ya incluido en campo 100)
            if ($libroPersona->rol === 'autor' && $libroPersona->persona->getId() === $autorPrincipalId) {
                continue;
            }

            $raw->appendField(new File_MARC_Data_Field('700', [
                new File_MARC_Subfield('a', $libroPersona->persona->getNombreCompleto()),
                new File_MARC_Subfield('e', $libroPersona->rol),
            ], '1', ' '));
        }

        return new Record($raw);
    }

    private static function build008(Libro $libro): string
    {
        $articulo = $libro->getArticulo();
        $now = new \DateTimeImmutable();

        // pos 00-05: fecha creación (yymmdd)
        $dateCreated = $now->format('ymd');

        // pos 06: 's' (fecha única de publicación)
        $dateType = 's';

        // pos 07-10: año de publicación
        $year = $articulo !== null ? str_pad((string) $articulo->getAnioPublicacion(), 4, ' ') : '    ';

        // pos 11-14: espacios (fecha 2 no usada)
        $date2 = '    ';

        // pos 15-17: código de país
        $country = 'xx ';
        if ($libro->getPaisPublicacion() !== null) {
            $country = self::COUNTRY_MAP[strtolower($libro->getPaisPublicacion())] ?? 'xx ';
        }

        // pos 18-21: código de ilustraciones (o espacios)
        $illustrations = '    ';
        if ($libro->getIlustraciones() !== null) {
            $illustrations = str_pad(substr('a   ', 0, 4), 4, ' ');
        }

        // pos 22: público objetivo (sin especificar)
        $targetAudience = ' ';

        // pos 23: forma del item (sin especificar)
        $formOfItem = ' ';

        // pos 24-27: naturaleza del contenido
        $natureOfContents = '    ';

        // pos 28: publicación gubernamental
        $govPub = ' ';

        // pos 29: publicación de conferencia
        $confPub = '0';

        // pos 30: festschrift
        $festschrift = '0';

        // pos 31: índice
        $index = '0';

        // pos 32: sin definir
        $undefined = ' ';

        // pos 33: forma literaria
        $litForm = '0';

        // pos 34: biografía
        $biography = ' ';

        // pos 35-37: código de idioma
        $langCode = 'und';
        if ($articulo !== null) {
            $langCode = self::LANGUAGE_MAP[$articulo->getIdioma()] ?? 'und';
        }

        // pos 38: registro modificado
        $modifiedRecord = ' ';

        // pos 39: fuente de catalogación
        $catSource = 'd';

        return $dateCreated . $dateType . $year . $date2 . $country
            . $illustrations . $targetAudience . $formOfItem . $natureOfContents
            . $govPub . $confPub . $festschrift . $index . $undefined
            . $litForm . $biography . $langCode . $modifiedRecord . $catSource;
    }

    public static function toMarcXml(Libro $libro): string
    {
        return self::suppressDeprecations(fn() => self::build($libro)->toXML());
    }

    public static function toIso2709(Libro $libro): string
    {
        return self::suppressDeprecations(fn() => self::build($libro)->toRaw());
    }

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
