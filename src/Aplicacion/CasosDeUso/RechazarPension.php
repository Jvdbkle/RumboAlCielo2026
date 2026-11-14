<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/** HU-09 criterio 2: el administrador rechaza una solicitud pendiente y el empleado ve que fue rechazada. */
final class RechazarPension
{
    public function __construct(private PensionRepositorio $pensiones)
    {
    }

    public function ejecutar(int $idPension): void
    {
        $pension = $this->pensiones->buscarPorId($idPension);
        if ($pension === null) {
            throw new ReglaDeNegocioException('La solicitud de pensión no existe.');
        }
        $pension->rechazar();
        $this->pensiones->guardar($pension);
    }
}