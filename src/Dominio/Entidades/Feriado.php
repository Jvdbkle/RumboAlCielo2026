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

    /** HU-15 criterios 1 y 2: la fecha y el motivo son obligatorios. (La fecha repetida la detecta la aplicación.) */
    public function registrar(): void
    {
        if (trim($this->motivo) === '') {
            throw new ReglaDeNegocioException('Debe indicar el motivo del feriado o día sin atención.');
        }
    }

    public function esFeriado(DateTimeImmutable $fecha): bool
    {
        return $this->fecha->format('Y-m-d') === $fecha->format('Y-m-d');
    }
}