<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\FeriadoRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Entidades\Plato;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-16 y HU-17: el empleado reserva el almuerzo de un día para sí mismo y para sus familiares.
 * El consumo queda a cargo del empleado titular. HU-20: devuelve lo necesario para confirmar
 * en pantalla (fecha, persona e importe) y el aviso al administrador.
 */
final class ReservarAlmuerzo
{
    public const SIN_ATENCION = 'Para ese día no hay atención ni menú disponible.';

    public function __construct(
        private ReservaRepositorio $reservas,
        private PensionRepositorio $pensiones,
        private FamiliarRepositorio $familiares,
        private UsuarioRepositorio $usuarios,
        private PersonaRepositorio $personas,
        private MenuRepositorio $menus,
        private FeriadoRepositorio $feriados,
    ) {
    }

    /** @param int[] $idsFamiliares Los familiares elegidos. */
    public function ejecutar(int $idEmpleado, bool $paraMi, array $idsFamiliares, DateTimeImmutable $fecha): ResultadoReserva
    {
        $fecha = $fecha->setTime(0, 0);

        // Primero se valida todo: si algo falla, no se guarda ninguna reserva (HU-20 criterio 2).
        $empleado = $this->usuarios->buscarPorId($idEmpleado);
        if ($empleado === null || !$empleado->estaHabilitado()) {
            throw new ReglaDeNegocioException('El empleado no está habilitado para reservar.');
        }

        // HU-17 criterio 3: solo el empleado y sus familiares registrados y activos.
        $idsPersona = [];
        if ($paraMi) {
            $idsPersona[] = $empleado->getIdPersona();
        }
        foreach (array_unique($idsFamiliares) as $idFamiliar) {
            $familiar = $this->familiares->buscarPorId($idFamiliar);
            if ($familiar === null || $familiar->getIdUsuario() !== $idEmpleado || !$familiar->estaActivo()) {
                throw new ReglaDeNegocioException('Solo puedes reservar para ti y para tus familiares registrados.');
            }
            $idsPersona[] = $familiar->getIdPersona();
        }
        if ($idsPersona === []) {
            throw new ReglaDeNegocioException('Selecciona al menos una persona para la reserva.');
        }

        // HU-16 criterios 2 y 5: el día debe tener atención, menú publicado y estar antes del corte.
        $plato = $this->platoDelDia($fecha);
        (new Reserva(0, 0, $idEmpleado, $plato->getIdPlato(), $fecha))->registrar();

        $mes = $fecha->modify('first day of this month');
        $porGuardar = [];
        $omitidas = [];
        foreach ($idsPersona as $idPersona) {
            $nombre = $this->nombreDe($idPersona);

            // HU-16 criterio 6: un almuerzo por persona por día. Una reserva cancelada no cuenta.
            if ($this->reservas->buscarActivaDePersonaEnFecha($idPersona, $fecha) !== null) {
                $omitidas[] = [$nombre, 'Ya tiene una reserva para ese día.'];
                continue;
            }

            // HU-16 criterio 7: con una solicitud de pensión pendiente no se reserva por separado.
            $pension = $this->pensiones->buscarPorPersonaYMes($idPersona, $mes);
            if ($pension !== null && $pension->estaPendiente()) {
                $omitidas[] = [$nombre, 'Tiene una solicitud de pensión pendiente en ese mes.'];
                continue;
            }

            // HU-16 criterio 4: con pensión activa la tarifa es la de pensionado.
            $modalidad = $pension !== null && $pension->estaActiva() ? Modalidad::Pensionada : Modalidad::Diaria;
            $reserva = new Reserva(0, $idPersona, $idEmpleado, $plato->getIdPlato(), $fecha, $modalidad);
            $reserva->registrar();
            $porGuardar[] = [$nombre, $reserva];
        }

        $registradas = [];
        $eventos = [];
        foreach ($porGuardar as [$nombre, $reserva]) {
            $id = $this->reservas->guardar($reserva);
            $registradas[] = [$nombre, $reserva->getTarifa()];

            // HU-20 criterio 3: aviso al administrador con el número real de la reserva.
            $guardada = $this->reservas->buscarPorId($id);
            if ($guardada !== null) {
                $guardada->notificarAdministrador();
                array_push($eventos, ...$guardada->extraerEventos());
            }
        }

        return new ResultadoReserva($fecha, $registradas, $omitidas, $eventos);
    }

    private function platoDelDia(DateTimeImmutable $fecha): Plato
    {
        if ($this->feriados->buscarPorFecha($fecha) !== null) {
            throw new ReglaDeNegocioException(self::SIN_ATENCION);
        }
        $menu = $this->menus->buscarPorFechaInicio(Menu::lunesDeLaSemana($fecha));
        $plato = $menu !== null && $menu->estaPublicado() ? $menu->consultarDia($fecha) : null;
        if ($plato === null) {
            throw new ReglaDeNegocioException(self::SIN_ATENCION);
        }
        return $plato;
    }

    private function nombreDe(int $idPersona): string
    {
        $persona = $this->personas->buscarPorId($idPersona);
        return $persona === null ? '' : $persona->obtenerNombreCompleto();
    }
}