<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Minimal .xlsx reader for budget cash-flow uploads (first sheet, cols A–C).
 */
final class SimpleXlsxReader
{
    /**
     * @return list<array{0: mixed, 1: mixed, 2: mixed}>
     */
    public static function rows(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if (!$path || !is_readable($path)) {
            throw new RuntimeException('Could not read the uploaded spreadsheet.');
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: '');

        if ($extension === 'csv' || str_contains((string) $file->getMimeType(), 'csv')) {
            return self::rowsFromCsv($path);
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive is required to read Excel files.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open the Excel file. Upload a valid .xlsx workbook.');
        }

        try {
            $sharedStrings = self::parseSharedStrings(
                (string) ($zip->getFromName('xl/sharedStrings.xml') ?: '')
            );
            $sheetXml = self::resolveFirstSheetXml($zip);

            if ($sheetXml === null || trim($sheetXml) === '') {
                throw new RuntimeException('The workbook does not contain a readable worksheet.');
            }

            return self::parseSheet($sheetXml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return list<array{0: mixed, 1: mixed, 2: mixed}>
     */
    private static function rowsFromCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Could not read the CSV file.');
        }

        $rows = [];

        try {
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = [
                    $data[0] ?? null,
                    $data[1] ?? null,
                    $data[2] ?? null,
                ];
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private static function parseSharedStrings(string $xml): array
    {
        if (trim($xml) === '') {
            return [];
        }

        $document = self::loadXml($xml);
        $strings = [];

        foreach ($document->si ?? [] as $item) {
            $text = '';

            if (isset($item->t)) {
                $text .= (string) $item->t;
            }

            foreach ($item->r ?? [] as $run) {
                if (isset($run->t)) {
                    $text .= (string) $run->t;
                }
            }

            $strings[] = $text;
        }

        return $strings;
    }

    private static function resolveFirstSheetXml(ZipArchive $zip): ?string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbook === false || $rels === false) {
            $fallback = $zip->getFromName('xl/worksheets/sheet1.xml');

            return $fallback === false ? null : (string) $fallback;
        }

        $workbookXml = self::loadXml((string) $workbook);
        $relsXml = self::loadXml((string) $rels);

        $firstSheet = ($workbookXml->sheets->sheet ?? [null])[0] ?? null;
        $relationshipId = '';

        if ($firstSheet instanceof SimpleXMLElement) {
            $attributes = $firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $relationshipId = (string) ($attributes['id'] ?? '');

            if ($relationshipId === '') {
                $relationshipId = (string) ($firstSheet['id'] ?? '');
            }
        }

        $target = 'worksheets/sheet1.xml';

        foreach ($relsXml->Relationship ?? [] as $relationship) {
            if ((string) $relationship['Id'] === $relationshipId) {
                $target = ltrim((string) $relationship['Target'], '/');
                break;
            }
        }

        if (!str_starts_with($target, 'xl/')) {
            $target = 'xl/'.$target;
        }

        $contents = $zip->getFromName($target);

        return $contents === false ? null : (string) $contents;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<array{0: mixed, 1: mixed, 2: mixed}>
     */
    private static function parseSheet(string $sheetXml, array $sharedStrings): array
    {
        $document = self::loadXml($sheetXml);
        $rows = [];

        foreach ($document->sheetData->row ?? [] as $row) {
            $values = [null, null, null];

            foreach ($row->c ?? [] as $cell) {
                $reference = (string) ($cell['r'] ?? '');
                $columnIndex = self::columnIndexFromReference($reference);

                if ($columnIndex === null || $columnIndex > 2) {
                    continue;
                }

                $values[$columnIndex] = self::cellValue($cell, $sharedStrings);
            }

            if ($values[0] === null && $values[1] === null && $values[2] === null) {
                continue;
            }

            $rows[] = $values;
        }

        return $rows;
    }

    private static function loadXml(string $xml): SimpleXMLElement
    {
        $stripped = preg_replace('/\sxmlns(:\w+)?="[^"]*"/i', '', $xml) ?? $xml;
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($stripped);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            throw new RuntimeException('Could not parse the Excel worksheet XML.');
        }

        return $document;
    }

    private static function columnIndexFromReference(string $reference): ?int
    {
        if (!preg_match('/^([A-Z]+)/', strtoupper($reference), $matches)) {
            return null;
        }

        $letters = $matches[1];
        $index = 0;

        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private static function cellValue(SimpleXMLElement $cell, array $sharedStrings): mixed
    {
        $type = (string) ($cell['t'] ?? '');

        if ($type === 'inlineStr') {
            $text = '';

            if (isset($cell->is->t)) {
                $text .= (string) $cell->is->t;
            }

            foreach ($cell->is->r ?? [] as $run) {
                if (isset($run->t)) {
                    $text .= (string) $run->t;
                }
            }

            return $text;
        }

        $raw = isset($cell->v) ? (string) $cell->v : '';

        if ($type === 's') {
            return $sharedStrings[(int) $raw] ?? null;
        }

        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            $number = (float) $raw;

            return $number == (int) $number ? (int) $number : $number;
        }

        return $raw;
    }
}
