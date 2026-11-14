<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-09 y HU-10: el administrador ve las solicitudes y las pensiones del mes actual en
 * adelante para confirmarlas, rechazarlas o revocarlas.
 */
final class ListarPensiones
{
    public function __construct(
        private PensionRepositorio $pensiones,
        private ArmarFilasPension $armarFilas,
    ) {
    }

    /** @return FilaPension[] */
    public function ejecutar(): array
    {
        $desde = Reloj::ahora()->modify('first day of this month')->setTime(0, 0);
        return $this->armarFilas->ejecutar($this->pensiones->listarDesde($desde));
    }
}