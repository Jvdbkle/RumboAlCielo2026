<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Archivos;

use RumboAlCielo\Aplicacion\Puertos\ArchivoSubido;

/**
 * Convierte lo que PHP recibe en $_FILES en un ArchivoSubido. El tipo se detecta leyendo el
 * contenido real del archivo (no la extensión ni lo que declara el navegador).
 */
final class ArchivosSubidosPhp
{
    /** Devuelve el archivo de ese campo, o null si el usuario no eligió ninguno. */
    public static function opcional(string $campo): ?ArchivoSubido
    {
        $datos = $_FILES[$campo] ?? null;
        if (!is_array($datos) || ($datos['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        // El archivo superó el límite del servidor: se trata como "demasiado pesado".
        if (in_array($datos['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return new ArchivoSubido('', ArchivoSubido::TAMANIO_MAXIMO_BYTES + 1, 'image/jpeg', '');
        }
        if ($datos['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $datos['tmp_name'])) {
            return new ArchivoSubido('', 0, '', '');
        }

        $ruta = (string) $datos['tmp_name'];
        return new ArchivoSubido((string) $datos['name'], (int) $datos['size'], self::tipoReal($ruta), $ruta);
    }

    /** Devuelve el archivo de ese campo; si no se eligió ninguno, uno vacío que el caso de uso rechazará. */
    public static function obligatorio(string $campo): ArchivoSubido
    {
        return self::opcional($campo) ?? new ArchivoSubido('', 0, '', '');
    }

    private static function tipoReal(string $ruta): string
    {
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $tipo = $info === false ? false : finfo_file($info, $ruta);
        // Además de mirar el tipo, se comprueba que de verdad sea una imagen legible.
        if ($tipo === false || @getimagesize($ruta) === false) {
            return 'application/octet-stream';
        }
        return $tipo;
    }
}