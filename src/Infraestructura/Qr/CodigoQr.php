<?php

declare(strict_types=1);

namespace RumboAlCielo\Infraestructura\Qr;

use InvalidArgumentException;

/**
 * Genera códigos QR (modo byte, corrección de errores nivel M, versiones 1 a 6) como imagen SVG.
 * No usa librerías externas: el código de una reserva es corto (32 caracteres) y siempre cabe.
 */
final class CodigoQr
{
    /** Por versión: [codewords de datos, codewords de corrección por bloque, cantidad de bloques]. */
    private const VERSIONES = [
        1 => [16, 10, 1],
        2 => [28, 16, 1],
        3 => [44, 26, 1],
        4 => [64, 18, 2],
        5 => [86, 24, 2],
        6 => [108, 16, 4],
    ];

    /** Posición central de los patrones de alineación por versión (columnas y filas). */
    private const ALINEACION = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34]];

    /** @var array<int, int> */
    private static array $exp = [];
    /** @var array<int, int> */
    private static array $log = [];

    /** Devuelve el QR como una imagen SVG lista para insertar en la página. */
    public static function svg(string $texto, string $descripcion = 'Código QR'): string
    {
        $matriz = self::matriz($texto);
        $n = count($matriz);
        $margen = 4;
        $trazo = '';
        for ($fila = 0; $fila < $n; $fila++) {
            for ($col = 0; $col < $n; $col++) {
                if ($matriz[$fila][$col]) {
                    $trazo .= 'M' . ($col + $margen) . ' ' . ($fila + $margen) . 'h1v1h-1z';
                }
            }
        }
        $lado = $n + 2 * $margen;
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $lado . ' ' . $lado . '" role="img" aria-label="'
            . htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') . '" shape-rendering="crispEdges">'
            . '<rect width="' . $lado . '" height="' . $lado . '" fill="#fff"/>'
            . '<path d="' . $trazo . '" fill="#000"/></svg>';
    }

    /** @return array<int, array<int, bool>> Matriz de módulos (true = oscuro), sin margen. */
    public static function matriz(string $texto): array
    {
        self::iniciarTablas();
        $bytes = array_values(unpack('C*', $texto) ?: []);
        $version = self::elegirVersion(count($bytes));
        [$datosTotales, $ecPorBloque, $bloques] = self::VERSIONES[$version];

        $codewords = self::codificar($bytes, $datosTotales);
        $final = self::agregarCorreccion($codewords, $ecPorBloque, $bloques);

        $n = 17 + 4 * $version;
        $modulos = array_fill(0, $n, array_fill(0, $n, false));
        $reservado = array_fill(0, $n, array_fill(0, $n, false));
        self::dibujarPatrones($modulos, $reservado, $version, $n);
        self::colocarDatos($modulos, $reservado, $final, $n);

        // Se prueban las 8 máscaras y se elige la de menor penalización.
        $mejor = null;
        $mejorPuntos = PHP_INT_MAX;
        for ($mascara = 0; $mascara < 8; $mascara++) {
            $candidata = self::aplicarMascara($modulos, $reservado, $mascara, $n);
            self::dibujarFormato($candidata, $mascara, $n);
            $puntos = self::penalizacion($candidata, $n);
            if ($puntos < $mejorPuntos) {
                $mejorPuntos = $puntos;
                $mejor = $candidata;
            }
        }
        return $mejor;
    }

    private static function elegirVersion(int $largo): int
    {
        foreach (self::VERSIONES as $version => [$datos]) {
            // 4 bits de modo + 8 bits de largo + 8 bits por byte.
            if (12 + 8 * $largo <= 8 * $datos) {
                return $version;
            }
        }
        throw new InvalidArgumentException('El texto es demasiado largo para el código QR.');
    }

    // ------------------------------------------------------------------ datos y corrección

    /** @param int[] $bytes @return int[] */
    private static function codificar(array $bytes, int $datosTotales): array
    {
        $bits = '0100' . str_pad(decbin(count($bytes)), 8, '0', STR_PAD_LEFT);
        foreach ($bytes as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }
        $capacidad = $datosTotales * 8;
        $bits .= str_repeat('0', min(4, $capacidad - strlen($bits)));
        $bits .= str_repeat('0', (8 - strlen($bits) % 8) % 8);

        $codewords = array_map('bindec', str_split($bits, 8));
        for ($relleno = 0; count($codewords) < $datosTotales; $relleno++) {
            $codewords[] = $relleno % 2 === 0 ? 0xEC : 0x11;
        }
        return $codewords;
    }

    /** @param int[] $datos @return int[] Datos y corrección intercalados por bloque. */
    private static function agregarCorreccion(array $datos, int $ecPorBloque, int $bloques): array
    {
        $porBloque = intdiv(count($datos), $bloques);
        $generador = self::polinomioGenerador($ecPorBloque);
        $bloquesDatos = [];
        $bloquesEc = [];
        for ($i = 0; $i < $bloques; $i++) {
            $bloque = array_slice($datos, $i * $porBloque, $porBloque);
            $bloquesDatos[] = $bloque;
            $bloquesEc[] = self::resto($bloque, $generador, $ecPorBloque);
        }

        $final = [];
        for ($i = 0; $i < $porBloque; $i++) {
            foreach ($bloquesDatos as $bloque) {
                $final[] = $bloque[$i];
            }
        }
        for ($i = 0; $i < $ecPorBloque; $i++) {
            foreach ($bloquesEc as $ec) {
                $final[] = $ec[$i];
            }
        }
        return $final;
    }

    private static function iniciarTablas(): void
    {
        if (self::$exp !== []) {
            return;
        }
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
    }

    private static function multiplicar(int $a, int $b): int
    {
        return ($a === 0 || $b === 0) ? 0 : self::$exp[self::$log[$a] + self::$log[$b]];
    }

    /** @return int[] Coeficientes del polinomio generador, sin el término principal. */
    private static function polinomioGenerador(int $grado): array
    {
        $poli = [1];
        for ($i = 0; $i < $grado; $i++) {
            $siguiente = array_fill(0, count($poli) + 1, 0);
            foreach ($poli as $j => $coef) {
                $siguiente[$j] ^= $coef;
                $siguiente[$j + 1] ^= self::multiplicar($coef, self::$exp[$i]);
            }
            $poli = $siguiente;
        }
        return array_slice($poli, 1);
    }

    /** @param int[] $datos @param int[] $generador @return int[] */
    private static function resto(array $datos, array $generador, int $grado): array
    {
        $resto = array_fill(0, $grado, 0);
        foreach ($datos as $byte) {
            $factor = $byte ^ array_shift($resto);
            $resto[] = 0;
            if ($factor !== 0) {
                foreach ($generador as $i => $coef) {
                    $resto[$i] ^= self::multiplicar($coef, $factor);
                }
            }
        }
        return $resto;
    }

    // ------------------------------------------------------------------ dibujo de la matriz

    /** @param array<int, array<int, bool>> $m @param array<int, array<int, bool>> $r */
    private static function dibujarPatrones(array &$m, array &$r, int $version, int $n): void
    {
        // Patrones de posición en tres esquinas, con su separador.
        foreach ([[0, 0], [0, $n - 7], [$n - 7, 0]] as [$f0, $c0]) {
            for ($f = -1; $f <= 7; $f++) {
                for ($c = -1; $c <= 7; $c++) {
                    $ff = $f0 + $f;
                    $cc = $c0 + $c;
                    if ($ff < 0 || $cc < 0 || $ff >= $n || $cc >= $n) {
                        continue;
                    }
                    $borde = $f === 0 || $f === 6 || $c === 0 || $c === 6;
                    $centro = $f >= 2 && $f <= 4 && $c >= 2 && $c <= 4;
                    $dentro = $f >= 0 && $f <= 6 && $c >= 0 && $c <= 6;
                    $m[$ff][$cc] = $dentro && ($borde || $centro);
                    $r[$ff][$cc] = true;
                }
            }
        }

        // Patrones de alineación (se omiten los que tocarían un patrón de posición).
        $posiciones = self::ALINEACION[$version];
        foreach ($posiciones as $fc) {
            foreach ($posiciones as $cc) {
                if ($r[$fc][$cc]) {
                    continue;
                }
                for ($f = -2; $f <= 2; $f++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $m[$fc + $f][$cc + $c] = max(abs($f), abs($c)) !== 1;
                        $r[$fc + $f][$cc + $c] = true;
                    }
                }
            }
        }

        // Líneas de sincronía.
        for ($i = 8; $i < $n - 8; $i++) {
            $m[6][$i] = $i % 2 === 0;
            $r[6][$i] = true;
            $m[$i][6] = $i % 2 === 0;
            $r[$i][6] = true;
        }

        // Módulo oscuro fijo y zonas reservadas para la información de formato.
        $m[$n - 8][8] = true;
        $r[$n - 8][8] = true;
        for ($i = 0; $i < 9; $i++) {
            $r[8][$i] = true;
            $r[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $r[8][$n - 1 - $i] = true;
            $r[$n - 1 - $i][8] = true;
        }
    }

    /**
     * @param array<int, array<int, bool>> $m
     * @param array<int, array<int, bool>> $r
     * @param int[] $codewords
     */
    private static function colocarDatos(array &$m, array $r, array $codewords, int $n): void
    {
        $bits = '';
        foreach ($codewords as $codeword) {
            $bits .= str_pad(decbin($codeword), 8, '0', STR_PAD_LEFT);
        }
        $i = 0;
        $total = strlen($bits);
        $haciaArriba = true;
        for ($col = $n - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                $col--;
            }
            for ($paso = 0; $paso < $n; $paso++) {
                $fila = $haciaArriba ? $n - 1 - $paso : $paso;
                foreach ([$col, $col - 1] as $c) {
                    if (!$r[$fila][$c]) {
                        $m[$fila][$c] = $i < $total && $bits[$i] === '1';
                        $i++;
                    }
                }
            }
            $haciaArriba = !$haciaArriba;
        }
    }

    /**
     * @param array<int, array<int, bool>> $m
     * @param array<int, array<int, bool>> $r
     * @return array<int, array<int, bool>>
     */
    private static function aplicarMascara(array $m, array $r, int $mascara, int $n): array
    {
        for ($f = 0; $f < $n; $f++) {
            for ($c = 0; $c < $n; $c++) {
                if ($r[$f][$c]) {
                    continue;
                }
                $invertir = match ($mascara) {
                    0 => ($f + $c) % 2 === 0,
                    1 => $f % 2 === 0,
                    2 => $c % 3 === 0,
                    3 => ($f + $c) % 3 === 0,
                    4 => (intdiv($f, 2) + intdiv($c, 3)) % 2 === 0,
                    5 => ($f * $c) % 2 + ($f * $c) % 3 === 0,
                    6 => (($f * $c) % 2 + ($f * $c) % 3) % 2 === 0,
                    default => (($f + $c) % 2 + ($f * $c) % 3) % 2 === 0,
                };
                if ($invertir) {
                    $m[$f][$c] = !$m[$f][$c];
                }
            }
        }
        return $m;
    }

    /** @param array<int, array<int, bool>> $m */
    private static function dibujarFormato(array &$m, int $mascara, int $n): void
    {
        // Nivel de corrección M = 00; 5 bits de datos + 10 bits BCH, con la máscara fija 101010000010010.
        $datos = (0b00 << 3) | $mascara;
        $resto = $datos << 10;
        for ($i = 14; $i >= 10; $i--) {
            if (($resto >> $i) & 1) {
                $resto ^= 0x537 << ($i - 10);
            }
        }
        $formato = (($datos << 10) | $resto) ^ 0x5412;

        for ($i = 0; $i < 15; $i++) {
            $bit = (($formato >> $i) & 1) === 1;
            // Alrededor del patrón superior izquierdo.
            if ($i < 6) {
                $m[$i][8] = $bit;
            } elseif ($i < 8) {
                $m[$i + 1][8] = $bit;
            } else {
                $m[8][14 - $i + ($i < 9 ? 1 : 0)] = $bit;
            }
            // Copia repartida entre los otros dos patrones.
            if ($i < 8) {
                $m[8][$n - 1 - $i] = $bit;
            } else {
                $m[$n - 15 + $i][8] = $bit;
            }
        }
        $m[$n - 8][8] = true;
    }

    // ------------------------------------------------------------------ elección de máscara

    /** @param array<int, array<int, bool>> $m */
    private static function penalizacion(array $m, int $n): int
    {
        $puntos = 0;

        // Regla 1: tiras de 5 o más módulos iguales en fila o columna.
        for ($a = 0; $a < $n; $a++) {
            foreach ([0, 1] as $sentido) {
                $racha = 1;
                for ($b = 1; $b < $n; $b++) {
                    $actual = $sentido === 0 ? $m[$a][$b] : $m[$b][$a];
                    $previo = $sentido === 0 ? $m[$a][$b - 1] : $m[$b - 1][$a];
                    if ($actual === $previo) {
                        $racha++;
                        if ($racha === 5) {
                            $puntos += 3;
                        } elseif ($racha > 5) {
                            $puntos++;
                        }
                    } else {
                        $racha = 1;
                    }
                }
            }
        }

        // Regla 2: bloques de 2x2 del mismo color.
        for ($f = 0; $f < $n - 1; $f++) {
            for ($c = 0; $c < $n - 1; $c++) {
                $v = $m[$f][$c];
                if ($v === $m[$f][$c + 1] && $v === $m[$f + 1][$c] && $v === $m[$f + 1][$c + 1]) {
                    $puntos += 3;
                }
            }
        }

        // Regla 3: patrón 1:1:3:1:1 con cuatro módulos claros a un lado.
        $patron = [true, false, true, true, true, false, true];
        for ($a = 0; $a < $n; $a++) {
            foreach ([0, 1] as $sentido) {
                for ($b = 0; $b <= $n - 7; $b++) {
                    $coincide = true;
                    foreach ($patron as $k => $esperado) {
                        $valor = $sentido === 0 ? $m[$a][$b + $k] : $m[$b + $k][$a];
                        if ($valor !== $esperado) {
                            $coincide = false;
                            break;
                        }
                    }
                    if (!$coincide) {
                        continue;
                    }
                    $antes = true;
                    $despues = true;
                    for ($k = 1; $k <= 4; $k++) {
                        $i1 = $b - $k;
                        $i2 = $b + 6 + $k;
                        if ($i1 >= 0 && ($sentido === 0 ? $m[$a][$i1] : $m[$i1][$a])) {
                            $antes = false;
                        }
                        if ($i2 < $n && ($sentido === 0 ? $m[$a][$i2] : $m[$i2][$a])) {
                            $despues = false;
                        }
                    }
                    if ($antes || $despues) {
                        $puntos += 40;
                    }
                }
            }
        }

        // Regla 4: proporción de módulos oscuros.
        $oscuros = 0;
        foreach ($m as $fila) {
            $oscuros += count(array_filter($fila));
        }
        $porcentaje = $oscuros * 100 / ($n * $n);
        $puntos += (int) (abs(intdiv((int) ($porcentaje * 100) - 5000, 500)) * 10);

        return $puntos;
    }
}