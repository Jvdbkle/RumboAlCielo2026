<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;

/**
 * HU-04 criterio 2: se muestran los familiares agregados a un empleado.
 * Solo aparecen los activos: un familiar eliminado ya no se puede seleccionar (HU-07).
 */
final class ListarFamiliares
{
    public function __construct(
        private FamiliarRepositorio $familiares,
        private PersonaRepositorio $personas,
    ) {
    }

    /** @return FilaFamiliar[] */
    public function ejecutar(int $idEmpleado): array
    {
        $filas = [];
        foreach ($this->familiares->listarActivosPorUsuario($idEmpleado) as $familiar) {
            $persona = $this->personas->buscarPorId($familiar->getIdPersona());
            $filas[] = new FilaFamiliar(
                $familiar->getIdFamiliar(),
                $persona === null ? '' : $persona->obtenerNombreCompleto(),
                $familiar->getParentesco()->etiqueta(),
            );
        }
        return $filas;
    }
}