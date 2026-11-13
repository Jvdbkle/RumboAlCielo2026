<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;

/** Repositorio falso para las pruebas: guarda los menús en memoria, sin base de datos. */
final class MenuRepositorioEnMemoria implements MenuRepositorio
{
    /** @var array<string, Menu> Indexado por la fecha del lunes (Y-m-d). */
    private array $menus = [];
    private int $siguienteId = 1;

    public function buscarPorFechaInicio(DateTimeImmutable $lunes): ?Menu
    {
        return $this->menus[$lunes->format('Y-m-d')] ?? null;
    }

    public function guardar(Menu $menu): int
    {
        $clave = $menu->getFechaInicio()->format('Y-m-d');
        if ($menu->getIdMenu() !== 0) {
            $this->menus[$clave] = $menu;
            return $menu->getIdMenu();
        }

        // Igual que la base de datos: un menú nuevo (id 0) recibe el siguiente número.
        $id = $this->siguienteId++;
        $guardado = new Menu($id, $menu->getFechaInicio(), $menu->getEstado());
        foreach ($menu->getPlatos() as $plato) {
            $guardado->asignarPlato($plato);
        }
        $this->menus[$clave] = $guardado;
        return $id;
    }
}