<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;

/** Un día (lunes a jueves) de la semana del menú, tal como lo ve el administrador (HU-12 y HU-13). */
final class DiaSemana
{
    public function __construct(
        public readonly DateTimeImmutable $fecha,
        public readonly ?DiaMenu $plato,
        public readonly ?string $motivoFeriado,
    ) {
    }
}