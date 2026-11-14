<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;

/** Lo que muestra el mostrador al validar un código QR válido (HU-22 criterio 1). */
final class ResultadoEntrega
{
    public function __construct(
        public readonly string $nombrePersona,
        public readonly DateTimeImmutable $horaEntrega,
    ) {
    }
}