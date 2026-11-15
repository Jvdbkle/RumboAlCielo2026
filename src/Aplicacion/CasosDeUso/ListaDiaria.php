<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;

/** La lista imprimible del día: total de almuerzos y personas agrupadas por titular (HU-24). */
final class ListaDiaria
{
    /** @param GrupoListaDiaria[] $grupos */
    public function __construct(
        public readonly DateTimeImmutable $fecha,
        public readonly int $total,
        public readonly array $grupos,
    ) {
    }
}