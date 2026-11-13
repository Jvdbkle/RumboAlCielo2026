<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\RegistraEventos;
use RumboAlCielo\Dominio\Reloj;

final class Reserva
{
    use RegistraEventos;

    public const TARIFA_DIARIA = 18.0;
    public const TARIFA_PENSIONADA = 15.0;
    /** Hora de corte del día del almuerzo: reservar y cancelar solo antes de las 09:00 AM. */
    public const HORA_CORTE = 9;

    private DateTimeImmutable $fecha;

    public function __construct(
        private int $idReserva,
        private int $idPersona,
        private int $idUsuario,
        private int $idPlato,
        DateTimeImmutable $fecha,
        private Modalidad $modalidad = Modalidad::Diaria,
        private float $tarifa = 0.0,
        private EstadoReserva $estado = EstadoReserva::Confirmada,
        private string $codigoQR = '',
        private ?DateTimeImmutable $horaEntrega = null,
    ) {
        $this->fecha = $fecha->setTime(0, 0);
    }

    public function getIdReserva(): int
    {
        return $this->idReserva;
    }

    public function getIdPersona(): int
    {
        return $this->idPersona;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getIdPlato(): int
    {
        return $this->idPlato;
    }

    public function getFecha(): DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getModalidad(): Modalidad
    {
        return $this->modalidad;
    }

    public function getTarifa(): float
    {
        return $this->tarifa;
    }

    public function getEstado(): EstadoReserva
    {
        return $this->estado;
    }

    public function getCodigoQR(): string
    {
        return $this->codigoQR;
    }

    public function getHoraEntrega(): ?DateTimeImmutable
    {
        return $this->horaEntrega;
    }

    /**
     * HU-16 criterios 1 a 5 y HU-17 criterio 1: de lunes a jueves, antes de las 09:00 AM del
     * día del almuerzo, tarifa según la modalidad y un código QR único.
     * (Feriado, menú publicado, duplicados y pensión pendiente los verifica el caso de uso.)
     */
    public function registrar(): void
    {
        if ((int) $this->fecha->format('N') > 4) {
            throw new ReglaDeNegocioException('Para ese día no hay atención ni menú disponible.');
        }
        if (!$this->antesDelCorte()) {
            throw new ReglaDeNegocioException('El horario de reservas cerró.');
        }
        $this->tarifa = $this->modalidad === Modalidad::Pensionada ? self::TARIFA_PENSIONADA : self::TARIFA_DIARIA;
        $this->estado = EstadoReserva::Confirmada;
        if ($this->codigoQR === '') {
            $this->codigoQR = bin2hex(random_bytes(16));
        }
    }

    /** HU-18 criterios 1 y 2. */
    public function cancelar(): void
    {
        if ($this->estado !== EstadoReserva::Confirmada) {
            throw new ReglaDeNegocioException('Solo se puede cancelar una reserva confirmada.');
        }
        if (!$this->antesDelCorte()) {
            throw new ReglaDeNegocioException('No se puede cancelar: pasaron las 09:00 AM y el almuerzo se cobrará.');
        }
        $this->estado = EstadoReserva::Cancelada;
    }

    /** HU-22 criterios 1 y 2. */
    public function marcarEntregada(): void
    {
        if ($this->estado === EstadoReserva::Entregada) {
            throw new ReglaDeNegocioException(
                'El código ya fue canjeado a las ' . $this->horaEntrega?->format('H:i') . '.'
            );
        }
        if ($this->estado === EstadoReserva::Cancelada) {
            throw new ReglaDeNegocioException('La reserva está cancelada.');
        }
        $this->estado = EstadoReserva::Entregada;
        $this->horaEntrega = Reloj::ahora();
    }

    /** HU-22 criterios 1 y 3: código correcto, reserva confirmada y fecha de hoy. */
    public function validarQR(string $codigo): bool
    {
        return $this->estado === EstadoReserva::Confirmada
            && hash_equals($this->codigoQR, $codigo)
            && $this->fecha->format('Y-m-d') === Reloj::ahora()->format('Y-m-d');
    }

    /** HU-20 criterio 3: avisa al administrador (lo despacha la aplicación). */
    public function notificarAdministrador(): void
    {
        $this->registrarEvento('reserva.registrada', [
            'idReserva' => $this->idReserva,
            'idPersona' => $this->idPersona,
            'fecha' => $this->fecha->format('Y-m-d'),
        ]);
    }

    /** HU-26 criterios 5 y 6: se cobra todo lo que no esté cancelado (incluye el que no asistió). */
    public function esCobrable(): bool
    {
        return $this->estado !== EstadoReserva::Cancelada;
    }

    private function antesDelCorte(): bool
    {
        return Reloj::ahora() < $this->fecha->setTime(self::HORA_CORTE, 0);
    }
}