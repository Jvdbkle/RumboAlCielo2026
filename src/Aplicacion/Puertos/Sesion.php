<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Puertos;

use DateTimeImmutable;

/**
 * Contrato de la sesión del usuario (HU-01, HU-05).
 * La capa de aplicación solo conoce este contrato; la sesión real de PHP
 * la implementa la capa de infraestructura.
 */
interface Sesion
{
    /** Abre la sesión de un usuario que ya ingresó correctamente. */
    public function iniciar(int $idUsuario, int $idRol, DateTimeImmutable $ahora): void;

    public function idUsuario(): ?int;

    public function idRol(): ?int;

    /** Momento de la última vez que el usuario usó el sistema. */
    public function ultimaActividad(): ?DateTimeImmutable;

    public function registrarActividad(DateTimeImmutable $ahora): void;

    /** Finaliza la sesión y borra todos sus datos. */
    public function destruir(): void;
}