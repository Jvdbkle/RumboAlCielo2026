<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use RumboAlCielo\Aplicacion\Puertos\GeneradorHojaCalculo;

/** Generador falso para las pruebas: en vez de un .xlsx devuelve las filas como JSON para poder revisarlas. */
final class GeneradorHojaCalculoEnMemoria implements GeneradorHojaCalculo
{
    public function generar(string $nombreHoja, array $encabezados, array $filas): string
    {
        return (string) json_encode(['hoja' => $nombreHoja, 'encabezados' => $encabezados, 'filas' => $filas], JSON_UNESCAPED_UNICODE);
    }
}