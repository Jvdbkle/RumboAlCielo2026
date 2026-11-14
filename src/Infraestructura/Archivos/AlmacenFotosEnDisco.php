<?php

declare(strict_types=1);

namespace RumboAlCielo\Infraestructura\Archivos;

use RuntimeException;
use RumboAlCielo\Aplicacion\Puertos\AlmacenFotos;
use RumboAlCielo\Aplicacion\Puertos\ArchivoSubido;

/**
 * Guarda las fotos de los platos en una carpeta del servidor. El nombre lo genera el
 * sistema (aleatorio, con extensión según el tipo real): nunca se usa el nombre que envió
 * el usuario, así no puede subir un archivo con nombre o extensión peligrosos.
 */
final class AlmacenFotosEnDisco implements AlmacenFotos
{
    public function __construct(private string $carpeta)
    {
    }

    public function guardar(ArchivoSubido $archivo): string
    {
        if (!is_dir($this->carpeta) && !mkdir($this->carpeta, 0755, true) && !is_dir($this->carpeta)) {
            throw new RuntimeException('No se pudo crear la carpeta de fotos.');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . $archivo->extension();
        if (!move_uploaded_file($archivo->rutaTemporal, rtrim($this->carpeta, '/\\') . DIRECTORY_SEPARATOR . $nombre)) {
            throw new RuntimeException('No se pudo guardar la foto.');
        }
        return $nombre;
    }
}