<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio;

/**
 * Las entidades no envían avisos por sí mismas (eso es infraestructura).
 * Registran un evento y la capa de aplicación lo despacha después.
 */
trait RegistraEventos
{
    /** @var list<array{evento: string, datos: array<string, mixed>}> */
    private array $eventos = [];

    private function registrarEvento(string $evento, array $datos = []): void
    {
        $this->eventos[] = ['evento' => $evento, 'datos' => $datos];
    }

    /** @return list<array{evento: string, datos: array<string, mixed>}> */
    public function extraerEventos(): array
    {
        $eventos = $this->eventos;
        $this->eventos = [];
        return $eventos;
    }
}