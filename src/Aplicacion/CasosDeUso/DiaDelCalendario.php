<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;

/**
 * Un día de atención (lunes a jueves) del calendario mensual.
 * Un día es "libre" cuando no es feriado y no tiene reservas del empleado ni de sus familiares.
 */
final class DiaDelCalendario
{
    /**
     * @param ReservaDelCalendario[] $reservas Las reservas del empleado y de sus familiares ese día.
     * @param bool $abierto Si todavía se puede reservar: hay menú publicado, no es feriado y no pasó el corte.
     */
    public function __construct(
        public readonly DateTimeImmutable $fecha,
        public readonly ?string $motivoFeriado,
        public readonly bool $hayMenu,
        public readonly bool $abierto,
        public readonly array $reservas,
    ) {
    }

    public function esFeriado(): bool
    {
        return $this->motivoFeriado !== null;
    }

    public function esLibre(): bool
    {
        return !$this->esFeriado() && $this->reservas === [];
    }
}