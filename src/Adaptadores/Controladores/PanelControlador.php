<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

/** Pantallas de inicio de cada rol (HU-01 criterio 1). Cada una exige su rol. */
final class PanelControlador extends ControladorBase
{
    public function __construct(private Guardia $guardia)
    {
    }

    public function mostrarAdministrador(): void
    {
        $this->guardia->exigirAdministrador();
        $this->render('panel', [
            'titulo' => 'Panel del administrador',
            'mensaje' => 'Bienvenido. Elige el módulo con el que quieres trabajar.',
            'modulos' => [
                ['titulo' => 'Usuarios', 'descripcion' => 'Registrar, editar, deshabilitar y reincorporar usuarios.', 'ruta' => '/administrador/usuarios'],
            ],
        ]);
    }

    public function mostrarEmpleado(): void
    {
        $this->guardia->exigirEmpleado();
        $this->render('panel', [
            'titulo' => 'Panel del empleado',
            'mensaje' => 'Bienvenido. Elige lo que quieres hacer.',
            'modulos' => [],
        ]);
    }
}