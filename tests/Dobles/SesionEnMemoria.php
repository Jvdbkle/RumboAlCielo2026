<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Puertos\Sesion;

/** Sesión falsa para las pruebas: guarda los datos en memoria en vez de usar $_SESSION. */
final class SesionEnMemoria implements Sesion
{
    private ?int $idUsuario = null;
    private ?int $idRol = null;
    private ?DateTimeImmutable $ultimaActividad = null;

    public function iniciar(int $idUsuario, int $idRol, DateTimeImmutable $ahora): void
    {
        $this->idUsuario = $idUsuario;
        $this->idRol = $idRol;
        $this->ultimaActividad = $ahora;
    }

    public function idUsuario(): ?int
    {
        return $this->idUsuario;
    }

    public function idRol(): ?int
    {
        return $this->idRol;
    }

    public function ultimaActividad(): ?DateTimeImmutable
    {
        return $this->ultimaActividad;
    }

    public function registrarActividad(DateTimeImmutable $ahora): void
    {
        $this->ultimaActividad = $ahora;
    }

    public function destruir(): void
    {
        $this->idUsuario = null;
        $this->idRol = null;
        $this->ultimaActividad = null;
    }
}