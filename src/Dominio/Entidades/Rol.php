<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

final class Rol
{
    public const ADMINISTRADOR = 'Administrador';
    public const EMPLEADO = 'Empleado';

    public function __construct(
        private int $idRol,
        private string $nombreRol,
    ) {
    }

    public function getIdRol(): int
    {
        return $this->idRol;
    }

    public function getNombreRol(): string
    {
        return $this->nombreRol;
    }

    public function esAdministrador(): bool
    {
        return $this->nombreRol === self::ADMINISTRADOR;
    }
}