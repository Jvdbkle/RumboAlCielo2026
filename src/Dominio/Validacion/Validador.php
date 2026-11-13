<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Validacion;

use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * Reglas de formato de los datos personales. Se usan en un solo lugar para que
 * usuarios y familiares validen exactamente igual.
 */
final class Validador
{
    public const NOMBRE_MIN = 2;
    public const NOMBRE_MAX = 100;
    public const CORREO_MAX = 150;
    public const LARGO_TELEFONO = 8;

    /**
     * HU-03 criterio 7 y HU-04 criterio 5: solo letras (con tildes y ñ) y espacios.
     * Devuelve el texto limpio: sin espacios sobrantes.
     */
    public static function nombre(string $valor, string $campo): string
    {
        $limpio = preg_replace('/\s+/u', ' ', trim($valor)) ?? '';
        if ($limpio === '') {
            throw new ReglaDeNegocioException("Falta el dato obligatorio: $campo.");
        }

        $largo = mb_strlen($limpio);
        if ($largo < self::NOMBRE_MIN || $largo > self::NOMBRE_MAX || preg_match('/^[\p{L} ]+$/u', $limpio) !== 1) {
            throw new ReglaDeNegocioException(
                "El campo $campo solo admite letras y espacios, de 2 a 100 caracteres."
            );
        }
        return $limpio;
    }

    /** HU-03 criterio 8: formato válido, máximo 150 caracteres; se guarda en minúsculas. */
    public static function correo(string $valor): string
    {
        $limpio = mb_strtolower(trim($valor));
        if ($limpio === '') {
            throw new ReglaDeNegocioException('Falta el dato obligatorio: correo.');
        }
        if (mb_strlen($limpio) > self::CORREO_MAX || filter_var($limpio, FILTER_VALIDATE_EMAIL) === false) {
            throw new ReglaDeNegocioException('El correo no tiene un formato válido (máximo 150 caracteres).');
        }
        return $limpio;
    }

    /** HU-03 criterio 9: exactamente 8 dígitos, solo números. */
    public static function telefono(string $valor): string
    {
        $limpio = trim($valor);
        if ($limpio === '') {
            throw new ReglaDeNegocioException('Falta el dato obligatorio: teléfono.');
        }
        if (preg_match('/^\d{' . self::LARGO_TELEFONO . '}$/', $limpio) !== 1) {
            throw new ReglaDeNegocioException('El teléfono debe tener exactamente 8 dígitos y solo números.');
        }
        return $limpio;
    }
}