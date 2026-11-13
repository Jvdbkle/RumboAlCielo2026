<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-14: el empleado consulta el menú de la semana con las fotos de cada día. Si ya pasó el
 * lunes a las 09:00 y el menú no fue publicado, se muestra un aviso (criterio 2).
 */
final class ConsultarMenu
{
    public const AVISO_EN_PROCESO = 'El menú de la semana está en proceso de carga.';

    public function __construct(private MenuRepositorio $menus)
    {
    }

    /** @param DateTimeImmutable|null $semana Cualquier fecha de la semana a consultar; por defecto, la semana actual. */
    public function ejecutar(?DateTimeImmutable $semana = null): ResultadoMenu
    {
        $ahora = Reloj::ahora();
        $lunes = Menu::lunesDeLaSemana($semana ?? $ahora);
        $menu = $this->menus->buscarPorFechaInicio($lunes);

        // Criterio 1: solo se muestran los menús publicados, con todos sus días.
        if ($menu !== null && $menu->estaPublicado()) {
            $dias = [];
            foreach ($menu->getPlatos() as $plato) {
                $dias[] = new DiaMenu(
                    $plato->getFecha(),
                    $plato->getSopa(),
                    $plato->getPlatoPrincipal(),
                    $plato->getEnsaladaGuarnicion(),
                    $plato->getPostre(),
                    $plato->getRefresco(),
                    $plato->getFotoSopa(),
                    $plato->getFotoPlatoPrincipal(),
                );
            }
            usort($dias, fn (DiaMenu $a, DiaMenu $b) => $a->fecha <=> $b->fecha);
            return new ResultadoMenu($lunes, true, $dias);
        }

        // Criterio 2: desde el lunes a las 09:01 sin menú publicado, se avisa que está en proceso.
        $aviso = $ahora >= $lunes->setTime(9, 1) ? self::AVISO_EN_PROCESO : '';
        return new ResultadoMenu($lunes, false, [], $aviso);
    }
}