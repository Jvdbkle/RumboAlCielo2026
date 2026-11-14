<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\FeriadoRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-19: el empleado ve el calendario del mes con los días de lunes a jueves y el estado de
 * sus reservas y las de sus familiares (libre, confirmada, entregada o feriado).
 */
final class ConsultarCalendarioReservas
{
    public function __construct(
        private ReservaRepositorio $reservas,
        private PersonaRepositorio $personas,
        private FeriadoRepositorio $feriados,
        private MenuRepositorio $menus,
    ) {
    }

    /**
     * @param DateTimeImmutable|null $mes Cualquier fecha del mes a consultar; por defecto, el mes actual.
     * @return DiaDelCalendario[] Los días de lunes a jueves del mes, en orden.
     */
    public function ejecutar(int $idEmpleado, ?DateTimeImmutable $mes = null): array
    {
        $ahora = Reloj::ahora();
        $primero = ($mes ?? $ahora)->modify('first day of this month')->setTime(0, 0);
        $ultimo = $primero->modify('last day of this month');

        // Criterio 2: solo las reservas a cargo de este empleado (suyas y de sus familiares).
        // Las canceladas no cuentan: ese día vuelve a verse libre.
        $porDia = [];
        foreach ($this->reservas->listarPorTitularEntre($idEmpleado, $primero, $ultimo) as $reserva) {
            if ($reserva->getEstado() !== EstadoReserva::Cancelada) {
                $porDia[$reserva->getFecha()->format('Y-m-d')][] = $this->aFila($reserva);
            }
        }

        // Criterio 1: solo los días de lunes a jueves.
        $dias = [];
        $menuPorSemana = [];
        for ($dia = $primero; $dia <= $ultimo; $dia = $dia->modify('+1 day')) {
            if ((int) $dia->format('N') > 4) {
                continue;
            }

            $feriado = $this->feriados->buscarPorFecha($dia);
            $lunes = Menu::lunesDeLaSemana($dia)->format('Y-m-d');
            $menuPorSemana[$lunes] ??= $this->menus->buscarPorFechaInicio(Menu::lunesDeLaSemana($dia));
            $menu = $menuPorSemana[$lunes];
            $hayMenu = $feriado === null && $menu !== null && $menu->estaPublicado() && $menu->consultarDia($dia) !== null;

            $dias[] = new DiaDelCalendario(
                $dia,
                $feriado?->getMotivo(),
                $hayMenu,
                $hayMenu && $ahora < $dia->setTime(Reserva::HORA_CORTE, 0),
                $porDia[$dia->format('Y-m-d')] ?? [],
            );
        }
        return $dias;
    }

    private function aFila(Reserva $reserva): ReservaDelCalendario
    {
        $persona = $this->personas->buscarPorId($reserva->getIdPersona());
        return new ReservaDelCalendario(
            $reserva->getIdReserva(),
            $persona === null ? '' : $persona->obtenerNombreCompleto(),
            $reserva->getEstado(),
            $reserva->getTarifa(),
            $reserva->puedeCancelarse(),
        );
    }
}