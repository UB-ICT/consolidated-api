<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    protected static $service;

    private final function __construct()
    {
    }

    protected static function initializeSheets()
    {
        if (is_null(self::$service)) {
            $client = new Client();
            $client->setAuthConfig(storage_path(env('GOOGLE_SHEET_CREDENTIALS')));
            $client->addScope(Sheets::SPREADSHEETS_READONLY);
            self::$service = new Sheets($client);
        }
    }

    public static function readSheet(string $spreadsheetId, string $range): array
    {
        try {
            self::initializeSheets();
            $response = self::$service->spreadsheets_values->get($spreadsheetId, $range);
            return $response->getValues() ?? [];
        } catch (\Exception $e) {
            Log::error('Google Sheets read error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Filter rows by courseCode and courseSection
     */
    public static function getRowsByCourse(string $spreadsheetId, string $range, string $courseCode, string $courseSection): array
    {
        $rows = self::readSheet($spreadsheetId, $range);

        if (empty($rows)) {
            return [];
        }

        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);

        $filtered = [];
        foreach ($dataRows as $row) {
            // pad row if shorter than headers
            $rowAssoc = array_combine(
                $headers,
                array_pad($row, count($headers), null)
            );

            if (
                isset($rowAssoc['CourseCode'], $rowAssoc['CourseSection']) &&
                $rowAssoc['CourseCode'] == $courseCode &&
                $rowAssoc['CourseSection'] == $courseSection
            ) {
                $filtered[] = $rowAssoc;
            }
        }

        return $filtered;
    }

}
