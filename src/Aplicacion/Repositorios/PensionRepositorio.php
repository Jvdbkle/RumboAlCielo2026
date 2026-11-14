<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Entidades\Pension;

interface PensionRepositorio
{
    public function buscarPorId(int $idPension): ?Pension;

    /** La pensión (o solicitud) de una persona para un mes, o null si no hay. */
    public function buscarPorPersonaYMes(int $idPersona, DateTimeImmutable $mes): ?Pension;

    /** @return Pension[] Las pensiones de un empleado titular y de sus familiares, la más reciente primero. */
    public function listarPorTitular(int $idUsuario): array;

    /** @return Pension[] Las pensiones de ese mes en adelante, ordenadas por mes. */
    public function listarDesde(DateTimeImmutable $mes): array;

    /** Guarda una pensión nueva (id 0) o actualiza una existente. Devuelve su id. */
    public function guardar(Pension $pension): int;

    public function eliminar(int $idPension): void;
}