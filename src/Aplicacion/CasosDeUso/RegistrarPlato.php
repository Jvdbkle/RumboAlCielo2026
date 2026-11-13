<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Puertos\AlmacenFotos;
use RumboAlCielo\Aplicacion\Puertos\ArchivoSubido;
use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Entidades\Plato;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-12 criterios 1, 2, 4 y 5: el administrador registra los componentes del plato de un
 * día de atención con las fotos de la sopa y del plato principal. El plato queda dentro del
 * menú de esa semana (HU-13).
 */
final class RegistrarPlato
{
    public function __construct(
        private MenuRepositorio $menus,
        private AlmacenFotos $fotos,
    ) {
    }

    /** @param string $fecha Fecha del día de atención en formato AAAA-MM-DD. */
    public function ejecutar(
        string $fecha,
        string $sopa,
        string $platoPrincipal,
        string $ensaladaGuarnicion,
        string $postre,
        string $refresco,
        ArchivoSubido $fotoSopa,
        ArchivoSubido $fotoPlatoPrincipal,
    ): void {
        $dia = DateTimeImmutable::createFromFormat('!Y-m-d', trim($fecha));
        if ($dia === false || $dia->format('Y-m-d') !== trim($fecha)) {
            throw new ReglaDeNegocioException('La fecha ingresada no es válida.');
        }
        if ((int) $dia->format('N') > 4) {
            throw new ReglaDeNegocioException('Solo se pueden registrar platos de lunes a jueves.');
        }

        $sopa = trim($sopa);
        $platoPrincipal = trim($platoPrincipal);
        $ensaladaGuarnicion = trim($ensaladaGuarnicion);
        $postre = trim($postre);
        $refresco = trim($refresco);

        // Primero se valida todo: si algo falla, no se guarda ninguna foto ni ningún dato.
        (new Plato(0, 0, $dia, $sopa, $platoPrincipal, $ensaladaGuarnicion, $postre, $refresco, 'borrador.jpg', 'borrador.jpg'))
            ->registrar();
        $fotoSopa->validarComoFoto();
        $fotoPlatoPrincipal->validarComoFoto();

        $lunes = Menu::lunesDeLaSemana($dia);
        $menu = $this->menus->buscarPorFechaInicio($lunes);
        if ($menu !== null && $menu->consultarDia($dia) !== null) {
            throw new ReglaDeNegocioException('Ya hay un plato registrado para ese día.');
        }
        if ($menu === null) {
            $this->menus->guardar(new Menu(0, $lunes));
            $menu = $this->menus->buscarPorFechaInicio($lunes);
        }

        $plato = new Plato(
            0,
            $menu->getIdMenu(),
            $dia,
            $sopa,
            $platoPrincipal,
            $ensaladaGuarnicion,
            $postre,
            $refresco,
            $this->fotos->guardar($fotoSopa),
            $this->fotos->guardar($fotoPlatoPrincipal),
        );
        $plato->registrar();

        $menu->asignarPlato($plato);
        $this->menus->guardar($menu);
    }
}