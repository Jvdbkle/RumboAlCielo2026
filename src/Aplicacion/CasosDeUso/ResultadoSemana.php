<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;

/** La semana del menú que ve el administrador para registrar platos y publicar (HU-12 y HU-13). */
final class ResultadoSemana
{
    /** @param DiaSemana[] $dias Siempre los cuatro días, de lunes a jueves. */
    public function __construct(
        public readonly DateTimeImmutable $lunes,
        public readonly bool $publicado,
        public readonly array $dias,
    ) {
    }

    public function tienePlatos(): bool
    {
        foreach ($this->dias as $dia) {
            if ($dia->plato !== null) {
                return true;
            }
        }
        return false;
    }
}