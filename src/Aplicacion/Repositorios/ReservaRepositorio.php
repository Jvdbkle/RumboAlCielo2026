<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use DateTimeImmutable;

/**
 * Consultas sobre reservas que necesita el módulo de Pensiones (HU-10 y HU-11).
 * El módulo de Reservas agregará aquí lo que necesite para registrar y cancelar.
 */
interface ReservaRepositorio
{
    /** Reservas de la persona en el mes que no están canceladas (confirmadas o entregadas). */
    public function contarReservadasEnMes(int $idPersona, DateTimeImmutable $mes): int;

    /** Reservas de la persona en el mes cuyo almuerzo ya fue entregado. */
    public function contarEntregadasEnMes(int $idPersona, DateTimeImmutable $mes): int;

    /** HU-10 criterio 1: las reservas del mes pasan a ser normales (18 Bs). */
    public function pasarAModalidadDiariaEnMes(int $idPersona, DateTimeImmutable $mes): void;
}