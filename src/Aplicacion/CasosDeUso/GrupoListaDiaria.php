<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Un empleado titular con las personas de su grupo que almuerzan hoy (HU-24 criterio 2). */
final class GrupoListaDiaria
{
    /** @param PersonaDeLista[] $personas El titular primero (si tiene reserva) y sus familiares debajo. */
    public function __construct(
        public readonly string $nombreTitular,
        public readonly array $personas,
    ) {
    }
}