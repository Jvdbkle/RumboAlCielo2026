<?php

declare(strict_types=1);

namespace RumboAlCielo\Infraestructura\Excel;

use RumboAlCielo\Aplicacion\Puertos\GeneradorHojaCalculo;
use RuntimeException;
use ZipArchive;

/** Arma un archivo .xlsx real (un zip con XML) usando solo la extensión zip de PHP. */
final class GeneradorXlsx implements GeneradorHojaCalculo
{
    public function generar(string $nombreHoja, array $encabezados, array $filas): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($ruta === false || $zip->open($ruta, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo de Excel.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->xml(mb_substr($nombreHoja, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        // Estilo 0: normal. Estilo 1: negrita (encabezados).
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>');

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $xml .= $this->fila(1, $encabezados, 1);
        foreach ($filas as $i => $fila) {
            $xml .= $this->fila($i + 2, $fila, 0);
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml . '</sheetData></worksheet>');
        $zip->close();

        $contenido = (string) file_get_contents($ruta);
        unlink($ruta);
        return $contenido;
    }

    /** @param array<int, string|int|float> $valores */
    private function fila(int $numero, array $valores, int $estilo): string
    {
        $celdas = '';
        foreach (array_values($valores) as $i => $valor) {
            $referencia = $this->columna($i) . $numero;
            if (is_int($valor) || is_float($valor)) {
                $celdas .= '<c r="' . $referencia . '" s="' . $estilo . '"><v>' . $valor . '</v></c>';
            } else {
                $celdas .= '<c r="' . $referencia . '" s="' . $estilo . '" t="inlineStr"><is><t xml:space="preserve">' . $this->xml((string) $valor) . '</t></is></c>';
            }
        }
        return '<row r="' . $numero . '">' . $celdas . '</row>';
    }

    private function columna(int $indice): string
    {
        $letras = '';
        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letras = chr(65 + ($n - 1) % 26) . $letras;
        }
        return $letras;
    }

    private function xml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}