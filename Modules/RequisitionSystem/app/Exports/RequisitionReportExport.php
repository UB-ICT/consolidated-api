<?php

namespace Modules\RequisitionSystem\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\RequisitionSystem\Models\Requisition;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RequisitionReportExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    ShouldAutoSize
{
    /**
     * Maps the request's sort_by value to the actual column to order by.
     */
    private const SORT_COLUMNS = [
        'number' => 'requisitions.number',
        'total' => 'requisitions.total',
        'cost_center' => 'cost_centers.name',
        'supplier' => 'suppliers.name',
        'status' => 'statuses.name',
    ];

    public function __construct(private readonly array $filters)
    {
    }

    public function query(): Builder
    {
        // A requisition can (in theory) have more than one supplier flagged
        // is_recommended — there's no DB constraint preventing it. Resolve a
        // single deterministic recommended supplier per requisition (lowest
        // pivot id) via a Postgres DISTINCT ON subquery, rather than a plain
        // join that could silently duplicate report rows.
        $recommendedSuppliers = DB::connection('porsql')
            ->table('requisition_suppliers')
            ->select(DB::raw('DISTINCT ON (requisition_id) requisition_id, supplier_id'))
            ->where('is_recommended', true)
            ->orderBy('requisition_id')
            ->orderBy('id');

        $query = Requisition::query()
            ->leftJoinSub(
                $recommendedSuppliers,
                'recommended_suppliers',
                fn ($join) => $join->on('requisitions.id', '=', 'recommended_suppliers.requisition_id')
            )
            ->leftJoin('suppliers', 'suppliers.id', '=', 'recommended_suppliers.supplier_id')
            ->leftJoin('cost_centers', 'cost_centers.id', '=', 'requisitions.cost_center_id')
            ->leftJoin('statuses', 'statuses.id', '=', 'requisitions.status_id')
            ->select([
                'requisitions.number',
                'requisitions.total',
                'cost_centers.name as cost_center_name',
                'suppliers.name as supplier_name',
                'statuses.name as status_name',
            ])
            ->whereBetween('requisitions.date_prepared', [
                $this->filters['date_from'] . ' 00:00:00',
                $this->filters['date_to'] . ' 23:59:59',
            ]);

        if (!empty($this->filters['supplier_id'])) {
            $query->where('recommended_suppliers.supplier_id', $this->filters['supplier_id']);
        }

        if (!empty($this->filters['cost_center_id'])) {
            $query->where('requisitions.cost_center_id', $this->filters['cost_center_id']);
        }

        if (!empty($this->filters['number'])) {
            $query->where('requisitions.number', 'ilike', '%' . $this->filters['number'] . '%');
        }

        if (isset($this->filters['amount_min']) && $this->filters['amount_min'] !== '') {
            $query->where('requisitions.total', '>=', $this->filters['amount_min']);
        }

        if (isset($this->filters['amount_max']) && $this->filters['amount_max'] !== '') {
            $query->where('requisitions.total', '<=', $this->filters['amount_max']);
        }

        $sortColumn = self::SORT_COLUMNS[$this->filters['sort_by'] ?? 'number'] ?? self::SORT_COLUMNS['number'];
        $sortDirection = ($this->filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sortColumn, $sortDirection);
    }

    public function headings(): array
    {
        return [
            'Requisition Number',
            'Amount',
            'Department / Cost Center',
            'Supplier',
            'Status',
        ];
    }

    public function map($requisition): array
    {
        return [
            $requisition->number,
            (float) $requisition->total,
            $requisition->cost_center_name ?? '—',
            $requisition->supplier_name ?? '—',
            $requisition->status_name ?? '—',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
        ];
    }
}
