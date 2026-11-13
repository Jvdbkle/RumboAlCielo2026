<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Dominio\Enumeraciones\Parentesco;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Validacion\Validador;

/**
 * HU-04 criterio 3: el administrador modifica los datos de un familiar y el sistema los
 * actualiza. Se aplican las mismas reglas de nombres y parentesco que al registrar.
 */
final class EditarFamiliar
{
    public function __construct(
        private FamiliarRepositorio $familiares,
        private PersonaRepositorio $personas,
    ) {
    }

    public function ejecutar(int $idFamiliar, string $nombres, string $apellidos, string $parentesco): void
    {
        $familiar = $this->familiares->buscarPorId($idFamiliar);
        $persona = $familiar === null ? null : $this->personas->buscarPorId($familiar->getIdPersona());
        if ($familiar === null || $persona === null) {
            throw new ReglaDeNegocioException('No se encontró al familiar.');
        }

        // Se valida todo antes de cambiar nada: si un dato es inválido no se modifica ninguno.
        Validador::nombre($nombres, 'nombres');
        Validador::nombre($apellidos, 'apellidos');
        $parentesco = Parentesco::tryFrom($parentesco);
        if ($parentesco === null) {
            throw new ReglaDeNegocioException('El parentesco solo puede ser cónyuge, hijo o hija.');
        }

        $familiar->editarDatos($parentesco);
        $persona->setNombres($nombres);
        $persona->setApellidos($apellidos);

        $this->personas->guardar($persona);
        $this->familiares->guardar($familiar);
    }
}