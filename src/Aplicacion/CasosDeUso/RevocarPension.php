<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-10: el administrador revoca una pensión activa registrada por error, siempre que no
 * haya consumos. Las reservas del mes se mantienen como reservas normales a 18 Bs.
 */
final class RevocarPension
{
    public function __construct(
        private PensionRepositorio $pensiones,
        private ReservaRepositorio $reservas,
    ) {
    }

    public function ejecutar(int $idPension): void
    {
        $pension = $this->pensiones->buscarPorId($idPension);
        if ($pension === null) {
            throw new ReglaDeNegocioException('La pensión no existe.');
        }
        $pension->revocar();

        // Criterio 2: con al menos un almuerzo entregado en el mes ya no se puede revocar.
        if ($this->reservas->contarEntregadasEnMes($pension->getIdPersona(), $pension->getMes()) > 0) {
            throw new ReglaDeNegocioException('No es posible revocar la pensión porque ya se registró un consumo.');
        }

        // Criterio 1: las reservas del mes pasan a ser normales y la pensión se elimina.
        $this->reservas->pasarAModalidadDiariaEnMes($pension->getIdPersona(), $pension->getMes());
        $this->pensiones->eliminar($idPension);
    }
}