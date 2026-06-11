<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequisitionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number'             => 'required|string',
            'cost_center_id'     => 'required|integer',
            'supplier_id'        => 'required|integer',
            'status_id'          => 'required|integer',
            'currency_id'        => 'required|integer',
            'stage_id'           => 'required|integer',
            'total'              => 'required|numeric',
            'date_prepared'      => 'required|date',

            // 1. ADD THIS LINE SO LARAVEL DOESN'T STRIP IT OUT FROM THE PAYLOAD
            'conversion_rate_id' => 'nullable|integer',
        ];
    }
}
