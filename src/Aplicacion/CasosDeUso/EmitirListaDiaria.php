<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-24: pasadas las 09:00 AM el administrador emite la lista imprimible del día, con el total
 * de almuerzos y a cada familiar agrupado debajo de su empleado titular. Antes de la hora de
 * corte la lista todavía no está disponible. Sin reservas el total es cero.
 */
final class EmitirListaDiaria
{
    public function __construct(
        private ReservaRepositorio $reservas,
        private PersonaRepositorio $personas,
        private UsuarioRepositorio $usuarios,
    ) {
    }

    public function ejecutar(): ListaDiaria
    {
        $ahora = Reloj::ahora();
        $hoy = $ahora->setTime(0, 0);

        // Criterio 3: hasta las 09:00 aún se puede reservar y cancelar, la lista no está cerrada.
        if ($ahora < $hoy->setTime(Reserva::HORA_CORTE, 0)) {
            throw new ReglaDeNegocioException('La lista del día estará disponible después de las 09:00 AM, la hora de corte.');
        }

        // Se agrupa por titular, conservando el orden de llegada de las reservas.
        $porTitular = [];
        $total = 0;
        foreach ($this->reservas->listarEntre($hoy, $hoy) as $reserva) {
            if ($reserva->getEstado() === EstadoReserva::Cancelada) {
                continue;
            }
            $total++;
            $porTitular[$reserva->getIdUsuario()][] = $reserva;
        }
        ksort($porTitular);

        $grupos = [];
        foreach ($porTitular as $idUsuario => $reservasDelGrupo) {
            $usuario = $this->usuarios->buscarPorId($idUsuario);
            $idPersonaTitular = $usuario?->getIdPersona();

            $personas = [];
            foreach ($reservasDelGrupo as $reserva) {
                $personas[] = new PersonaDeLista(
                    $this->nombre($reserva->getIdPersona()),
                    $reserva->getIdPersona() === $idPersonaTitular,
                    $reserva->getEstado() === EstadoReserva::Entregada,
                );
            }
            // Criterio 2: el titular va primero y sus familiares debajo.
            usort($personas, fn (PersonaDeLista $a, PersonaDeLista $b) => $b->esTitular <=> $a->esTitular);

            $grupos[] = new GrupoListaDiaria($idPersonaTitular === null ? '' : $this->nombre($idPersonaTitular), $personas);
        }
        usort($grupos, fn (GrupoListaDiaria $a, GrupoListaDiaria $b) => strcmp($a->nombreTitular, $b->nombreTitular));

        return new ListaDiaria($hoy, $total, $grupos);
    }

    private function nombre(int $idPersona): string
    {
        $persona = $this->personas->buscarPorId($idPersona);
        return $persona === null ? '' : $persona->obtenerNombreCompleto();
    }
}