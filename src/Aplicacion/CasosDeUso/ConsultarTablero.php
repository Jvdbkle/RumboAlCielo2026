<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-23: el administrador ve de un vistazo el total de empleados habilitados, el estado del
 * menú de la semana y las reservas del día (pensionados y no pensionados, entregadas y
 * pendientes). Si todavía no hay información, los valores quedan en cero o en "Sin datos".
 */
final class ConsultarTablero
{
    public const SIN_DATOS = 'Sin datos';

    public function __construct(
        private UsuarioRepositorio $usuarios,
        private RolRepositorio $roles,
        private MenuRepositorio $menus,
        private ReservaRepositorio $reservas,
    ) {
    }

    public function ejecutar(): Tablero
    {
        $hoy = Reloj::ahora()->setTime(0, 0);

        $habilitados = 0;
        foreach ($this->usuarios->listarTodos() as $usuario) {
            $rol = $this->roles->buscarPorId($usuario->getIdRol());
            if ($usuario->estaHabilitado() && $rol !== null && !$rol->esAdministrador()) {
                $habilitados++;
            }
        }

        $menu = $this->menus->buscarPorFechaInicio(Menu::lunesDeLaSemana($hoy));
        $estadoMenu = $menu === null ? self::SIN_DATOS : ($menu->estaPublicado() ? 'Publicado' : 'Pendiente');

        $conteo = [Modalidad::Pensionada->value => [0, 0], Modalidad::Diaria->value => [0, 0]];
        foreach ($this->reservas->listarEntre($hoy, $hoy) as $reserva) {
            if ($reserva->getEstado() === EstadoReserva::Cancelada) {
                continue;
            }
            $indice = $reserva->getEstado() === EstadoReserva::Entregada ? 0 : 1;
            $conteo[$reserva->getModalidad()->value][$indice]++;
        }

        return new Tablero(
            $habilitados,
            $estadoMenu,
            $this->aConteo($conteo[Modalidad::Pensionada->value]),
            $this->aConteo($conteo[Modalidad::Diaria->value]),
        );
    }

    /** @param array{0: int, 1: int} $par [entregadas, pendientes] */
    private function aConteo(array $par): ConteoReservas
    {
        return new ConteoReservas($par[0] + $par[1], $par[0], $par[1]);
    }
}