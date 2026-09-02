<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RequisitionSystem\Models\Requisition;

class RequisitionForwardReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Requisition|null $requisition */
        $requisition = $this->route('requisition');

        return [
            'cost_center_id' => [
                'required',
                'integer',
                'exists:porsql.cost_centers,id',
                Rule::notIn([$requisition?->cost_center_id]),
            ],
            'comments' => 'nullable|string|max:2000',
        ];
    }
}
