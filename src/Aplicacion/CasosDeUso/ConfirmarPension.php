<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/** HU-09 criterios 1 y 3: el administrador confirma una solicitud pendiente de un mes que aún no inició. */
final class ConfirmarPension
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
        $pension->confirmar();
        $this->pensiones->guardar($pension);
    }
}