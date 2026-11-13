<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use RumboAlCielo\Aplicacion\Puertos\AlmacenFotos;
use RumboAlCielo\Aplicacion\Puertos\ArchivoSubido;

/** Almacén falso para las pruebas: no escribe en el disco, solo recuerda qué fotos se guardaron. */
final class AlmacenFotosEnMemoria implements AlmacenFotos
{
    /** @var list<string> */
    private array $guardadas = [];

    public function guardar(ArchivoSubido $archivo): string
    {
        $nombre = 'foto-' . (count($this->guardadas) + 1) . '.' . $archivo->extension();
        $this->guardadas[] = $nombre;
        return $nombre;
    }

    /** @return list<string> */
    public function guardadas(): array
    {
        return $this->guardadas;
    }
}