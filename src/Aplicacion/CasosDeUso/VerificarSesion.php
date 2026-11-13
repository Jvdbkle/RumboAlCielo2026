<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Puertos\Sesion;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-05 criterio 2: si la sesión se cerró o expiró, no se permite entrar a pantallas
 * privadas. La sesión expira tras 15 minutos sin usar el sistema.
 */
final class VerificarSesion
{
    public const MINUTOS_INACTIVIDAD = 15;

    public function __construct(private Sesion $sesion)
    {
    }

    /** Devuelve true si hay una sesión vigente; si expiró, la destruye y devuelve false. */
    public function ejecutar(): bool
    {
        $ultima = $this->sesion->ultimaActividad();
        if ($this->sesion->idUsuario() === null || $ultima === null) {
            return false;
        }

        $ahora = Reloj::ahora();
        $limite = $ultima->modify('+' . self::MINUTOS_INACTIVIDAD . ' minutes');
        if ($ahora > $limite) {
            $this->sesion->destruir();
            return false;
        }

        // Cada uso del sistema renueva el plazo de 15 minutos.
        $this->sesion->registrarActividad($ahora);
        return true;
    }
}