<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/** HU-04 criterio 3: el administrador abre los datos de un familiar activo para modificarlos. */
final class ConsultarFamiliar
{
    public function __construct(
        private FamiliarRepositorio $familiares,
        private PersonaRepositorio $personas,
    ) {
    }

    public function ejecutar(int $idFamiliar): DatosFamiliar
    {
        $familiar = $this->familiares->buscarPorId($idFamiliar);
        $persona = $familiar === null ? null : $this->personas->buscarPorId($familiar->getIdPersona());
        if ($familiar === null || $persona === null || !$familiar->estaActivo()) {
            throw new ReglaDeNegocioException('No se encontró al familiar.');
        }

        return new DatosFamiliar(
            $familiar->getIdFamiliar(),
            $familiar->getIdUsuario(),
            $persona->getNombres(),
            $persona->getApellidos(),
            $familiar->getParentesco()->value,
        );
    }
}