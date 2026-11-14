<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;

/**
 * HU-11: el empleado consulta el estado de su pensión y la de sus familiares, con los
 * almuerzos reservados y recogidos. Si no hay ninguna, la lista llega vacía.
 */
final class ConsultarMisPensiones
{
    public function __construct(
        private PensionRepositorio $pensiones,
        private ArmarFilasPension $armarFilas,
    ) {
    }

    /** @return FilaPension[] */
    public function ejecutar(int $idEmpleado): array
    {
        return $this->armarFilas->ejecutar($this->pensiones->listarPorTitular($idEmpleado));
    }
}