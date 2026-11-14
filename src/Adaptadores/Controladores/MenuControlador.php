<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarMenu;

/** HU-14: el empleado consulta el menú de la semana con las fotos de cada día. */
final class MenuControlador extends ControladorBase
{
    public function __construct(
        private Guardia $guardia,
        private ConsultarMenu $consultarMenu,
    ) {
    }

    public function mostrar(): void
    {
        $this->guardia->exigirEmpleado();
        $semana = self::fecha($_GET['semana'] ?? '');
        $resultado = $this->consultarMenu->ejecutar($semana);

        $this->render('menu_empleado', [
            'resultado' => $resultado,
            'anterior' => $resultado->lunes->modify('-7 day'),
            'siguiente' => $resultado->lunes->modify('+7 day'),
        ]);
    }

    private static function fecha(mixed $texto): ?DateTimeImmutable
    {
        $texto = is_string($texto) ? trim($texto) : '';
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
        return $fecha !== false && $fecha->format('Y-m-d') === $texto ? $fecha : null;
    }
}