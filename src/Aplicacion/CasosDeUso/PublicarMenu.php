<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\FeriadoRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-13 criterios 1 y 3: el administrador publica el menú de la semana (lunes a jueves)
 * para que los empleados puedan consultarlo y reservar. Un día feriado o sin atención no
 * puede publicarse.
 */
final class PublicarMenu
{
    public function __construct(
        private MenuRepositorio $menus,
        private FeriadoRepositorio $feriados,
    ) {
    }

    /** @param string $fecha Cualquier fecha de la semana a publicar, en formato AAAA-MM-DD. */
    public function ejecutar(string $fecha): void
    {
        $dia = DateTimeImmutable::createFromFormat('!Y-m-d', trim($fecha));
        if ($dia === false || $dia->format('Y-m-d') !== trim($fecha)) {
            throw new ReglaDeNegocioException('La fecha ingresada no es válida.');
        }

        $menu = $this->menus->buscarPorFechaInicio(Menu::lunesDeLaSemana($dia));
        if ($menu === null) {
            throw new ReglaDeNegocioException('No hay platos registrados para esa semana.');
        }

        // Criterio 3: no se publica menú para una fecha feriada o sin atención.
        foreach ($menu->getPlatos() as $plato) {
            $feriado = $this->feriados->buscarPorFecha($plato->getFecha());
            if ($feriado !== null) {
                throw new ReglaDeNegocioException(sprintf(
                    'No se puede publicar menú para el %s: es feriado o día sin atención (%s).',
                    $plato->getFecha()->format('d/m/Y'),
                    $feriado->getMotivo()
                ));
            }
        }

        $menu->publicar();
        $this->menus->guardar($menu);
    }
}