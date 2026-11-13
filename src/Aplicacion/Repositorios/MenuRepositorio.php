<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Entidades\Menu;

interface MenuRepositorio
{
    /** El menú de la semana que inicia ese lunes (con sus platos), o null si no existe. */
    public function buscarPorFechaInicio(DateTimeImmutable $lunes): ?Menu;

    /** Guarda el menú con sus platos. Si es nuevo (id 0) lo crea. Devuelve su id. */
    public function guardar(Menu $menu): int;
}