<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CatalogoTestDataSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return ['RolesAndPermissionsSeeder'];
    }

    public function run(): void
    {
        // ============================================================
        // TIPO DE DOCUMENTO (solo libros)
        // ============================================================
        $this->table('tipo_documento')->insert([
            [
                'codigo' => 'LIB',
                'descripcion' => 'Libro',
                'renovable' => true,
                'detalle' => 'Documento tipo libro',
            ],
        ])->save();

        $tipoLibroId = $this->fetchRow("SELECT id FROM tipo_documento WHERE codigo = 'LIB'")['id'];

        // ============================================================
        // TEMAS
        // ============================================================
        $temas = [
            ['titulo' => 'Programacion'],
            ['titulo' => 'Base de Datos'],
            ['titulo' => 'Redes'],
            ['titulo' => 'Inteligencia Artificial'],
            ['titulo' => 'Matematicas'],
            ['titulo' => 'Fisica'],
            ['titulo' => 'Algoritmos'],
            ['titulo' => 'Sistemas Operativos'],
            ['titulo' => 'Ingenieria de Software'],
            ['titulo' => 'Seguridad Informatica'],
        ];
        $this->table('tema')->insert($temas)->save();

        // ============================================================
        // MATERIAS
        // ============================================================
        $materias = [
            ['titulo' => 'Programacion I'],
            ['titulo' => 'Programacion II'],
            ['titulo' => 'Base de Datos I'],
            ['titulo' => 'Base de Datos II'],
            ['titulo' => 'Redes de Computadoras'],
            ['titulo' => 'Analisis Matematico I'],
            ['titulo' => 'Algebra'],
            ['titulo' => 'Fisica I'],
            ['titulo' => 'Ingenieria de Software I'],
            ['titulo' => 'Sistemas Operativos'],
        ];
        $this->table('materia')->insert($materias)->save();

        // ============================================================
        // CARRERAS
        // ============================================================
        $carreras = [
            ['codigo' => 'ISI', 'nombre' => 'Ingenieria en Sistemas de Informacion'],
            ['codigo' => 'IEM', 'nombre' => 'Ingenieria Electromecanica'],
            ['codigo' => 'IQ', 'nombre' => 'Ingenieria Quimica'],
        ];
        $this->table('carrera')->insert($carreras)->save();

        // ============================================================
        // CARRERA - MATERIA (relaciones)
        // ============================================================
        $carreraISI = $this->fetchRow("SELECT id FROM carrera WHERE codigo = 'ISI'")['id'];
        $carreraIEM = $this->fetchRow("SELECT id FROM carrera WHERE codigo = 'IEM'")['id'];
        $carreraIQ = $this->fetchRow("SELECT id FROM carrera WHERE codigo = 'IQ'")['id'];

        $allMaterias = $this->fetchAll("SELECT id, titulo FROM materia");
        $materiaMap = [];
        foreach ($allMaterias as $m) {
            $materiaMap[$m['titulo']] = $m['id'];
        }

        $carreraMaterias = [
            // ISI tiene todas las materias de sistemas
            ['carrera_id' => $carreraISI, 'materia_id' => $materiaMap['Programacion I']],
            ['carrera_id' => $carreraISI, 'materia_id' => $materiaMap['Programacion II']],
            ['carrera_id' => $carreraISI, 'materia_id' => $materiaMap['Base de Datos I']],
            ['carrera_id' => $carreraISI, 'materia_id' => $materiaMap['Base de Datos II']],
            ['carrera_id' => $carreraISI, 'materia_id' => $materiaMap['Redes de Computadoras']],
            ['carrera_id' => $carreraISI, 'materia_id' => $materiaMap['Analisis Matematico I']],
            ['carrera_id' => $carreraISI, 'materia_id' => $materiaMap['Algebra']],
            ['carrera_id' => $carreraISI, 'materia_id' => $materiaMap['Ingenieria de Software I']],
            ['carrera_id' => $carreraISI, 'materia_id' => $materiaMap['Sistemas Operativos']],
            // IEM comparte matematica y fisica
            ['carrera_id' => $carreraIEM, 'materia_id' => $materiaMap['Analisis Matematico I']],
            ['carrera_id' => $carreraIEM, 'materia_id' => $materiaMap['Algebra']],
            ['carrera_id' => $carreraIEM, 'materia_id' => $materiaMap['Fisica I']],
            // IQ comparte matematica y fisica
            ['carrera_id' => $carreraIQ, 'materia_id' => $materiaMap['Analisis Matematico I']],
            ['carrera_id' => $carreraIQ, 'materia_id' => $materiaMap['Algebra']],
            ['carrera_id' => $carreraIQ, 'materia_id' => $materiaMap['Fisica I']],
        ];
        $this->table('carrera_materia')->insert($carreraMaterias)->save();

        // ============================================================
        // ARTICULOS + LIBROS (20 libros variados)
        // ============================================================
        $now = date('Y-m-d H:i:s');

        $libros = [
            [
                'titulo' => 'Introduccion a los Algoritmos',
                'anio' => 2009,
                'idioma' => 'es',
                'descripcion' => 'Texto clasico sobre algoritmos y estructuras de datos',
                'isbn' => '9780262033848',
                'autor' => 'Thomas H. Cormen',
                'autores' => 'Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest',
                'editorial' => 'MIT Press',
                'paginas' => 1312,
                'lugar' => 'Cambridge',
                'temas' => ['Algoritmos', 'Programacion', 'Matematicas'],
                'materias' => ['Programacion I', 'Programacion II'],
            ],
            [
                'titulo' => 'Sistemas de Base de Datos',
                'anio' => 2013,
                'idioma' => 'es',
                'descripcion' => 'Fundamentos de sistemas de bases de datos relacionales',
                'isbn' => '9780133970777',
                'autor' => 'Ramez Elmasri',
                'autores' => 'Ramez Elmasri, Shamkant B. Navathe',
                'editorial' => 'Pearson',
                'paginas' => 1200,
                'lugar' => 'Boston',
                'temas' => ['Base de Datos', 'Programacion'],
                'materias' => ['Base de Datos I', 'Base de Datos II'],
            ],
            [
                'titulo' => 'Redes de Computadoras',
                'anio' => 2017,
                'idioma' => 'es',
                'descripcion' => 'Enfoque descendente sobre redes de computadoras',
                'isbn' => '9780133594140',
                'autor' => 'James F. Kurose',
                'autores' => 'James F. Kurose, Keith W. Ross',
                'editorial' => 'Pearson',
                'paginas' => 864,
                'lugar' => 'Boston',
                'temas' => ['Redes', 'Seguridad Informatica'],
                'materias' => ['Redes de Computadoras'],
            ],
            [
                'titulo' => 'Inteligencia Artificial: Un Enfoque Moderno',
                'anio' => 2020,
                'idioma' => 'es',
                'descripcion' => 'Texto de referencia en inteligencia artificial',
                'isbn' => '9780134610993',
                'autor' => 'Stuart Russell',
                'autores' => 'Stuart Russell, Peter Norvig',
                'editorial' => 'Pearson',
                'paginas' => 1136,
                'lugar' => 'New Jersey',
                'temas' => ['Inteligencia Artificial', 'Algoritmos', 'Matematicas'],
                'materias' => ['Programacion II'],
            ],
            [
                'titulo' => 'Calculo de una Variable',
                'anio' => 2015,
                'idioma' => 'es',
                'descripcion' => 'Calculo diferencial e integral de una variable',
                'isbn' => '9786075228778',
                'autor' => 'James Stewart',
                'autores' => 'James Stewart',
                'editorial' => 'Cengage Learning',
                'paginas' => 960,
                'lugar' => 'Mexico DF',
                'temas' => ['Matematicas'],
                'materias' => ['Analisis Matematico I'],
            ],
            [
                'titulo' => 'Algebra Lineal y sus Aplicaciones',
                'anio' => 2012,
                'idioma' => 'es',
                'descripcion' => 'Introduccion al algebra lineal con aplicaciones',
                'isbn' => '9780321982384',
                'autor' => 'David C. Lay',
                'autores' => 'David C. Lay, Steven R. Lay, Judi J. McDonald',
                'editorial' => 'Pearson',
                'paginas' => 576,
                'lugar' => 'Boston',
                'temas' => ['Matematicas'],
                'materias' => ['Algebra'],
            ],
            [
                'titulo' => 'Fisica Universitaria',
                'anio' => 2018,
                'idioma' => 'es',
                'descripcion' => 'Texto de fisica general para ingenieria',
                'isbn' => '9786073244374',
                'autor' => 'Hugh D. Young',
                'autores' => 'Hugh D. Young, Roger A. Freedman',
                'editorial' => 'Pearson',
                'paginas' => 1568,
                'lugar' => 'Mexico DF',
                'temas' => ['Fisica', 'Matematicas'],
                'materias' => ['Fisica I'],
            ],
            [
                'titulo' => 'Clean Code',
                'anio' => 2008,
                'idioma' => 'en',
                'descripcion' => 'A Handbook of Agile Software Craftsmanship',
                'isbn' => '9780132350884',
                'autor' => 'Robert C. Martin',
                'autores' => 'Robert C. Martin',
                'editorial' => 'Prentice Hall',
                'paginas' => 464,
                'lugar' => 'New Jersey',
                'temas' => ['Programacion', 'Ingenieria de Software'],
                'materias' => ['Ingenieria de Software I', 'Programacion II'],
            ],
            [
                'titulo' => 'Design Patterns',
                'anio' => 1994,
                'idioma' => 'en',
                'descripcion' => 'Elements of Reusable Object-Oriented Software',
                'isbn' => '9780201633610',
                'autor' => 'Erich Gamma',
                'autores' => 'Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides',
                'editorial' => 'Addison-Wesley',
                'paginas' => 416,
                'lugar' => 'Reading',
                'temas' => ['Programacion', 'Ingenieria de Software'],
                'materias' => ['Ingenieria de Software I'],
            ],
            [
                'titulo' => 'Sistemas Operativos Modernos',
                'anio' => 2014,
                'idioma' => 'es',
                'descripcion' => 'Texto integral sobre sistemas operativos',
                'isbn' => '9780133591620',
                'autor' => 'Andrew S. Tanenbaum',
                'autores' => 'Andrew S. Tanenbaum, Herbert Bos',
                'editorial' => 'Pearson',
                'paginas' => 1136,
                'lugar' => 'Amsterdam',
                'temas' => ['Sistemas Operativos', 'Programacion'],
                'materias' => ['Sistemas Operativos'],
            ],
            [
                'titulo' => 'Ingenieria de Software',
                'anio' => 2016,
                'idioma' => 'es',
                'descripcion' => 'Texto sobre procesos y practicas de ingenieria de software',
                'isbn' => '9780133943030',
                'autor' => 'Ian Sommerville',
                'autores' => 'Ian Sommerville',
                'editorial' => 'Pearson',
                'paginas' => 816,
                'lugar' => 'Londres',
                'temas' => ['Ingenieria de Software'],
                'materias' => ['Ingenieria de Software I'],
            ],
            [
                'titulo' => 'The Pragmatic Programmer',
                'anio' => 2019,
                'idioma' => 'en',
                'descripcion' => 'Your Journey to Mastery, 20th Anniversary Edition',
                'isbn' => '9780135957059',
                'autor' => 'David Thomas',
                'autores' => 'David Thomas, Andrew Hunt',
                'editorial' => 'Addison-Wesley',
                'paginas' => 352,
                'lugar' => 'Boston',
                'temas' => ['Programacion', 'Ingenieria de Software'],
                'materias' => ['Programacion II', 'Ingenieria de Software I'],
            ],
            [
                'titulo' => 'Estructuras de Datos y Algoritmos en Java',
                'anio' => 2014,
                'idioma' => 'es',
                'descripcion' => 'Implementacion de estructuras de datos en Java',
                'isbn' => '9781118771334',
                'autor' => 'Michael T. Goodrich',
                'autores' => 'Michael T. Goodrich, Roberto Tamassia',
                'editorial' => 'Wiley',
                'paginas' => 736,
                'lugar' => 'New York',
                'temas' => ['Algoritmos', 'Programacion'],
                'materias' => ['Programacion I', 'Programacion II'],
            ],
            [
                'titulo' => 'Fundamentos de Bases de Datos',
                'anio' => 2019,
                'idioma' => 'es',
                'descripcion' => 'Conceptos fundamentales de bases de datos',
                'isbn' => '9780078022159',
                'autor' => 'Abraham Silberschatz',
                'autores' => 'Abraham Silberschatz, Henry F. Korth, S. Sudarshan',
                'editorial' => 'McGraw-Hill',
                'paginas' => 1376,
                'lugar' => 'New York',
                'temas' => ['Base de Datos'],
                'materias' => ['Base de Datos I', 'Base de Datos II'],
            ],
            [
                'titulo' => 'Computer Networking: A Top-Down Approach',
                'anio' => 2021,
                'idioma' => 'en',
                'descripcion' => 'Networking fundamentals with top-down approach',
                'isbn' => '9780136681557',
                'autor' => 'James F. Kurose',
                'autores' => 'James F. Kurose, Keith W. Ross',
                'editorial' => 'Pearson',
                'paginas' => 800,
                'lugar' => 'Boston',
                'temas' => ['Redes'],
                'materias' => ['Redes de Computadoras'],
            ],
            [
                'titulo' => 'Deep Learning',
                'anio' => 2016,
                'idioma' => 'en',
                'descripcion' => 'Comprehensive text on deep learning techniques',
                'isbn' => '9780262035613',
                'autor' => 'Ian Goodfellow',
                'autores' => 'Ian Goodfellow, Yoshua Bengio, Aaron Courville',
                'editorial' => 'MIT Press',
                'paginas' => 800,
                'lugar' => 'Cambridge',
                'temas' => ['Inteligencia Artificial', 'Matematicas', 'Algoritmos'],
                'materias' => ['Programacion II'],
            ],
            [
                'titulo' => 'Calculo: Trascendentes Tempranas',
                'anio' => 2020,
                'idioma' => 'es',
                'descripcion' => 'Calculo con funciones trascendentes desde el inicio',
                'isbn' => '9786075268699',
                'autor' => 'Dennis G. Zill',
                'autores' => 'Dennis G. Zill, Warren S. Wright',
                'editorial' => 'Cengage Learning',
                'paginas' => 784,
                'lugar' => 'Mexico DF',
                'temas' => ['Matematicas'],
                'materias' => ['Analisis Matematico I'],
            ],
            [
                'titulo' => 'Seguridad Informatica',
                'anio' => 2018,
                'idioma' => 'es',
                'descripcion' => 'Principios y practicas de seguridad informatica',
                'isbn' => '9780134794105',
                'autor' => 'William Stallings',
                'autores' => 'William Stallings, Lawrie Brown',
                'editorial' => 'Pearson',
                'paginas' => 832,
                'lugar' => 'Boston',
                'temas' => ['Seguridad Informatica', 'Redes'],
                'materias' => ['Redes de Computadoras'],
            ],
            [
                'titulo' => 'Refactoring',
                'anio' => 2018,
                'idioma' => 'en',
                'descripcion' => 'Improving the Design of Existing Code, 2nd Edition',
                'isbn' => '9780134757599',
                'autor' => 'Martin Fowler',
                'autores' => 'Martin Fowler',
                'editorial' => 'Addison-Wesley',
                'paginas' => 448,
                'lugar' => 'Boston',
                'temas' => ['Programacion', 'Ingenieria de Software'],
                'materias' => ['Ingenieria de Software I'],
            ],
            [
                'titulo' => 'Fisica para Ciencias e Ingenieria',
                'anio' => 2019,
                'idioma' => 'es',
                'descripcion' => 'Texto de fisica con enfoque en ingenieria',
                'isbn' => '9786075266893',
                'autor' => 'Raymond A. Serway',
                'autores' => 'Raymond A. Serway, John W. Jewett',
                'editorial' => 'Cengage Learning',
                'paginas' => 1296,
                'lugar' => 'Mexico DF',
                'temas' => ['Fisica', 'Matematicas'],
                'materias' => ['Fisica I'],
            ],
        ];

        // Obtener mapas de IDs para temas y materias
        $allTemas = $this->fetchAll("SELECT id, titulo FROM tema");
        $temaMap = [];
        foreach ($allTemas as $t) {
            $temaMap[$t['titulo']] = $t['id'];
        }

        // Insertar cada libro
        foreach ($libros as $index => $libroData) {
            // 1. Insertar articulo
            $this->table('articulo')->insert([
                [
                    'titulo' => $libroData['titulo'],
                    'anio_publicacion' => $libroData['anio'],
                    'tipo_documento_id' => $tipoLibroId,
                    'idioma' => $libroData['idioma'],
                    'descripcion' => $libroData['descripcion'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])->save();

            $articuloId = $this->fetchRow(
                "SELECT id FROM articulo WHERE titulo = '{$libroData['titulo']}'"
            )['id'];

            // 2. Insertar libro (subclase)
            $this->table('libro')->insert([
                [
                    'articulo_id' => $articuloId,
                    'isbn' => $libroData['isbn'],
                    'paginas' => $libroData['paginas'],
                    'autor' => $libroData['autor'],
                    'autores' => $libroData['autores'],
                    'editorial' => $libroData['editorial'],
                    'lugar_de_publicacion' => $libroData['lugar'],
                ],
            ])->save();

            // 3. Insertar ejemplares (entre 1 y 4 por libro)
            $cantEjemplares = ($index % 4) + 1; // 1, 2, 3, 4, 1, 2, ...
            $ejemplares = [];
            for ($e = 1; $e <= $cantEjemplares; $e++) {
                $barcode = str_pad((string)(($index + 1) * 100 + $e), 13, '0', STR_PAD_LEFT);
                $ejemplares[] = [
                    'codigo_barras' => $barcode,
                    'habilitado' => ($e <= $cantEjemplares - 1 || $cantEjemplares === 1) ? true : false,
                    // El ultimo ejemplar de libros con >1 copia esta deshabilitado
                    'articulo_id' => $articuloId,
                    'signatura_topografica' => sprintf('CDU-%03d.%d', $index + 1, $e),
                ];
            }
            $this->table('ejemplar')->insert($ejemplares)->save();

            // 4. Asociar temas
            $articuloTemas = [];
            foreach ($libroData['temas'] as $temaNombre) {
                if (isset($temaMap[$temaNombre])) {
                    $articuloTemas[] = [
                        'articulo_id' => $articuloId,
                        'tema_id' => $temaMap[$temaNombre],
                    ];
                }
            }
            if (!empty($articuloTemas)) {
                $this->table('articulo_tema')->insert($articuloTemas)->save();
            }

            // 5. Asociar materias
            $articuloMaterias = [];
            foreach ($libroData['materias'] as $materiaNombre) {
                if (isset($materiaMap[$materiaNombre])) {
                    $articuloMaterias[] = [
                        'articulo_id' => $articuloId,
                        'materia_id' => $materiaMap[$materiaNombre],
                    ];
                }
            }
            if (!empty($articuloMaterias)) {
                $this->table('materia_articulo')->insert($articuloMaterias)->save();
            }
        }
    }
}
