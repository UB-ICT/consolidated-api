<?php

namespace Modules\RequisitionSystem\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use iio\libmergepdf\Merger;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Requisition;

class RequisitionExportService
{
    /**
     * Build a single printable PDF: requisition details, then quotation
     * PDFs, then the activity log.
     *
     * @return array{path: string, download_name: string}
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
        ])->setPaper('a4');

        $activityPdf = Pdf::loadView('requisitionsystem::exports.requisition-activity-log', [
            'requisition' => $requisition,
            'logUsers' => $logUsers,
        ])->setPaper('a4');

        $merger = new Merger();
        $merger->addRaw($detailsPdf->output());

        foreach ($requisition->attachments as $attachment) {
            if (!$attachment->file_path || !Storage::disk('local')->exists($attachment->file_path)) {
                continue;
            }

            $absolutePath = Storage::disk('local')->path($attachment->file_path);

            try {
                $merger->addFile($absolutePath);
            } catch (\Throwable $exception) {
                // Skip unreadable/non-PDF quote files so the rest of the print still works.
                continue;
            }
        }

        $merger->addRaw($activityPdf->output());

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $number = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $requisition->number) ?: (string) $requisition->id;
        $pdfPath = $tmpDir . '/requisition-print-' . $requisition->id . '-' . uniqid('', true) . '.pdf';

        file_put_contents($pdfPath, $merger->merge());

        return [
            'path' => $pdfPath,
            'download_name' => "requisition-{$number}.pdf",
        ];
    }
}
