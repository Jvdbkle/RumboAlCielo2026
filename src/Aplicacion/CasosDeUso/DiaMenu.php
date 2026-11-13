<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;

/** El plato de un día del menú semanal, tal como lo ve el empleado (HU-14 criterio 1). */
final class DiaMenu
{
    public function __construct(
        public readonly DateTimeImmutable $fecha,
        public readonly string $sopa,
        public readonly string $platoPrincipal,
        public readonly string $ensaladaGuarnicion,
        public readonly string $postre,
        public readonly string $refresco,
        public readonly string $fotoSopa,
        public readonly string $fotoPlatoPrincipal,
    ) {
    }
}