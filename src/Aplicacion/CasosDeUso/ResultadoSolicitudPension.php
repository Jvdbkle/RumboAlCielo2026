<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;

/** Lo que pasó al solicitar la pensión de un mes (HU-08 criterios 1 y 3). */
final class ResultadoSolicitudPension
{
    /**
     * @param string[] $registradas Nombres de las personas con solicitud nueva (pendiente).
     * @param array<int, array{0: string, 1: string}> $yaExistentes Pares [nombre, estado] de quienes ya tenían pensión o solicitud.
     */
    public function __construct(
        public readonly DateTimeImmutable $mes,
        public readonly array $registradas,
        public readonly array $yaExistentes,
    ) {
    }
}