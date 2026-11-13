<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Puertos;

use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * Un archivo que el usuario envió desde el formulario. El tipo real lo detecta la capa de
 * adaptadores leyendo el contenido del archivo, no su extensión (HU-12 criterios 2 y 5).
 */
final class ArchivoSubido
{
    public const TAMANIO_MAXIMO_BYTES = 5 * 1024 * 1024;
    public const TIPOS_PERMITIDOS = ['image/jpeg', 'image/png'];

    public function __construct(
        public readonly string $nombreOriginal,
        public readonly int $tamanioBytes,
        public readonly string $tipoReal,
        public readonly string $rutaTemporal,
    ) {
    }

    /** HU-12 criterios 2 y 5: solo JPG o PNG reales y de hasta 5 MB. */
    public function validarComoFoto(): void
    {
        if ($this->tamanioBytes <= 0) {
            throw new ReglaDeNegocioException('No se recibió la foto o el archivo está vacío.');
        }
        if (!in_array($this->tipoReal, self::TIPOS_PERMITIDOS, true)) {
            throw new ReglaDeNegocioException('La foto debe ser JPG o PNG.');
        }
        if ($this->tamanioBytes > self::TAMANIO_MAXIMO_BYTES) {
            throw new ReglaDeNegocioException('La foto no puede pesar más de 5 MB.');
        }
    }

    /** Extensión segura según el tipo real detectado. */
    public function extension(): string
    {
        return $this->tipoReal === 'image/png' ? 'png' : 'jpg';
    }
}