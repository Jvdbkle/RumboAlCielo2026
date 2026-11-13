<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

abstract class ControladorBase
{
    /** @param array<string, mixed> $datos Variables que la vista podrá usar. */
    protected function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        require dirname(__DIR__) . '/Vistas/' . $vista . '.php';
    }

    protected function redirigir(string $ruta): never
    {
        header('Location: ' . $ruta);
        exit;
    }
}