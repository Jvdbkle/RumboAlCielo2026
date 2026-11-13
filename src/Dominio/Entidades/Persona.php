<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use RumboAlCielo\Dominio\Validacion\Validador;

final class Persona
{
    public function __construct(
        private int $idPersona,
        private string $nombres,
        private string $apellidos,
    ) {
    }

    /**
     * HU-03 criterios 6 y 7: los nombres y apellidos son obligatorios y solo admiten
     * letras y espacios. Con datos inválidos la persona no se crea.
     */
    public static function registrar(int $idPersona, string $nombres, string $apellidos): self
    {
        return new self(
            $idPersona,
            Validador::nombre($nombres, 'nombres'),
            Validador::nombre($apellidos, 'apellidos'),
        );
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

    /** HU-03 criterio 4 (edición): mismas reglas que al registrar. */
    public function setNombres(string $nombres): void
    {
        $this->nombres = Validador::nombre($nombres, 'nombres');
    }

    public function setApellidos(string $apellidos): void
    {
        $this->apellidos = Validador::nombre($apellidos, 'apellidos');
    }

    public function obtenerNombreCompleto(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }
}