<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use RumboAlCielo\Dominio\Enumeraciones\EstadoFamiliar;
use RumboAlCielo\Dominio\Enumeraciones\Parentesco;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

final class Familiar
{
    public function __construct(
        private int $idFamiliar,
        private int $idPersona,
        private int $idUsuario,
        private Parentesco $parentesco,
        private EstadoFamiliar $estado = EstadoFamiliar::Activo,
    ) {
    }

    public function getIdFamiliar(): int
    {
        return $this->idFamiliar;
    }

    public function getIdPersona(): int
    {
        return $this->idPersona;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getParentesco(): Parentesco
    {
        return $this->parentesco;
    }

    public function getEstado(): EstadoFamiliar
    {
        return $this->estado;
    }

    public function estaActivo(): bool
    {
        return $this->estado === EstadoFamiliar::Activo;
    }

    /** HU-04 criterios 3 y 4: el parentesco solo puede ser cónyuge, hijo o hija (lo garantiza el enum). */
    public function editarDatos(Parentesco $parentesco): void
    {
        if (!$this->estaActivo()) {
            throw new ReglaDeNegocioException('No se puede editar un familiar eliminado.');
        }
        $this->parentesco = $parentesco;
    }

    /** HU-07 criterio 1. */
    public function eliminar(): void
    {
        if (!$this->estaActivo()) {
            throw new ReglaDeNegocioException('El familiar ya fue eliminado.');
        }
        $this->estado = EstadoFamiliar::Eliminado;
    }
}