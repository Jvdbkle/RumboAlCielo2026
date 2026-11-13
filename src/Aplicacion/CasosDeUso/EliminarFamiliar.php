<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;


final class EliminarFamiliar
{
    public function __construct(private FamiliarRepositorio $familiares)
    {
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
        return true;
    }
}