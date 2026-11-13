<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;

/** Lo que el empleado ve al consultar el menú de una semana (HU-14). */
final class ResultadoMenu
{
    /** @param DiaMenu[] $dias Los días de lunes a jueves; vacío si el menú no está publicado. */
    public function __construct(
        public readonly DateTimeImmutable $lunes,
        public readonly bool $publicado,
        public readonly array $dias,
        public readonly string $aviso = '',
    ) {
    }
}