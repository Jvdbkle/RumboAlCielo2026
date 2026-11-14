<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Entidades\Reserva;

interface ReservaRepositorio
{
    public function buscarPorId(int $idReserva): ?Reserva;

    /** HU-22: la reserva que corresponde a ese código QR, o null si no existe. */
    public function buscarPorCodigoQR(string $codigo): ?Reserva;

    /** HU-16 criterio 6: la reserva no cancelada de una persona en una fecha, o null si no hay. */
    public function buscarActivaDePersonaEnFecha(int $idPersona, DateTimeImmutable $fecha): ?Reserva;

    /**
     * HU-19 y HU-21: las reservas (de cualquier estado) de un empleado titular y de sus familiares
     * entre dos fechas, ambas incluidas, ordenadas por fecha.
     *
     * @return Reserva[]
     */
    public function listarPorTitularEntre(int $idUsuario, DateTimeImmutable $desde, DateTimeImmutable $hasta): array;

    /**
     * HU-28: las reservas (de cualquier estado) entre dos fechas, ambas incluidas, ordenadas por fecha;
     * si se indica un titular, solo las suyas y las de sus familiares.
     *
     * @return Reserva[]
     */
    public function listarEntre(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $idUsuario = null): array;

    /**
     * HU-07 criterio 2: las reservas confirmadas de una persona desde esa fecha en adelante.
     *
     * @return Reserva[]
     */
    public function listarConfirmadasDePersonaDesde(int $idPersona, DateTimeImmutable $desde): array;

    /** Guarda una reserva nueva (id 0) o actualiza una existente. Devuelve su id. */
    public function guardar(Reserva $reserva): int;

    /** Reservas de la persona en el mes que no están canceladas (confirmadas o entregadas). */
    public function contarReservadasEnMes(int $idPersona, DateTimeImmutable $mes): int;

    /** Reservas de la persona en el mes cuyo almuerzo ya fue entregado. */
    public function contarEntregadasEnMes(int $idPersona, DateTimeImmutable $mes): int;

    /** HU-10 criterio 1: las reservas del mes pasan a ser normales (18 Bs). */
    public function pasarAModalidadDiariaEnMes(int $idPersona, DateTimeImmutable $mes): void;
}