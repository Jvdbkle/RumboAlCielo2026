<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Un archivo listo para descargar (CSV o Excel). */
final class ArchivoExportado
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $tipoMime,
        public readonly string $contenido,
    ) {
    }
}