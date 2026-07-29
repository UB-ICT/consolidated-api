<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class RequisitionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',

            'supplier_id' => 'nullable|integer|exists:porsql.suppliers,id',
            'cost_center_id' => 'nullable|integer|exists:porsql.cost_centers,id',
            'number' => 'nullable|string|max:255',
            'amount_min' => 'nullable|numeric|min:0',
            'amount_max' => 'nullable|numeric|min:0|gte:amount_min',

            'sort_by' => 'nullable|string|in:number,total,cost_center,supplier,status',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $dateFrom = $this->input('date_from');
            $dateTo = $this->input('date_to');

            if (!$dateFrom || !$dateTo) {
                return;
            }

            if (Carbon::parse($dateFrom)->diffInMonths(Carbon::parse($dateTo)) > 12) {
                $validator->errors()->add(
                    'date_to',
                    'The report date range cannot exceed 12 months.'
                );
            }
        });
    }
}
