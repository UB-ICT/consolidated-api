<?php

namespace Modules\RequisitionSystem\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Requisition;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;

class RequisitionExportService
{
    /**
     * Build a ZIP archive with a summary PDF, quotation PDFs, and any
     * supporting documents from activity-log comments.
     *
     * @return array{path: string, download_name: string}
     */
    public function buildZip(Requisition $requisition): array
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

        $summaryPdf = Pdf::loadView('requisitionsystem::exports.requisition-summary', [
            'requisition' => $requisition,
            'currency' => $currency,
            'logUsers' => $logUsers,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipPath = $tmpDir . '/requisition-export-' . $requisition->id . '-' . uniqid('', true) . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create requisition export archive.');
        }

        $number = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $requisition->number) ?: (string) $requisition->id;
        $zip->addFromString(
            "requisition-{$number}-summary.pdf",
            $summaryPdf->output()
        );

        foreach ($requisition->attachments as $index => $attachment) {
            if (!$attachment->file_path || !Storage::disk('local')->exists($attachment->file_path)) {
                continue;
            }

            $supplierName = $attachment->supplier?->name ?? 'supplier';
            $safeSupplier = $this->safeFilePart($supplierName);
            $safeName = $this->safeFilePart($attachment->file_name ?: 'quote.pdf');
            $zip->addFile(
                Storage::disk('local')->path($attachment->file_path),
                sprintf('quotes/%02d-%s-%s', $index + 1, $safeSupplier, $safeName)
            );
        }

        $logAttachmentIndex = 0;
        foreach ($requisition->logs->sortBy('created_at') as $log) {
            if (!$log->file_path || !Storage::disk('local')->exists($log->file_path)) {
                continue;
            }

            $logAttachmentIndex++;
            $safeName = $this->safeFilePart($log->file_name ?: 'supporting-document.pdf');
            $zip->addFile(
                Storage::disk('local')->path($log->file_path),
                sprintf(
                    'activity-attachments/%02d-%s',
                    $logAttachmentIndex,
                    $safeName
                )
            );
        }

        $zip->close();

        return [
            'path' => $zipPath,
            'download_name' => "requisition-{$number}-export.zip",
        ];
    }

    private function safeFilePart(string $value): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: 'file';

        return trim($safe, '-') ?: 'file';
    }
}
