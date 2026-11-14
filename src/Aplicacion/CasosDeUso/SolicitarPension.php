<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Entidades\Pension;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-08: el empleado solicita la pensión mensual para sí mismo y para sus familiares.
 * Cada persona queda con una solicitud pendiente a su nombre y con el empleado como titular.
 */
final class SolicitarPension
{
    public function __construct(
        private PensionRepositorio $pensiones,
        private FamiliarRepositorio $familiares,
        private UsuarioRepositorio $usuarios,
        private PersonaRepositorio $personas,
    ) {
    }

    /**
     * @param int[] $idsFamiliares Los familiares elegidos.
     * @param DateTimeImmutable|null $mes Cualquier fecha del mes a solicitar; por defecto, el mes siguiente.
     */
    public function ejecutar(
        int $idEmpleado,
        bool $paraMi,
        array $idsFamiliares,
        ?DateTimeImmutable $mes = null,
    ): ResultadoSolicitudPension {
        $mes = ($mes ?? Reloj::ahora()->modify('first day of next month'))
            ->modify('first day of this month')->setTime(0, 0);

        // Primero se valida todo: si algo falla, no se guarda ninguna solicitud.
        $empleado = $this->usuarios->buscarPorId($idEmpleado);
        if ($empleado === null || !$empleado->estaHabilitado()) {
            throw new ReglaDeNegocioException('El empleado no está habilitado para solicitar pensiones.');
        }

        $idsPersona = [];
        if ($paraMi) {
            $idsPersona[] = $empleado->getIdPersona();
        }
        foreach (array_unique($idsFamiliares) as $idFamiliar) {
            $familiar = $this->familiares->buscarPorId($idFamiliar);
            if ($familiar === null || $familiar->getIdUsuario() !== $idEmpleado || !$familiar->estaActivo()) {
                throw new ReglaDeNegocioException('Uno de los familiares seleccionados no es válido.');
            }
            $idsPersona[] = $familiar->getIdPersona();
        }
        if ($idsPersona === []) {
            throw new ReglaDeNegocioException('Selecciona al menos una persona para la pensión.');
        }

        // HU-08 criterio 2: cada solicitud se prepara antes de guardar; si el mes ya inició, falla aquí.
        $porGuardar = [];
        $registradas = [];
        $yaExistentes = [];
        foreach ($idsPersona as $idPersona) {
            $nombre = $this->nombreDe($idPersona);
            $existente = $this->pensiones->buscarPorPersonaYMes($idPersona, $mes);

            // HU-08 criterio 3: no se duplica; se informa el estado de la existente.
            // Una solicitud rechazada puede volver a pedirse.
            if ($existente !== null && !$existente->estaRechazada()) {
                $yaExistentes[] = [$nombre, $existente->getEstado()->value];
                continue;
            }

            $pension = $existente ?? new Pension(0, $idPersona, $idEmpleado, $mes);
            $pension->solicitar();
            $porGuardar[] = $pension;
            $registradas[] = $nombre;
        }

        foreach ($porGuardar as $pension) {
            $this->pensiones->guardar($pension);
        }

        return new ResultadoSolicitudPension($mes, $registradas, $yaExistentes);
    }

    private function nombreDe(int $idPersona): string
    {
        $persona = $this->personas->buscarPorId($idPersona);
        return $persona === null ? '' : $persona->obtenerNombreCompleto();
    }
}