<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-07: el administrador elimina a un familiar solo si confirma la acción (criterios 1 y 3).
 * La eliminación no borra el registro (queda como "Eliminado") para conservar su consumo
 * anterior en los reportes. Sus reservas futuras se anulan sin cobro (criterio 2).
 */
final class EliminarFamiliar
{
    public function __construct(
        private FamiliarRepositorio $familiares,
        private ReservaRepositorio $reservas,
    ) {
    }

    /** Devuelve true si el familiar fue eliminado y false si el administrador canceló. */
    public function ejecutar(int $idFamiliar, bool $confirmado): bool
    {
        $familiar = $this->familiares->buscarPorId($idFamiliar);
        if ($familiar === null) {
            throw new ReglaDeNegocioException('No se encontró al familiar.');
        }

        // Criterio 3: sin confirmación no se elimina nada y el registro se mantiene.
        if (!$confirmado) {
            return false;
        }

        $familiar->eliminar();
        $this->familiares->guardar($familiar);

        // Criterio 2: se anulan las reservas que aún no llegaron a su hora de corte.
        // Las de días pasados (o de hoy después de las 09:00) se conservan para los reportes.
        $hoy = Reloj::ahora()->setTime(0, 0);
        foreach ($this->reservas->listarConfirmadasDePersonaDesde($familiar->getIdPersona(), $hoy) as $reserva) {
            if ($reserva->puedeCancelarse()) {
                $reserva->cancelar();
                $this->reservas->guardar($reserva);
            }
        }
        return true;
    }
}