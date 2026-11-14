<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\FeriadoRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-12 y HU-13: el administrador ve los cuatro días de atención de una semana (lunes a
 * jueves) con el plato registrado de cada uno, los feriados y si el menú ya fue publicado.
 * Es la pantalla desde la que registra platos, los edita y publica el menú.
 */
final class ConsultarSemanaDelMenu
{
    public function __construct(
        private MenuRepositorio $menus,
        private FeriadoRepositorio $feriados,
    ) {
    }

    /** @param DateTimeImmutable|null $semana Cualquier fecha de la semana; por defecto, la semana actual. */
    public function ejecutar(?DateTimeImmutable $semana = null): ResultadoSemana
    {
        $lunes = Menu::lunesDeLaSemana($semana ?? Reloj::ahora());
        $menu = $this->menus->buscarPorFechaInicio($lunes);

        $dias = [];
        for ($i = 0; $i < 4; $i++) {
            $fecha = $lunes->modify("+$i day");
            $plato = $menu?->consultarDia($fecha);
            $feriado = $this->feriados->buscarPorFecha($fecha);

            $dias[] = new DiaSemana(
                $fecha,
                $plato === null ? null : new DiaMenu(
                    $plato->getFecha(),
                    $plato->getSopa(),
                    $plato->getPlatoPrincipal(),
                    $plato->getEnsaladaGuarnicion(),
                    $plato->getPostre(),
                    $plato->getRefresco(),
                    $plato->getFotoSopa(),
                    $plato->getFotoPlatoPrincipal(),
                ),
                $feriado?->getMotivo(),
            );
        }

        return new ResultadoSemana($lunes, $menu !== null && $menu->estaPublicado(), $dias);
    }
}