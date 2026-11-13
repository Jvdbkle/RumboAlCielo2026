<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Enumeraciones\EstadoPension;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

final class Pension
{
    /** Tarifa de pensionado (Bs por almuerzo). */
    public const TARIFA = 15.0;

    private DateTimeImmutable $mes;

    /** @param DateTimeImmutable $mes Cualquier fecha del mes; se guarda el día 1. */
    public function __construct(
        private int $idPension,
        private int $idPersona,
        private int $idUsuario,
        DateTimeImmutable $mes,
        private float $tarifa = self::TARIFA,
        private EstadoPension $estado = EstadoPension::Pendiente,
    ) {
        $this->mes = $mes->modify('first day of this month')->setTime(0, 0);
    }

    public function getIdPension(): int
    {
        return $this->idPension;
    }

    public function getIdPersona(): int
    {
        return $this->idPersona;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getMes(): DateTimeImmutable
    {
        return $this->mes;
    }

    public function getTarifa(): float
    {
        return $this->tarifa;
    }

    public function getEstado(): EstadoPension
    {
        return $this->estado;
    }

    /** HU-08 criterio 2: solo antes de que inicie el mes. */
    public function solicitar(): void
    {
        $this->verificarMesNoIniciado('solo puede solicitarse antes del inicio del mes');
        $this->tarifa = self::TARIFA;
        $this->estado = EstadoPension::Pendiente;
    }

    /** HU-09 criterios 1 y 3. */
    public function confirmar(): void
    {
        $this->verificarPendiente();
        $this->verificarMesNoIniciado('el plazo para la pensión de ese mes está cerrado');
        $this->estado = EstadoPension::Activa;
    }

    /** HU-09 criterio 2. */
    public function rechazar(): void
    {
        $this->verificarPendiente();
        $this->estado = EstadoPension::Rechazada;
    }

    /**
     * HU-10 criterio 1: solo se revoca una pensión activa. La pensión revocada se elimina
     * (la aplicación la borra por el repositorio) y no existe un estado "revocada".
     * La regla "sin almuerzos entregados" necesita las reservas y la verifica el caso de uso.
     */
    public function revocar(): void
    {
        if (!$this->estaActiva()) {
            throw new ReglaDeNegocioException('Solo se puede revocar una pensión activa.');
        }
    }

    public function estaActiva(): bool
    {
        return $this->estado === EstadoPension::Activa;
    }

    public function estaPendiente(): bool
    {
        return $this->estado === EstadoPension::Pendiente;
    }

    private function verificarPendiente(): void
    {
        if (!$this->estaPendiente()) {
            throw new ReglaDeNegocioException('La solicitud no está pendiente.');
        }
    }

    private function verificarMesNoIniciado(string $detalle): void
    {
        if (Reloj::ahora() >= $this->mes) {
            throw new ReglaDeNegocioException('El mes de la pensión ya inició: ' . $detalle . '.');
        }
    }
}