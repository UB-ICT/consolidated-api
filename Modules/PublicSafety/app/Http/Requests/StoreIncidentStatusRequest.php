<?php

namespace Modules\PublicSafety\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'statuses'=>['required'],
        ];
    }
};
