<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Una persona de la lista diaria, con su casilla de control manual (HU-24). */
final class PersonaDeLista
{
    public function __construct(
        public readonly string $nombre,
        public readonly bool $esTitular,
        public readonly bool $entregada,
    ) {
    }
}