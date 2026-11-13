<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

final class Feriado
{
    public function __construct(
        private int $idFeriado,
        private DateTimeImmutable $fecha,
        private string $motivo,
    ) {
    }

    public function getIdFeriado(): int
    {
        return $this->idFeriado;
    }

    public function getFecha(): DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getMotivo(): string
    {
        return $this->motivo;
    }

       public const MOTIVO_MIN = 3;
    public const MOTIVO_MAX = 100;

    /**
     * HU-15 criterios 1 y 2: el motivo es obligatorio y tiene entre 3 y 100 caracteres.
     * (La fecha repetida la detecta la aplicación.)
     */
    public function registrar(): void
    {
        $motivo = trim($this->motivo);
        if ($motivo === '') {
            throw new ReglaDeNegocioException('Debe indicar el motivo del feriado o día sin atención.');
        }
        $largo = mb_strlen($motivo);
        if ($largo < self::MOTIVO_MIN || $largo > self::MOTIVO_MAX) {
            throw new ReglaDeNegocioException('El motivo debe tener entre 3 y 100 caracteres.');
        }
    }

    public function esFeriado(DateTimeImmutable $fecha): bool
    {
        return $this->fecha->format('Y-m-d') === $fecha->format('Y-m-d');
    }
}