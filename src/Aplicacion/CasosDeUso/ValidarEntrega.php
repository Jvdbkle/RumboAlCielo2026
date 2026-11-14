<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-22: el administrador escanea el código QR en el mostrador. Si es válido para hoy, la
 * reserva pasa a entregada. Si ya fue canjeado, no corresponde a hoy o no existe, se rechaza
 * (la pantalla muestra la alerta visual y sonora) y no se registra ninguna entrega.
 */
final class ValidarEntrega
{
    public function __construct(
        private ReservaRepositorio $reservas,
        private PersonaRepositorio $personas,
    ) {
    }

    public function ejecutar(string $codigo): ResultadoEntrega
    {
        $codigo = trim($codigo);
        $reserva = $codigo === '' ? null : $this->reservas->buscarPorCodigoQR($codigo);
        if ($reserva === null) {
            throw new ReglaDeNegocioException('El código QR no es válido.');
        }

        // Criterio 3: un código de otro día no sirve.
        if ($reserva->getFecha()->format('Y-m-d') !== Reloj::ahora()->format('Y-m-d')) {
            throw new ReglaDeNegocioException('El código QR no corresponde a la fecha de hoy.');
        }

        // Criterio 2: la entidad rechaza un código ya canjeado (con la hora) o una reserva cancelada.
        // Criterio 1: con un código válido registra la entrega.
        $reserva->marcarEntregada();
        $this->reservas->guardar($reserva);

        $persona = $this->personas->buscarPorId($reserva->getIdPersona());
        return new ResultadoEntrega(
            $persona === null ? '' : $persona->obtenerNombreCompleto(),
            $reserva->getHoraEntrega() ?? Reloj::ahora(),
        );
    }
}