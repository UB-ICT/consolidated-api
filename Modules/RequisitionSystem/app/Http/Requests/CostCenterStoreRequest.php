<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CostCenterStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $costCenterId = $this->route('cost_center') ? $this->route('cost_center')->id : null;

        return [
            'name' => 'required|string|max:255|unique:cost_centers,name,' . $costCenterId,
        ];
    }
}
