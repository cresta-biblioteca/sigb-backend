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
        // CARRERAS
        // ============================================================
        $carreras = [
            ['codigo' => 'ISI', 'nombre' => 'Ingenieria en Sistemas de Informacion'],
            ['codigo' => 'IEM', 'nombre' => 'Ingenieria Electromecanica'],
            ['codigo' => 'IQ', 'nombre' => 'Ingenieria Quimica'],
        ];
        $this->table('carrera')->insert($carreras)->save();

        // ============================================================
        // ARTICULOS + LIBROS (20 libros variados)
        // Ahora con personas normalizadas en tabla persona + libro_persona
        // ============================================================
        $now = date('Y-m-d H:i:s');

        $libros = [
            [
                'titulo' => 'Introduccion a los Algoritmos',
                'anio' => 2009,
                'idioma' => 'es',
                'descripcion' => 'Texto clasico sobre algoritmos y estructuras de datos',
                'isbn' => '9780262033848',
                'editorial' => 'MIT Press',
                'paginas' => 1312,
                'lugar' => 'Cambridge',
                'edicion' => '3a edicion',
                'dimensiones' => '24 cm',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'Thomas H.', 'apellido' => 'Cormen', 'rol' => 'autor'],
                    ['nombre' => 'Charles E.', 'apellido' => 'Leiserson', 'rol' => 'coautor'],
                    ['nombre' => 'Ronald L.', 'apellido' => 'Rivest', 'rol' => 'coautor'],
                    ['nombre' => 'Clifford', 'apellido' => 'Stein', 'rol' => 'coautor'],
                ],
                'temas' => ['Algoritmos', 'Programacion', 'Matematicas'],
            ],
            [
                'titulo' => 'Sistemas de Base de Datos',
                'anio' => 2013,
                'idioma' => 'es',
                'descripcion' => 'Fundamentos de sistemas de bases de datos relacionales',
                'isbn' => '9780133970777',
                'editorial' => 'Pearson',
                'paginas' => 1200,
                'lugar' => 'Boston',
                'edicion' => '7a edicion',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'Ramez', 'apellido' => 'Elmasri', 'rol' => 'autor'],
                    ['nombre' => 'Shamkant B.', 'apellido' => 'Navathe', 'rol' => 'coautor'],
                ],
                'temas' => ['Base de Datos', 'Programacion'],
            ],
            [
                'titulo' => 'Redes de Computadoras',
                'anio' => 2017,
                'idioma' => 'es',
                'descripcion' => 'Enfoque descendente sobre redes de computadoras',
                'isbn' => '9780133594140',
                'editorial' => 'Pearson',
                'paginas' => 864,
                'lugar' => 'Boston',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'James F.', 'apellido' => 'Kurose', 'rol' => 'autor'],
                    ['nombre' => 'Keith W.', 'apellido' => 'Ross', 'rol' => 'coautor'],
                ],
                'temas' => ['Redes', 'Seguridad Informatica'],
            ],
            [
                'titulo' => 'Inteligencia Artificial: Un Enfoque Moderno',
                'anio' => 2020,
                'idioma' => 'es',
                'descripcion' => 'Texto de referencia en inteligencia artificial',
                'isbn' => '9780134610993',
                'editorial' => 'Pearson',
                'paginas' => 1136,
                'lugar' => 'New Jersey',
                'edicion' => '4a edicion',
                'dimensiones' => '26 cm',
                'ilustraciones' => 'ilustraciones a color',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'Stuart', 'apellido' => 'Russell', 'rol' => 'autor'],
                    ['nombre' => 'Peter', 'apellido' => 'Norvig', 'rol' => 'coautor'],
                ],
                'temas' => ['Inteligencia Artificial', 'Algoritmos', 'Matematicas'],
            ],
            [
                'titulo' => 'Calculo de una Variable',
                'anio' => 2015,
                'idioma' => 'es',
                'descripcion' => 'Calculo diferencial e integral de una variable',
                'isbn' => '9786075228778',
                'editorial' => 'Cengage Learning',
                'paginas' => 960,
                'lugar' => 'Mexico DF',
                'edicion' => '8a edicion',
                'pais_publicacion' => 'mx',
                'personas' => [
                    ['nombre' => 'James', 'apellido' => 'Stewart', 'rol' => 'autor'],
                ],
                'temas' => ['Matematicas'],
            ],
            [
                'titulo' => 'Algebra Lineal y sus Aplicaciones',
                'anio' => 2012,
                'idioma' => 'es',
                'descripcion' => 'Introduccion al algebra lineal con aplicaciones',
                'isbn' => '9780321982384',
                'editorial' => 'Pearson',
                'paginas' => 576,
                'lugar' => 'Boston',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'David C.', 'apellido' => 'Lay', 'rol' => 'autor'],
                    ['nombre' => 'Steven R.', 'apellido' => 'Lay', 'rol' => 'coautor'],
                    ['nombre' => 'Judi J.', 'apellido' => 'McDonald', 'rol' => 'coautor'],
                ],
                'temas' => ['Matematicas'],
            ],
            [
                'titulo' => 'Fisica Universitaria',
                'anio' => 2018,
                'idioma' => 'es',
                'descripcion' => 'Texto de fisica general para ingenieria',
                'isbn' => '9786073244374',
                'editorial' => 'Pearson',
                'paginas' => 1568,
                'lugar' => 'Mexico DF',
                'edicion' => '14a edicion',
                'ilustraciones' => 'ilustraciones a color',
                'dimensiones' => '28 cm',
                'pais_publicacion' => 'mx',
                'personas' => [
                    ['nombre' => 'Hugh D.', 'apellido' => 'Young', 'rol' => 'autor'],
                    ['nombre' => 'Roger A.', 'apellido' => 'Freedman', 'rol' => 'coautor'],
                ],
                'temas' => ['Fisica', 'Matematicas'],
            ],
            [
                'titulo' => 'Clean Code',
                'anio' => 2008,
                'idioma' => 'en',
                'descripcion' => 'A Handbook of Agile Software Craftsmanship',
                'isbn' => '9780132350884',
                'editorial' => 'Prentice Hall',
                'paginas' => 464,
                'lugar' => 'New Jersey',
                'pais_publicacion' => 'us',
                'notas' => 'Lectura recomendada para Ingenieria de Software I',
                'personas' => [
                    ['nombre' => 'Robert C.', 'apellido' => 'Martin', 'rol' => 'autor'],
                ],
                'temas' => ['Programacion', 'Ingenieria de Software'],
            ],
            [
                'titulo' => 'Design Patterns',
                'anio' => 1994,
                'idioma' => 'en',
                'descripcion' => 'Elements of Reusable Object-Oriented Software',
                'isbn' => '9780201633610',
                'editorial' => 'Addison-Wesley',
                'paginas' => 416,
                'lugar' => 'Reading',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'Erich', 'apellido' => 'Gamma', 'rol' => 'autor'],
                    ['nombre' => 'Richard', 'apellido' => 'Helm', 'rol' => 'coautor'],
                    ['nombre' => 'Ralph', 'apellido' => 'Johnson', 'rol' => 'coautor'],
                    ['nombre' => 'John', 'apellido' => 'Vlissides', 'rol' => 'coautor'],
                ],
                'temas' => ['Programacion', 'Ingenieria de Software'],
            ],
            [
                'titulo' => 'Sistemas Operativos Modernos',
                'anio' => 2014,
                'idioma' => 'es',
                'descripcion' => 'Texto integral sobre sistemas operativos',
                'isbn' => '9780133591620',
                'editorial' => 'Pearson',
                'paginas' => 1136,
                'lugar' => 'Amsterdam',
                'edicion' => '4a edicion',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'Andrew S.', 'apellido' => 'Tanenbaum', 'rol' => 'autor'],
                    ['nombre' => 'Herbert', 'apellido' => 'Bos', 'rol' => 'coautor'],
                ],
                'temas' => ['Sistemas Operativos', 'Programacion'],
            ],
            [
                'titulo' => 'Ingenieria de Software',
                'anio' => 2016,
                'idioma' => 'es',
                'descripcion' => 'Texto sobre procesos y practicas de ingenieria de software',
                'isbn' => '9780133943030',
                'editorial' => 'Pearson',
                'paginas' => 816,
                'lugar' => 'Londres',
                'edicion' => '10a edicion',
                'pais_publicacion' => 'gb',
                'personas' => [
                    ['nombre' => 'Ian', 'apellido' => 'Sommerville', 'rol' => 'autor'],
                ],
                'temas' => ['Ingenieria de Software'],
            ],
            [
                'titulo' => 'The Pragmatic Programmer',
                'anio' => 2019,
                'idioma' => 'en',
                'descripcion' => 'Your Journey to Mastery, 20th Anniversary Edition',
                'isbn' => '9780135957059',
                'editorial' => 'Addison-Wesley',
                'paginas' => 352,
                'lugar' => 'Boston',
                'edicion' => '2nd edition',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'David', 'apellido' => 'Thomas', 'rol' => 'autor'],
                    ['nombre' => 'Andrew', 'apellido' => 'Hunt', 'rol' => 'coautor'],
                ],
                'temas' => ['Programacion', 'Ingenieria de Software'],
            ],
            [
                'titulo' => 'Estructuras de Datos y Algoritmos en Java',
                'anio' => 2014,
                'idioma' => 'es',
                'descripcion' => 'Implementacion de estructuras de datos en Java',
                'isbn' => '9781118771334',
                'editorial' => 'Wiley',
                'paginas' => 736,
                'lugar' => 'New York',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'Michael T.', 'apellido' => 'Goodrich', 'rol' => 'autor'],
                    ['nombre' => 'Roberto', 'apellido' => 'Tamassia', 'rol' => 'coautor'],
                ],
                'temas' => ['Algoritmos', 'Programacion'],
            ],
            [
                'titulo' => 'Fundamentos de Bases de Datos',
                'anio' => 2019,
                'idioma' => 'es',
                'descripcion' => 'Conceptos fundamentales de bases de datos',
                'isbn' => '9780078022159',
                'editorial' => 'McGraw-Hill',
                'paginas' => 1376,
                'lugar' => 'New York',
                'edicion' => '7a edicion',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'Abraham', 'apellido' => 'Silberschatz', 'rol' => 'autor'],
                    ['nombre' => 'Henry F.', 'apellido' => 'Korth', 'rol' => 'coautor'],
                    ['nombre' => 'S.', 'apellido' => 'Sudarshan', 'rol' => 'coautor'],
                ],
                'temas' => ['Base de Datos'],
            ],
            [
                'titulo' => 'Computer Networking: A Top-Down Approach',
                'anio' => 2021,
                'idioma' => 'en',
                'descripcion' => 'Networking fundamentals with top-down approach',
                'isbn' => '9780136681557',
                'editorial' => 'Pearson',
                'paginas' => 800,
                'lugar' => 'Boston',
                'edicion' => '8th edition',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'James F.', 'apellido' => 'Kurose', 'rol' => 'autor'],
                    ['nombre' => 'Keith W.', 'apellido' => 'Ross', 'rol' => 'coautor'],
                ],
                'temas' => ['Redes'],
            ],
            [
                'titulo' => 'Deep Learning',
                'anio' => 2016,
                'idioma' => 'en',
                'descripcion' => 'Comprehensive text on deep learning techniques',
                'isbn' => '9780262035613',
                'editorial' => 'MIT Press',
                'paginas' => 800,
                'lugar' => 'Cambridge',
                'ilustraciones' => 'ilustraciones y graficos',
                'pais_publicacion' => 'us',
                'serie' => 'Adaptive Computation and Machine Learning',
                'personas' => [
                    ['nombre' => 'Ian', 'apellido' => 'Goodfellow', 'rol' => 'autor'],
                    ['nombre' => 'Yoshua', 'apellido' => 'Bengio', 'rol' => 'coautor'],
                    ['nombre' => 'Aaron', 'apellido' => 'Courville', 'rol' => 'coautor'],
                ],
                'temas' => ['Inteligencia Artificial', 'Matematicas', 'Algoritmos'],
            ],
            [
                'titulo' => 'Calculo: Trascendentes Tempranas',
                'anio' => 2020,
                'idioma' => 'es',
                'descripcion' => 'Calculo con funciones trascendentes desde el inicio',
                'isbn' => '9786075268699',
                'editorial' => 'Cengage Learning',
                'paginas' => 784,
                'lugar' => 'Mexico DF',
                'edicion' => '6a edicion',
                'pais_publicacion' => 'mx',
                'personas' => [
                    ['nombre' => 'Dennis G.', 'apellido' => 'Zill', 'rol' => 'autor'],
                    ['nombre' => 'Warren S.', 'apellido' => 'Wright', 'rol' => 'coautor'],
                ],
                'temas' => ['Matematicas'],
            ],
            [
                'titulo' => 'Seguridad Informatica',
                'anio' => 2018,
                'idioma' => 'es',
                'descripcion' => 'Principios y practicas de seguridad informatica',
                'isbn' => '9780134794105',
                'editorial' => 'Pearson',
                'paginas' => 832,
                'lugar' => 'Boston',
                'pais_publicacion' => 'us',
                'personas' => [
                    ['nombre' => 'William', 'apellido' => 'Stallings', 'rol' => 'autor'],
                    ['nombre' => 'Lawrie', 'apellido' => 'Brown', 'rol' => 'coautor'],
                ],
                'temas' => ['Seguridad Informatica', 'Redes'],
            ],
            [
                'titulo' => 'Refactoring',
                'anio' => 2018,
                'idioma' => 'en',
                'descripcion' => 'Improving the Design of Existing Code, 2nd Edition',
                'isbn' => '9780134757599',
                'editorial' => 'Addison-Wesley',
                'paginas' => 448,
                'lugar' => 'Boston',
                'edicion' => '2nd edition',
                'pais_publicacion' => 'us',
                'notas' => 'Complemento de Clean Code para refactorizacion',
                'personas' => [
                    ['nombre' => 'Martin', 'apellido' => 'Fowler', 'rol' => 'autor'],
                ],
                'temas' => ['Programacion', 'Ingenieria de Software'],
            ],
            [
                'titulo' => 'Fisica para Ciencias e Ingenieria',
                'anio' => 2019,
                'idioma' => 'es',
                'descripcion' => 'Texto de fisica con enfoque en ingenieria',
                'isbn' => '9786075266893',
                'editorial' => 'Cengage Learning',
                'paginas' => 1296,
                'lugar' => 'Mexico DF',
                'edicion' => '10a edicion',
                'ilustraciones' => 'ilustraciones a color',
                'dimensiones' => '28 cm',
                'pais_publicacion' => 'mx',
                'personas' => [
                    ['nombre' => 'Raymond A.', 'apellido' => 'Serway', 'rol' => 'autor'],
                    ['nombre' => 'John W.', 'apellido' => 'Jewett', 'rol' => 'coautor'],
                ],
                'temas' => ['Fisica', 'Matematicas'],
            ],
        ];

        // Obtener mapa de IDs para temas
        $allTemas = $this->fetchAll("SELECT id, titulo FROM tema");
        $temaMap = [];
        foreach ($allTemas as $t) {
            $temaMap[$t['titulo']] = $t['id'];
        }

        // Cache de personas ya creadas (nombre+apellido -> id)
        $personaCache = [];

        // Insertar cada libro
        foreach ($libros as $index => $libroData) {
            // 1. Insertar articulo
            $this->table('articulo')->insert([
                [
                    'titulo' => $libroData['titulo'],
                    'anio_publicacion' => $libroData['anio'],
                    'tipo' => 'libro',
                    'idioma' => $libroData['idioma'],
                    'descripcion' => $libroData['descripcion'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])->save();

            $articuloId = $this->fetchRow(
                "SELECT id FROM articulo WHERE titulo = '" .
                addslashes($libroData['titulo']) . "'"
            )['id'];

            // 2. Insertar libro
            $libroRow = [
                'articulo_id' => $articuloId,
                'isbn' => $libroData['isbn'],
                'paginas' => $libroData['paginas'],
                'editorial' => $libroData['editorial'],
                'lugar_de_publicacion' => $libroData['lugar'],
                'edicion' => $libroData['edicion'] ?? null,
                'dimensiones' => $libroData['dimensiones'] ?? null,
                'ilustraciones' => $libroData['ilustraciones'] ?? null,
                'serie' => $libroData['serie'] ?? null,
                'numero_serie' => $libroData['numero_serie'] ?? null,
                'notas' => $libroData['notas'] ?? null,
                'pais_publicacion' => $libroData['pais_publicacion'] ?? null,
            ];
            $this->table('libro')->insert([$libroRow])->save();

            // 3. Insertar personas y relaciones libro_persona
            if (!empty($libroData['personas'])) {
                foreach ($libroData['personas'] as $orden => $personaData) {
                    $cacheKey = $personaData['nombre'] . '|' . $personaData['apellido'];

                    if (!isset($personaCache[$cacheKey])) {
                        $existing = $this->fetchRow(
                            "SELECT id FROM persona WHERE nombre = '" .
                            addslashes($personaData['nombre']) . "' AND apellido = '" .
                            addslashes($personaData['apellido']) . "'"
                        );

                        if ($existing) {
                            $personaCache[$cacheKey] = $existing['id'];
                        } else {
                            $this->table('persona')->insert([
                                [
                                    'nombre' => $personaData['nombre'],
                                    'apellido' => $personaData['apellido'],
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ],
                            ])->save();

                            $personaCache[$cacheKey] = $this->fetchRow(
                                "SELECT id FROM persona WHERE nombre = '" .
                                addslashes($personaData['nombre']) . "' AND apellido = '" .
                                addslashes($personaData['apellido']) . "'"
                            )['id'];
                        }
                    }

                    $this->table('libro_persona')->insert([
                        [
                            'libro_id' => $articuloId,
                            'persona_id' => $personaCache[$cacheKey],
                            'rol' => $personaData['rol'],
                            'orden' => $orden,
                        ],
                    ])->save();
                }
            }

            // 4. Insertar ejemplares (entre 1 y 4 por libro)
            $cantEjemplares = ($index % 4) + 1;
            $ejemplares = [];
            for ($e = 1; $e <= $cantEjemplares; $e++) {
                $barcode = str_pad((string)(($index + 1) * 100 + $e), 13, '0', STR_PAD_LEFT);
                $ejemplares[] = [
                    'codigo_barras' => $barcode,
                    'habilitado' => ($e <= $cantEjemplares - 1 || $cantEjemplares === 1) ? true : false,
                    'articulo_id' => $articuloId,
                    'signatura_topografica' => sprintf('CDU-%03d.%d', $index + 1, $e),
                ];
            }
            $this->table('ejemplar')->insert($ejemplares)->save();

            // 5. Asociar temas
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
        }
    }
}
