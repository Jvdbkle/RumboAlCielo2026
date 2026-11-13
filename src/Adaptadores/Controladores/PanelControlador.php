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
            'mensaje' => 'Bienvenido. Desde aquí gestionarás usuarios, menús, pensiones y reportes.',
        ]);
    }

    public function mostrarEmpleado(): void
    {
        $this->guardia->exigirEmpleado();
        $this->render('panel', [
            'titulo' => 'Panel del empleado',
            'mensaje' => 'Bienvenido. Desde aquí consultarás el menú y reservarás tu almuerzo.',
        ]);
    }
}