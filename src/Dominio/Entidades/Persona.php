<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

final class Persona
{
    public function __construct(
        private int $idPersona,
        private string $nombres,
        private string $apellidos,
    ) {
    }

    public function getIdPersona(): int
    {
        return $this->idPersona;
    }

    public function getNombres(): string
    {
        return $this->nombres;
    }

    public function getApellidos(): string
    {
        return $this->apellidos;
    }

    public function setNombres(string $nombres): void
    {
        $this->nombres = $nombres;
    }

    public function setApellidos(string $apellidos): void
    {
        $this->apellidos = $apellidos;
    }

    public function obtenerNombreCompleto(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }
}