<?php

declare(strict_types=1);

namespace App\Shared;

use DateTimeImmutable;

class HorarioBiblioteca
{
    // Días hábiles: 1=lunes … 5=viernes (ISO 8601)
    private const DIAS_HABILES = [1, 2, 3, 4, 5];

    // Horario de apertura y cierre (usado para isOpenAt)
    private const APERTURA_MINUTOS = 12 * 60; // 12:00 → 720
    private const CIERRE_MINUTOS   = 19 * 60; // 19:00 → 1140

    /**
     * Indica si la biblioteca está abierta en el instante dado.
     */
    public static function isOpenAt(DateTimeImmutable $datetime): bool
    {
        $diaSemana = (int) $datetime->format('N');
        $minutos   = (int) $datetime->format('G') * 60 + (int) $datetime->format('i');

        return in_array($diaSemana, self::DIAS_HABILES, true)
            && $minutos >= self::APERTURA_MINUTOS
            && $minutos < self::CIERRE_MINUTOS;
    }

    /**
     * Calcula la fecha de vencimiento de una reserva descontando solo
     * horas de días hábiles (lun-vie). Las horas de fin de semana no
     * se consumen del plazo de 24 horas.
     *
     * Ejemplos:
     *   Viernes 18:50 → Lunes 18:50   (5h10min del viernes + 18h50min del lunes)
     *   Domingo 10:00 → Martes 00:00  (fin de semana no cuenta, 24hs desde lunes 00:00)
     *   Lunes   15:00 → Martes 15:00  (día hábil normal)
     */
    public static function calcularVencimientoReserva(DateTimeImmutable $desde): DateTimeImmutable
    {
        $esDiaHabil = in_array((int) $desde->format('N'), self::DIAS_HABILES, true);

        if ($esDiaHabil) {
            $medianoche          = $desde->modify('+1 day')->setTime(0, 0, 0);
            $segundosRestantesHoy = $medianoche->getTimestamp() - $desde->getTimestamp();
            $segundosRestantes   = (24 * 3600) - $segundosRestantesHoy;
        } else {
            // Fin de semana: las 24hs corren íntegras desde el próximo día hábil
            $segundosRestantes = 24 * 3600;
        }

        $proximoDiaHabil = $desde->modify('+1 day')->setTime(0, 0, 0);
        while (!in_array((int) $proximoDiaHabil->format('N'), self::DIAS_HABILES, true)) {
            $proximoDiaHabil = $proximoDiaHabil->modify('+1 day');
        }

        return $proximoDiaHabil->modify("+{$segundosRestantes} seconds");
    }
}
