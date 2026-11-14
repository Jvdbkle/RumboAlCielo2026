<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Entidades\Pension;

/** Convierte pensiones en filas listas para mostrar; lo comparten las listas del administrador y del empleado. */
final class ArmarFilasPension
{
    public function __construct(
        private PersonaRepositorio $personas,
        private UsuarioRepositorio $usuarios,
        private ReservaRepositorio $reservas,
    ) {
    }

    /**
     * @param Pension[] $pensiones
     * @return FilaPension[]
     */
    public function ejecutar(array $pensiones): array
    {
        $filas = [];
        foreach ($pensiones as $pension) {
            $titular = $this->usuarios->buscarPorId($pension->getIdUsuario());
            $filas[] = new FilaPension(
                $pension->getIdPension(),
                $this->nombreDe($pension->getIdPersona()),
                $titular === null ? '' : $this->nombreDe($titular->getIdPersona()),
                $pension->getMes(),
                $pension->getEstado(),
                $this->reservas->contarReservadasEnMes($pension->getIdPersona(), $pension->getMes()),
                $this->reservas->contarEntregadasEnMes($pension->getIdPersona(), $pension->getMes()),
            );
        }
        return $filas;
    }

    private function nombreDe(int $idPersona): string
    {
        $persona = $this->personas->buscarPorId($idPersona);
        return $persona === null ? '' : $persona->obtenerNombreCompleto();
    }
}