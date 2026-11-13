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
 * HU-12 criterios 2, 3, 4 y 5: el administrador modifica un componente o sustituye una foto
 * del plato de un día. Una foto inválida se rechaza y se conserva la anterior. Al guardar,
 * el plato registra el aviso de cambio (plato.modificado) para los empleados con reserva
 * en esa fecha; ese aviso se despacha desde el módulo de Reservas.
 */
final class EditarPlato
{
    public function __construct(
        private MenuRepositorio $menus,
        private AlmacenFotos $fotos,
    ) {
    }

    /**
     * @param string $fecha Fecha del plato en formato AAAA-MM-DD.
     * Las fotos son opcionales: con null se conserva la foto actual.
     */
    public function ejecutar(
        string $fecha,
        string $sopa,
        string $platoPrincipal,
        string $ensaladaGuarnicion,
        string $postre,
        string $refresco,
        ?ArchivoSubido $fotoSopa = null,
        ?ArchivoSubido $fotoPlatoPrincipal = null,
    ): void {
        $dia = DateTimeImmutable::createFromFormat('!Y-m-d', trim($fecha));
        if ($dia === false || $dia->format('Y-m-d') !== trim($fecha)) {
            throw new ReglaDeNegocioException('La fecha ingresada no es válida.');
        }

        $menu = $this->menus->buscarPorFechaInicio(Menu::lunesDeLaSemana($dia));
        $plato = $menu === null ? null : $menu->consultarDia($dia);
        if ($menu === null || $plato === null) {
            throw new ReglaDeNegocioException('No hay un plato registrado para ese día.');
        }

        $sopa = trim($sopa);
        $platoPrincipal = trim($platoPrincipal);
        $ensaladaGuarnicion = trim($ensaladaGuarnicion);
        $postre = trim($postre);
        $refresco = trim($refresco);

        // Primero se valida todo: si algo falla, no cambia nada y se conservan los datos y fotos anteriores.
        (new Plato(
            $plato->getIdPlato(),
            $plato->getIdMenu(),
            $dia,
            $sopa,
            $platoPrincipal,
            $ensaladaGuarnicion,
            $postre,
            $refresco,
            $plato->getFotoSopa(),
            $plato->getFotoPlatoPrincipal(),
        ))->editar();
        $fotoSopa?->validarComoFoto();
        $fotoPlatoPrincipal?->validarComoFoto();

        $plato->cambiarComponentes(
            $sopa,
            $platoPrincipal,
            $ensaladaGuarnicion,
            $postre,
            $refresco,
            $fotoSopa === null ? null : $this->fotos->guardar($fotoSopa),
            $fotoPlatoPrincipal === null ? null : $this->fotos->guardar($fotoPlatoPrincipal),
        );
        $plato->editar();
        $plato->notificarCambio();

        $this->menus->guardar($menu);
    }
}