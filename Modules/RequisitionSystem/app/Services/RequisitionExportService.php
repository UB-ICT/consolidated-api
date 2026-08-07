<?php

namespace Modules\RequisitionSystem\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use iio\libmergepdf\Merger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Requisition;

class RequisitionExportService
{
    /**
     * Build a single printable PDF: requisition details, then quotation
     * PDFs embedded in-line, then the activity log.
     *
     * @return array{path: string, download_name: string, mime: string}
     */
    public function buildPrintPdf(Requisition $requisition): array
    {
        $requisition->load([
            'items.chartOfAccount',
            'suppliers',
            'costCenter',
            'stage',
            'status',
            'attachments.supplier',
            'logs',
            'tags',
        ]);

        $logUserIds = $requisition->logs->pluck('user_id')->unique()->filter()->values();
        $logUsers = User::query()
            ->whereIn('id', $logUserIds->all())
            ->get()
            ->keyBy('id');

        $currency = $requisition->currency_id
            ? Currency::query()->find($requisition->currency_id)
            : null;

        $generatedAt = now();

        $detailsPdf = Pdf::loadView('requisitionsystem::exports.requisition-summary', [
            'requisition' => $requisition,
            'currency' => $currency,
            'generatedAt' => $generatedAt,
        ])->setPaper('a4')->output();

        $activityPdf = Pdf::loadView('requisitionsystem::exports.requisition-activity-log', [
            'requisition' => $requisition,
            'logUsers' => $logUsers,
        ])->setPaper('a4')->output();

        $parts = [$detailsPdf];

        foreach ($requisition->attachments as $attachment) {
            if (!$attachment->file_path || !Storage::disk('local')->exists($attachment->file_path)) {
                continue;
            }

            $absolutePath = Storage::disk('local')->path($attachment->file_path);
            $contents = @file_get_contents($absolutePath);

            if ($contents === false || !str_starts_with($contents, '%PDF')) {
                Log::warning('Skipping non-PDF quotation attachment during print.', [
                    'requisition_id' => $requisition->id,
                    'attachment_id' => $attachment->id,
                ]);
                continue;
            }

            if (!$this->canMergePdf($contents)) {
                Log::warning('Skipping unreadable quotation PDF during print merge.', [
                    'requisition_id' => $requisition->id,
                    'attachment_id' => $attachment->id,
                ]);
                continue;
            }

            $parts[] = $contents;
        }

        $parts[] = $activityPdf;

        $merged = $this->mergeParts($parts, $requisition->id, $detailsPdf, $activityPdf);

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $number = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $requisition->number) ?: (string) $requisition->id;
        $pdfPath = $tmpDir . '/requisition-print-' . $requisition->id . '-' . uniqid('', true) . '.pdf';

        file_put_contents($pdfPath, $merged);

        return [
            'path' => $pdfPath,
            'download_name' => "requisition-{$number}.pdf",
            'mime' => 'application/pdf',
        ];
    }

    private function canMergePdf(string $pdfContents): bool
    {
        try {
            $probe = new Merger();
            $probe->addRaw($pdfContents);
            $probe->merge();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $parts
     */
    private function mergeParts(
        array $parts,
        int $requisitionId,
        string $detailsPdf,
        string $activityPdf
    ): string {
        try {
            $merger = new Merger();
            foreach ($parts as $part) {
                $merger->addRaw($part);
            }

            return $merger->merge();
        } catch (\Throwable $exception) {
            Log::error('Full print merge failed; returning details + activity only.', [
                'requisition_id' => $requisitionId,
                'message' => $exception->getMessage(),
            ]);

            $fallback = new Merger();
            $fallback->addRaw($detailsPdf);
            $fallback->addRaw($activityPdf);

            return $fallback->merge();
        }
    }
}
